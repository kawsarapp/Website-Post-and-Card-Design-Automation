@extends('layouts.app')

@section('content')

{{-- ১. স্টাইল ফাইল ইমপোর্ট করা হলো --}}
@include('partials.studio_styles')

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

    {{-- ২. Publish Modal ইমপোর্ট করা হলো --}}
    @include('partials.studio_publish_modal')

</div>

@include('news.studio.scripts')
@include('news.studio.rtv-design')

{{-- ৩. Extra Scripts ইমপোর্ট করা হলো --}}
@include('partials.studio_extra_scripts')

@endsection