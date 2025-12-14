<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>

<script>

    var canvas;
    var mainImageObj = null;
    var frameObj = null;
    var currentLayout = null; 
    let history = []; 
    let historyStep = -1;
    let isHistoryProcessing = false;
	let currentZoom = 1;
    
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
    
    // 🔥 UPDATED LOGIC HERE: Use ai_title if exists, else title
    var newsData = {
        title: {!! json_encode(!empty($newsItem->ai_title) ? $newsItem->ai_title : $newsItem->title) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };


	
	function fitToScreen() {
        const container = document.getElementById('workspace-container');
        const wrapper = document.getElementById('canvas-wrapper');
        
        if (!container || !wrapper) return;

        const availableWidth = container.clientWidth - 60; // 60px padding
        const availableHeight = container.clientHeight - 60;

        const canvasWidth = 1080;
        const canvasHeight = 1080;

        const scaleX = availableWidth / canvasWidth;
        const scaleY = availableHeight / canvasHeight;
        
        let scale = Math.min(scaleX, scaleY);

        currentZoom = scale;
        updateZoomDisplay();
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
        
        if (wrapper) {
            wrapper.style.transform = `scale(${currentZoom})`;
        }
        if (zoomText) {
            zoomText.innerText = Math.round(currentZoom * 100) + "%";
        }
    }
	
    function initCanvas() {
        canvas = new fabric.Canvas('newsCanvas', { 
            backgroundColor: '#fff', 
            preserveObjectStacking: true, 
            selection: true 
        });
        
        loadStoredCustomFont();
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

        setTimeout(fitToScreen, 100); 
        window.addEventListener('resize', fitToScreen);
    }

    
    window.uploadCustomFont = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const fontName = file.name.split('.')[0]; 
                const fontUrl = e.target.result;

                applyCustomFont(fontName, fontUrl);

                try {
                    localStorage.setItem('custom_font_name', fontName);
                    localStorage.setItem('custom_font_url', fontUrl);
                    alert(`✅ ফন্ট '${fontName}' সেভ হয়েছে!`);
                } catch (err) {
                    console.warn("Local Storage Full or Error", err);
                    alert("⚠️ ফন্টটি বড় হওয়ায় ব্রাউজারে সেভ করা যায়নি, তবে এখন ব্যবহার করতে পারবেন।");
                }
            };
            reader.readAsDataURL(file);
        }
    };


    function applyCustomFont(fontName, fontUrl) {
        const newFont = new FontFace(fontName, `url(${fontUrl})`);
        newFont.load().then(function(loadedFont) {
            document.fonts.add(loadedFont);
            
            const select = document.getElementById('font-family');
            if(select) {
                let exists = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === fontName) exists = true;
                }
                if (!exists) {
                    const option = document.createElement("option");
                    option.text = "📂 " + fontName;
                    option.value = fontName;
                    select.add(option, select.options[0]);
                }
                select.value = fontName;
            }

            const obj = canvas.getActiveObject();
            if (obj && (obj.type === 'text' || obj.type === 'textbox')) {
                obj.set("fontFamily", fontName);
                canvas.requestRenderAll();
                saveHistory();
            }
            
            userSettings.font = fontName;

        }).catch(err => console.error("Font Load Error:", err));
    }

    function loadStoredCustomFont() {
        const storedName = localStorage.getItem('custom_font_name');
        const storedUrl = localStorage.getItem('custom_font_url');
        
        if (storedName && storedUrl) {
            console.log("♻ Loading Saved Custom Font:", storedName);
            applyCustomFont(storedName, storedUrl);
        }
    }

    window.applyAdminTemplate = function(imageUrl, layoutName, isRestore = false) {
    console.log("🚀 Applying Template with Fixed Image & Zoom:", layoutName);

    // ১. সেটিংস রিসেট
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

    // মেইন অবজেক্ট বাদে বাকি সব রিমুভ
    for (let i = objects.length - 1; i >= 0; i--) {
        let obj = objects[i];
        if (obj.isMainImage || obj.isHeadline || obj.isDate) continue;
        canvas.remove(obj);
    }

    // ৩. টাইটেল না থাকলে তৈরি করা
    if(!titleObj) {
        titleObj = new fabric.Textbox(newsData.title || "Headline Here", {
            left: 50, top: 800, width: 980, fontSize: 60, fill: '#ffffff',
            fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', isHeadline: true
        });
        canvas.add(titleObj);
    }

    // ৪. ফ্রেম লোড
    fabric.Image.fromURL(imageUrl, function(img) {
        img.set({ 
            left: 0, top: 0, scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, 
            selectable: false, evented: false, isFrame: true 
        });
        
        window.frameObj = img;
        canvas.add(img);

        // ৫. লেয়ার অর্ডারিং
        if(mainImgObj) canvas.sendToBack(mainImgObj); // ইমেজ সবার নিচে
        canvas.sendToBack(img); // ফ্রেম তার উপরে (কিন্তু ইমেজের নিচে না, লজিক্যালি ফ্রেম ইমেজের উপরে থাকা উচিত যদি ট্রান্সপারেন্ট হয়)
        if(mainImgObj) canvas.bringForward(img); // ফ্রেম ইমেজের উপরে
        if(titleObj) canvas.bringToFront(titleObj);
        if(dateObj) canvas.bringToFront(dateObj);

        // ডিফল্ট ফন্ট সেটিংস
        const commonDefaults = {
            fontFamily: "'Hind Siliguri', sans-serif",
            fill: '#000000',
            backgroundColor: '',
            fontSize: 60
        };


        const layouts = {
            'ntv': { 
                title: { ...commonDefaults, top: 705, left: 555, originX: 'center', textAlign: 'center', width: 1000, fill: '#000000', fontSize: 50 }, 
                date:  { ...commonDefaults, top: 633, left: 240, originX: 'right', fill: '#000000', fontSize: 30 },
                image: { ...commonDefaults, left: 17, top: 62, width: 1080, height: 520, zoom: 1.0 }
            },
            'rtv': { 
                title: { 
                    ...commonDefaults, 
                    top: 603, left: 540, originX: 'center', textAlign: 'center', width: 950, 
                    fill: '#d90429', fontSize: 45 
                },
                date: { ...commonDefaults, top: 43, left: 500, originX: 'left', fill: '#d90429', fontSize: 30 },
                image: { ...commonDefaults, left: 40, top: 115, width: 1000, height: 430, zoom: 0.9 }
            },
            'dhakapost': { 
                title: { ...commonDefaults, top: 772, left: 545, originX: 'center', textAlign: 'center', width: 980, fill: '#ffffff' }, 
                date:  { ...commonDefaults, top: 20, left: 975, originX: 'center', fill: '#000', fontSize: 30 },
                image: { ...commonDefaults, left: 40, top: 130, width: 1000, height: 430, zoom: 1.3 }
            },
            'todayevents': { 
                title: { ...commonDefaults, top: 710, left: 540, originX: 'center', textAlign: 'center', width: 1000, fill: '#000000' }, 
                date:  { ...commonDefaults, top: 1015, left: 1050, originX: 'right', fill: '#000000', fontSize: 26 },
                image: { ...commonDefaults, left: 40, top: 120, width: 1000, height: 430, zoom: 1.1 }
            },
            'bottom': { 
                title: { ...commonDefaults, top: 800, left: 540, width: 980, textAlign: 'center', originX: 'center', fill: '#ffffff' }, 
                date: { ...commonDefaults, top: 50, left: 50, originX: 'left' },
                image: { ...commonDefaults, left: 0, top: 0, width: 1080, height: 1080, zoom: 1.0 }
            },
			'BanglaLiveNews': { 
				title: { ...commonDefaults, top: 685, left: 540, width: 980, textAlign: 'center', originX: 'center', fill: '#ffffff', fontSize: 60, fontFamily: "'Hind Siliguri', sans-serif" },
				date:  { ...commonDefaults, top: 43, left: 850, originX: 'left', fill: '#000000', fontSize: 30 },
				image: { ...commonDefaults, left: 50, top: 150, width: 980, height: 550, zoom: 1.0 }
			},

			'Jaijaidin1': { 
				title: { ...commonDefaults, top: 750, left: 540, width: 950, textAlign: 'center', originX: 'center', fill: '#ffffff', fontSize: 55, fontFamily: "'Hind Siliguri', sans-serif" },
				date:  { ...commonDefaults, top: 38, left: 1042, originX: 'right', fill: '#000', fontSize: 28 },
				image: { ...commonDefaults, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 } // একটু জুম আউট
			},

			'Jaijaidin2': { 
				title: { ...commonDefaults, top: 720, left: 540, width: 950, textAlign: 'center', originX: 'center', fill: '#ffffff' },
				date:  { ...commonDefaults, top: 640, left: 28, originX: 'left', fill: '#000', fontSize: 32 },
				image: { ...commonDefaults, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
			},

			'Jaijaidin3': { 
				title: { ...commonDefaults, top: 750, left: 540, width: 900, textAlign: 'center', originX: 'center', fill: '#ffffff' },
				date:  { ...commonDefaults, top: 40, left: 860, originX: 'left', fill: '#000000',fontSize: 32 },
				image: { ...commonDefaults, left: 1, top: 200, width: 1080, height: 450, zoom: 1.0, originX: 'center' }
			},

			'Jaijaidin4': { 
				title: { ...commonDefaults, top: 600, left: 540, width: 900, textAlign: 'center', originX: 'center', fill: '#000000' },
				date:  { ...commonDefaults, top: 900, left: 540, originX: 'center', fill: '#000000' },
				image: { ...commonDefaults, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
			},
			'ShotterKhoje': { 
				title: { ...commonDefaults, top: 730, left: 540, width: 900, textAlign: 'center', originX: 'center', fill: '#ffffff' },
				date:  { ...commonDefaults, top: 15, left: 460, originX: 'left', fill: '#ffffff', fontSize: 28 },
				image: { ...commonDefaults, left: 40, top: 80, width: 980, height: 520, zoom: 1.2 }
			},
			'BanglaLiveNews1': { 
				title: { ...commonDefaults, top: 712, left: 545, width: 1050, textAlign: 'center', originX: 'center', fill: '#ffffff' },
				date:  { ...commonDefaults, top: 635, left: 130, originX: 'center', fill: '#000000', fontSize: 30 },
				image: { ...commonDefaults, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
			},
			'jonomot': { 
				title: { ...commonDefaults, top: 770, left: 545, width: 1050, textAlign: 'center', originX: 'center', fill: '#ffffff' },
				date:  { ...commonDefaults, top: 45, left: 120, originX: 'center', fill: '#000000', fontSize: 30 },
				image: { ...commonDefaults, left: 1, top: 160, width: 1080, height: 540, zoom: 1.0 }
			}
			
			

			
			
        };

        const defaultLayout = layouts['bottom'];
        const targetLayout = layouts[layoutName] || defaultLayout;

        // ==========================================
        // 🔥 ৭. মেইন ইমেজ পজিশনিং ও জুম লজিক
        // ==========================================
        if (mainImgObj && targetLayout.image) {
            const imgConfig = targetLayout.image;
            console.log("📐 Processing Image Zoom:", imgConfig.zoom);

            // ১. স্কেল বের করা
            const scaleX = imgConfig.width / mainImgObj.width;
            const scaleY = imgConfig.height / mainImgObj.height;
            
            // ২. বেসিক স্কেল (Cover Mode)
            let finalScale = Math.max(scaleX, scaleY);

            // ৩. ম্যানুয়াল জুম অ্যাপ্লাই করা
            const customZoom = (imgConfig.zoom !== undefined) ? imgConfig.zoom : 1.0;
            finalScale = finalScale * customZoom;

            // ৪. ইমেজে সেট করা
            mainImgObj.set({
                scaleX: finalScale,
                scaleY: finalScale,
                left: imgConfig.left + (imgConfig.width / 2), 
                top: imgConfig.top + (imgConfig.height / 2),
                originX: 'center',
                originY: 'center',
                clipPath: null 
            });
            mainImgObj.setCoords();
        }

        // ৮. টাইটেল পজিশনিং
        if(titleObj) {
            if (isRestore && window.userSettings?.titlePos) {
                titleObj.set(window.userSettings.titlePos);
            } else {
                const config = targetLayout.title;
                titleObj.set({
                    top: config.top, left: config.left, width: config.width,
                    textAlign: config.textAlign, originX: config.originX,
                    fontSize: config.fontSize, backgroundColor: config.backgroundColor,
                    fill: config.fill, fontFamily: config.fontFamily
                });
                
                if(!config.fontFamily.includes('📂')) {
                    let cleanFont = config.fontFamily.replace(/'/g, "").split(',')[0].trim();
                    WebFont.load({ google: { families: [cleanFont] } });
                }

                updateUI(config.fontSize, config.fill, config.fontFamily);
                
                userSettings.color = config.fill;
                userSettings.font = config.fontFamily;
                userSettings.size = config.fontSize;
                userSettings.bg = config.backgroundColor;
            }
            titleObj.setCoords(); 
        }

        // ৯. ডেট পজিশনিং
        if(dateObj) {
            if (isRestore && window.userSettings?.datePos) {
                dateObj.set(window.userSettings.datePos);
            } else {
                const dConfig = targetLayout.date;
                dateObj.set({
                    top: dConfig.top, left: dConfig.left, originX: dConfig.originX,
                    fontSize: dConfig.fontSize, fill: dConfig.fill, backgroundColor: dConfig.backgroundColor
                });
            }
            dateObj.setCoords();
        }

        canvas.requestRenderAll();
        saveHistory();

    }, { crossOrigin: 'anonymous' });
};

    function updateUI(size, color, font) {
        if(document.getElementById('val-size')) document.getElementById('val-size').innerText = size;
        if(document.getElementById('text-size')) document.getElementById('text-size').value = size;
        if(document.getElementById('text-color')) document.getElementById('text-color').value = color;
        if(document.getElementById('font-family')) document.getElementById('font-family').value = font;
    }

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
                let fontName = userSettings.font;
                if(!fontName.includes('📂')) {
                     fontName = fontName.replace(/'/g, "").split(',')[0].trim();
                     WebFont.load({ google: { families: [fontName] } });
                }
                titleObj.set({ fill: userSettings.color, fontSize: parseInt(userSettings.size), backgroundColor: userSettings.bg, fontFamily: fontName });
                updateUI(userSettings.size, userSettings.color, userSettings.font);
                canvas.requestRenderAll();
            }
        }, 600);
        if (userSettings.logo) addProfileLogo(userSettings.logo);
        addDateText();
    }

    function saveCurrentDesign() {
        const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
        const dateObj = canvas.getObjects().find(obj => obj.isDate);
        let tPos = null, dPos = null;
        if (titleObj) tPos = { left: titleObj.left, top: titleObj.top, width: titleObj.width, textAlign: titleObj.textAlign, originX: titleObj.originX, fill: titleObj.fill, fontFamily: titleObj.fontFamily };
        if (dateObj) dPos = { left: dateObj.left, top: dateObj.top, originX: dateObj.originX };

        const preferences = {
            template : userSettings.template, frameUrl : userSettings.frameUrl,
            font : titleObj ? titleObj.fontFamily : userSettings.font,
            color : titleObj ? titleObj.fill : userSettings.color,
            bg : titleObj ? titleObj.backgroundColor : userSettings.bg,
            size : titleObj ? titleObj.fontSize : userSettings.size,
            titlePos : tPos, datePos : dPos, layout : currentLayout || userSettings.layout
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
        img.set({ 
            scaleX: scale, scaleY: scale, 
            left: canvas.width / 2, top: canvas.height / 2, // Center
            originX: 'center', originY: 'center', // Center Origin for Zoom
            selectable: true, isMainImage: true 
        });
        mainImageObj = img; canvas.add(img); canvas.sendToBack(img);
    }

    window.controlMainImage = function(action, value) {
        let img = canvas.getObjects().find(o => o.isMainImage);
        if (!img) { alert("❌ কোনো নিউজ ইমেজ পাওয়া যায়নি!"); return; }
        switch (action) {
            case 'zoom':
                let newScale = img.scaleX + value;
                if (newScale > 0.1) img.set({ scaleX: newScale, scaleY: newScale });
                break;
            case 'moveX': img.set('left', img.left + value); break;
            case 'moveY': img.set('top', img.top + value); break;
            case 'reset':
                const scale = Math.max(canvas.width / img.width, canvas.height / img.height);
                img.set({ scaleX: scale, scaleY: scale, left: canvas.width / 2, top: canvas.height / 2, originX: 'center', originY: 'center' });
                break;
        }
        img.setCoords(); canvas.requestRenderAll(); saveHistory();
    };
	
	
	// ==========================================
    // 📑 MULTI-LAYER CONTROL SYSTEM
    // ==========================================

    // ১. লেয়ার লিস্ট রেন্ডার করা
    window.renderLayerList = function() {
        const container = document.getElementById('layer-list-container');
        if (!container) return;

        container.innerHTML = ''; // ক্লিয়ার
        
        // ক্যানভাসের সব অবজেক্ট নেওয়া (Reverse যাতে উপরের লেয়ার উপরে দেখায়)
        const objects = canvas.getObjects().slice().reverse();

        if (objects.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-400 text-center">কোনো লেয়ার নেই</p>';
            return;
        }

        objects.forEach((obj, index) => {
            // আসল ইনডেক্স (Fabric এ নিচ থেকে গণনা হয়)
            const realIndex = objects.length - 1 - index;

            // নাম ঠিক করা
            let name = "Shape / Rect";
            let icon = "🟦";
            
            if (obj.isMainImage) { name = "News Image"; icon = "🖼️"; }
            else if (obj.isFrame) { name = "Frame / Overlay"; icon = "🔲"; }
            else if (obj.isHeadline) { name = "Headline Text"; icon = "📝"; }
            else if (obj.isDate) { name = "Date Text"; icon = "📅"; }
            else if (obj.type === 'image') { name = "Logo / Image"; icon = "📷"; }
            else if (obj.type === 'text' || obj.type === 'textbox') { name = "Custom Text"; icon = "✍️"; }

            // অ্যাক্টিভ ক্লাস
            const isActive = canvas.getActiveObject() === obj ? "border-indigo-500 bg-indigo-50" : "border-gray-200 bg-white";

            const itemHtml = `
                <div class="flex items-center justify-between p-2 border rounded-lg ${isActive} hover:bg-gray-50 transition group cursor-pointer" onclick="selectLayer(${realIndex})">
                    <div class="flex items-center gap-2 truncate">
                        <span class="text-lg">${icon}</span>
                        <span class="text-xs font-bold text-gray-700 truncate w-32">${name}</span>
                    </div>
                    <div class="flex gap-1 opacity-60 group-hover:opacity-100">
                        <button onclick="toggleVisibility(event, ${realIndex})" class="p-1 hover:text-blue-600" title="Hide/Show">
                            ${obj.visible ? '👁️' : '🚫'}
                        </button>
                        <button onclick="toggleLock(event, ${realIndex})" class="p-1 hover:text-red-600" title="Lock/Unlock">
                            ${obj.lockMovementX ? '🔒' : '🔓'}
                        </button>
                        <button onclick="deleteLayer(event, ${realIndex})" class="p-1 hover:text-red-600" title="Delete">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
            container.innerHTML += itemHtml;
        });
    };

    // ২. লেয়ার সিলেক্ট করা
    window.selectLayer = function(index) {
        const obj = canvas.item(index);
        if (obj) {
            canvas.setActiveObject(obj);
            canvas.renderAll();
            renderLayerList(); // রি-রেন্ডার যাতে কালার চেঞ্জ হয়
        }
    };

    // ৩. হাইড / শো
    window.toggleVisibility = function(e, index) {
        e.stopPropagation(); // প্যারেন্ট ডিভ ক্লিক বন্ধ করতে
        const obj = canvas.item(index);
        if (obj) {
            obj.visible = !obj.visible;
            if (!obj.visible) canvas.discardActiveObject(); // হাইড করলে সিলেকশন বাদ
            canvas.renderAll();
            renderLayerList();
        }
    };

    // ৪. লক / আনলক
    window.toggleLock = function(e, index) {
        e.stopPropagation();
        const obj = canvas.item(index);
        if (obj) {
            const isLocked = !obj.lockMovementX;
            obj.set({
                lockMovementX: isLocked,
                lockMovementY: isLocked,
                lockScalingX: isLocked,
                lockScalingY: isLocked,
                lockRotation: isLocked,
                selectable: !isLocked // লক থাকলে সিলেক্ট করা যাবে না
            });
            canvas.renderAll();
            renderLayerList();
        }
    };

    // ৫. ডিলিট
    window.deleteLayer = function(e, index) {
        e.stopPropagation();
        if(confirm('এই লেয়ারটি ডিলিট করতে চান?')) {
            const obj = canvas.item(index);
            canvas.remove(obj);
            saveHistory();
            renderLayerList();
        }
    };

    // ৬. পজিশন মুভমেন্ট হেল্পার
    window.moveLayer = function(direction) {
        const obj = canvas.getActiveObject();
        if(!obj) return;
        
        if(direction === 'up') canvas.bringForward(obj);
        if(direction === 'down') canvas.sendBackwards(obj);
        if(direction === 'top') canvas.bringToFront(obj);
        if(direction === 'bottom') canvas.sendToBack(obj);
        
        canvas.renderAll();
        saveHistory();
        renderLayerList(); // অর্ডার চেঞ্জ হলে লিস্ট আপডেট
    };

    // 🔥 ইভেন্ট লিসেনারে অ্যাড করা (initCanvas এর ভেতরে)
    // ক্যানভাসে কিছু চেঞ্জ হলেই লেয়ার লিস্ট আপডেট হবে
    /* initCanvas ফাংশনের শেষে এই লাইনগুলো আছে কিনা চেক করুন, না থাকলে দিন:
       canvas.on('object:added', () => { saveHistory(); renderLayerList(); });
       canvas.on('object:removed', () => { saveHistory(); renderLayerList(); });
       canvas.on('object:modified', () => { saveHistory(); }); 
       canvas.on('selection:created', renderLayerList);
       canvas.on('selection:updated', renderLayerList);
    */
	
	
	
	

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
    //function loadFonts() { WebFont.load({ google: { families: ['Hind Siliguri:300,400,500,600,700', 'Noto Sans Bengali', 'Baloo Da 2', 'Galada', 'Anek Bangla', 'Tiro Bangla', 'Mina', 'Oswald', 'Roboto', 'Montserrat'] } }); }
    
	function loadFonts() {
        WebFont.load({
            google: { 
                families: [
                    'Hind Siliguri:300,400,500,600,700', 
                    'Noto Sans Bengali:400,700', 
                    'Baloo Da 2:400,500,600,700', 
                    'Galada', 
                    'Anek Bangla:400,600,800', 
                    'Tiro Bangla', 
                    'Mina', 
                    'Noto Serif Bengali:400,700', 
                    'Atma:300,400,500,600,700',
                    'Eczar:400,600,800',
                    'Kavivanar',
                    'Bonbon',
                    'Modak',
                    'Laila',
                    'Kurale',
                    'Podkova',
                    
                    // ইংরেজি ফন্ট
                    'Oswald:400,700', 
                    'Roboto:400,700', 
                    'Montserrat:400,700', 
                    'Lato:400,700', 
                    'Open Sans:400,700', 
                    'Poppins:400,600,700', 
                    'Raleway:400,700',
                    'Merriweather:400,700',
                    'Playfair Display:400,700'
                ] 
            }
        });
    }
	
	
	function switchTab(tabName) { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); event.target.classList.add('active'); ['design', 'text', 'image', 'layers'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden')); document.getElementById('tab-' + tabName).classList.remove('hidden'); }
    function updateActiveProp(prop, value) { const obj = canvas.getActiveObject(); if (obj) { obj.set(prop, value); if(prop === 'backgroundColor') document.getElementById('transparent-bg-check').checked = false; canvas.renderAll(); if(obj.isHeadline) { if(prop === 'fill') savePreference('color', value); if(prop === 'backgroundColor') savePreference('bg', value); if(prop === 'fontSize') savePreference('size', value); } saveHistory(); } if(prop==='fontSize') document.getElementById('val-size').innerText = value; }
    
    // Change Font (Dynamic)
    function changeFont(fontName) {
        const obj = canvas.getActiveObject();
        if (obj) {
            if(fontName.includes('📂')) {
                const actualName = fontName.replace('📂 ', '');
                obj.set("fontFamily", actualName);
                canvas.requestRenderAll();
                saveHistory();
                return;
            }

			
			const localFonts = [
                'Noto Serif Cond Thin',
                'Noto Serif Cond Light',
                'Noto Serif Cond Regular',
                'Noto Serif Cond Medium',
                'Noto Serif Cond SemiBold',
                'Noto Serif Cond Bold',
                'Noto Serif Cond ExtraBold',
                'Noto Serif Cond Black',
                'SolaimanLipi', // আগের যদি থাকে
                'Shamim'        // আগের যদি থাকে
            ];
			
            
            const cleanFont = fontName.replace(/'/g, "").split(',')[0].trim();

            if (localFonts.includes(cleanFont)) {
                obj.set("fontFamily", cleanFont);
                canvas.requestRenderAll();
                saveHistory();
                if(obj.isHeadline) savePreference('font', fontName);
                return;
            }

            WebFont.load({ 
                google: { families: [cleanFont + ':400,700'] }, 
                active: function() { 
                    obj.set("fontFamily", cleanFont); 
                    canvas.requestRenderAll(); 
                    if(obj.isHeadline) savePreference('font', fontName); 
                    saveHistory(); 
                } 
            });
        }
    }
	
	
	
	// ==========================================
    // 🔥 STUDIO DIRECT POST (EXACT DOWNLOAD QUALITY)
    // ==========================================

    // Helper: DataURL to Blob
    function dataURLToBlob(dataURL) {
        var arr = dataURL.split(','), mime = arr[0].match(/:(.*?);/)[1];
        var bstr = atob(arr[1]);
        var n = bstr.length;
        var u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], {type:mime});
    }

    
	
	function postDirectFromStudio() {
        // ১. চেকবক্সের ভ্যালু চেক করা
        const isSocialOnly = document.getElementById('socialOnlyCheck').checked;
        
        let confirmMsg = "আপনি কি এই ডিজাইনটি সরাসরি পোস্ট করতে চান?";
        if (isSocialOnly) {
            confirmMsg = "⚠️ আপনি 'Only Social' সিলেক্ট করেছেন। \nনিউজটি ওয়েবসাইটে যাবে না, শুধু ফেসবুক/টেলিগ্রামে পোস্ট হবে। \n\nআপনি কি নিশ্চিত?";
        }

        if (!confirm(confirmMsg)) return;

        const btn = document.querySelector('button[onclick="postDirectFromStudio()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = "⏳ Uploading...";
        btn.disabled = true;

        canvas.discardActiveObject(); 
        canvas.renderAll();

        try {
            const dataURL = canvas.toDataURL({ format: 'png', multiplier: 1.5, quality: 1.0 });
            const blob = dataURLToBlob(dataURL);

            const formData = new FormData();
            formData.append('design_image', blob, 'studio-final.png');
            
            // 🔥🔥 NEW: চেকবক্সের ভ্যালু পাঠানো
            if (isSocialOnly) {
                formData.append('social_only', '1');
            }
            
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('news.publish-studio', $newsItem->id) }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": token },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("✅ ডিজাইন পোস্ট প্রসেসিংয়ে পাঠানো হয়েছে!");
                    window.location.href = "{{ route('news.index') }}"; 
                } else {
                    alert("❌ এরর: " + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert("❌ নেটওয়ার্ক এরর!");
                btn.innerHTML = originalText;
                btn.disabled = false;
            });

        } catch (error) {
            console.error(error);
            alert("❌ ক্যানভাস এরর।");
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
	
	
	
	
	
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
    function activateDebugTools() { const debugBox = document.createElement('div'); debugBox.id = 'pos-finder'; debugBox.style.cssText = "position:fixed; bottom:20px; left:20px; background:rgba(0,0,0,0.8); color:#00ff00; padding:15px; z-index:9999; font-family:monospace; font-size:14px; border-radius:8px; pointer-events:none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"; debugBox.innerHTML = "Select text to see pos"; document.body.appendChild(debugBox); function updatePositionDisplay() { const obj = canvas.getActiveObject(); if (!obj) { debugBox.innerHTML = "Select object"; return; } debugBox.innerHTML = `Top: ${Math.round(obj.top)}<br>Left: ${Math.round(obj.left)}<br>OriginX: ${obj.originX}`; } canvas.on('object:moving', updatePositionDisplay); canvas.on('selection:created', updatePositionDisplay); }

    window.onload = initCanvas;
</script>