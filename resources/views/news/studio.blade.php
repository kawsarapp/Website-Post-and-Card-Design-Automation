@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .tab-btn.active { border-bottom: 2px solid #4f46e5; color: #4f46e5; }
    input[type=range] { height: 26px; -webkit-appearance: none; width: 100%; background: transparent; }
    input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 6px; cursor: pointer; background: #e2e8f0; border-radius: 3px; }
    input[type=range]::-webkit-slider-thumb { height: 16px; width: 16px; border-radius: 50%; background: #4f46e5; cursor: pointer; -webkit-appearance: none; margin-top: -5px; }
    .label-title { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 0.05em; }
    .layer-btn { background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; font-size: 11px; transition: all 0.2s; }
    .layer-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
	

	<!--custom font-->
	/* studio.blade.php এর <style> এর ভেতরে */

	/* studio.blade.php এর <style> এর ভেতরে */

@font-face {
    font-family: 'Noto Serif Cond Thin';
    src: url('/fonts/NotoSerifBengali_Condensed-Thin.ttf') format('truetype');
}

@font-face {
    font-family: 'Noto Serif Cond Light';
    src: url('/fonts/NotoSerifBengali_Condensed-Light.ttf') format('truetype');
}

@font-face {
    font-family: 'Noto Serif Cond Regular';
    src: url('/fonts/NotoSerifBengali_Condensed-Regular.ttf') format('truetype');
    font-weight: normal;
}

@font-face {
    font-family: 'Noto Serif Cond Medium';
    src: url('/fonts/NotoSerifBengali_Condensed-Medium.ttf') format('truetype');
}

@font-face {
    font-family: 'Noto Serif Cond SemiBold';
    src: url('/fonts/NotoSerifBengali_Condensed-SemiBold.ttf') format('truetype');
}

@font-face {
    font-family: 'Noto Serif Cond Bold';
    src: url('/fonts/NotoSerifBengali_Condensed-Bold.ttf') format('truetype');
    font-weight: bold;
}

@font-face {
    font-family: 'Noto Serif Cond ExtraBold';
    src: url('/fonts/NotoSerifBengali_Condensed-ExtraBold.ttf') format('truetype');
}

@font-face {
    font-family: 'Noto Serif Cond Black';
    src: url('/fonts/NotoSerifBengali_Condensed-Black.ttf') format('truetype');
}


<!--cusom font end-->

</style>

<div class="fixed inset-0 bg-gray-100 z-50 flex flex-col font-bangla">
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm z-30 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('news.index') }}" class="flex items-center gap-1 text-gray-500 hover:text-gray-800 transition font-bold text-sm bg-gray-100 px-3 py-1.5 rounded-lg">← Back</a>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                🎨 স্টুডিও প্রো <span class="text-sm bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-normal">SaaS</span>
            </h1>
        </div>
       
	   <div class="flex gap-3 items-center">
    {{-- 🔥 Social Only Checkbox --}}
    <label class="flex items-center gap-1 cursor-pointer bg-white border border-gray-300 px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 select-none">
        <input type="checkbox" id="socialOnlyCheck" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
        <span>Only Social</span>
    </label>

    <button type="button" onclick="restoreSavedDesign()" class="btn btn-warning text-white gap-2 text-sm font-bold px-3 py-1.5 rounded-lg">
        <i class="fas fa-undo"></i> রিস্টোর
    </button>

    <button onclick="resetCanvas()" class="text-gray-500 hover:text-red-500 font-bold text-sm px-3 border border-gray-300 rounded-lg py-1.5 hover:bg-red-50 transition">
        ↻ Reset
    </button>

    <button onclick="saveCurrentDesign()" class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-100 transition border border-indigo-200 flex items-center gap-1 shadow-sm">
        💾 Save
    </button>

    <button id="downloadBtn" onclick="downloadCard()" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:shadow-lg transition flex items-center gap-2 transform hover:-translate-y-0.5">
        📥 ডাউনলোড
    </button>

    <button onclick="postDirectFromStudio()" class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-red-700 transition flex items-center gap-2 shadow-lg border border-red-500">
        🚀 Post Now
    </button>
</div>

	   
    </div>

    <div class="flex flex-1 overflow-hidden">
        <div class="w-[400px] bg-white border-r border-gray-200 flex flex-col h-full z-20 shadow-xl font-bangla">
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button onclick="switchTab('design')" class="tab-btn active flex-1 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">ডিজাইন</button>
                <button onclick="switchTab('text')" class="tab-btn flex-1 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">টেক্সট</button>
                <button onclick="switchTab('image')" class="tab-btn flex-1 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">ইমেজ</button>
                <button onclick="switchTab('layers')" class="tab-btn flex-1 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-100 transition">লেয়ার</button>
            </div>

            <div class="flex justify-center gap-6 p-2 bg-white border-b shadow-sm z-10">
                <button onclick="undo()" class="text-gray-500 hover:text-indigo-600 p-1.5 rounded hover:bg-gray-50 transition" title="Undo">↩ Undo</button>
                <button onclick="redo()" class="text-gray-500 hover:text-indigo-600 p-1.5 rounded hover:bg-gray-50 transition" title="Redo">Redo ↪</button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-5 pb-20">
                <div id="tab-design" class="space-y-6">
    <div>
        <label class="label-title">🎨 প্রিসেট ডিজাইন</label>
        
        @if(count($availableTemplates) == 0)
            <div class="bg-red-50 text-red-600 p-3 rounded text-xs text-center border border-red-200">
                ❌ আপনার অ্যাকাউন্টে কোনো টেমপ্লেট অ্যাক্সেস নেই। এডমিনের সাথে যোগাযোগ করুন।
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 max-h-[400px] overflow-y-auto custom-scrollbar p-1">
            
            @foreach($availableTemplates as $template)
                <div onclick="applyAdminTemplate('{{ asset($template['image']) }}', '{{ $template['layout'] }}')" 
                     class="cursor-pointer border border-gray-200 rounded-lg hover:border-indigo-500 transition p-1 bg-white group hover:shadow-md">
                    
                    <img src="{{ asset($template['image']) }}" 
                         alt="{{ $template['name'] }}" 
                         class="w-full h-20 object-contain bg-gray-50 mb-1 rounded">
                    
                    <p class="text-[10px] text-center font-bold text-gray-600 group-hover:text-indigo-600">
                        {{ $template['name'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
    
    <div class="border-t pt-4">
        <label class="label-title">🖼️ কাস্টমাইজ</label>
        <div class="grid grid-cols-2 gap-2 mb-3">
            <label class="cursor-pointer bg-indigo-50 border border-dashed border-indigo-300 text-indigo-600 p-3 rounded-lg text-center font-bold text-xs hover:bg-indigo-100 transition flex flex-col items-center justify-center gap-1 h-24">
                <input type="file" class="hidden" accept="image/*" onchange="setBackgroundImage(this)">
                <span>🌄 ব্যাকগ্রাউন্ড বদলান</span>
            </label>
            <label class="cursor-pointer bg-purple-50 border border-dashed border-purple-300 text-purple-600 p-3 rounded-lg text-center font-bold text-xs hover:bg-purple-100 transition flex flex-col items-center justify-center gap-1 h-24">
                <input type="file" class="hidden" accept="image/png" onchange="addCustomFrame(this)">
                <span>🖼️ ফ্রেম আপলোড (PNG)</span>
            </label>
        </div>
        <div class="flex items-center justify-between bg-gray-50 p-2 rounded border mt-2">
            <span class="text-xs text-gray-500">সলিড কালার:</span>
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

                <div id="tab-text" class="space-y-6 hidden">
				
				
				<div class="mt-2 mb-2">
    <label class="label-title">🌐 গুগল ফন্ট নাম লিখুন</label>
    <div class="flex gap-2">
        <input type="text" id="google-font-input" placeholder="Ex: Lobster, SolaimanLipi" 
               class="w-full border p-1.5 rounded text-sm border-gray-300">
        <button onclick="changeFont(document.getElementById('google-font-input').value)" 
                class="bg-blue-600 text-white px-3 rounded text-xs">Load</button>
    </div>
</div>

<div class="mt-2 border-t pt-2">
    <label class="label-title">📂 কাস্টম ফন্ট আপলোড (TTF/OTF)</label>
    <label class="w-full cursor-pointer bg-gray-100 border border-dashed border-gray-400 text-gray-700 px-2 py-2 rounded-lg text-xs font-bold hover:bg-gray-200 text-center block">
        <input type="file" accept=".ttf,.otf,.woff,.woff2" onchange="uploadCustomFont(this)" class="hidden">
        📥 আপলোড ফন্ট ফাইল
    </label>
</div>
				
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="addText('Headline')" class="bg-gray-800 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-black shadow-sm transition">+ Add Title</button>
                        <button onclick="addText('Subtitle', 30)" class="bg-white border border-gray-300 text-gray-700 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 shadow-sm transition">+ Subtitle</button>
                    </div>
                    <div class="border-t pt-4 mt-2">
                        <label class="label-title">🅰️ ফন্ট স্টাইল</label>
<select id="font-family" onchange="changeFont(this.value)" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bangla focus:ring-2 focus:ring-indigo-500 outline-none">
    <option value="" disabled selected>-- ফন্ট সিলেক্ট করুন --</option>
    
	<optgroup label="📂 কাস্টম (Noto Serif Condensed)">
        <option value="'Noto Serif Cond Thin'">Noto Serif (Thin)</option>
        <option value="'Noto Serif Cond Light'">Noto Serif (Light)</option>
        <option value="'Noto Serif Cond Regular'">Noto Serif (Regular)</option>
        <option value="'Noto Serif Cond Medium'">Noto Serif (Medium)</option>
        <option value="'Noto Serif Cond SemiBold'">Noto Serif (SemiBold)</option>
        <option value="'Noto Serif Cond Bold'">Noto Serif (Bold)</option>
        <option value="'Noto Serif Cond ExtraBold'">Noto Serif (ExtraBold)</option>
        <option value="'Noto Serif Cond Black'">Noto Serif (Black)</option>
    </optgroup>
	
    <optgroup label="🔥 জনপ্রিয় বাংলা ফন্ট">
        <option value="'Hind Siliguri', sans-serif">হিন্দ শিলিগুড়ি (Hind Siliguri)</option>
        <option value="'Noto Sans Bengali', sans-serif">নোটো স্যান্স (Noto Sans)</option>
        <option value="'Baloo Da 2', cursive">বালু দা ২ (Baloo Da 2)</option>
        <option value="'Galada', cursive">গলাদা (Galada)</option>
        <option value="'Anek Bangla', sans-serif">অনেক বাংলা (Anek Bangla)</option>
        <option value="'Tiro Bangla', serif">তিরো বাংলা (Tiro Bangla)</option>
        <option value="'Mina', sans-serif">মিনা (Mina)</option>
        <option value="'Noto Serif Bengali', serif">নোটো সেরিফ (Noto Serif)</option>
        <option value="'Atma', cursive">আত্মা (Atma)</option>
        <option value="'Eczar', serif">একজার (Eczar)</option>
        <option value="'Firoza', sans-serif">ফিরোজা (Firoza)</option>
    </optgroup>

    <optgroup label="🎨 স্টাইলিশ বাংলা (Not Standard)">
        <option value="'Kavivanar', cursive">কবিভানার (Kavivanar)</option>
        <option value="'Bonbon', cursive">বনবন (Bonbon)</option>
        <option value="'Modak', cursive">মোদক (Modak)</option>
        <option value="'Laila', serif">লায়লা (Laila)</option>
        <option value="'Kurale', serif">কুড়ালে (Kurale)</option>
        <option value="'Podkova', serif">পডকোভা (Podkova)</option>
    </optgroup>
    
    <optgroup label="English Modern Fonts">
        <option value="'Roboto', sans-serif">Roboto</option>
        <option value="'Oswald', sans-serif">Oswald</option>
        <option value="'Montserrat', sans-serif">Montserrat</option>
        <option value="'Open Sans', sans-serif">Open Sans</option>
        <option value="'Lato', sans-serif">Lato</option>
        <option value="'Poppins', sans-serif">Poppins</option>
        <option value="'Raleway', sans-serif">Raleway</option>
        <option value="'Merriweather', serif">Merriweather</option>
        <option value="'Playfair Display', serif">Playfair Display</option>
    </optgroup>
</select>
                        
                        <label class="label-title mt-4">📝 এডিট টেক্সট</label>
                        <textarea id="text-content" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-3 font-bangla focus:ring-2 focus:ring-indigo-500 outline-none" rows="3" oninput="updateActiveProp('text', this.value)"></textarea>
                        
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div><label class="text-xs text-gray-500 mb-1 block">Text Color</label><input type="color" id="text-color" class="w-full h-9 rounded cursor-pointer border border-gray-200 p-0.5" oninput="updateActiveProp('fill', this.value)"></div>
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

                <div id="tab-image" class="space-y-6 hidden">
    
    <label class="w-full cursor-pointer bg-white border border-gray-300 text-gray-700 px-3 py-3 rounded-lg shadow-sm text-sm font-bold hover:bg-gray-50 text-center block transition transform hover:-translate-y-0.5">
        <input type="file" accept="image/*" onchange="addImageOnCanvas(this)" class="hidden">
        📷 এক্সট্রা ইমেজ যোগ করুন
    </label>

    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
        <label class="label-title mb-3 text-center border-b pb-2 block">🖼️ মেইন ইমেজ কন্ট্রোল</label>
        
        <div class="flex items-center justify-between mb-4 bg-white p-2 rounded shadow-sm">
            <button onclick="controlMainImage('zoom', -0.05)" class="p-2 bg-red-50 text-red-600 rounded hover:bg-red-100 font-bold transition text-lg" title="Zoom Out">➖</button>
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Zoom / Size</span>
            <button onclick="controlMainImage('zoom', 0.05)" class="p-2 bg-green-50 text-green-600 rounded hover:bg-green-100 font-bold transition text-lg" title="Zoom In">➕</button>
        </div>

        <div class="grid grid-cols-3 gap-2 max-w-[150px] mx-auto">
            <div></div>
            <button onclick="controlMainImage('moveY', -20)" class="p-2 bg-white border border-gray-300 rounded hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm text-gray-600 font-bold">⬆️</button>
            <div></div>

            <button onclick="controlMainImage('moveX', -20)" class="p-2 bg-white border border-gray-300 rounded hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm text-gray-600 font-bold">⬅️</button>
            <button onclick="controlMainImage('reset')" class="p-2 bg-red-100 border border-red-200 rounded hover:bg-red-200 transition shadow-sm text-red-600 font-bold text-xs" title="Reset Image">RESET</button>
            <button onclick="controlMainImage('moveX', 20)" class="p-2 bg-white border border-gray-300 rounded hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm text-gray-600 font-bold">➡️</button>

            <div></div>
            <button onclick="controlMainImage('moveY', 20)" class="p-2 bg-white border border-gray-300 rounded hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm text-gray-600 font-bold">⬇️</button>
            <div></div>
        </div>
        
        <p class="text-[10px] text-gray-400 text-center mt-3">মেইন ইমেজ অ্যাডজাস্ট করুন</p>
    </div>

    <div class="border-t pt-4">
         <label class="label-title">ইমেজ অপাসিটি</label>
         <div>
            <input type="range" min="0" max="1" step="0.1" id="img-opacity" oninput="updateActiveProp('opacity', parseFloat(this.value))">
         </div>
    </div>
</div>

                <div id="tab-layers" class="space-y-4 hidden">

    <!-- লেয়ার ম্যানেজমেন্ট + Refresh -->
    <label class="label-title flex justify-between items-center">
        লেয়ার ম্যানেজমেন্ট
        <button onclick="renderLayerList()" class="text-[10px] text-blue-600 hover:underline">Refresh</button>
    </label>

    <!-- লেয়ার লিস্ট কন্টেইনার -->
    <div id="layer-list-container" class="space-y-2 max-h-[400px] overflow-y-auto custom-scrollbar p-1">
    </div>

    <!-- পজিশন কন্ট্রোল + মুভমেন্ট -->
    <label class="label-title">পজিশন কন্ট্রোল</label>
    <div class="grid grid-cols-2 gap-3">
         <button onclick="canvas.bringForward(canvas.getActiveObject())" class="layer-btn">⬆ এক ধাপ উপরে</button>
         <button onclick="canvas.sendBackwards(canvas.getActiveObject())" class="layer-btn">⬇ এক ধাপ নিচে</button>
         <button onclick="canvas.bringToFront(canvas.getActiveObject())" class="layer-btn font-bold text-indigo-600">🔝 সবার উপরে</button>
         <button onclick="canvas.sendToBack(canvas.getActiveObject())" class="layer-btn font-bold text-indigo-600">BOTTOM</button>
    </div>

    <div class="border-t pt-4 mt-2">
        <label class="label-title">পজিশন মুভমেন্ট</label>
        <div class="grid grid-cols-2 gap-3">
             <button onclick="moveLayer('up')" class="layer-btn">⬆ এক ধাপ উপরে</button>
             <button onclick="moveLayer('down')" class="layer-btn">⬇ এক ধাপ নিচে</button>
             <button onclick="moveLayer('top')" class="layer-btn font-bold text-indigo-600">🔝 সবার উপরে</button>
             <button onclick="moveLayer('bottom')" class="layer-btn font-bold text-indigo-600">BOTTOM</button>
        </div>
    </div>

    <!-- ডিলিট বাটন -->
    <div class="border-t pt-4 mt-2">
        <button onclick="deleteActive()" class="w-full bg-red-50 text-red-600 border border-red-200 py-2.5 rounded-lg font-bold text-sm hover:bg-red-100 transition">🗑️ ডিলিট করুন</button>
    </div>

</div>

				
				
				
				
            </div>
        </div>
        
        <div id="workspace-container" class="flex-1 bg-gray-200 flex items-center justify-center overflow-hidden relative p-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
    
			<div class="absolute bottom-6 right-6 flex bg-white shadow-lg rounded-full px-4 py-2 gap-4 z-40 border border-gray-200">
			   <button onclick="changeZoom(-0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-xl focus:outline-none cursor-pointer">➖</button>
			   <span class="text-sm font-bold text-gray-400 pt-1 select-none" id="zoom-level">FIT</span>
			   <button onclick="changeZoom(0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-xl focus:outline-none cursor-pointer">➕</button>
			   <button onclick="fitToScreen()" class="text-xs font-bold text-blue-500 hover:underline border-l pl-3 ml-1" title="Fit to Screen">⟲ Fit</button>
		   </div>

		   <div id="canvas-wrapper" class="shadow-2xl transition-transform duration-200 ease-out origin-center ring-8 ring-white">
			   <canvas id="newsCanvas" width="1080" height="1080"></canvas>
		   </div>
		</div>
    </div>

	@include('news.studio.scripts')
	@include('news.studio.rtv-design')
@endsection