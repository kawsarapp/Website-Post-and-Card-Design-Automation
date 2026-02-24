// ==========================================
// 🎨 টেমপ্লেট কনফিগারেশন (নতুন টেমপ্লেট এখানে অ্যাড করুন)
// ==========================================
const T_DEFAULTS = { fontFamily: "'Hind Siliguri', 'sans-serif', 'SolaimanLipi', 'Noto Serif Cond SemiBold'", fill: '#000000', backgroundColor: '', fontSize: 60 };

const STUDIO_TEMPLATES = {
    'bottom': { 
        title: { ...T_DEFAULTS, top: 800, left: 540, width: 980, textAlign: 'center', originX: 'center', fill: '#ffffff' }, 
        date: { ...T_DEFAULTS, top: 50, left: 50, originX: 'left' },
        image: { ...T_DEFAULTS, left: 0, top: 0, width: 1080, height: 1080, zoom: 1.0 }
    },
    'ntv': { 
        title: { ...T_DEFAULTS, top: 705, left: 555, originX: 'center', textAlign: 'center', width: 1000, fontSize: 50 }, 
        date:  { ...T_DEFAULTS, top: 633, left: 240, originX: 'right', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 17, top: 62, width: 1080, height: 520, zoom: 1.0 }
    },
    'rtv': { 
        title: { ...T_DEFAULTS, top: 603, left: 540, originX: 'center', textAlign: 'center', width: 950, fill: '#d90429', fontSize: 45 },
        date: { ...T_DEFAULTS, top: 43, left: 500, originX: 'left', fill: '#d90429', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 40, top: 115, width: 1000, height: 430, zoom: 0.9 }
    },
    'dhakapost': { 
        title: { ...T_DEFAULTS, top: 772, left: 545, originX: 'center', textAlign: 'center', width: 980, fill: '#ffffff' }, 
        date:  { ...T_DEFAULTS, top: 20, left: 975, originX: 'center', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 40, top: 130, width: 1000, height: 430, zoom: 1.3 }
    },
    'todayevents': { 
        title: { ...T_DEFAULTS, top: 760, left: 560, originX: 'center', originY: 'center', textAlign: 'center', width: 900, fontFamily: 'Noto Serif Cond Black' }, 
        date:  { ...T_DEFAULTS, top: 1015, left: 640, originX: 'right', fontSize: 26, backgroundColor: 'red', fontFamily: 'SolaimanLipi', padding: 6 },
        image: { ...T_DEFAULTS, left: 40, top: 120, width: 1000, height: 430, zoom: 1.2 }
    },
    'todayeventsSingle': { 
        title: { ...T_DEFAULTS, top: 700, left: 560, originX: 'center', originY: 'center', textAlign: 'center', width: 1080, fontFamily: 'SolaimanLipi' }, 
        date:  { ...T_DEFAULTS, top: 1045, left: 615, originX: 'right', fontSize: 26, backgroundColor: 'red', fontFamily: 'SolaimanLipi', padding: 6 },
        image: { ...T_DEFAULTS, left: 45, top: 100, width: 1000, height: 430, zoom: 1.0 }
    },
    'todayeventsSingle1': { 
        title: { ...T_DEFAULTS, top: 700, left: 560, originX: 'center', originY: 'center', textAlign: 'center', width: 1080, fontFamily: 'Noto Serif Cond Black' }, 
        date:  { ...T_DEFAULTS, top: 1045, left: 615, originX: 'right', fontSize: 26, backgroundColor: 'red', fontFamily: 'SolaimanLipi', padding: 6 },
        image: { ...T_DEFAULTS, left: 45, top: 100, width: 1000, height: 430, zoom: 1.0 }
    },
    'BanglaLiveNews': { 
        title: { ...T_DEFAULTS, top: 685, left: 540, width: 980, textAlign: 'center', originX: 'center', fill: '#ffffff', fontFamily: "'Hind Siliguri', sans-serif" },
        date:  { ...T_DEFAULTS, top: 43, left: 850, originX: 'left', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 50, top: 150, width: 980, height: 550, zoom: 1.0 }
    },
    'Jaijaidin1': { 
        title: { ...T_DEFAULTS, top: 750, left: 540, width: 950, textAlign: 'center', originX: 'center', fill: '#ffffff', fontSize: 55, fontFamily: "'Hind Siliguri', sans-serif" },
        date:  { ...T_DEFAULTS, top: 38, left: 1042, originX: 'right', fontSize: 28 },
        image: { ...T_DEFAULTS, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
    },
    'Jaijaidin2': { 
        title: { ...T_DEFAULTS, top: 720, left: 540, width: 950, textAlign: 'center', originX: 'center', fill: '#ffffff' },
        date:  { ...T_DEFAULTS, top: 640, left: 28, originX: 'left', fontSize: 32 },
        image: { ...T_DEFAULTS, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
    },
    'Jaijaidin3': { 
        title: { ...T_DEFAULTS, top: 750, left: 540, width: 900, textAlign: 'center', originX: 'center', fill: '#ffffff' },
        date:  { ...T_DEFAULTS, top: 40, left: 860, originX: 'left', fontSize: 32 },
        image: { ...T_DEFAULTS, left: 1, top: 200, width: 1080, height: 450, zoom: 1.0, originX: 'center' }
    },
    'Jaijaidin4': { 
        title: { ...T_DEFAULTS, top: 600, left: 540, width: 900, textAlign: 'center', originX: 'center' },
        date:  { ...T_DEFAULTS, top: 900, left: 540, originX: 'center' },
        image: { ...T_DEFAULTS, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
    },
    'ShotterKhoje': { 
        title: { ...T_DEFAULTS, top: 730, left: 540, width: 900, textAlign: 'center', originX: 'center', fill: '#ffffff' },
        date:  { ...T_DEFAULTS, top: 15, left: 460, originX: 'left', fill: '#ffffff', fontSize: 28 },
        image: { ...T_DEFAULTS, left: 40, top: 80, width: 980, height: 520, zoom: 1.2 }
    },
    'BanglaLiveNews1': { 
        title: { ...T_DEFAULTS, top: 712, left: 545, width: 1050, textAlign: 'center', originX: 'center', fill: '#ffffff' },
        date:  { ...T_DEFAULTS, top: 635, left: 130, originX: 'center', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 40, top: 160, width: 1000, height: 450, zoom: 1.1 }
    },
    'jonomot': { 
        title: { ...T_DEFAULTS, top: 770, left: 545, width: 1050, textAlign: 'center', originX: 'center', fill: '#ffffff' },
        date:  { ...T_DEFAULTS, top: 45, left: 120, originX: 'center', fontSize: 30 },
        image: { ...T_DEFAULTS, left: 1, top: 160, width: 1080, height: 540, zoom: 1.0 }
    },
    'Bangladeshmail24': { 
        title: { ...T_DEFAULTS, top: 650, left: 545, originX: 'center', originY: 'center', textAlign: 'center', width: 1050, fontFamily: 'Noto Serif Cond Black' }, 
        date:  { ...T_DEFAULTS, top: 520, left: 120, originX: 'center', fontSize: 30, fontFamily: "'Noto Serif Cond Black'" },
        image: { ...T_DEFAULTS, left: 1, top: 20, width: 1080, height: 530, zoom: 1.0 }
    },
    'WatchBangladesh': { 
        title: { ...T_DEFAULTS, top: 650, left: 555, originX: 'center', originY: 'center', textAlign: 'center', width: 1050, fontFamily: "'Noto Serif Cond Black'" }, 
        date:  { ...T_DEFAULTS, top: 524, left: 649, originX: 'right', fill: '#fff', fontSize: 30, fontFamily: "'Noto Serif Cond Black'", padding: 6 },
        image: { ...T_DEFAULTS, left: 1, top: 20, width: 1080, height: 530, zoom: 1.0 }
    },
    'TodayEventsDualFrame': { 
        title: { ...T_DEFAULTS, top: 650, left: 545, originX: 'center', originY: 'center', textAlign: 'center', width: 1050, fontFamily: "'Noto Serif Cond Black'" }, 
        date:  { ...T_DEFAULTS, top: 1020, left: 600, originX: 'center', fontSize: 30, fontFamily: "'Noto Serif Cond Black'" },
        image: { ...T_DEFAULTS, left: 1, top: 20, width: 1080, height: 530, zoom: 1.0 }
    },
    'Thenews24Main': { 
        title: { ...T_DEFAULTS, top: 720, left: 540, originX: 'center', originY: 'center', textAlign: 'center', width: 1000, fill: '#fff', fontFamily: 'Noto Serif Bengali SemiBold' }, 
        date:  { ...T_DEFAULTS, top: 50, left: 1045, originX: 'right', fontSize: 28, fontFamily: 'NotoSerifBengali-Regular', padding: 6 },
        image: { ...T_DEFAULTS, left: 45, top: 100, width: 1000, height: 430, zoom: 1.1 }
    },
    'Thenews24UniversalAds': { 
        title: { ...T_DEFAULTS, top: 720, left: 540, originX: 'center', originY: 'center', textAlign: 'center', width: 1000, fill: '#fff', fontFamily: 'Noto Serif Bengali SemiBold' }, 
        date:  { ...T_DEFAULTS, top: 50, left: 1045, originX: 'right', fontSize: 28, fontFamily: 'NotoSerifBengali-Regular', padding: 6 },
        image: { ...T_DEFAULTS, left: 45, top: 100, width: 1000, height: 430, zoom: 1.1 }
    }
};