<style>
    /* =========================================
       1. FONT OPTIMIZATION & FULL LIST
       ========================================= */
    
    /* Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;700&display=swap');

    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    /* SolaimanLipi */
    @font-face { font-family: 'SolaimanLipi'; src: url('/fonts/SolaimanLipi.ttf') format('truetype'); font-weight: normal; font-display: swap; }

    /* Noto Serif Condensed Family */
    @font-face { font-family: 'Noto Serif Cond Thin'; src: url('/fonts/NotoSerifBengali_Condensed-Thin.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond ExtraLight'; src: url('/fonts/NotoSerifBengali_Condensed-ExtraLight.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond Light'; src: url('/fonts/NotoSerifBengali_Condensed-Light.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond Regular'; src: url('/fonts/NotoSerifBengali_Condensed-Regular.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond Medium'; src: url('/fonts/NotoSerifBengali_Condensed-Medium.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond SemiBold'; src: url('/fonts/NotoSerifBengali_Condensed-SemiBold.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond Bold'; src: url('/fonts/NotoSerifBengali_Condensed-Bold.ttf') format('truetype'); font-weight: bold; font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond ExtraBold'; src: url('/fonts/A NotoSerifBengali_Condensed-ExtraBold.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Cond Black'; src: url('/fonts/NotoSerifBengali_Condensed-Black.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'Noto Serif Bengali SemiBold'; src: url('/fonts/NotoSerifBengali-SemiBold.ttf') format('truetype'); font-display: swap; }
    @font-face { font-family: 'NotoSerifBengali-Regular'; src: url('/fonts/NotoSerifBengali-Regular.ttf') format('truetype'); font-display: swap; }



    /* Li Alinur Family */
    @font-face { font-family: 'Li Alinur Banglaborno'; src: url('/fonts/Li Alinur Banglaborno Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Alinur Banglaborno'; src: url('/fonts/Li Alinur Banglaborno Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Alinur Kuyasha'; src: url('/fonts/Li Alinur Kuyasha Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Alinur Kuyasha'; src: url('/fonts/Li Alinur Kuyasha Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Alinur Sangbadpatra'; src: url('/fonts/Li Alinur Sangbadpatra Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Alinur Sangbadpatra'; src: url('/fonts/Li Alinur Sangbadpatra Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Alinur Tumatul'; src: url('/fonts/wwwLi Alinur Tumatul Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Alinur Tumatul'; src: url('/fonts/wwwLi Alinur Tumatul Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    /* Other Li Fonts */
    @font-face { font-family: 'Li MA Hai'; src: url('/fonts/Li M. A. Hai Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li MA Hai'; src: url('/fonts/Li M. A. Hai Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Purno Pran'; src: url('/fonts/Li Purno Pran Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Purno Pran'; src: url('/fonts/Li Purno Pran Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Sabbir Sorolota'; src: url('/fonts/Li Sabbir Sorolota Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Sabbir Sorolota'; src: url('/fonts/Li Sabbir Sorolota Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Shohid Abu Sayed'; src: url('/fonts/Li Shohid Abu Sayed Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Shohid Abu Sayed'; src: url('/fonts/ALi Shohid Abu Sayed Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Abu JM Akkas'; src: url('/fonts/Li Abu J M Akkas Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Abu JM Akkas'; src: url('/fonts/Li Abu J M Akkas Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Mehdi Ekushey'; src: url('/fonts/Li Mehdi Ekushey Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Mehdi Ekushey'; src: url('/fonts/ALi Mehdi Ekushey Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    @font-face { font-family: 'Li Shadhinata'; src: url('/fonts/Li Shadhinata2 2.0 Unicode.ttf') format('truetype'); font-weight: normal; font-display: swap; }
    @font-face { font-family: 'Li Shadhinata'; src: url('/fonts/Li Shadhinata2 2.0 Unicode Italic.ttf') format('truetype'); font-style: italic; font-display: swap; }

    /* =========================================
       2. UI & PERFORMANCE STYLES
       ========================================= */
    
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    .tab-btn.active { border-bottom: 2px solid #4f46e5; color: #4f46e5; }
    
    /* Hardware Acceleration for Canvas */
    #canvas-wrapper { 
        will-change: transform; 
        transform: translateZ(0); 
    }
    
    #tab-design, #tab-text, #tab-image, #tab-layers { contain: content; }

    input[type=range] { height: 26px; -webkit-appearance: none; width: 100%; background: transparent; }
    input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 6px; cursor: pointer; background: #e2e8f0; border-radius: 3px; }
    input[type=range]::-webkit-slider-thumb { height: 16px; width: 16px; border-radius: 50%; background: #4f46e5; cursor: pointer; -webkit-appearance: none; margin-top: -5px; }
    
    .label-title { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 0.05em; }
    .layer-btn { background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; font-size: 11px; transition: all 0.2s; }
    .layer-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
</style>