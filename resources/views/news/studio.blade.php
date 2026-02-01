@extends('layouts.app')

@section('content')
<style>
    /* =========================================
       1. FONT OPTIMIZATION & FULL LIST
       ========================================= */
    
    /* Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;700&display=swap');

    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    /* Font Loading Performance: font-display: swap ব্যবহার করা হয়েছে যাতে ফন্ট লোড হতে দেরি হলেও টেক্সট দেখা যায় */
    
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

<div class="fixed inset-0 bg-gray-100 z-50 flex flex-col font-bangla h-dvh">
    
    {{-- Header Section --}}
    <div class="bg-white border-b border-gray-200 px-4 py-2 md:px-6 md:py-3 flex flex-col md:flex-row justify-between items-center shadow-sm z-30 shrink-0 gap-3 md:gap-0">
        <div class="flex items-center justify-between w-full md:w-auto gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('news.index') }}" class="flex items-center gap-1 text-gray-500 hover:text-gray-800 transition font-bold text-sm bg-gray-100 px-2 py-1 rounded-lg">←</a>
                <h1 class="text-lg md:text-xl font-bold text-gray-800 flex items-center gap-2 truncate">
                    🎨 স্টুডিও <span class="hidden sm:inline">প্রো</span> 
                    <span class="text-[10px] md:text-sm bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-normal">SaaS</span>
                </h1>
            </div>
        </div>
       
       <div class="flex flex-wrap justify-center md:justify-end gap-2 items-center w-full md:w-auto">
            <button type="button" onclick="restoreSavedDesign()" class="btn btn-warning text-white px-2 py-1.5 rounded-lg text-xs" title="Restore"><i class="fas fa-undo"></i></button>
            <button onclick="resetCanvas()" class="text-gray-500 hover:text-red-500 font-bold text-xs px-2 py-1.5 border border-gray-300 rounded-lg transition" title="Reset">↻</button>
            <button onclick="saveCurrentDesign()" class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg font-bold text-xs hover:bg-indigo-100 transition border border-indigo-200 shadow-sm">💾 Save</button>
            <button id="downloadBtn" onclick="downloadCard()" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-3 py-1.5 rounded-lg font-bold text-xs shadow-md transition transform hover:-translate-y-0.5">📥 Down</button>
            
            {{-- 🔥 POST BUTTON calls the optimized modal opener --}}
            <button onclick="openPublishModal()" class="bg-red-600 text-white px-3 py-1.5 rounded-lg font-bold text-xs hover:bg-red-700 transition shadow-md border border-red-500">
                🚀 Post
            </button>
        </div>
    </div>

    {{-- Main Workspace --}}
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        {{-- Canvas Area --}}
        <div id="workspace-container" class="order-1 md:order-2 w-full md:flex-1 h-[45vh] md:h-auto bg-gray-200 flex items-center justify-center overflow-hidden relative p-2 md:p-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] border-b md:border-b-0 md:border-l border-gray-300">
            <div class="absolute bottom-2 right-2 md:bottom-6 md:right-6 flex bg-white/90 shadow-lg rounded-full px-3 py-1 gap-3 z-40 border border-gray-200 scale-90 md:scale-100 origin-bottom-right">
               <button onclick="changeZoom(-0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-lg focus:outline-none">➖</button>
               <span class="text-xs font-bold text-gray-400 pt-1 select-none" id="zoom-level">FIT</span>
               <button onclick="changeZoom(0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-lg focus:outline-none">➕</button>
               <button onclick="fitToScreen()" class="text-[10px] font-bold text-blue-500 hover:underline border-l pl-2 ml-1">⟲ Fit</button>
           </div>
           
           {{-- 🔥 Preloader for Canvas --}}
           <div id="canvas-loader" class="absolute inset-0 flex items-center justify-center bg-gray-200 z-30 hidden">
                <span class="text-indigo-600 font-bold animate-pulse">🖼️ লোড হচ্ছে...</span>
           </div>

           <div id="canvas-wrapper" class="shadow-2xl transition-transform duration-200 ease-out origin-center ring-4 md:ring-8 ring-white">
               <canvas id="newsCanvas" width="1080" height="1080" style="max-width: 100%; max-height: 100%;"></canvas>
           </div>
        </div>

        {{-- Sidebar --}}
        <div class="order-2 md:order-1 w-full md:w-[400px] bg-white border-r border-gray-200 flex flex-col h-auto md:h-full z-20 shadow-xl font-bangla flex-1 md:flex-none overflow-hidden">
            <div class="flex border-b border-gray-200 bg-gray-50 shrink-0">
                <button onclick="switchTab('design')" class="tab-btn active flex-1 py-3 text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">ডিজাইন</button>
                <button onclick="switchTab('text')" class="tab-btn flex-1 py-3 text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">টেক্সট</button>
                <button onclick="switchTab('image')" class="tab-btn flex-1 py-3 text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">ইমেজ</button>
                <button onclick="switchTab('layers')" class="tab-btn flex-1 py-3 text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">লেয়ার</button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 pb-20 md:pb-5 bg-white">
                {{-- Design Tab --}}
                <div id="tab-design" class="space-y-6">
                    <div>
                        <label class="label-title">🎨 প্রিসেট ডিজাইন</label>
                        <div class="grid grid-cols-3 md:grid-cols-2 gap-3 max-h-[400px] overflow-y-auto custom-scrollbar p-1">
                            @foreach($availableTemplates as $template)
                                <div onclick="applyAdminTemplate('{{ asset($template['image']) }}', '{{ $template['layout'] }}')" class="cursor-pointer border border-gray-200 rounded-lg hover:border-indigo-500 transition p-1 bg-white group hover:shadow-md">
                                    {{-- 🔥 loading="lazy" for speed --}}
                                    <img src="{{ asset($template['image']) }}" alt="{{ $template['name'] }}" loading="lazy" class="w-full h-16 md:h-20 object-contain bg-gray-50 mb-1 rounded">
                                    <p class="text-[10px] text-center font-bold text-gray-600 group-hover:text-indigo-600 truncate">{{ $template['name'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <label class="label-title">🖼️ কাস্টমাইজ</label>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <label class="cursor-pointer bg-indigo-50 border border-dashed border-indigo-300 text-indigo-600 p-3 rounded-lg text-center font-bold text-xs hover:bg-indigo-100 transition flex flex-col items-center justify-center gap-1 h-20 md:h-24">
                                <input type="file" class="hidden" accept="image/*" onchange="setBackgroundImage(this)">
                                <span>🌄 ব্যাকগ্রাউন্ড</span>
                            </label>
                            <label class="cursor-pointer bg-purple-50 border border-dashed border-purple-300 text-purple-600 p-3 rounded-lg text-center font-bold text-xs hover:bg-purple-100 transition flex flex-col items-center justify-center gap-1 h-20 md:h-24">
                                <input type="file" class="hidden" accept="image/png" onchange="addCustomFrame(this)">
                                <span>🖼️ ফ্রেম (PNG)</span>
                            </label>
                        </div>
                        <div class="flex items-center justify-between bg-gray-50 p-2 rounded border mt-2">
                            <span class="text-xs text-gray-500">কালার:</span>
                            <div class="flex items-center gap-2">
                                <input type="color" class="w-8 h-8 rounded cursor-pointer border-0" oninput="setBackgroundColor(this.value)">
                                <button onclick="removeFrame()" class="text-[10px] text-red-500 hover:underline">Remove Frame</button>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <label class="flex-1 cursor-pointer bg-white border border-gray-200 text-gray-700 px-2 py-2 rounded text-xs font-bold hover:bg-gray-50 text-center">
                                <input type="file" accept="image/*" onchange="uploadLogo(this)" class="hidden">
                                📤 লোগো অ্যাড করুন
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Text Tab --}}
                <div id="tab-text" class="space-y-6 hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="addText('Headline')" class="bg-gray-800 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-black shadow-sm transition">+ Title</button>
                        <button onclick="addText('Subtitle', 30)" class="bg-white border border-gray-300 text-gray-700 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 shadow-sm transition">+ Subtitle</button>
                    </div>
                    
                    <div class="border-t pt-4 mt-2">
                        <label class="label-title">🅰️ ফন্ট স্টাইল</label>
                        <select id="font-family" onchange="changeFont(this.value)" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bangla focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="" disabled selected>-- ফন্ট সিলেক্ট করুন --</option>
                            
                            <optgroup label="🔥 Li Series (Stylish)">
                                <option value="'Li Alinur Banglaborno'">Li Alinur Banglaborno</option>
                                <option value="'Li Alinur Kuyasha'">Li Alinur Kuyasha</option>
                                <option value="'Li Alinur Sangbadpatra'">Li Alinur Sangbadpatra</option>
                                <option value="'Li Alinur Tumatul'">Li Alinur Tumatul</option>
                                <option value="'Li MA Hai'">Li M.A. Hai</option>
                                <option value="'Li Purno Pran'">Li Purno Pran</option>
                                <option value="'Li Sabbir Sorolota'">Li Sabbir Sorolota</option>
                                <option value="'Li Shohid Abu Sayed'">Li Shohid Abu Sayed</option>
                                <option value="'Li Abu JM Akkas'">Li Abu J.M. Akkas</option>
                                <option value="'Li Mehdi Ekushey'">Li Mehdi Ekushey</option>
                                <option value="'Li Shadhinata'">Li Shadhinata</option>
                            </optgroup>

                            <optgroup label="📂 Noto Serif Condensed">
                                <option value="'Noto Serif Cond Thin'">Noto Serif (Thin)</option>
                                <option value="'Noto Serif Cond ExtraLight'">Noto Serif (ExtraLight)</option>
                                <option value="'Noto Serif Cond Light'">Noto Serif (Light)</option>
                                <option value="'Noto Serif Cond Regular'">Noto Serif (Regular)</option>
                                <option value="'Noto Serif Cond Medium'">Noto Serif (Medium)</option>
                                <option value="'Noto Serif Cond SemiBold'">Noto Serif (SemiBold)</option>
                                <option value="'Noto Serif Cond Bold'">Noto Serif (Bold)</option>
                                <option value="'Noto Serif Cond ExtraBold'">Noto Serif (ExtraBold)</option>
                                <option value="'Noto Serif Cond Black'">Noto Serif (Black)</option>
                            </optgroup>

                            <optgroup label="📰 Popular Bangla">
                                <option value="'SolaimanLipi'">SolaimanLipi</option>
                                <option value="'Hind Siliguri', sans-serif">হিন্দ শিলিগুড়ি</option>
                                <option value="'Noto Sans Bengali', sans-serif">নোটো স্যান্স</option>
                                <option value="'Baloo Da 2', cursive">বালু দা ২</option>
                                <option value="'Galada', cursive">গলাদা</option>
                                <option value="'Anek Bangla', sans-serif">অনেক বাংলা</option>
                            </optgroup>
                        </select>
                        
                        <label class="label-title mt-4">📝 এডিট টেক্সট</label>
                        <textarea id="text-content" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-3 font-bangla focus:ring-2 focus:ring-indigo-500 outline-none" rows="3" oninput="updateActiveProp('text', this.value)"></textarea>
                        
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Color (Selected)</label>
                                {{-- 🔥 FIXED: সিলেক্টিভ টেক্সট কালার --}}
                                <input type="color" id="text-color" class="w-full h-9 rounded cursor-pointer border border-gray-200 p-0.5" oninput="applyTextColor(this.value)">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Background</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="text-bg" class="w-9 h-9 rounded cursor-pointer border border-gray-200 p-0.5" oninput="updateActiveProp('backgroundColor', this.value)">
                                    <label class="text-[10px] border px-1.5 py-1 rounded cursor-pointer"><input type="checkbox" id="transparent-bg-check" onchange="toggleTransparentBg(this.checked)"> No BG</label>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div><label class="text-xs text-gray-500 flex justify-between">Size <span id="val-size" class="font-bold">60</span></label><input type="range" min="10" max="200" id="text-size" oninput="updateActiveProp('fontSize', parseInt(this.value))"></div>
                        </div>

                        <div class="flex gap-1 mt-4 bg-gray-100 p-1 rounded-lg border border-gray-200">
                             <button onclick="toggleStyle('bold')" class="flex-1 py-1.5 rounded hover:bg-white font-bold text-gray-700 transition">B</button>
                             <button onclick="toggleStyle('italic')" class="flex-1 py-1.5 rounded hover:bg-white italic text-gray-700 transition">I</button>
                             <button onclick="toggleStyle('underline')" class="flex-1 py-1.5 rounded hover:bg-white underline text-gray-700 transition">U</button>
                             <div class="w-[1px] bg-gray-300 mx-1 my-1"></div>
                             <button onclick="updateActiveProp('textAlign', 'left')" class="flex-1 py-1.5 rounded hover:bg-white text-gray-700 transition">⬅</button>
                             <button onclick="updateActiveProp('textAlign', 'center')" class="flex-1 py-1.5 rounded hover:bg-white text-gray-700 transition">⬇</button>
                             <button onclick="updateActiveProp('textAlign', 'right')" class="flex-1 py-1.5 rounded hover:bg-white text-gray-700 transition">➡</button>
                        </div>
                    </div>
                </div>
                
                {{-- Image Tab --}}
                <div id="tab-image" class="space-y-6 hidden">
                    <label class="w-full cursor-pointer bg-white border border-gray-300 text-gray-700 px-3 py-3 rounded-lg shadow-sm text-sm font-bold hover:bg-gray-50 text-center block">
                        <input type="file" accept="image/*" onchange="addImageOnCanvas(this)" class="hidden">
                        📷 এক্সট্রা ইমেজ
                    </label>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <label class="label-title mb-3 text-center border-b pb-2 block">🖼️ মেইন ইমেজ</label>
                        <div class="flex items-center justify-between mb-4 bg-white p-2 rounded shadow-sm">
                            <button onclick="controlMainImage('zoom', -0.05)" class="p-2 bg-red-50 text-red-600 font-bold">➖</button>
                            <span class="text-xs font-bold text-gray-500">ZOOM</span>
                            <button onclick="controlMainImage('zoom', 0.05)" class="p-2 bg-green-50 text-green-600 font-bold">➕</button>
                        </div>
                        <div class="grid grid-cols-3 gap-2 max-w-[150px] mx-auto">
                            <div></div>
                            <button onclick="controlMainImage('moveY', -20)" class="p-2 bg-white border rounded shadow-sm font-bold">⬆️</button>
                            <div></div>
                            <button onclick="controlMainImage('moveX', -20)" class="p-2 bg-white border rounded shadow-sm font-bold">⬅️</button>
                            <button onclick="controlMainImage('reset')" class="p-2 bg-red-100 border rounded shadow-sm text-red-600 font-bold text-xs">RESET</button>
                            <button onclick="controlMainImage('moveX', 20)" class="p-2 bg-white border rounded shadow-sm font-bold">➡️</button>
                            <div></div>
                            <button onclick="controlMainImage('moveY', 20)" class="p-2 bg-white border rounded shadow-sm font-bold">⬇️</button>
                            <div></div>
                        </div>
                    </div>
                    <div class="border-t pt-4">
                         <label class="label-title">অপাসিটি</label>
                         <div>
                            <input type="range" min="0" max="1" step="0.1" id="img-opacity" oninput="updateActiveProp('opacity', parseFloat(this.value))">
                         </div>
                    </div>
                </div>

                {{-- Layers Tab --}}
                <div id="tab-layers" class="space-y-4 hidden">
                    <label class="label-title flex justify-between items-center">
                        লেয়ার ম্যানেজমেন্ট
                        <button onclick="renderLayerList()" class="text-[10px] text-blue-600 hover:underline">Refresh</button>
                    </label>
                    <div id="layer-list-container" class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar p-1"></div>
                    <div class="grid grid-cols-2 gap-3">
                         <button onclick="canvas.bringForward(canvas.getActiveObject())" class="layer-btn">⬆ এক ধাপ উপরে</button>
                         <button onclick="canvas.sendBackwards(canvas.getActiveObject())" class="layer-btn">⬇ এক ধাপ নিচে</button>
                         <button onclick="canvas.bringToFront(canvas.getActiveObject())" class="layer-btn font-bold text-indigo-600">🔝 সবার উপরে</button>
                         <button onclick="canvas.sendToBack(canvas.getActiveObject())" class="layer-btn font-bold text-indigo-600">BOTTOM</button>
                    </div>
                    <div class="border-t pt-4 mt-2">
                        <button onclick="deleteActive()" class="w-full bg-red-50 text-red-600 border border-red-200 py-2.5 rounded-lg font-bold text-sm hover:bg-red-100 transition">🗑️ ডিলিট করুন</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Publish Modal --}}
    <div id="studioPublishModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[100] backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in-up">
            <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">🚀 Publish Settings</h3>
                <button onclick="closePublishModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3 bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                    <input type="checkbox" id="modalSocialOnly" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer" onchange="toggleCategoryField(this.checked)">
                    <div>
                        <label for="modalSocialOnly" class="font-bold text-gray-700 cursor-pointer select-none">Only Social Media</label>
                        <p class="text-xs text-gray-500">ওয়েবসাইটে পোস্ট হবে না, শুধু ফেসবুক/টেলিগ্রামে যাবে।</p>
                    </div>
                </div>

                <div id="categoryFieldWrapper">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-bold text-gray-700">Website Category</label>
                        <button type="button" onclick="refreshStudioCategories()" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-200 transition font-bold flex items-center gap-1 border border-indigo-200">
                            🔄 Refresh List
                        </button>
                    </div>

                    {{-- 🔥 অটো লোড হওয়া ড্রপডাউন --}}
                    <select id="modalCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">⏳ Loading Categories...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Social Caption</label>
                    <textarea id="modalCaption" rows="4" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Write something...">{{ $newsItem->ai_title ?? $newsItem->title }}</textarea>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
                <button onclick="closePublishModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-100">Cancel</button>
                <button onclick="confirmStudioPost()" id="btnFinalPost" class="px-6 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 shadow-lg flex items-center gap-2">
                    ✈️ Confirm & Post
                </button>
            </div>
        </div>
    </div>
</div>

@include('news.studio.scripts')
@include('news.studio.rtv-design')

{{-- 🔥 EXTRA SCRIPTS FOR FEATURES --}}
<script>
    // ✅ গ্লোবাল ভেরিয়েবল ক্যাটাগরি রাখার জন্য
    let studioCategories = [];

    // 🔥 ১. সিলেক্টিভ টেক্সট কালার ফাংশন (Selection Color)
    window.applyTextColor = function(color) {
        const activeObj = canvas.getActiveObject();
        
        if (activeObj && (activeObj.type === 'i-text' || activeObj.type === 'textbox')) {
            // যদি এডিটিং মোডে থাকে এবং টেক্সট সিলেক্ট করা থাকে
            if (activeObj.isEditing && activeObj.selectionStart !== activeObj.selectionEnd) {
                // শুধু সিলেক্টেড অংশের কালার চেঞ্জ
                activeObj.setSelectionStyles({ fill: color });
            } else {
                // অন্যথায় পুরো টেক্সটের কালার চেঞ্জ
                activeObj.set('fill', color);
            }
            canvas.requestRenderAll();
            if(typeof saveHistory === 'function') saveHistory();
        }
    };

    // 🔥 ২. মোডাল ওপেন হলে অটো ক্যাটাগরি লোড
    window.openPublishModal = function() {
        const modal = document.getElementById('studioPublishModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // যদি ক্যাটাগরি আগে লোড না হয়ে থাকে, তবে লোড করো
        if(studioCategories.length === 0) {
            refreshStudioCategories();
        } else {
            populateStudioDropdown();
        }
    };

    // 🔥 ক্যাটাগরি ফেচ এবং ড্রপডাউন পপুলেট
    window.refreshStudioCategories = function() {
        const btn = document.querySelector('button[onclick="refreshStudioCategories()"]');
        const select = document.getElementById('modalCategory');
        
        if(btn) { btn.innerText = "⏳ Loading..."; btn.disabled = true; }
        if(select) select.innerHTML = '<option value="">⏳ Fetching data...</option>';

        fetch("{{ route('settings.fetch-categories') }}")
            .then(res => res.json())
            .then(data => {
                if(!data.error) {
                    studioCategories = data;
                    populateStudioDropdown();
                    if(btn) btn.innerText = "✅ Updated";
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(err => console.error("Category Fetch Error:", err))
            .finally(() => {
                if(btn) { 
                    setTimeout(() => { 
                        btn.innerHTML = '🔄 Refresh List'; 
                        btn.disabled = false; 
                    }, 1500); 
                }
            });
    };

    // 🔥 ড্রপডাউন HTML তৈরি
    window.populateStudioDropdown = function() {
        const select = document.getElementById('modalCategory');
        if(!select) return;
        
        select.innerHTML = '<option value="">-- Select Category --</option>';
        
        // ডিফল্ট ক্যাটাগরি
        select.innerHTML += '<option value="1">Uncategorized (Default)</option>';

        studioCategories.forEach(cat => {
            let option = document.createElement('option');
            option.value = cat.id;
            option.text = `${cat.name} (ID: ${cat.id})`;
            select.appendChild(option);
        });
    };
    
    window.closePublishModal = function() {
        document.getElementById('studioPublishModal').classList.add('hidden');
        document.getElementById('studioPublishModal').classList.remove('flex');
    };
    
    // Canvas Loader Trigger (Optional if you want to show manual loading)
    function showCanvasLoader() {
        document.getElementById('canvas-loader').classList.remove('hidden');
    }
    function hideCanvasLoader() {
        document.getElementById('canvas-loader').classList.add('hidden');
    }
</script>
@endsection