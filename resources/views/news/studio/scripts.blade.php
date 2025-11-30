<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>

<script>
    // 1. GLOBAL VARIABLES
    var canvas;
    var mainImageObj = null;
    var frameObj = null;
    var currentLayout = null; 
    let history = []; 
    let historyStep = -1;
    let isHistoryProcessing = false;
    
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
        title: {!! json_encode($newsItem->title) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };

    // 2. INITIALIZATION
    function initCanvas() {
        canvas = new fabric.Canvas('newsCanvas', { backgroundColor: '#fff', preserveObjectStacking: true, selection: true });
        loadFonts();
        
        if (newsData.image) {
            var imgObj = new Image();
            imgObj.crossOrigin = "anonymous";
            imgObj.src = newsData.image;
            imgObj.onload = function() {
                fabric.Image.fromURL(newsData.image, function(img) {
                    setupMainImage(img); 
                    restoreSavedDesign(); 
                }, { crossOrigin: 'anonymous' });
            };
            imgObj.onerror = function() {
                console.warn("Image Load Failed"); 
                restoreSavedDesign(); 
            };
        } else {
            restoreSavedDesign();
        }

        canvas.on('selection:created', updateSidebarValues);
        canvas.on('selection:updated', updateSidebarValues);
        canvas.on('object:added', saveHistory);
        canvas.on('object:modified', saveHistory);
        
        initKeyboardEvents();
        activateDebugTools();
    }

    // 3. CORE FUNCTIONS 
    
    // ✅ Apply Template (Strict Mode)
    window.applyAdminTemplate = function(imageUrl, layoutName, isRestore = false) {
        console.log("🚀 Applying Template:", imageUrl, "Layout:", layoutName);

        // ১. পজিশন রিসেট: নতুন কার্ডে ক্লিক করলে সব পজিশন ক্লিয়ার
        if (!isRestore) {
            window.userSettings.titlePos = null;
            window.userSettings.datePos = null;
        }

        currentLayout = layoutName;
        userSettings.frameUrl = imageUrl;

        // ২. ক্লিনআপ
        const objects = canvas.getObjects();
        let titleObj = objects.find(obj => obj.isHeadline);
        let dateObj = objects.find(obj => obj.isDate);
        let mainImgObj = objects.find(obj => obj.isMainImage);

        for (let i = objects.length - 1; i >= 0; i--) {
            let obj = objects[i];
            if (obj.isMainImage || obj.isHeadline || obj.isDate) continue;
            canvas.remove(obj);
        }

        // হেডলাইন তৈরি (যদি না থাকে)
        if(!titleObj) {
            titleObj = new fabric.Textbox(newsData.title || "Headline Here", {
                left: 50, top: 800, width: 980, fontSize: 60, fill: '#ffffff',
                fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', isHeadline: true
            });
            canvas.add(titleObj);
        }

        // ফ্রেম লোড
        fabric.Image.fromURL(imageUrl, function(img) {
            img.set({ 
                left: 0, top: 0, scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, 
                selectable: false, evented: false, isFrame: true 
            });
            
            window.frameObj = img;
            canvas.add(img);

            // লেয়ারিং
            if(mainImgObj) canvas.sendToBack(mainImgObj);
            canvas.sendToBack(img);
            if(mainImgObj) canvas.bringForward(img);
            if(titleObj) canvas.bringToFront(titleObj);
            if(dateObj) canvas.bringToFront(dateObj);

            // 🔥 ৪. Strict Layout Configuration (সব ভ্যালু বাধ্যতামূলক)
            // ডিফল্ট ভ্যালু: Black text, Hind Siliguri, No BG
            const commonDefaults = {
                fontFamily: "'Hind Siliguri', sans-serif",
                fill: '#000000',
                backgroundColor: '',
                fontSize: 60
            };

            const layouts = {
                // NTV: (Black Text)
                'ntv': { 
                    title: { ...commonDefaults, top: 705, left: 555, originX: 'center', textAlign: 'center', width: 900, fill: '#000000', fontSize: 50 }, 
                    date:  { ...commonDefaults, top: 633, left: 240, originX: 'right', fill: '#000', fontSize: 30 } 
                },
                
                // RTV: (Red Text, Baloo Da Font)
                'rtv': { 
                    title: { 
                        ...commonDefaults, 
                        top: 603, 
                        left: 525,
                        originX: 'center', 
                        textAlign: 'center', 
                        width: 950, 
                        fill: '#d90429',
                        fontFamily: "'Baloo Da 2', cursive", 
                        fontSize: 45 
                    },
                    date: { 
                        ...commonDefaults, 
                        top: 43, 
                        left: 500, // ডেট পজিশন একটু ডানে সরানো হলো
                        originX: 'left', 
                        fill: '#d90429', 
                        fontSize: 30 
                    } 
                },

                // Dhaka Post: (Black BG, White Text)
                'dhakapost': { 
                    title: { ...commonDefaults, top: 772, left: 545, originX: 'center', textAlign: 'center', width: 980, fill: '#ffffff' }, 
                    date:  { ...commonDefaults, top: 20, left: 975, originX: 'center', fill: '#ffffff', fontSize: 25 } 
                },
                
                // Dhaka Post New (Dark)
                'dhakapost_new': { 
                    title: { ...commonDefaults, top: 780, left: 540, originX: 'center', textAlign: 'center', width: 950, fill: '#ffffff' }, 
                    date:  { ...commonDefaults, top: 730, left: 540, originX: 'center', fill: '#ffffff', fontSize: 25 } 
                },

                // Today Events
                'todayevents': { 
                    title: { ...commonDefaults, top: 750, left: 540, originX: 'center', textAlign: 'center', width: 900, fill: '#000000' }, 
                    date:  { ...commonDefaults, top: 30, left: 1050, originX: 'right', fill: '#000000' } 
                },

                // Default Fallback
                'bottom': { 
                    title: { ...commonDefaults, top: 800, left: 540, width: 980, textAlign: 'center', originX: 'center', fill: '#ffffff' }, 
                    date: { ...commonDefaults, top: 50, left: 50, originX: 'left' } 
                }
            };

            const defaultLayout = layouts['bottom'];
            const targetLayout = layouts[layoutName] || defaultLayout;

            // 🔥 ৫. টাইটেল অ্যাপ্লাই (Strict Values)
            if(titleObj) {
                if (isRestore && window.userSettings?.titlePos) {
                    titleObj.set(window.userSettings.titlePos);
                } else {
                    // এখানে আমরা || userSettings.color ব্যবহার করছি না, সরাসরি লেআউটের ভ্যালু নিচ্ছি
                    const config = targetLayout.title;
                    
                    titleObj.set({
                        top: config.top,
                        left: config.left,
                        width: config.width,
                        textAlign: config.textAlign,
                        originX: config.originX,
                        
                        fontSize: config.fontSize,
                        backgroundColor: config.backgroundColor,
                        fill: config.fill,
                        fontFamily: config.fontFamily
                    });
                    
                    // ফন্ট লোড
                    let cleanFont = config.fontFamily.replace(/'/g, "").split(',')[0].trim();
                    WebFont.load({ google: { families: [cleanFont] } });

                    // UI আপডেট
                    updateUI(config.fontSize, config.fill, config.fontFamily);

                    // গ্লোবাল আপডেট
                    userSettings.color = config.fill;
                    userSettings.font = config.fontFamily;
                    userSettings.size = config.fontSize;
                    userSettings.bg = config.backgroundColor;
                }
                titleObj.setCoords(); 
            }

            // ডেট অ্যাপ্লাই
            if(dateObj) {
                if (isRestore && window.userSettings?.datePos) {
                    dateObj.set(window.userSettings.datePos);
                } else {
                    const dConfig = targetLayout.date;
                    dateObj.set({
                        top: dConfig.top,
                        left: dConfig.left,
                        originX: dConfig.originX,
                        fontSize: dConfig.fontSize,
                        fill: dConfig.fill, 
                        backgroundColor: dConfig.backgroundColor
                    });
                }
                dateObj.setCoords();
            }

            canvas.requestRenderAll();
            saveHistory();

        }, { crossOrigin: 'anonymous' });
    }

    // Helper to update Sidebar UI
    function updateUI(size, color, font) {
        if(document.getElementById('val-size')) document.getElementById('val-size').innerText = size;
        if(document.getElementById('text-size')) document.getElementById('text-size').value = size;
        if(document.getElementById('text-color')) document.getElementById('text-color').value = color;
        if(document.getElementById('font-family')) document.getElementById('font-family').value = font;
    }

    // ... (বাকি Restore, Save, Utility ফাংশনগুলো আগের মতোই থাকবে) ...
    // এখানে নিচে আর কোনো পরিবর্তন নেই। আগের কোডের ফাংশনগুলো (restoreSavedDesign, saveCurrentDesign, etc) এখানে থাকবে।
    
    // ✅ Restore Function
    function restoreSavedDesign() {
        console.log("♻ Restoring Design...", userSettings);
        if (userSettings.frameUrl) {
            applyAdminTemplate(userSettings.frameUrl, userSettings.layout || 'bottom', true);
        } else {
            let titleObj = canvas.getObjects().find(o => o.isHeadline);
            if(!titleObj) {
                titleObj = new fabric.Textbox(newsData.title, { left: 50, top: 800, width: 980, fontSize: 60, fill: '#000', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', isHeadline: true });
                canvas.add(titleObj);
            }
        }
        setTimeout(() => {
            let titleObj = canvas.getObjects().find(o => o.isHeadline);
            if (titleObj) {
                let fontName = userSettings.font.replace(/'/g, "").split(',')[0].trim();
                titleObj.set({ fill: userSettings.color, fontSize: parseInt(userSettings.size), backgroundColor: userSettings.bg, fontFamily: fontName });
                WebFont.load({ google: { families: [fontName] } });
                updateUI(userSettings.size, userSettings.color, userSettings.font);
                canvas.requestRenderAll();
            }
        }, 600);
        if (userSettings.logo) addProfileLogo(userSettings.logo);
        addDateText();
    }

    // ✅ Save Function
    function saveCurrentDesign() {
        const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
        const dateObj = canvas.getObjects().find(obj => obj.isDate);
        let tPos = null, dPos = null;
        if (titleObj) tPos = { left: titleObj.left, top: titleObj.top, width: titleObj.width, textAlign: titleObj.textAlign, originX: titleObj.originX, fill: titleObj.fill, fontFamily: titleObj.fontFamily };
        if (dateObj) dPos = { left: dateObj.left, top: dateObj.top, originX: dateObj.originX };

        const preferences = {
            template : userSettings.template,
            frameUrl : userSettings.frameUrl,
            font     : titleObj ? titleObj.fontFamily : userSettings.font,
            color    : titleObj ? titleObj.fill : userSettings.color,
            bg       : titleObj ? titleObj.backgroundColor : userSettings.bg,
            size     : titleObj ? titleObj.fontSize : userSettings.size,
            titlePos : tPos,
            datePos  : dPos,
            layout   : currentLayout || userSettings.layout
        };
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        fetch("{{ route('settings.save-design') }}", {
            method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": token },
            body: JSON.stringify({ preferences })
        }).then(res => res.json()).then(data => {
            if (data.success) { 
                alert("✅ ডিজাইন সেভ হয়েছে!"); 
                localStorage.setItem('studio_prefs', JSON.stringify(preferences)); 
                Object.assign(userSettings, preferences);
            }
        });
    }

    function setupMainImage(img) {
        if (mainImageObj) canvas.remove(mainImageObj);
        const scale = Math.max(canvas.width / img.width, canvas.height / img.height);
        img.set({ scaleX: scale, scaleY: scale, left: (canvas.width - img.width * scale) / 2, top: (canvas.height - img.height * scale) / 2, selectable: true, isMainImage: true });
        mainImageObj = img; 
        canvas.add(img); 
        canvas.sendToBack(img);
    }
    function addProfileLogo(url) { fabric.Image.fromURL(url, function(img) { img.scaleToWidth(150); img.set({ left: 880, top: 50 }); canvas.add(img); canvas.bringToFront(img); }, { crossOrigin: "anonymous" }); }
    function addDateText() {
        const oldDate = canvas.getObjects().find(o => o.isDate);
        if(oldDate) canvas.remove(oldDate);
        const date = new Date();
        const months = ["জানুয়ারি", "ফেব্রুয়ারি", "মার্চ", "এপ্রিল", "মে", "জুন", "জুলাই", "আগস্ট", "সেপ্টেম্বর", "অক্টোবর", "নভেম্বর", "ডিসেম্বর"];
        const convert = (num) => num.toString().split('').map(d => ['০','১','২','৩','৪','৫','৬','৭','৮','৯'][d]||d).join('');
        const dateStr = `${convert(date.getDate())} ${months[date.getMonth()]}, ${convert(date.getFullYear())}`;
        const dateText = new fabric.Text(dateStr, { left: 50, top: 50, fontSize: 24, fill: '#fff', fontFamily: 'Hind Siliguri', backgroundColor: '#d90429', padding: 8, isDate: true });
        canvas.add(dateText); canvas.bringToFront(dateText);
    }
    function setBackgroundImage(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { fabric.Image.fromURL(e.target.result, function(img) { setupMainImage(img); saveHistory(); }); }; r.readAsDataURL(input.files[0]); } }
    function addCustomFrame(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { applyAdminTemplate(e.target.result, 'bottom'); }; r.readAsDataURL(input.files[0]); } }
    function removeFrame() { if(frameObj) { canvas.remove(frameObj); frameObj = null; } userSettings.frameUrl = null; savePreference('frameUrl', null); saveHistory(); }
    function loadFonts() { WebFont.load({ google: { families: ['Hind Siliguri:300,400,500,600,700', 'Noto Sans Bengali', 'Baloo Da 2', 'Galada', 'Anek Bangla', 'Tiro Bangla', 'Mina', 'Oswald', 'Roboto', 'Montserrat'] } }); }
    function switchTab(tabName) { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); event.target.classList.add('active'); ['design', 'text', 'image', 'layers'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden')); document.getElementById('tab-' + tabName).classList.remove('hidden'); }
    function updateActiveProp(prop, value) { const obj = canvas.getActiveObject(); if (obj) { obj.set(prop, value); if(prop === 'backgroundColor') document.getElementById('transparent-bg-check').checked = false; canvas.renderAll(); if(obj.isHeadline) { if(prop === 'fill') savePreference('color', value); if(prop === 'backgroundColor') savePreference('bg', value); if(prop === 'fontSize') savePreference('size', value); } saveHistory(); } if(prop==='fontSize') document.getElementById('val-size').innerText = value; }
    
	//function changeFont(fontName) { const obj = canvas.getActiveObject(); if (obj) { const cleanFont = fontName.replace(/'/g, "").split(',')[0].trim(); WebFont.load({ google: { families: [cleanFont] }, active: function() { obj.set("fontFamily", cleanFont); canvas.requestRenderAll(); if(obj.isHeadline) savePreference('font', fontName); saveHistory(); }}); } }
    
	// ✅ Change Font (Updated for Dynamic Loading)
function changeFont(fontName) {
    const obj = canvas.getActiveObject();
    if (obj) {
        // ফন্টের নাম ক্লিন করা হচ্ছে
        const cleanFont = fontName.replace(/'/g, "").split(',')[0].trim();

        // লোডিং ইন্ডিকেটর কনসোলে দেখানো
        console.log("⏳ Loading font:", cleanFont);

        // Google থেকে ফন্ট লোড করা
        WebFont.load({
            google: {
                families: [cleanFont + ':400,700'] // Regular & Bold ওয়েট লোড করা হচ্ছে
            },
            active: function() {
                // ফন্ট লোড সফল হলে অ্যাপ্লাই হবে
                obj.set("fontFamily", cleanFont);
                canvas.requestRenderAll();

                // সেভ করা এবং হিস্ট্রি আপডেট করা
                if (obj.isHeadline) savePreference('font', fontName);
                saveHistory();
                console.log("✅ Font Applied:", cleanFont);
            },
            inactive: function() {
                // ফন্ট লোড না হলে এরর মেসেজ
                alert("❌ ফন্টটি পাওয়া যায়নি! সঠিক নাম দিন।");
            }
        });
    }
}
	
	
	function toggleTransparentBg(checked) { const obj = canvas.getActiveObject(); if (obj) { const color = checked ? '' : (document.getElementById('text-bg').value || '#000'); obj.set('backgroundColor', color); canvas.renderAll(); if(obj.isHeadline) savePreference('bg', color); } }
    function toggleStyle(style) { const obj = canvas.getActiveObject(); if (!obj) return; if (style === 'bold') obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold'); if (style === 'italic') obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic'); if (style === 'underline') obj.set('underline', !obj.underline); canvas.renderAll(); }
    function addText(text, size = 50) { const t = new fabric.Textbox(text, { left: 100, top: 100, width: 400, fontSize: size, fill: '#fff', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', backgroundColor: 'rgba(0,0,0,0.5)' }); canvas.add(t); canvas.setActiveObject(t); switchTab('text'); }
    function savePreference(key, value) { try { const prefs = JSON.parse(localStorage.getItem('studio_prefs')) || {}; prefs[key] = value; localStorage.setItem('studio_prefs', JSON.stringify(prefs)); } catch(e) {} }
    function downloadCard() { canvas.discardActiveObject(); canvas.renderAll(); const link = document.createElement('a'); link.download = `News_${Date.now()}.png`; link.href = canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 }); link.click(); }
    function resetCanvas() { if (confirm('রিসেট করতে চান?')) { localStorage.removeItem('studio_prefs'); location.reload(); } }
    function saveHistory() { if (isHistoryProcessing || !canvas) return; const json = JSON.stringify(canvas); if (historyStep >= 0 && history[historyStep] === json) return; historyStep++; history = history.slice(0, historyStep); history.push(json); }
    function undo() { if (historyStep > 0) { isHistoryProcessing = true; historyStep--; canvas.loadFromJSON(history[historyStep], function () { canvas.renderAll(); isHistoryProcessing = false; }); } }
    function redo() { if (historyStep < history.length - 1) { isHistoryProcessing = true; historyStep++; canvas.loadFromJSON(history[historyStep], function () { canvas.renderAll(); isHistoryProcessing = false; }); } }
    let currentZoom = 0.5;
    function changeZoom(delta) { currentZoom += delta; if (currentZoom < 0.1) currentZoom = 0.1; document.getElementById('canvas-wrapper').style.transform = `scale(${currentZoom})`; }
    function initKeyboardEvents() { document.addEventListener('keydown', function(e) { if ((e.key === 'Delete' || e.key === 'Backspace') && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') { const obj = canvas.getActiveObject(); if (obj) canvas.remove(obj); } if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); } if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); } }); }
    function updateSidebarValues() { const obj = canvas.getActiveObject(); if (!obj) return; if (obj.type === 'textbox' || obj.type === 'text') { switchTab('text'); if(document.getElementById('text-content')) document.getElementById('text-content').value = obj.text; if(document.getElementById('text-color')) document.getElementById('text-color').value = obj.fill; } }
    function uploadLogo(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { addProfileLogo(e.target.result); }; r.readAsDataURL(input.files[0]); } }
    function addImageOnCanvas(input) { if (input.files && input.files[0]) { const r = new FileReader(); r.onload = function (e) { fabric.Image.fromURL(e.target.result, function(img) { img.scaleToWidth(300); canvas.add(img); canvas.centerObject(img); canvas.setActiveObject(img); }); }; r.readAsDataURL(input.files[0]); } }
    function deleteActive() { const obj = canvas.getActiveObject(); if (obj) canvas.remove(obj); }
    function activateDebugTools() { const debugBox = document.createElement('div'); debugBox.id = 'pos-finder'; debugBox.style.cssText = "position:fixed; bottom:20px; left:20px; background:rgba(0,0,0,0.8); color:#00ff00; padding:15px; z-index:9999; font-family:monospace; font-size:14px; border-radius:8px; pointer-events:none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"; debugBox.innerHTML = "Select text to see pos"; document.body.appendChild(debugBox); function updatePositionDisplay() { const obj = canvas.getActiveObject(); if (!obj) { debugBox.innerHTML = "Select object"; return; } debugBox.innerHTML = `Top: ${Math.round(obj.top)}<br>Left: ${Math.round(obj.left)}<br>OriginX: ${obj.originX}`; } canvas.on('object:moving', updatePositionDisplay); canvas.on('selection:created', updatePositionDisplay); }



    window.uploadCustomFont = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                // ফন্টের নাম ফাইলের নাম থেকে নেওয়া
                const fontName = file.name.split('.')[0]; 
                const fontUrl = e.target.result;

                // ব্রাউজারে ফন্ট লোড করা (FontFace API)
                const newFont = new FontFace(fontName, `url(${fontUrl})`);
                
                newFont.load().then(function(loadedFont) {
                    document.fonts.add(loadedFont); // ডকুমেন্ট-এ অ্যাড করা
                    
                    // ক্যানভাসে অ্যাপ্লাই করা
                    const obj = canvas.getActiveObject();
                    if (obj) {
                        obj.set("fontFamily", fontName);
                        canvas.requestRenderAll();
                        saveHistory();
                    }
                    
                    // ড্রপডাউনে অপশন যোগ করা (যাতে পরে আবার সিলেক্ট করা যায়)
                    const select = document.getElementById('font-family');
                    if(select) {
                        const option = document.createElement("option");
                        option.text = "📂 " + fontName;
                        option.value = fontName;
                        option.selected = true;
                        select.add(option);
                    }

                    alert(`✅ কাস্টম ফন্ট '${fontName}' যুক্ত হয়েছে!`);
                }).catch(function(error) {
                    alert("❌ ফন্ট লোড করা যায়নি: " + error);
                });
            };
            reader.readAsDataURL(file);
        }
    };





    window.onload = initCanvas;
</script>