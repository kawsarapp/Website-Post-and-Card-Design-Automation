<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // --- INITIAL DATA ---
    const userSettings = {
        brand: {!! json_encode($settings->brand_name ?? 'News Desk') !!},
        logo: {!! json_encode($settings->logo_url ?? null) !!},
        theme: {!! json_encode($settings->default_theme_color ?? 'red') !!}
    };

    const newsData = {
        title: {!! json_encode($newsItem->title) !!},
        source: {!! json_encode($newsItem->website->name) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };

    let state = {
        title: newsData.title,
        image: newsData.image,
        badge: newsData.source,
        brand: userSettings.brand, 
        logo: userSettings.logo,   
        themeColor: userSettings.theme, 
        date: "",
        zoom: 0.5
    };
    
    // ডাটাবেস থেকে ডিফল্ট টেমপ্লেট লোড
    let currentTemplate = "{!! $settings->default_template ?? 'dhaka_post_card' !!}";

    // --- LOGIC ---
    function toBanglaNum(str) {
        return str.toString().replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]);
    }

    function init() {
        const date = new Date();
        const months = ["জানুয়ারি", "ফেব্রুয়ারি", "মার্চ", "এপ্রিল", "মে", "জুন", "জুলাই", "আগস্ট", "সেপ্টেম্বর", "অক্টোবর", "নভেম্বর", "ডিসেম্বর"];
        const day = toBanglaNum(date.getDate().toString().padStart(2, '0'));
        const year = toBanglaNum(date.getFullYear());
        state.date = `${day} ${months[date.getMonth()]}, ${year}`;
        
        render();
        updatePreviewScale();
    }

    function render() {
        const container = document.getElementById('canvas-container');
        
        // templates অবজেক্টটি এখন templates.js.blade.php থেকে আসছে
        const templateFunc = templates[currentTemplate] || templates['dhaka_post_card'];
        
        // স্টেট পাস করা হচ্ছে
        container.innerHTML = templateFunc(state);

        const imgEl = document.getElementById('cardImage');
        const logoImg = document.getElementById('logoImg');
        const logoWrapper = document.getElementById('logoWrapper');
        const badgeEl = document.getElementById('textBadge');

        // Image Fix
        if(imgEl && state.image) {
            imgEl.src = state.image;
            imgEl.onerror = function() {
                console.warn("Image failed, using fallback.");
                this.src = {!! json_encode($newsItem->thumbnail_url) !!};
            };
        }
        
        if(state.logo) {
            if(logoImg) logoImg.src = state.logo;
            if(logoWrapper) logoWrapper.classList.remove('hidden');
            if(badgeEl) badgeEl.style.display = 'none';
        } else {
            if(logoWrapper) logoWrapper.classList.add('hidden');
            if(badgeEl) badgeEl.style.display = 'block';
        }

        const titleEl = document.getElementById('cardTitle');
        const slider = document.querySelector('input[type=range]');
        if(titleEl && slider) titleEl.style.fontSize = slider.value + 'px';
    }

    // --- ACTIONS ---
    function changeTemplate(val) { currentTemplate = val; render(); }
    function updateCard() { state.title = document.getElementById('inputTitle').value; render(); }
    function updateBadgeText() { state.badge = document.getElementById('badgeTextInput').value; render(); }
    function updateBrand() { state.brand = document.getElementById('brandInput').value; render(); }
    function updateFontSize(val) { const el = document.getElementById('cardTitle'); if(el) el.style.fontSize = val + "px"; }
    function setThemeColor(color) { state.themeColor = color; render(); }
    
    function uploadLogo() {
        const fileInput = document.getElementById('logoInput');
        const file = fileInput.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) { 
                state.logo = e.target.result; 
                render(); 
            }
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('logo', file);
            
            fetch("{{ route('settings.upload-logo') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) userSettings.logo = data.url;
            })
            .catch(error => console.error('Upload Error:', error));
        }
    }
    function resetLogo() { document.getElementById('logoInput').value = ""; state.logo = null; render(); }
    
    function changeZoom(delta) { state.zoom += delta; if(state.zoom < 0.2) state.zoom = 0.2; updatePreviewScale(); }
    function resetZoom() { state.zoom = 0.5; updatePreviewScale(); }
    function updatePreviewScale() { document.getElementById('preview-wrapper').style.transform = `scale(${state.zoom})`; }

    function downloadCard() {
        const originalNode = document.getElementById("canvas-container");
        const btn = document.getElementById('downloadBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = "⏳ জেনারেট হচ্ছে...";
        btn.disabled = true;

        window.scrollTo(0, 0); 

        const clone = originalNode.cloneNode(true);
        clone.style.transform = "none";
        clone.style.position = "fixed";
        clone.style.top = "0";
        clone.style.left = "0";
        clone.style.zIndex = "-9999";
        clone.style.width = "1080px";
        clone.style.height = "1080px";
        clone.style.borderRadius = "0";
        
        document.body.appendChild(clone);
        
        setTimeout(() => {
            html2canvas(clone, { 
                scale: 1,
                width: 1080, 
                height: 1080, 
                useCORS: true, 
                allowTaint: true,
                backgroundColor: null,
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = `News_${Date.now()}.png`;
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();
                
                document.body.removeChild(clone);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }, 300);
    }

    init();
</script>