// <script>
    console.log("✅ Init Script Loaded");

    // 1. Global Variables Setup (Attach to window)
    window.canvas = null;
    
    // PHP থেকে ডাটা নিয়ে গ্লোবাল ভেরিয়েবলে রাখা
    window.dbPrefs = {!! json_encode($settings->design_preferences ?? null) !!};

    // লোকাল স্টোরেজ চেক
    let savedPrefs = {};
    try {
        const localRaw = localStorage.getItem('studio_prefs');
        savedPrefs = localRaw ? JSON.parse(localRaw) : {};
    } catch (e) { console.error(e); }

    // 2. User Settings & News Data Setup
    window.userSettings = {
        logo: {!! json_encode($settings->logo_url ?? null) !!},
        brand: {!! json_encode($settings->brand_name ?? 'News') !!},
        
        // Priority: LocalStorage > DB > Default
        template: savedPrefs.template || window.dbPrefs?.template || "{!! $settings->default_template ?? 'classic' !!}",
        font: savedPrefs.font || window.dbPrefs?.font || "'Hind Siliguri', sans-serif",
        color: savedPrefs.color || window.dbPrefs?.color || '#ffffff',
        bg: savedPrefs.bg || window.dbPrefs?.bg || 'rgba(0,0,0,0.6)',
        size: parseInt(savedPrefs.size || window.dbPrefs?.size || 60),
        frameUrl: savedPrefs.frameUrl || window.dbPrefs?.frameUrl || null,

        // 🔥 নতুন: পজিশন লোড করা হচ্ছে (Title Position)
        titlePos: savedPrefs.titlePos || window.dbPrefs?.titlePos || null
    };

    window.newsData = {
        title: {!! json_encode($newsItem->title) !!},
        image: "{{ $newsItem->thumbnail_url ? route('proxy.image', ['url' => $newsItem->thumbnail_url]) : '' }}"
    };

    // 3. Initialization Function
    window.initCanvas = function() {
        // ক্যানভাস ইনস্ট্যান্স তৈরি
        window.canvas = new fabric.Canvas('newsCanvas', {
            backgroundColor: '#fff',
            preserveObjectStacking: true,
            selection: true
        });

        // ফন্ট লোড করা (text-tools.blade.php থেকে আসবে)
        if (typeof window.loadFonts === 'function') window.loadFonts();

        // ইমেজ লোডিং হ্যান্ডলিং
        if (window.newsData.image) {
            const imgObj = new Image();
            imgObj.crossOrigin = "anonymous";
            imgObj.src = window.newsData.image;

            imgObj.onload = function() {
                fabric.Image.fromURL(window.newsData.image, function(img) {
                    if (typeof window.setupMainImage === 'function') {
                        window.setupMainImage(img);
                    }
                    loadSavedDesign(); // utils বা এখানেই থাকবে
                    if (typeof window.saveHistory === 'function') window.saveHistory();
                }, { crossOrigin: 'anonymous' });
            };

            imgObj.onerror = function() {
                console.warn("⚠️ Image Load Failed. Loading Template Only.");
                loadSavedDesign();
                if (typeof window.saveHistory === 'function') window.saveHistory();
            };
        } else {
            loadSavedDesign();
            if (typeof window.saveHistory === 'function') window.saveHistory();
        }

        // ইভেন্ট লিসেনার অ্যাড করা
        canvas.on('selection:created', () => { if(typeof window.updateSidebarValues === 'function') window.updateSidebarValues(); });
        canvas.on('selection:updated', () => { if(typeof window.updateSidebarValues === 'function') window.updateSidebarValues(); });
        
        canvas.on('object:added', () => { if(typeof window.saveHistory === 'function') window.saveHistory(); });
        canvas.on('object:modified', () => { if(typeof window.saveHistory === 'function') window.saveHistory(); });
        canvas.on('object:removed', () => { if(typeof window.saveHistory === 'function') window.saveHistory(); });

        if (typeof window.initKeyboardEvents === 'function') window.initKeyboardEvents();
    };

    // Internal Helper for Init
    function loadSavedDesign() {
        // ১. টেমপ্লেট অ্যাপ্লাই
        if (typeof window.applyTemplate === 'function') {
            window.applyTemplate(window.userSettings.template);
        }

        // ২. ফ্রেম লোড
        if (window.userSettings.frameUrl) {
            fabric.Image.fromURL(window.userSettings.frameUrl, function(img) {
                if (typeof window.setupFrameObj === 'function') window.setupFrameObj(img);
            }, { crossOrigin: 'anonymous' });
        }

        // ৩. লোগো লোড
        if (window.userSettings.logo && typeof window.addProfileLogo === 'function') {
            window.addProfileLogo(window.userSettings.logo);
        }

        // ৪. ডেট এবং স্টাইল
        if (typeof window.addDateText === 'function') window.addDateText();
        if (typeof window.applyLastStyles === 'function') window.applyLastStyles();
        
        // UI আপডেট
        const select = document.getElementById('templateSelect');
        if(select) select.value = window.userSettings.template;
    }

    // Window Load Event
    window.onload = window.initCanvas;
</script>