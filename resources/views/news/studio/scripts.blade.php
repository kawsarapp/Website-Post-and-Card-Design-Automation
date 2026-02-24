<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>

<script>
    // ==========================================
    // ⚙️ ১. কনফিগারেশন (partials ফোল্ডার থেকে লোড হচ্ছে)
    // ==========================================
    @include('partials.studio_fonts')
    @include('partials.studio_templates')

    // ==========================================
    // 🌍 ২. গ্লোবাল ভেরিয়েবল
    // ==========================================
    var canvas, mainImageObj = null, frameObj = null, currentLayout = null; 
    let history = [], historyStep = -1, isHistoryProcessing = false, currentZoom = 1;
    
    var savedPrefs = {};
    try { savedPrefs = JSON.parse(localStorage.getItem('studio_prefs')) || {}; } catch (e) {}
    var dbPrefs = {!! json_encode($settings->design_preferences ?? null) !!};

    var userSettings = {
        logo: {!! json_encode($settings->logo_url ?? null) !!},
        template: 'custom_png',
        font: savedPrefs.font || dbPrefs?.font || "'Hind Siliguri', sans-serif",
        color: savedPrefs.color || dbPrefs?.color || '#ffffff',
        bg: savedPrefs.bg || dbPrefs?.bg || '',
        size: savedPrefs.size || dbPrefs?.size || 60,
        frameUrl: savedPrefs.frameUrl || dbPrefs?.frameUrl || null,
        titlePos: savedPrefs.titlePos || dbPrefs?.titlePos || null, 
        datePos: savedPrefs.datePos || dbPrefs?.datePos || null,
        layout: savedPrefs.layout || dbPrefs?.layout || 'bottom'
    };
    
    var newsData = {
        title: {!! json_encode(!empty($newsItem->ai_title) ? $newsItem->ai_title : $newsItem->title) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };

    // ==========================================
    // 🚀 ৩. ক্যানভাস ইনিশিয়ালাইজেশন ও কোর ফাংশন
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() { initCanvas(); });

    function initCanvas() {
        canvas = new fabric.Canvas('newsCanvas', { backgroundColor: '#fff', preserveObjectStacking: true, selection: true, renderOnAddRemove: false });

        setTimeout(() => { loadStoredCustomFont(); loadFonts(); }, 10);

        if (newsData.image) {
            fabric.Image.fromURL(newsData.image, function(img) {
                if (img) { setupMainImage(img); canvas.requestRenderAll(); }
                restoreSavedDesign(); 
                canvas.set('renderOnAddRemove', true);
                canvas.requestRenderAll();
            }, { crossOrigin: 'anonymous' });
        } else {
            restoreSavedDesign();
            canvas.set('renderOnAddRemove', true);
        }

        canvas.on('selection:created', updateSidebarValues);
        canvas.on('selection:updated', updateSidebarValues);
        canvas.on('object:added', () => { saveHistory(); renderLayerList(); });
        canvas.on('object:removed', () => { saveHistory(); renderLayerList(); });
        canvas.on('object:modified', () => { saveHistory(); }); 
        
        initKeyboardEvents();
        activateDebugTools();
        setTimeout(fitToScreen, 50); 
        window.addEventListener('resize', fitToScreen);
    }

    function fitToScreen() {
        const container = document.getElementById('workspace-container');
        const wrapper = document.getElementById('canvas-wrapper');
        if (!container || !wrapper) return;
        const scale = Math.min((container.clientWidth - 60) / 1080, (container.clientHeight - 60) / 1080);
        currentZoom = scale; updateZoomDisplay();
    }
	
	function changeZoom(delta) {
        currentZoom += delta;
        if (currentZoom < 0.1) currentZoom = 0.1;
        if (currentZoom > 2.0) currentZoom = 2.0;
        updateZoomDisplay();
    }
	
	function updateZoomDisplay() {
        const wrapper = document.getElementById('canvas-wrapper');
        const zoomText = document.getElementById('zoom-level');
        if (wrapper) wrapper.style.transform = `scale(${currentZoom})`;
        if (zoomText) zoomText.innerText = Math.round(currentZoom * 100) + "%";
    }

    function setupMainImage(img) {
        if (mainImageObj) canvas.remove(mainImageObj);
        const scale = Math.max(canvas.width / img.width, canvas.height / img.height);
        img.set({ scaleX: scale, scaleY: scale, left: canvas.width / 2, top: canvas.height / 2, originX: 'center', originY: 'center', selectable: true, isMainImage: true });
        mainImageObj = img; canvas.add(img); canvas.sendToBack(img);
    }

    window.controlMainImage = function(action, value) {
        let img = canvas.getObjects().find(o => o.isMainImage);
        if (!img) { alert("❌ কোনো নিউজ ইমেজ পাওয়া যায়নি!"); return; }
        switch (action) {
            case 'zoom': let newScale = img.scaleX + value; if (newScale > 0.1) img.set({ scaleX: newScale, scaleY: newScale }); break;
            case 'moveX': img.set('left', img.left + value); break;
            case 'moveY': img.set('top', img.top + value); break;
            case 'reset': const scale = Math.max(canvas.width / img.width, canvas.height / img.height); img.set({ scaleX: scale, scaleY: scale, left: canvas.width / 2, top: canvas.height / 2, originX: 'center', originY: 'center' }); break;
        }
        img.setCoords(); canvas.requestRenderAll(); saveHistory();
    };

    // ==========================================
    // 🎨 ৪. টেমপ্লেট ও ডিজাইন অ্যাপ্লাই লজিক
    // ==========================================
    window.applyAdminTemplate = function(imageUrl, layoutName, isRestore = false) {
        console.log("🚀 Applying Template:", layoutName);
        if (!isRestore) { window.userSettings.titlePos = null; window.userSettings.datePos = null; }
        currentLayout = layoutName; userSettings.frameUrl = imageUrl;

        const objects = canvas.getObjects();
        let titleObj = objects.find(obj => obj.isHeadline);
        let dateObj = objects.find(obj => obj.isDate);
        let mainImgObj = objects.find(obj => obj.isMainImage);

        for (let i = objects.length - 1; i >= 0; i--) {
            let obj = objects[i];
            if (!obj.isMainImage && !obj.isHeadline && !obj.isDate) canvas.remove(obj);
        }

        if(!titleObj) {
            titleObj = new fabric.Textbox(newsData.title || "Headline Here", { left: 50, top: 800, width: 980, fontSize: 60, fill: '#ffffff', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', isHeadline: true });
            canvas.add(titleObj);
        }

        fabric.Image.fromURL(imageUrl, function(img) {
            img.set({ left: 0, top: 0, scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, selectable: false, evented: false, isFrame: true });
            window.frameObj = img; canvas.add(img);

            if(mainImgObj) canvas.sendToBack(mainImgObj);
            canvas.sendToBack(img);
            if(mainImgObj) canvas.bringForward(img);
            if(titleObj) canvas.bringToFront(titleObj);
            if(dateObj) canvas.bringToFront(dateObj);

            const targetLayout = STUDIO_TEMPLATES[layoutName] || STUDIO_TEMPLATES['bottom'];

            // Image Zooming
            if (mainImgObj && targetLayout.image) {
                const imgConfig = targetLayout.image;
                let finalScale = Math.max(imgConfig.width / mainImgObj.width, imgConfig.height / mainImgObj.height) * (imgConfig.zoom !== undefined ? imgConfig.zoom : 1.0);
                mainImgObj.set({ scaleX: finalScale, scaleY: finalScale, left: imgConfig.left + (imgConfig.width / 2), top: imgConfig.top + (imgConfig.height / 2), originX: 'center', originY: 'center', clipPath: null });
                mainImgObj.setCoords();
            }

            // Title Positioning
            if(titleObj) {
                if (isRestore && window.userSettings?.titlePos) { titleObj.set(window.userSettings.titlePos); } 
                else {
                    const config = targetLayout.title;
                    titleObj.set({ top: config.top, left: config.left, width: config.width, textAlign: config.textAlign, originX: config.originX, fontSize: config.fontSize, backgroundColor: config.backgroundColor, fill: config.fill, fontFamily: config.fontFamily });
                    if(!config.fontFamily.includes('📂')) WebFont.load({ google: { families: [config.fontFamily.replace(/'/g, "").split(',')[0].trim()] } });
                    updateUI(config.fontSize, config.fill, config.fontFamily);
                    Object.assign(userSettings, { color: config.fill, font: config.fontFamily, size: config.fontSize, bg: config.backgroundColor });
                }
                titleObj.setCoords(); 
            }

            // Date Positioning
            if(dateObj) {
                if (isRestore && window.userSettings?.datePos) { dateObj.set(window.userSettings.datePos); } 
                else {
                    const dConfig = targetLayout.date;
                    dateObj.set({ top: dConfig.top, left: dConfig.left, originX: dConfig.originX, fontSize: dConfig.fontSize, fill: dConfig.fill, backgroundColor: dConfig.backgroundColor, fontFamily: dConfig.fontFamily });
                }
                dateObj.setCoords();
            }

            canvas.requestRenderAll(); saveHistory();
        }, { crossOrigin: 'anonymous' });
    };

    function restoreSavedDesign() {
        if (userSettings.frameUrl) { applyAdminTemplate(userSettings.frameUrl, userSettings.layout || 'bottom', true); } 
        else {
            let titleObj = canvas.getObjects().find(o => o.isHeadline);
            if(!titleObj) { titleObj = new fabric.Textbox(newsData.title, { left: 50, top: 800, width: 980, fontSize: 60, fill: '#000', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', isHeadline: true }); canvas.add(titleObj); }
        }
        setTimeout(() => {
            let titleObj = canvas.getObjects().find(o => o.isHeadline);
            if (titleObj) {
                let fontName = userSettings.font;
                if(!fontName.includes('📂')) WebFont.load({ google: { families: [fontName.replace(/'/g, "").split(',')[0].trim()] } });
                titleObj.set({ fill: userSettings.color, fontSize: parseInt(userSettings.size), backgroundColor: userSettings.bg, fontFamily: fontName });
                updateUI(userSettings.size, userSettings.color, userSettings.font); canvas.requestRenderAll();
            }
        }, 600);
        if (userSettings.logo) addProfileLogo(userSettings.logo);
        addDateText();
    }

    function saveCurrentDesign() {
        const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
        const dateObj = canvas.getObjects().find(obj => obj.isDate);
        const preferences = {
            template: userSettings.template, frameUrl: userSettings.frameUrl,
            font: titleObj ? titleObj.fontFamily : userSettings.font, color: titleObj ? titleObj.fill : userSettings.color,
            bg: titleObj ? titleObj.backgroundColor : userSettings.bg, size: titleObj ? titleObj.fontSize : userSettings.size,
            titlePos: titleObj ? { left: titleObj.left, top: titleObj.top, width: titleObj.width, textAlign: titleObj.textAlign, originX: titleObj.originX, fill: titleObj.fill, fontFamily: titleObj.fontFamily } : null, 
            datePos: dateObj ? { left: dateObj.left, top: dateObj.top, originX: dateObj.originX } : null, layout: currentLayout || userSettings.layout
        };
        fetch("{{ route('settings.save-design') }}", { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') }, body: JSON.stringify({ preferences }) })
        .then(res => res.json()).then(data => { if (data.success) { alert("✅ ডিজাইন সেভ হয়েছে!"); localStorage.setItem('studio_prefs', JSON.stringify(preferences)); Object.assign(userSettings, preferences); } });
    }

    // ==========================================
    // 🔤 ৫. ফন্ট ম্যানেজমেন্ট
    // ==========================================
    function loadFonts() {
        WebFont.load({ google: { families: STUDIO_FONTS.google }, custom: { families: STUDIO_FONTS.local }, active: function() { console.log("✅ All Fonts Loaded!"); if(canvas) canvas.requestRenderAll(); } });
    }

    window.uploadCustomFont = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const fontName = file.name.split('.')[0]; 
                const fontUrl = e.target.result;
                applyCustomFont(fontName, fontUrl);
                try { localStorage.setItem('custom_font_name', fontName); localStorage.setItem('custom_font_url', fontUrl); alert(`✅ ফন্ট '${fontName}' সেভ হয়েছে!`); } 
                catch (err) { alert("⚠️ ফন্টটি বড় হওয়ায় ব্রাউজারে সেভ করা যায়নি, তবে এখন ব্যবহার করতে পারবেন।"); }
            };
            reader.readAsDataURL(file);
        }
    };

    function applyCustomFont(fontName, fontUrl) {
        const newFont = new FontFace(fontName, `url(${fontUrl})`);
        newFont.load().then(function(loadedFont) {
            document.fonts.add(loadedFont);
            const select = document.getElementById('font-family');
            if(select && !Array.from(select.options).some(opt => opt.value === fontName)) { select.add(new Option("📂 " + fontName, fontName), select.options[0]); }
            select.value = fontName;
            const obj = canvas.getActiveObject();
            if (obj && (obj.type === 'text' || obj.type === 'textbox')) { obj.set("fontFamily", fontName); canvas.requestRenderAll(); saveHistory(); }
            userSettings.font = fontName;
        });
    }

    function loadStoredCustomFont() {
        const storedName = localStorage.getItem('custom_font_name'), storedUrl = localStorage.getItem('custom_font_url');
        if (storedName && storedUrl) applyCustomFont(storedName, storedUrl);
    }

    function changeFont(fontName) {
        const obj = canvas.getActiveObject();
        if (!obj) return;
        if(fontName.includes('📂')) { obj.set("fontFamily", fontName.replace('📂 ', '')); canvas.requestRenderAll(); saveHistory(); return; }
        const cleanFont = fontName.replace(/'/g, "").split(',')[0].trim();
        if (STUDIO_FONTS.local.includes(cleanFont)) { obj.set("fontFamily", cleanFont); canvas.requestRenderAll(); saveHistory(); if(obj.isHeadline) savePreference('font', fontName); } 
        else { WebFont.load({ google: { families: [cleanFont + ':400,700'] }, active: function() { obj.set("fontFamily", cleanFont); canvas.requestRenderAll(); if(obj.isHeadline) savePreference('font', fontName); saveHistory(); } }); }
    }

    // ==========================================
    // 📑 ৬. লেয়ার ম্যানেজমেন্ট
    // ==========================================
    window.renderLayerList = function() {
        const container = document.getElementById('layer-list-container');
        if (!container) return; container.innerHTML = '';
        const objects = canvas.getObjects().slice().reverse();
        if (objects.length === 0) { container.innerHTML = '<p class="text-xs text-gray-400 text-center">কোনো লেয়ার নেই</p>'; return; }

        objects.forEach((obj, index) => {
            const realIndex = objects.length - 1 - index;
            let name = "Shape / Rect", icon = "🟦";
            if (obj.isMainImage) { name = "News Image"; icon = "🖼️"; } else if (obj.isFrame) { name = "Frame / Overlay"; icon = "🔲"; } else if (obj.isHeadline) { name = "Headline Text"; icon = "📝"; } else if (obj.isDate) { name = "Date Text"; icon = "📅"; } else if (obj.type === 'image') { name = "Logo / Image"; icon = "📷"; } else if (obj.type === 'text' || obj.type === 'textbox') { name = "Custom Text"; icon = "✍️"; }
            const isActive = canvas.getActiveObject() === obj ? "border-indigo-500 bg-indigo-50" : "border-gray-200 bg-white";
            container.innerHTML += `<div class="flex items-center justify-between p-2 border rounded-lg ${isActive} hover:bg-gray-50 transition group cursor-pointer" onclick="selectLayer(${realIndex})"><div class="flex items-center gap-2 truncate"><span class="text-lg">${icon}</span><span class="text-xs font-bold text-gray-700 truncate w-32">${name}</span></div><div class="flex gap-1 opacity-60 group-hover:opacity-100"><button onclick="toggleVisibility(event, ${realIndex})" class="p-1 hover:text-blue-600">${obj.visible ? '👁️' : '🚫'}</button><button onclick="toggleLock(event, ${realIndex})" class="p-1 hover:text-red-600">${obj.lockMovementX ? '🔒' : '🔓'}</button><button onclick="deleteLayer(event, ${realIndex})" class="p-1 hover:text-red-600">🗑️</button></div></div>`;
        });
    };

    window.selectLayer = function(index) { const obj = canvas.item(index); if (obj) { canvas.setActiveObject(obj); canvas.renderAll(); renderLayerList(); } };
    window.toggleVisibility = function(e, index) { e.stopPropagation(); const obj = canvas.item(index); if (obj) { obj.visible = !obj.visible; if (!obj.visible) canvas.discardActiveObject(); canvas.renderAll(); renderLayerList(); } };
    window.toggleLock = function(e, index) { e.stopPropagation(); const obj = canvas.item(index); if (obj) { const isLocked = !obj.lockMovementX; obj.set({ lockMovementX: isLocked, lockMovementY: isLocked, lockScalingX: isLocked, lockScalingY: isLocked, lockRotation: isLocked, selectable: !isLocked }); canvas.renderAll(); renderLayerList(); } };
    window.deleteLayer = function(e, index) { e.stopPropagation(); if(confirm('ডিলিট করতে চান?')) { canvas.remove(canvas.item(index)); saveHistory(); renderLayerList(); } };
    window.moveLayer = function(direction) { const obj = canvas.getActiveObject(); if(!obj) return; if(direction === 'up') canvas.bringForward(obj); if(direction === 'down') canvas.sendBackwards(obj); if(direction === 'top') canvas.bringToFront(obj); if(direction === 'bottom') canvas.sendToBack(obj); canvas.renderAll(); saveHistory(); renderLayerList(); };

    // ==========================================
    // 🌐 ৭. এপিআই এবং পোস্টিং (Web & Social)
    // ==========================================
    function dataURLToBlob(dataURL) {
        var arr = dataURL.split(','), mime = arr[0].match(/:(.*?);/)[1], bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--) u8arr[n] = bstr.charCodeAt(n);
        return new Blob([u8arr], {type:mime});
    }

    function postDirectFromStudio() {
        const isSocialOnly = document.getElementById('socialOnlyCheck').checked;
        if (!confirm(isSocialOnly ? "⚠️ 'Only Social' সিলেক্ট করেছেন। নিশ্চিত?" : "সরাসরি পোস্ট করতে চান?")) return;
        const btn = document.querySelector('button[onclick="postDirectFromStudio()"]'); const originalText = btn.innerHTML; btn.innerHTML = "⏳ Uploading..."; btn.disabled = true;
        canvas.discardActiveObject(); canvas.renderAll();
        try {
            const formData = new FormData(); formData.append('design_image', dataURLToBlob(canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 })), 'studio-final.png');
            if (isSocialOnly) formData.append('social_only', '1');
            fetch("{{ route('news.publish-studio', $newsItem->id) }}", { method: "POST", headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: formData })
            .then(res => res.json()).then(data => { if (data.success) { alert("✅ পাঠানো হয়েছে!"); window.location.href = "{{ route('news.index') }}"; } else { alert("❌ " + data.message); btn.innerHTML = originalText; btn.disabled = false; } });
        } catch (error) { alert("❌ ক্যানভাস এরর।"); btn.innerHTML = originalText; btn.disabled = false; }
    }

    function confirmStudioPost() {
        const isSocialOnly = document.getElementById('modalSocialOnly').checked, categoryId = document.getElementById('modalCategory').value, caption = document.getElementById('modalCaption').value;
        if (!isSocialOnly && !categoryId) { alert("⚠️ ওয়েবসাইটে পোস্ট করার জন্য ক্যাটাগরি সিলেক্ট করুন।"); return; }
        const btn = document.getElementById('btnFinalPost'); const originalText = btn.innerHTML; btn.innerHTML = "⏳ Uploading..."; btn.disabled = true;
        canvas.discardActiveObject(); canvas.renderAll();
        try {
            const formData = new FormData(); formData.append('design_image', dataURLToBlob(canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 })), 'studio-final.png');
            if (isSocialOnly) formData.append('social_only', '1'); else if (categoryId) formData.append('category_id', categoryId);
            formData.append('social_caption', caption); 
            fetch("{{ route('news.publish-studio', $newsItem->id) }}", { method: "POST", headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: formData })
            .then(res => res.json()).then(data => { if (data.success) { alert("✅ পাবলিশিং শুরু হয়েছে!"); window.location.href = "{{ route('news.index') }}"; } else { alert("❌ " + data.message); btn.innerHTML = originalText; btn.disabled = false; } });
        } catch (error) { alert("❌ ক্যানভাস এরর।"); btn.innerHTML = originalText; btn.disabled = false; }
    }

    function refreshStudioCategories() {
        const btn = document.querySelector('button[onclick="refreshStudioCategories()"]'), select = document.getElementById('modalCategory');
        const originalText = btn.innerHTML; btn.innerHTML = "⏳ Loading..."; btn.disabled = true;
        fetch('/settings/fetch-categories').then(res => res.json()).then(data => {
            if (data.error) alert('❌ ' + data.error);
            else {
                select.innerHTML = '<option value="">-- Select Category --</option>';
                if (Array.isArray(data) && data.length > 0) { data.forEach(cat => select.innerHTML += `<option value="${cat.id}">${cat.name} (ID: ${cat.id})</option>`); select.innerHTML += `<option value="1">Uncategorized</option>`; alert("✅ আপডেট হয়েছে!"); } else alert("⚠️ ক্যাটাগরি নেই।");
            }
        }).finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }

    // ==========================================
    // 🛠️ ৮. ইউটিলিটি ও হিস্ট্রি (Undo/Redo, UI)
    // ==========================================
    function openPublishModal() { document.getElementById('studioPublishModal').classList.remove('hidden'); document.getElementById('studioPublishModal').classList.add('flex'); }
    function closePublishModal() { document.getElementById('studioPublishModal').classList.add('hidden'); document.getElementById('studioPublishModal').classList.remove('flex'); }
    function toggleCategoryField(isChecked) { const w = document.getElementById('categoryFieldWrapper'); isChecked ? w.classList.add('opacity-50', 'pointer-events-none') : w.classList.remove('opacity-50', 'pointer-events-none'); }
    function updateUI(size, color, font) { if(document.getElementById('val-size')) document.getElementById('val-size').innerText = size; if(document.getElementById('text-size')) document.getElementById('text-size').value = size; if(document.getElementById('text-color')) document.getElementById('text-color').value = color; if(document.getElementById('font-family')) document.getElementById('font-family').value = font; }
    function switchTab(tabName) { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); event.target.classList.add('active'); ['design', 'text', 'image', 'layers'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden')); document.getElementById('tab-' + tabName).classList.remove('hidden'); }
    function updateActiveProp(prop, value) { const obj = canvas.getActiveObject(); if (obj) { obj.set(prop, value); if(prop === 'backgroundColor') document.getElementById('transparent-bg-check').checked = false; canvas.renderAll(); if(obj.isHeadline) { if(prop === 'fill') savePreference('color', value); if(prop === 'backgroundColor') savePreference('bg', value); if(prop === 'fontSize') savePreference('size', value); } saveHistory(); } if(prop==='fontSize') document.getElementById('val-size').innerText = value; }
    function toggleTransparentBg(checked) { const obj = canvas.getActiveObject(); if (obj) { const color = checked ? '' : (document.getElementById('text-bg').value || '#000'); obj.set('backgroundColor', color); canvas.renderAll(); if(obj.isHeadline) savePreference('bg', color); } }
    function toggleStyle(style) { const obj = canvas.getActiveObject(); if (!obj) return; if (style === 'bold') obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold'); if (style === 'italic') obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic'); if (style === 'underline') obj.set('underline', !obj.underline); canvas.renderAll(); }
    function addText(text, size = 50) { const t = new fabric.Textbox(text, { left: 100, top: 100, width: 400, fontSize: size, fill: '#fff', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', backgroundColor: 'rgba(0,0,0,0.5)' }); canvas.add(t); canvas.setActiveObject(t); switchTab('text'); }
    function savePreference(key, value) { try { const prefs = JSON.parse(localStorage.getItem('studio_prefs')) || {}; prefs[key] = value; localStorage.setItem('studio_prefs', JSON.stringify(prefs)); } catch(e) {} }
    function downloadCard() { canvas.discardActiveObject(); canvas.renderAll(); const link = document.createElement('a'); link.download = `News_${Date.now()}.png`; link.href = canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 }); link.click(); }
    function resetCanvas() { if (confirm('রিসেট করতে চান?')) { localStorage.removeItem('studio_prefs'); localStorage.removeItem('custom_font_url'); location.reload(); } }
    function saveHistory() { if (isHistoryProcessing || !canvas) return; const json = JSON.stringify(canvas); if (historyStep >= 0 && history[historyStep] === json) return; historyStep++; history = history.slice(0, historyStep); history.push(json); }
    function undo() { if (historyStep > 0) { isHistoryProcessing = true; historyStep--; canvas.loadFromJSON(history[historyStep], function () { canvas.renderAll(); isHistoryProcessing = false; }); } }
    function redo() { if (historyStep < history.length - 1) { isHistoryProcessing = true; historyStep++; canvas.loadFromJSON(history[historyStep], function () { canvas.renderAll(); isHistoryProcessing = false; }); } }
    function initKeyboardEvents() { document.addEventListener('keydown', function(e) { if ((e.key === 'Delete' || e.key === 'Backspace') && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') { const obj = canvas.getActiveObject(); if (obj) canvas.remove(obj); } if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); } if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); } }); }
    function updateSidebarValues() { const obj = canvas.getActiveObject(); if (!obj) return; if (obj.type === 'textbox' || obj.type === 'text') { switchTab('text'); if(document.getElementById('text-content')) document.getElementById('text-content').value = obj.text; if(document.getElementById('text-color')) document.getElementById('text-color').value = obj.fill; } }
    function uploadLogo(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { addProfileLogo(e.target.result); }; r.readAsDataURL(input.files[0]); } }
    function addImageOnCanvas(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { fabric.Image.fromURL(e.target.result, function(img) { img.scaleToWidth(300); canvas.add(img); canvas.centerObject(img); canvas.setActiveObject(img); }); }; r.readAsDataURL(input.files[0]); } }
    function deleteActive() { const obj = canvas.getActiveObject(); if (obj) canvas.remove(obj); }
    function addProfileLogo(url) { fabric.Image.fromURL(url, function(img) { img.scaleToWidth(150); img.set({ left: 880, top: 50 }); canvas.add(img); canvas.bringToFront(img); }, { crossOrigin: "anonymous" }); }
    function addDateText() { const oldDate = canvas.getObjects().find(o => o.isDate); if(oldDate) canvas.remove(oldDate); const date = new Date(); const months = ["জানুয়ারি", "ফেব্রুয়ারি", "মার্চ", "এপ্রিল", "মে", "জুন", "জুলাই", "আগস্ট", "সেপ্টেম্বর", "অক্টোবর", "নভেম্বর", "ডিসেম্বর"]; const convert = (num) => num.toString().split('').map(d => ['০','১','২','৩','৪','৫','৬','৭','৮','৯'][d]||d).join(''); const dateStr = `${convert(date.getDate())} ${months[date.getMonth()]}, ${convert(date.getFullYear())}`; const dateText = new fabric.Text(dateStr, { left: 50, top: 50, fontSize: 24, fill: '#fff', fontFamily: 'Hind Siliguri', backgroundColor: '#d90429', padding: 8, isDate: true }); canvas.add(dateText); canvas.bringToFront(dateText); }
    function setBackgroundImage(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { fabric.Image.fromURL(e.target.result, function(img) { setupMainImage(img); saveHistory(); }); }; r.readAsDataURL(input.files[0]); } }
    function addCustomFrame(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { applyAdminTemplate(e.target.result, 'bottom'); }; r.readAsDataURL(input.files[0]); } }
    function removeFrame() { if(frameObj) { canvas.remove(frameObj); frameObj = null; } userSettings.frameUrl = null; savePreference('frameUrl', null); saveHistory(); }
    function activateDebugTools() { const debugBox = document.createElement('div'); debugBox.id = 'pos-finder'; debugBox.style.cssText = "position:fixed; bottom:20px; left:20px; background:rgba(0,0,0,0.8); color:#00ff00; padding:15px; z-index:9999; font-family:monospace; font-size:14px; border-radius:8px; pointer-events:none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"; debugBox.innerHTML = "Select text to see pos"; document.body.appendChild(debugBox); function updatePositionDisplay() { const obj = canvas.getActiveObject(); if (!obj) { debugBox.innerHTML = "Select object"; return; } debugBox.innerHTML = `Top: ${Math.round(obj.top)}<br>Left: ${Math.round(obj.left)}<br>OriginX: ${obj.originX}`; } canvas.on('object:moving', updatePositionDisplay); canvas.on('selection:created', updatePositionDisplay); }

</script>