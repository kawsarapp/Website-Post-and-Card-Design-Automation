<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // --- INITIAL DATA ---
    const newsData = {
        title: {!! json_encode($newsItem->title) !!},
        source: {!! json_encode($newsItem->website->name) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };

    let state = {
        title: newsData.title,
        image: newsData.image,
        badge: newsData.source,
        brand: "DHAKA POST",
        date: "",
        logo: null,
        themeColor: 'red',
        zoom: 0.5
    };
    
    let currentTemplate = 'dhaka_post_card';

    const getThemeClass = () => {
        const colors = {
            red: { bg: 'bg-red-600', text: 'text-red-600', border: 'border-red-600' },
            blue: { bg: 'bg-blue-600', text: 'text-blue-600', border: 'border-blue-600' },
            emerald: { bg: 'bg-emerald-600', text: 'text-emerald-600', border: 'border-emerald-600' },
            purple: { bg: 'bg-purple-700', text: 'text-purple-700', border: 'border-purple-700' },
            black: { bg: 'bg-black', text: 'text-black', border: 'border-black' }
        };
        return colors[state.themeColor] || colors['red'];
    }

    // --- TEMPLATES ---
    const templates = {
        
	// ✅ RTV NEWS CARD (FINAL ALIGNMENT FIX)
        rtv_news_card: () => {
            return `
            <div class="w-[1080px] h-[1080px] relative flex flex-col font-bangla overflow-hidden" style="background: linear-gradient(to bottom, #590000 0%, #590000 25%, #001f52 25%, #001f52 100%);">
                
                <div class="absolute top-11 left-1/2 transform -translate-x-1/2 z-20">
                    <div class="bg-black text-white px-12 h-[70px] flex items-center justify-center rounded-t-[35px] border-t-[10px] border-l-[10px] border-r-[10px] border-[#ffee00]">
						<span class="text-4xl font-bold -mt-8">${state.date}</span>
					</div>

                </div>

                <div class="w-full px-12 pt-28 pb-8 z-10">
                    <div class="w-full h-[560px] bg-white p-2 rounded-[35px] border-[6px] border-[#ffee00] shadow-2xl overflow-hidden relative">
                        <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover rounded-[28px]">
                    </div>
                </div>

                <div class="flex-1 flex items-center justify-center px-16 text-center -mt-8">
                    <h1 id="cardTitle" class="text-[70px] font-bold leading-[1.3] text-white drop-shadow-lg -mt-4">
                        ${state.title.replace(/(.{15,})/g, '$1<br>').replace(/([ঀ-৾]+)/g, '<span style="color: #ffee00;">$1</span>')}
                    </h1>
                </div>

                <div class="h-[200px] w-full flex justify-between items-end px-14 pb-14 relative">
                    
                    <div class="flex flex-col items-start gap-3">
                        <div class="w-28 h-28 bg-gradient-to-br from-[#0088cc] to-[#005580] rounded-full flex items-center justify-center border-[5px] border-white shadow-lg overflow-hidden relative">
                             ${state.logo 
                                ? `<img id="logoImg" src="${state.logo}" class="w-full h-full object-cover">` 
                                : `<span class="text-white font-bold italic text-4xl -mt-4 ml-1">Rtv</span>` // Logo Text Lifted
                            }
                        </div>
                        <span class="text-white text-2xl font-medium ml-3 -mt-2">আজ এবং আগামীর</span>
                    </div>

                    <div class="absolute bottom-14 left-1/2 transform -translate-x-1/2 text-center flex flex-col items-center">
                        
                        <div class="text-[#ffee00] text-5xl font-bold border-b-[6px] border-[#ffee00] mb-3 cursor-pointer flex items-center justify-center h-[60px]">
                            <span class="-mt-10 leading-none">বিস্তারিত কমেন্টে</span>
                        </div>
                        
                        <div class="text-white text-3xl opacity-90">rtvonline.com</div>
                    </div>

                    <div class="w-28"></div> 
                </div>

            </div>`;
        },


		
		

        // ✅ DHAKA POST DESIGN (BUTTON TEXT FIX)
        dhaka_post_card: () => {
            return `
            <div class="w-[1080px] h-[1080px] bg-[#0a1e2f] flex flex-col font-bangla relative overflow-hidden">
                
                <div class="h-[140px] flex justify-between items-center px-14 border-b border-white/5 shrink-0">
                    <div class="text-[#e6b800] text-4xl font-bold tracking-wide pt-2">${state.date}</div>
                    <div class="logo-container">
                        ${state.logo 
                            ? `<img id="logoImg" src="${state.logo}" class="h-16 w-auto object-contain">` 
                            : `<span id="brandNameDisplay" class="text-5xl font-extrabold tracking-wide text-white uppercase">${state.brand}</span>`
                        }
                    </div>
                </div>

                <div class="w-full h-[560px] bg-[#0d2a42] relative overflow-hidden shrink-0">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-[#0a1e2f] to-transparent"></div>
                </div>

                <div class="flex-1 flex items-center justify-center px-14 text-center relative">
                    <h1 id="cardTitle" class="text-[58px] font-bold leading-[1.35] text-white drop-shadow-lg -mt-4">
                        ${state.title}
                    </h1>
                </div>

                <div class="h-[160px] flex items-start justify-center pt-2 shrink-0">
                    <div class="bg-white text-[#0a1e2f] px-16 h-[80px] rounded-full shadow-[0_0_25px_rgba(255,255,255,0.2)] flex items-center justify-center">
                        <span class="text-3xl font-bold -mt-5">বিস্তারিত কমেন্টে</span>
                    </div>
                </div>

            </div>`;
        },

        // Other templates (Standardized size 1080px)
        viral_bold: () => {
            const c = getThemeClass();
            return `
            <div class="w-[1080px] h-[1080px] bg-[#111] flex flex-col relative font-bangla overflow-hidden">
                <div class="h-[65%] w-full relative overflow-hidden group">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover opacity-90">
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#111]"></div>
                    <div class="absolute top-12 left-12">
                        <div class="${c.bg} text-white px-8 py-3 text-3xl font-black italic uppercase tracking-wider transform -skew-x-12 inline-block shadow-[6px_6px_0px_0px_rgba(255,255,255,0.2)]" id="textBadge">${state.badge}</div>
                    </div>
                </div>
                <div class="h-[35%] w-full p-16 flex flex-col justify-start relative z-10">
                     <div class="w-32 h-3 ${c.bg} mb-8"></div>
                     <h1 id="cardTitle" class="text-[75px] font-bold text-white leading-[1.2] mb-6 drop-shadow-xl text-left">${state.title}</h1>
                     <div class="mt-auto pt-8 border-t border-gray-800 flex justify-between items-center text-gray-400">
                        <span class="text-3xl font-bold uppercase tracking-widest text-white" id="brandNameDisplay">${state.brand}</span>
                        <span class="text-3xl font-mono">${state.date}</span>
                     </div>
                </div>
            </div>`;
        },
        quote_pro: () => {
             const c = getThemeClass();
             return `
             <div class="w-[1080px] h-[1080px] bg-slate-50 border-[30px] border-white relative font-bangla flex flex-col overflow-hidden">
                <div class="h-[55%] relative overflow-hidden m-6 rounded-[40px]">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover grayscale-[20%]">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-10 left-10 text-white/90 font-bold text-2xl uppercase tracking-[0.2em] border-l-8 ${c.border} pl-6">
                        ${state.badge}
                    </div>
                </div>
                <div class="h-[45%] px-20 py-10 flex flex-col justify-center relative">
                    <div class="absolute -top-12 right-20 text-[200px] leading-none ${c.text} opacity-20 font-serif">”</div>
                    <h1 id="cardTitle" class="text-[65px] font-bold text-slate-800 leading-tight z-10 text-center italic">${state.title}</h1>
                </div>
             </div>`;
        },
        classic: () => {
            const c = getThemeClass();
            return `
            <div class="w-[1080px] h-[1080px] flex flex-col bg-white font-bangla overflow-hidden">
                <div class="h-[60%] relative overflow-hidden">
                     <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                     <div class="absolute bottom-0 left-0 w-full h-1/3 bg-gradient-to-t from-black/50 to-transparent"></div>
                     <div class="absolute top-10 left-10 ${c.bg} text-white px-8 py-3 text-4xl font-bold uppercase shadow-lg">${state.badge}</div>
                </div>
                <div class="h-[40%] px-20 flex flex-col justify-center items-center text-center bg-white text-slate-900 relative">
                     <div class="w-32 h-3 ${c.bg} mb-8 rounded-full"></div>
                     <h1 id="cardTitle" class="text-[60px] font-bold leading-snug line-clamp-4">${state.title}</h1>
                     <div class="absolute bottom-10 w-full px-20 flex justify-between border-t border-gray-100 pt-6 text-gray-500">
                        <span class="text-3xl font-bold uppercase ${c.text}" id="brandNameDisplay">${state.brand}</span>
                        <span class="text-3xl font-semibold">${state.date}</span>
                     </div>
                </div>
            </div>`;
        },
        modern_split: () => {
             const c = getThemeClass();
             return `
             <div class="w-[1080px] h-[1080px] flex bg-white relative font-bangla overflow-hidden">
                <div class="w-1/2 h-full relative overflow-hidden">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/10"></div>
                </div>
                <div class="w-1/2 h-full p-20 flex flex-col justify-center bg-slate-50 relative">
                    <div class="absolute top-0 right-0 w-48 h-48 ${c.bg} opacity-10 rounded-bl-[150px]"></div>
                    <div class="w-full mb-10">
                        <span class="${c.text} border-2 ${c.border} px-6 py-2 text-2xl font-bold uppercase tracking-widest rounded-lg bg-white shadow-sm" id="textBadge">${state.badge}</span>
                    </div>
                    <h1 id="cardTitle" class="text-[70px] font-extrabold text-slate-900 leading-[1.2] mb-12 text-left">
                        ${state.title}
                    </h1>
                    <div class="mt-auto border-t-2 border-slate-200 pt-10 flex justify-between items-center text-slate-500">
                         <span class="text-3xl font-bold uppercase text-slate-800 tracking-wider" id="brandNameDisplay">${state.brand}</span>
                         <span class="text-3xl font-medium">${state.date}</span>
                    </div>
                </div>
            </div>`;
        },
        bold_overlay: () => {
             const c = getThemeClass();
             return `
             <div class="w-[1080px] h-[1080px] relative overflow-hidden bg-black font-bangla">
                <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover opacity-70">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                <div class="absolute top-0 w-full h-6 ${c.bg}"></div>
                <div class="absolute bottom-0 w-full p-24 flex flex-col items-start justify-end h-full">
                    <div class="${c.bg} text-white font-bold px-8 py-3 text-3xl mb-8 uppercase tracking-widest inline-block skew-x-[-10deg] shadow-lg">
                        <span class="skew-x-[10deg] inline-block">${state.badge}</span>
                    </div>
                    <h1 id="cardTitle" class="text-[90px] font-black text-white leading-tight drop-shadow-2xl text-left border-l-[16px] ${c.border} pl-12">
                        ${state.title}
                    </h1>
                    <div class="w-full flex justify-between items-center mt-16 text-gray-300 border-t border-white/20 pt-10">
                        <span class="text-5xl font-bold text-white tracking-widest uppercase" id="brandNameDisplay">${state.brand}</span>
                        <span class="text-4xl font-light">${state.date}</span>
                    </div>
                </div>
            </div>`;
        },
        broadcast_tv: () => {
             const c = getThemeClass();
             return `
            <div class="w-[1080px] h-[1080px] relative bg-gray-900 overflow-hidden font-bangla">
                <div class="h-[82%] w-full relative">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                     <div class="absolute top-12 left-12 flex items-center gap-6">
                         <div class="bg-red-600 text-white px-6 py-3 text-3xl font-bold uppercase animate-pulse shadow-md rounded">● LIVE</div>
                         <div class="bg-black/60 text-white px-6 py-3 text-3xl font-bold uppercase backdrop-blur-md rounded border border-white/10">${state.badge}</div>
                    </div>
                </div>
                <div class="h-[18%] w-full bg-blue-950 relative flex items-center px-16 border-t-[12px] border-yellow-400">
                     <div class="bg-yellow-400 text-blue-950 font-black text-5xl px-10 py-4 absolute -top-16 left-16 skew-x-[-20deg] shadow-[0_4px_10px_rgba(0,0,0,0.3)]">
                        <span class="skew-x-[20deg] inline-block">BREAKING</span>
                     </div>
                     <div class="w-full flex justify-between items-center text-white pt-4">
                        <h1 id="cardTitle" class="text-[50px] font-bold line-clamp-2 w-[75%] leading-snug">${state.title}</h1>
                        <div class="flex flex-col items-end border-l-4 pl-10 border-blue-700/50">
                             <span class="text-3xl font-bold text-yellow-400 uppercase tracking-wider" id="brandNameDisplay">${state.brand}</span>
                             <span class="text-2xl opacity-80">${state.date}</span>
                        </div>
                     </div>
                </div>
            </div>`;
        },
        insta_modern: () => {
             const c = getThemeClass();
             return `
             <div class="w-[1080px] h-[1080px] bg-white relative font-bangla p-16 flex flex-col items-center justify-center">
                <div class="w-full h-full border border-gray-100 rounded-[60px] shadow-2xl overflow-hidden relative bg-gray-900">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-black/10"></div>
                    <div class="absolute top-0 w-full p-16 flex justify-between items-center">
                        <div class="flex items-center gap-4 bg-white/10 backdrop-blur px-8 py-3 rounded-full border border-white/10">
                             <div class="w-4 h-4 ${c.bg} rounded-full animate-pulse"></div>
                             <span class="text-white font-bold text-2xl uppercase tracking-wider">${state.badge}</span>
                        </div>
                        <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-20 w-auto drop-shadow-lg"></div>
                    </div>
                    <div class="absolute bottom-0 w-full p-16 pb-20">
                        <h1 id="cardTitle" class="text-[70px] font-bold text-white leading-tight mb-10 drop-shadow-lg border-l-[10px] ${c.border} pl-10">
                            ${state.title}
                        </h1>
                        <div class="flex items-center gap-6 text-gray-300 ml-10">
                             <span class="font-bold text-3xl text-white" id="brandNameDisplay">${state.brand}</span>
                             <span>•</span>
                             <span class="text-2xl">${state.date}</span>
                        </div>
                    </div>
                </div>
             </div>`;
        }
    };

    // Fallbacks
    templates.glass_blur = templates.classic; 
    templates.neon_dark = templates.viral_bold;

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
        const templateFunc = templates[currentTemplate] || templates['dhaka_post_card'];
        
        container.innerHTML = templateFunc();

        const imgEl = document.getElementById('cardImage');
        const logoImg = document.getElementById('logoImg');
        const logoWrapper = document.getElementById('logoWrapper');
        const badgeEl = document.getElementById('textBadge');

        if(imgEl && state.image) imgEl.src = state.image;
        
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
        const file = document.getElementById('logoInput').files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) { state.logo = e.target.result; render(); }
            reader.readAsDataURL(file);
        }
    }
    function resetLogo() { document.getElementById('logoInput').value = ""; state.logo = null; render(); }
    
    function changeZoom(delta) { state.zoom += delta; if(state.zoom < 0.2) state.zoom = 0.2; updatePreviewScale(); }
    function resetZoom() { state.zoom = 0.5; updatePreviewScale(); }
    function updatePreviewScale() { document.getElementById('preview-wrapper').style.transform = `scale(${state.zoom})`; }

    // ✅ DOWNLOAD FIX
    function downloadCard() {
        const originalNode = document.getElementById("canvas-container");
        const btn = document.getElementById('downloadBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = "⏳ জেনারেট হচ্ছে...";
        btn.disabled = true;

        window.scrollTo(0, 0); // Fix offset issue

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
                logging: false,
                scrollX: 0, 
                scrollY: 0, 
                windowWidth: 1080,
                windowHeight: 1080
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