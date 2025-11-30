<script>
    console.log("✅ Utils Script Loaded (Aggressive Mode)");

    // --- Global Variables ---
    let canvasHistory = []; 
    let historyStep = -1;
    let isHistoryProcessing = false;

    // --- Helper Functions ---
    window.savePreference = function(key, value) {
        try {
            const prefs = JSON.parse(localStorage.getItem('studio_prefs')) || {};
            prefs[key] = value;
            localStorage.setItem('studio_prefs', JSON.stringify(prefs));
        } catch(e) {}
    };

    window.saveHistory = function() {
        if (isHistoryProcessing || !canvas) return;
        if (canvasHistory.length > 20) {
            canvasHistory.shift();
            historyStep--;
        }
        const json = JSON.stringify(canvas);
        if (historyStep >= 0 && canvasHistory[historyStep] === json) return;
        historyStep++;
        canvasHistory = canvasHistory.slice(0, historyStep);
        canvasHistory.push(json);
    };

    window.undo = function() {
        if (historyStep > 0) {
            isHistoryProcessing = true;
            historyStep--;
            canvas.loadFromJSON(canvasHistory[historyStep], function () {
                canvas.renderAll();
                reassignReferences();
                isHistoryProcessing = false;
            });
        }
    };

    window.redo = function() {
        if (historyStep < canvasHistory.length - 1) {
            isHistoryProcessing = true;
            historyStep++;
            canvas.loadFromJSON(canvasHistory[historyStep], function () {
                canvas.renderAll();
                reassignReferences();
                isHistoryProcessing = false;
            });
        }
    };

    window.reassignReferences = function() {
        if(canvas) {
            const mainImg = canvas.getObjects().find(o => o.isMainImage);
            if(mainImg) mainImageObj = mainImg;
            const frm = canvas.getObjects().find(o => o.isFrame);
            if(frm) frameObj = frm;
        }
    };

    window.deleteActive = function() {
        const obj = canvas.getActiveObject();
        if (obj) { canvas.remove(obj); saveHistory(); }
    };

    window.updateSidebarValues = function() {
        const obj = canvas.getActiveObject();
        if (!obj) return;
        if (obj.type === 'textbox' || obj.type === 'text') {
            if(typeof switchTab === 'function') switchTab('text');
            const contentEl = document.getElementById('text-content');
            if(contentEl) contentEl.value = obj.text;
            const colorEl = document.getElementById('text-color');
            if(colorEl) colorEl.value = obj.fill;
        }
    };

    window.saveCurrentDesign = function() {
    const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
    const dateObj = canvas.getObjects().find(obj => obj.isDate); // ডেট খুঁজে বের করা

    // পজিশন অবজেক্ট তৈরি
    let tPos = null;
    let dPos = null;

    if (titleObj) {
        tPos = { left: titleObj.left, top: titleObj.top, width: titleObj.width, textAlign: titleObj.textAlign, originX: titleObj.originX };
    }
    if (dateObj) {
        dPos = { left: dateObj.left, top: dateObj.top, originX: dateObj.originX };
    }

    const preferences = {
        // ... আগের সেটিংস ...
        template : userSettings.template,
        frameUrl : userSettings.frameUrl,
        font     : titleObj ? titleObj.fontFamily : userSettings.font,
        color    : titleObj ? titleObj.fill : userSettings.color,
        
        // 🔥 নতুন: দুটো পজিশনই সেভ করছি
        titlePos : tPos,
        datePos  : dPos
    };

    // ... (fetch request আগের মতোই থাকবে) ...
    // fetch(...).then(...)
    
    // লোকাল আপডেট
    if(typeof window.userSettings !== 'undefined') {
        Object.assign(window.userSettings, preferences);
    }
    localStorage.setItem('studio_prefs', JSON.stringify(preferences));
    alert("✅ সব পজিশন সেভ হয়েছে!");
};

    window.downloadCard = function() {
        if(!canvas) return;
        canvas.discardActiveObject();
        canvas.renderAll();
        const link = document.createElement('a');
        link.download = `News_${Date.now()}.png`;
        link.href = canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 });
        link.click();
    };

    // 🔥🔥🔥 AGGRESSIVE RESTORE FUNCTION 🔥🔥🔥
    window.restoreSavedDesign = function() {
        console.log("🚀 Restore Started...");

        // 1. Get Data
        let savedData = localStorage.getItem('studio_prefs');
        let dbData = (typeof window.dbPrefs !== 'undefined') ? window.dbPrefs : null;
        if (typeof dbData === 'string') { try { dbData = JSON.parse(dbData); } catch(e) {} }
        
        let prefs = dbData || (savedData ? JSON.parse(savedData) : null);

        if (!prefs) {
            alert("❌ কোনো ডাটা পাওয়া যায়নি!");
            return;
        }

        // 2. Set Global Settings
        window.userSettings.template = prefs.template || 'classic';
        window.userSettings.font     = prefs.font || "'Hind Siliguri', sans-serif";
        window.userSettings.color    = prefs.color || '#ffffff';
        window.userSettings.size     = parseInt(prefs.size) || 60;
        window.userSettings.bg       = (prefs.bg && prefs.bg !== 'null') ? prefs.bg : '';
        window.userSettings.frameUrl = prefs.frameUrl || null;

        // 3. Apply Template (Resets Canvas)
        if (typeof window.applyTemplate === 'function') {
            window.applyTemplate(window.userSettings.template);
        }

        // 4. 🔥 THE LOOP 🔥 (এটা ৫ বার চেষ্টা করবে যাতে কালার মিস না হয়)
        let attempts = 0;
        const maxAttempts = 6;
        
        const aggressiveInterval = setInterval(() => {
            attempts++;
            console.log(`⏳ Attempt ${attempts}/${maxAttempts} to force styles...`);

            const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
            
            if (titleObj) {
                // A. Force Apply Styles
                titleObj.set('fill', window.userSettings.color);
                titleObj.set('fontSize', window.userSettings.size);
                titleObj.set('backgroundColor', window.userSettings.bg);

                let fontName = window.userSettings.font.replace(/'/g, "").split(',')[0].trim();
                titleObj.set("fontFamily", fontName);

                // B. Frame Restore (Only on first attempt to avoid flickering)
                if (attempts === 1 && window.userSettings.frameUrl) {
                    fabric.Image.fromURL(window.userSettings.frameUrl, function(img) {
                        const oldFrame = canvas.getObjects().find(o => o.isFrame);
                        if (oldFrame) canvas.remove(oldFrame);
                        img.set({ left: 0, top: 0, scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, selectable: false, evented: false, isFrame: true });
                        canvas.add(img);
                        canvas.bringToFront(img);
                        window.frameObj = img;
                    }, { crossOrigin: 'anonymous' });
                }

                // C. Update UI
                if(document.getElementById('text-color')) document.getElementById('text-color').value = window.userSettings.color;
                
                // D. Force Render
                canvas.requestRenderAll();
            }

            // 6 বার চেষ্টার পর লুপ থামবে
            if (attempts >= maxAttempts) {
                clearInterval(aggressiveInterval);
                window.saveHistory();
                console.log("✅ Restore Loop Finished.");
                // alert("✅ রিস্টোর সম্পন্ন! কালার: " + window.userSettings.color);
            }

        }, 200); // প্রতি ২০০ মিলিসেকেন্ডে একবার চেক করবে
    };

    window.initKeyboardEvents = function() {
        document.addEventListener('keydown', function(e) {
            if ((e.key === 'Delete' || e.key === 'Backspace') && e.target.tagName !== 'INPUT') deleteActive();
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); }
        });
    };
</script>