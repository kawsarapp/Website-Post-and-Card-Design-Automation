@extends('layouts.app')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap');
   
    /* Slider Style */
    input[type=range] { -webkit-appearance: none; width: 100%; height: 6px; background: #e2e8f0; border-radius: 5px; outline: none; }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #4f46e5; cursor: pointer; transition: background .15s ease-in-out; }
    input[type=range]::-webkit-slider-thumb:hover { background: #4338ca; }

    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">News Studio (Advanced)</h2>
    <a href="{{ route('websites.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
        ← Back to Scraper
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
    @foreach($newsItems as $item)
    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition border border-gray-100">
        <div class="h-40 overflow-hidden bg-gray-200 relative group">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
            @else
                <div class="flex items-center justify-center h-full text-gray-400">No Image</div>
            @endif
        </div>
       
        <div class="p-4">
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">{{ $item->website->name }}</span>
            <h3 class="text-lg font-semibold leading-tight mt-1 mb-2 line-clamp-3 text-gray-800 font-['Hind_Siliguri']">{{ $item->title }}</h3>
            <p class="text-xs text-gray-500 flex items-center gap-1">
                🕒 {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}
            </p>
           
            <div class="mt-4 flex gap-2">
                <button onclick="openGenerator('{{ addslashes($item->title) }}', '{{ $item->thumbnail_url }}', '{{ $item->website->name }}')"
                        class="flex-1 bg-indigo-600 text-white py-2 rounded text-sm font-bold hover:bg-indigo-700 transition shadow-sm flex items-center justify-center gap-2">
                    🎨 Make Card
                </button>
                @if($item->is_posted)
                    <button class="bg-green-100 text-green-700 px-3 py-2 rounded border border-green-300 cursor-default" title="Already Posted">
                        ✅ Posted
                    </button>
                @else
                    <form action="{{ route('news.post', $item->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-purple-600 text-white px-3 py-2 rounded hover:bg-purple-700 transition font-bold flex items-center gap-1" onclick="return confirm('আপনি কি নিশ্চিত যে এটি রিরাইট করে পোস্ট করতে চান?')">
                            🚀 Post WP
                        </button>
                    </form>
                @endif
                <a href="{{ $item->original_link }}" target="_blank" class="px-3 py-2 border border-gray-300 rounded text-gray-600 hover:bg-gray-100 transition" title="Visit Original Link">
                    🔗
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">
    {{ $newsItems->links() }}
</div>

<div id="cardModal" class="fixed inset-0 bg-black bg-opacity-95 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-full max-w-7xl h-[95vh] flex overflow-hidden shadow-2xl">
       
        <div class="w-1/3 bg-gray-50 p-6 border-r border-gray-200 flex flex-col gap-4 overflow-y-auto custom-scrollbar z-20 relative">
            <h3 class="text-xl font-extrabold text-gray-800 border-b pb-3 mb-1">⚙️ Studio Controls</h3>
            
            <div class="bg-purple-50 p-3 rounded border border-purple-200 shadow-sm">
                <label class="block text-xs font-bold text-purple-700 uppercase mb-2">🎨 Choose Template</label>
                <select id="templateSelector" onchange="changeTemplate(this.value)" class="w-full border p-2 rounded text-sm focus:ring-2 focus:ring-purple-500 outline-none font-semibold text-gray-700">
					<option value="classic">Classic Studio (Vertical)</option>
					<option value="modern_split">Modern Split (Horizontal)</option>
					<option value="bold_overlay">Bold Breaking (Overlay)</option>
					
					<option value="broadcast_tv">📺 TV Broadcast Style</option>
					<option value="glass_blur">💧 Glassmorphism (Premium)</option>
					<option value="minimal_frame">🖼️ Minimal Frame (Clean)</option>
					<option value="neon_dark">🌑 Neon Dark (Gaming/Tech)</option>
					<option value="magazine_cover">📰 Magazine Cover</option>
				</select>
            </div>

            <div id="layoutControlGroup" class="bg-indigo-50 p-3 rounded border border-indigo-100 shadow-sm">
                <label class="block text-xs font-bold text-indigo-600 uppercase mb-2">📐 Layout & Shape</label>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <button onclick="setLayout('classic')" class="bg-white border border-gray-300 px-2 py-2 text-xs rounded hover:bg-indigo-100 transition">Classic (60/40)</button>
                    <button onclick="setLayout('cinematic')" class="bg-white border border-gray-300 px-2 py-2 text-xs rounded hover:bg-indigo-100 transition font-bold text-indigo-700">Cinematic (70/30)</button>
                    <button onclick="setLayout('square')" class="bg-white border border-gray-300 px-2 py-2 text-xs rounded hover:bg-indigo-100 transition">Split (50/50)</button>
                    <button onclick="setLayout('full')" class="bg-white border border-gray-300 px-2 py-2 text-xs rounded hover:bg-indigo-100 transition">Overlay</button>
                </div>
                <div class="mt-2 border-t border-indigo-200 pt-2 flex justify-between items-center">
                    <label class="text-xs font-bold text-gray-600">Corner Curve</label>
                    <input type="range" min="0" max="100" value="0" class="w-24" id="radiusRange" oninput="updateRadius()">
                </div>
            </div>

            <div class="bg-white p-3 rounded border shadow-sm">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">🅰️ Text Style</label>
                <div class="flex gap-2 mb-2">
                    <button onclick="setTextAlign('left')" class="flex-1 bg-gray-100 border px-2 py-1 rounded text-xs hover:bg-gray-200">⬅ Left</button>
                    <button onclick="setTextAlign('center')" class="flex-1 bg-gray-100 border px-2 py-1 rounded text-xs hover:bg-gray-200 font-bold text-indigo-600">⬇ Center</button>
                    <button onclick="setTextAlign('right')" class="flex-1 bg-gray-100 border px-2 py-1 rounded text-xs hover:bg-gray-200">Right ➡</button>
                </div>
                <div class="flex items-center justify-between border-t pt-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Size</span>
                        <input type="range" min="30" max="100" value="50" class="w-20" oninput="updateFontSize(this.value)">
                    </div>
                    <div class="flex gap-1">
                        <button onclick="updateTitleColor('#000000')" class="w-5 h-5 rounded-full bg-black border"></button>
                        <button onclick="updateTitleColor('#ffffff')" class="w-5 h-5 rounded-full bg-white border"></button>
                        <button onclick="updateTitleColor('#dc2626')" class="w-5 h-5 rounded-full bg-red-600 border"></button>
                        <input type="color" class="w-5 h-5 p-0 border-0 rounded-full overflow-hidden cursor-pointer" oninput="updateTitleColor(this.value)">
                    </div>
                </div>
            </div>

            <div class="bg-white p-3 rounded border shadow-sm">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Headline</label>
                <textarea id="inputTitle" class="w-full border p-2 rounded text-sm h-16 focus:ring-2 focus:ring-indigo-500 outline-none font-['Hind_Siliguri'] font-bold text-lg" oninput="updateCard()"></textarea>
            </div>
            <div class="bg-white p-3 rounded border shadow-sm">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Branding</label>
                <div class="flex gap-2 mb-2">
                    <input type="file" id="logoInput" accept="image/*" onchange="uploadLogo()" class="hidden">
                    <label for="logoInput" class="flex-1 cursor-pointer bg-gray-100 border border-dashed border-gray-400 text-gray-600 px-3 py-2 rounded text-sm hover:bg-gray-200 text-center flex items-center justify-center gap-2">
                        📁 Upload Logo
                    </label>
                    <button onclick="resetLogo()" class="bg-red-50 text-red-600 border border-red-200 px-3 py-2 rounded text-sm hover:bg-red-100">✖</button>
                </div>
                <input type="text" id="badgeTextInput" placeholder="Badge Text (e.g. NEWS)" class="w-full border p-2 rounded text-sm mb-2" oninput="updateBadgeText()">
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Footer Name</label>
                <input type="text" id="brandInput" value="My News Portal" class="w-full border p-2 rounded text-sm" oninput="updateBrand()">
            </div>

            <div class="bg-white p-3 rounded border shadow-sm" id="themeSection">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Background Theme</label>
                <div class="grid grid-cols-4 gap-3">
                    <button onclick="setTheme('bg-white', 'text-gray-900', 'bg-red-600', 'white')" class="h-8 rounded border bg-white"></button>
                    <button onclick="setTheme('bg-gray-900', 'text-white', 'bg-yellow-500', 'dark')" class="h-8 rounded bg-gray-900"></button>
                    <button onclick="setTheme('bg-blue-900', 'text-white', 'bg-white', 'dark')" class="h-8 rounded bg-blue-900"></button>
                    <button onclick="setTheme('bg-red-900', 'text-white', 'bg-white', 'dark')" class="h-8 rounded bg-red-900"></button>
                </div>
            </div>

            <div class="mt-auto pt-4 border-t space-y-3">
                <button id="downloadBtn" onclick="downloadCard()" class="w-full bg-green-600 text-white py-3.5 rounded-lg font-bold text-lg hover:bg-green-700 shadow-lg flex items-center justify-center gap-2">
                    📥 Download Image
                </button>
                <button onclick="closeModal()" class="w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50">Close</button>
            </div>
        </div>

        <div class="w-2/3 bg-gray-300 flex items-center justify-center overflow-auto p-8">
            <div id="preview-wrapper" style="transform: scale(0.55); transform-origin: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <div id="canvas-container" class="bg-white relative flex flex-col overflow-hidden"
                     style="width: 1080px; height: 1080px; flex-shrink: 0;">
                     </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // --- GLOBAL VARIABLES ---
    const modal = document.getElementById('cardModal');
    const proxyRoute = "{{ route('proxy.image') }}";
    
    // Store current state
    let currentData = {
        title: "নিউজ লোড হচ্ছে...",
        image: "",
        source: "NEWS",
        brand: "My News Portal",
        date: "",
        logo: null
    };
    
    let currentLayoutType = 'classic';
    
    // --- TEMPLATES DEFINITION ---
    // --- TEMPLATES DEFINITION (UPDATED: 8 DESIGNS) ---
    const templates = {
        // 1. CLASSIC DESIGN
        classic: `
            <div id="imagePart" class="h-[60%] relative overflow-hidden group bg-gray-100 transition-all duration-500 w-full">
                <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                <div id="overlayGradient" class="hidden absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-0"></div>
                <div id="badgeContainer" class="absolute top-10 left-10 z-10">
                    <div id="textBadge" class="bg-red-600 text-white px-8 py-3 text-4xl font-bold uppercase tracking-widest shadow-lg rounded-md">NEWS</div>
                    <div id="logoWrapper" class="hidden filter drop-shadow-2xl"><img id="logoImg" src="" class="h-28 w-auto object-contain"></div>
                </div>
            </div>
            <div id="cardBody" class="h-[40%] px-12 pt-5 pb-6 flex flex-col justify-center items-center text-center relative bg-white text-black transition-all duration-500 w-full">
                <div id="textWrapper" class="flex-1 w-full flex flex-col items-center justify-center">
                    <div id="decoLine" class="w-24 h-3 bg-red-600 mb-4 rounded-full transition-colors duration-300"></div>
                    <h1 id="cardTitle" class="text-[50px] font-bold leading-snug font-['Hind_Siliguri'] line-clamp-4 pb-1 w-full drop-shadow-none transition-all duration-200">Title Here</h1>
                </div>
                <div id="cardFooter" class="w-full mt-auto pt-4 border-t border-current border-opacity-20 flex justify-between items-end opacity-80 font-['Hind_Siliguri']">
                    <span class="text-3xl font-bold tracking-wide uppercase" id="brandNameDisplay">Brand</span>
                    <span class="text-3xl font-bold" id="currentDate">Date</span>
                </div>
            </div>`,

        // 2. MODERN SPLIT
        modern_split: `
            <div class="flex h-full w-full bg-white relative">
                <div class="w-1/2 h-full relative overflow-hidden">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute top-8 left-8">
                         <div id="textBadge" class="bg-blue-600 text-white px-6 py-2 text-2xl font-bold uppercase tracking-widest shadow-md">NEWS</div>
                         <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-20 w-auto"></div>
                    </div>
                </div>
                <div class="w-1/2 h-full p-16 flex flex-col justify-center bg-slate-50 relative">
                    <div class="w-20 h-2 bg-blue-600 mb-10"></div>
                    <div id="textWrapper" class="w-full">
                        <h1 id="cardTitle" class="text-[55px] font-extrabold text-slate-900 leading-[1.2] font-['Hind_Siliguri'] mb-8 text-left">Title Here</h1>
                    </div>
                    <div class="mt-auto border-t-2 border-slate-200 pt-8 flex justify-between items-center text-slate-500 font-['Hind_Siliguri']">
                         <span class="text-2xl font-bold uppercase text-blue-900 tracking-wider" id="brandNameDisplay">Brand</span>
                         <span class="text-2xl font-medium" id="currentDate">Date</span>
                    </div>
                </div>
            </div>`,

        // 3. BOLD OVERLAY
        bold_overlay: `
            <div class="relative h-full w-full overflow-hidden bg-black">
                <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover opacity-80">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
                <div class="absolute top-10 right-10">
                     <div id="textBadge" class="bg-red-700 text-white px-6 py-3 text-3xl font-bold uppercase -skew-x-12 shadow-2xl border-l-4 border-white">BREAKING</div>
                     <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-24 w-auto drop-shadow-lg"></div>
                </div>
                <div class="absolute bottom-0 w-full p-20 flex flex-col items-start justify-end h-full">
                    <div class="bg-red-600 text-white font-bold px-4 py-1 text-xl mb-4 uppercase tracking-widest inline-block">Top Story</div>
                    <div id="textWrapper" class="w-full">
                        <h1 id="cardTitle" class="text-[75px] font-black text-white leading-tight font-['Hind_Siliguri'] drop-shadow-2xl text-left border-l-8 border-red-600 pl-8">Title Here</h1>
                    </div>
                    <div class="w-full flex justify-between items-center mt-12 text-gray-300 border-t border-white/20 pt-6 font-['Hind_Siliguri']">
                        <span class="text-4xl font-bold text-white tracking-widest uppercase" id="brandNameDisplay">Brand</span>
                        <span class="text-3xl font-light" id="currentDate">Date</span>
                    </div>
                </div>
            </div>`,

        // 4. TV BROADCAST STYLE (NEW)
        broadcast_tv: `
            <div class="relative h-full w-full bg-gray-900 overflow-hidden">
                <div class="h-[85%] w-full relative">
                    <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/80"></div>
                     <div class="absolute top-8 left-8 flex items-center gap-3">
                         <div class="bg-red-600 text-white px-4 py-1 text-xl font-bold uppercase animate-pulse">● LIVE</div>
                         <div id="textBadge" class="bg-black/50 text-white px-4 py-1 text-xl font-bold uppercase backdrop-blur-sm">UPDATE</div>
                         <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-16 w-auto"></div>
                    </div>
                </div>
                <div class="h-[15%] w-full bg-blue-900 relative flex items-center px-10 border-t-4 border-yellow-400">
                     <div class="bg-yellow-400 text-blue-900 font-black text-3xl px-6 py-2 absolute -top-8 left-10 skew-x-[-15deg] shadow-lg">
                        LATEST NEWS
                     </div>
                     <div class="w-full flex justify-between items-center text-white pt-2">
                        <h1 id="cardTitle" class="text-[40px] font-bold font-['Hind_Siliguri'] line-clamp-1 w-[80%] leading-normal">Headline goes here...</h1>
                        <div class="flex flex-col items-end border-l pl-6 border-blue-700">
                             <span class="text-xl font-bold text-yellow-400 uppercase" id="brandNameDisplay">Brand</span>
                             <span class="text-lg opacity-80" id="currentDate">Date</span>
                        </div>
                     </div>
                </div>
            </div>`,

        // 5. GLASSMORPHISM (NEW)
        glass_blur: `
            <div class="relative h-full w-full overflow-hidden bg-gray-800 flex items-center justify-center">
                <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover scale-110 filter blur-sm brightness-75">
                
                <div class="relative w-[90%] bg-white/10 backdrop-blur-xl border border-white/20 p-12 rounded-3xl shadow-2xl flex flex-col items-center text-center">
                    
                    <div class="absolute -top-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-full text-2xl font-bold shadow-lg uppercase" id="textBadge">
                        Highlight
                    </div>
                     <div id="logoWrapper" class="hidden absolute -top-10"><img id="logoImg" src="" class="h-24 w-auto drop-shadow-lg bg-white rounded-full p-2"></div>
                    
                    <div class="mt-8 mb-6 w-20 h-1 bg-white/50 rounded-full"></div>
                    
                    <h1 id="cardTitle" class="text-[60px] font-bold text-white leading-tight font-['Hind_Siliguri'] drop-shadow-md mb-8">Title Here</h1>
                    
                    <div class="w-full border-t border-white/20 pt-6 flex justify-between items-center text-white/90 font-['Hind_Siliguri']">
                        <span class="text-2xl font-bold tracking-widest uppercase" id="brandNameDisplay">Brand</span>
                        <span class="text-2xl" id="currentDate">Date</span>
                    </div>
                </div>
            </div>`,

        // 6. MINIMAL FRAME (NEW)
        minimal_frame: `
            <div class="h-full w-full bg-[#f0f2f5] p-12 flex flex-col justify-center items-center">
                <div class="bg-white p-6 rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.15)] w-full h-full flex flex-col">
                    <div class="h-[65%] w-full rounded-2xl overflow-hidden relative">
                         <img id="cardImage" src="" class="w-full h-full object-cover transform hover:scale-105 transition duration-700">
                         <div class="absolute top-6 left-6">
                            <span class="bg-white/90 backdrop-blur text-black px-5 py-2 rounded-lg text-xl font-bold shadow-sm border border-gray-100 uppercase" id="textBadge">News</span>
                            <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-14 w-auto"></div>
                         </div>
                    </div>
                    
                    <div class="h-[35%] flex flex-col justify-center px-4 pt-6">
                        <h1 id="cardTitle" class="text-[50px] font-bold text-gray-800 leading-tight font-['Hind_Siliguri'] line-clamp-3">Title Here</h1>
                        
                        <div class="mt-auto flex items-center gap-4 border-t border-gray-100 pt-6">
                            <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xl">N</div>
                            <div class="flex flex-col">
                                <span class="text-lg font-bold text-gray-700 leading-none" id="brandNameDisplay">Brand Name</span>
                                <span class="text-base text-gray-400" id="currentDate">Date</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`,

        // 7. NEON DARK (NEW)
        neon_dark: `
            <div class="h-full w-full bg-[#0a0a0a] relative flex flex-col overflow-hidden">
                <div class="absolute top-[-20%] right-[-20%] w-[800px] h-[800px] bg-purple-900/30 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-[-20%] left-[-20%] w-[600px] h-[600px] bg-blue-900/20 rounded-full blur-[100px]"></div>

                <div class="h-[55%] w-full relative z-10 p-8 pb-0">
                    <div class="w-full h-full rounded-2xl overflow-hidden border border-gray-800 relative group">
                        <img id="cardImage" src="" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition duration-500">
                        <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-[#0a0a0a] to-transparent"></div>
                         <div class="absolute top-6 right-6">
                            <span class="text-cyan-400 border border-cyan-400 px-4 py-1 text-xl font-mono tracking-widest uppercase rounded bg-cyan-900/20" id="textBadge">TECH</span>
                            <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-16 w-auto brightness-200 contrast-125"></div>
                        </div>
                    </div>
                </div>

                <div class="h-[45%] w-full p-10 z-10 flex flex-col justify-center">
                    <h1 id="cardTitle" class="text-[55px] font-bold text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 font-['Hind_Siliguri'] leading-tight mb-6">Title Here</h1>
                    
                    <div class="flex items-center justify-between border-t border-gray-800 pt-6 mt-auto">
                         <span class="text-2xl font-bold text-purple-500 uppercase tracking-widest" id="brandNameDisplay">Brand</span>
                         <span class="text-xl text-gray-500 font-mono" id="currentDate">Date</span>
                    </div>
                </div>
            </div>`,

         // 8. MAGAZINE COVER (NEW)
        magazine_cover: `
            <div class="h-full w-full relative bg-white">
                <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/90"></div>
                
                <div class="absolute top-0 w-full p-12 text-center border-b border-white/20 backdrop-blur-sm bg-black/10">
                     <h2 class="text-[120px] font-black text-white leading-none tracking-tighter opacity-20 absolute top-2 left-0 w-full select-none">MAGAZINE</h2>
                     <span class="relative text-5xl font-bold text-white uppercase tracking-[1em]" id="brandNameDisplay">BRAND</span>
                </div>

                <div class="absolute bottom-0 w-full p-16 pb-24 flex flex-col items-center text-center">
                    <div class="bg-white text-black text-xl font-bold px-6 py-2 uppercase tracking-widest mb-6" id="textBadge">Feature Story</div>
                    <div id="logoWrapper" class="hidden mb-6"><img id="logoImg" src="" class="h-20 w-auto bg-white p-2"></div>
                    
                    <h1 id="cardTitle" class="text-[70px] font-black text-white font-['Hind_Siliguri'] leading-[1.1] mb-4 drop-shadow-2xl">Title Here</h1>
                    
                    <div class="w-32 h-2 bg-red-600 my-6"></div>
                    <p class="text-gray-300 text-2xl" id="currentDate">Date</p>
                </div>
            </div>`
    };

    // --- MAIN FUNCTIONS ---

    function toBanglaNum(str) {
        return str.toString().replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]);
    }

    function openGenerator(title, image, source) {
        modal.classList.remove('hidden');
        
        // Prepare Data
        const decodedTitle = new DOMParser().parseFromString(title, "text/html").documentElement.textContent;
        currentData.title = decodedTitle;
        currentData.source = source;
        currentData.image = image ? proxyRoute + "?url=" + encodeURIComponent(image) : "";
        
        // Set Inputs
        document.getElementById('inputTitle').value = decodedTitle;
        document.getElementById('badgeTextInput').value = source;
        document.getElementById('logoInput').value = ""; // reset file input

        // Set Date
        const date = new Date();
        const day = toBanglaNum(date.getDate().toString().padStart(2, '0'));
        const month = toBanglaNum((date.getMonth() + 1).toString().padStart(2, '0'));
        const year = toBanglaNum(date.getFullYear());
        currentData.date = `${day}.${month}.${year}`;

        // Reset to Default Template
        document.getElementById('templateSelector').value = 'classic';
        changeTemplate('classic');
    }

    function closeModal() { modal.classList.add('hidden'); }

    // --- TEMPLATE ENGINE ---

    function changeTemplate(type) {
        currentLayoutType = type;
        const container = document.getElementById('canvas-container');
        const layoutControls = document.getElementById('layoutControlGroup');
        const themeSection = document.getElementById('themeSection');

        // Inject HTML
        if(templates[type]) {
            container.innerHTML = templates[type];
        } else {
            container.innerHTML = templates['classic'];
        }

        // Apply Data to New DOM
        const titleEl = document.getElementById('cardTitle');
        const imgEl = document.getElementById('cardImage');
        const badgeEl = document.getElementById('textBadge');
        const brandEl = document.getElementById('brandNameDisplay');
        const dateEl = document.getElementById('currentDate');
        const logoImg = document.getElementById('logoImg');
        const logoWrapper = document.getElementById('logoWrapper');

        if(titleEl) titleEl.innerText = document.getElementById('inputTitle').value;
        if(imgEl) imgEl.src = currentData.image;
        if(brandEl) brandEl.innerText = document.getElementById('brandInput').value;
        if(dateEl) dateEl.innerText = currentData.date;
        
        // Handle Logo vs Text Badge
        if(currentData.logo) {
            if(logoImg) logoImg.src = currentData.logo;
            if(logoWrapper) logoWrapper.classList.remove('hidden');
            if(badgeEl) badgeEl.classList.add('hidden');
        } else {
            if(badgeEl) {
                badgeEl.innerText = document.getElementById('badgeTextInput').value;
                badgeEl.classList.remove('hidden');
            }
            if(logoWrapper) logoWrapper.classList.add('hidden');
        }

        // Toggle Controls Visibility based on Template
        if(type === 'classic') {
            layoutControls.classList.remove('opacity-50', 'pointer-events-none');
            themeSection.classList.remove('opacity-50', 'pointer-events-none');
            setLayout('cinematic'); // Set default sub-layout for classic
        } else {
            // Disable specific layout controls for non-classic templates
            layoutControls.classList.add('opacity-50', 'pointer-events-none');
            themeSection.classList.add('opacity-50', 'pointer-events-none');
            // Remove any classic specific classes from container
            container.style.borderRadius = "0px";
        }
    }

    // --- EDITING LOGIC (UPDATED FOR DYNAMIC DOM) ---

    function updateCard() {
        const el = document.getElementById('cardTitle');
        if(el) el.innerText = document.getElementById('inputTitle').value;
    }

    function updateBrand() {
        const el = document.getElementById('brandNameDisplay');
        if(el) el.innerText = document.getElementById('brandInput').value;
    }

    function updateBadgeText() {
        const val = document.getElementById('badgeTextInput').value;
        const el = document.getElementById('textBadge');
        if(el) el.innerText = val;
        
        // Ensure text is shown, logo hidden
        const logoWrapper = document.getElementById('logoWrapper');
        if(logoWrapper) logoWrapper.classList.add('hidden');
        if(el) el.classList.remove('hidden');
        currentData.logo = null;
    }

    function uploadLogo() {
        const file = document.getElementById('logoInput').files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                currentData.logo = e.target.result;
                const logoImg = document.getElementById('logoImg');
                const logoWrapper = document.getElementById('logoWrapper');
                const textBadge = document.getElementById('textBadge');
                
                if(logoImg) logoImg.src = e.target.result;
                if(logoWrapper) logoWrapper.classList.remove('hidden');
                if(textBadge) textBadge.classList.add('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetLogo() {
        document.getElementById('logoInput').value = "";
        currentData.logo = null;
        const logoWrapper = document.getElementById('logoWrapper');
        const textBadge = document.getElementById('textBadge');
        if(logoWrapper) logoWrapper.classList.add('hidden');
        if(textBadge) textBadge.classList.remove('hidden');
    }

    // --- STYLING LOGIC ---

    function updateFontSize(size) {
        const el = document.getElementById('cardTitle');
        if(el) el.style.fontSize = size + "px";
    }

    function updateTitleColor(color) {
        const el = document.getElementById('cardTitle');
        if(el) {
            el.style.color = color;
            el.classList.remove('text-black', 'text-white', 'text-gray-900');
        }
    }

    function setTextAlign(align) {
        const wrapper = document.getElementById('textWrapper');
        const cardBody = document.getElementById('cardBody'); // Only exists in Classic
        const title = document.getElementById('cardTitle');

        if(currentLayoutType === 'classic' && cardBody) {
             cardBody.classList.remove('text-left', 'text-center', 'text-right', 'items-start', 'items-center', 'items-end');
             wrapper.classList.remove('items-start', 'items-center', 'items-end');
             if(align === 'left') { cardBody.classList.add('text-left', 'items-start'); wrapper.classList.add('items-start'); }
             else if(align === 'center') { cardBody.classList.add('text-center', 'items-center'); wrapper.classList.add('items-center'); }
             else { cardBody.classList.add('text-right', 'items-end'); wrapper.classList.add('items-end'); }
        } else {
            // Generic alignment for other templates
            title.classList.remove('text-left', 'text-center', 'text-right');
            title.classList.add('text-' + align);
        }
    }

    function updateRadius() {
        const val = document.getElementById('radiusRange').value;
        const container = document.getElementById('canvas-container');
        container.style.borderRadius = val + 'px';
    }

    // --- CLASSIC SPECIFIC ---
    function setLayout(type) {
        if(currentLayoutType !== 'classic') return;

        const imagePart = document.getElementById('imagePart');
        const cardBody = document.getElementById('cardBody');
        const overlay = document.getElementById('overlayGradient');
        const title = document.getElementById('cardTitle');

        // Reset Base
        cardBody.className = "relative bg-white text-black px-12 pt-5 pb-6 flex flex-col justify-center items-center text-center w-full transition-all duration-500";
        overlay.classList.add('hidden');
        imagePart.className = "relative overflow-hidden group bg-gray-100 transition-all duration-500 w-full";
        title.style.color = ""; // reset color

        if (type === 'classic') {
            imagePart.classList.add('h-[60%]'); cardBody.classList.add('h-[40%]');
        } else if (type === 'cinematic') {
            imagePart.classList.add('h-[70%]'); cardBody.classList.add('h-[30%]');
        } else if (type === 'square') {
            imagePart.classList.add('h-[50%]'); cardBody.classList.add('h-[50%]');
        } else if (type === 'full') {
            imagePart.classList.remove('h-[60%]'); imagePart.classList.add('h-full');
            cardBody.className = "absolute bottom-0 w-full h-auto px-12 pb-16 pt-32 flex flex-col bg-transparent text-white z-20";
            overlay.classList.remove('hidden');
            title.style.color = "white";
        }
    }

    function setTheme(bgClass, textClass, accentClass, mode) {
        if(currentLayoutType !== 'classic') return;
        const cardBody = document.getElementById('cardBody');
        const decoLine = document.getElementById('decoLine');
        const textBadge = document.getElementById('textBadge');

        cardBody.className = cardBody.className.replace(/bg-\S+/g, bgClass);
        cardBody.className = cardBody.className.replace(/text-\S+/g, textClass);
        if(decoLine) decoLine.className = `w-24 h-3 mb-4 rounded-full transition-colors duration-300 ${accentClass}`;
        if(textBadge) {
             if(mode === 'white') textBadge.className = `bg-red-600 text-white px-8 py-3 text-4xl font-bold uppercase tracking-widest shadow-lg rounded-md`;
             else textBadge.className = `${accentClass} text-gray-900 px-8 py-3 text-4xl font-bold uppercase tracking-widest shadow-lg rounded-md`;
        }
    }

    // --- DOWNLOAD ---
    function downloadCard() {
        const originalNode = document.getElementById("canvas-container");
        const btn = document.getElementById('downloadBtn');
        const originalText = btn.innerHTML;
       
        btn.innerHTML = "⏳ রেন্ডারিং...";
        btn.disabled = true;
        btn.classList.add('opacity-75');

        const clone = originalNode.cloneNode(true);
        clone.style.transform = "none";
        clone.style.position = "fixed";
        clone.style.top = "-10000px";
        clone.style.left = "-10000px";
        clone.style.zIndex = "9999";
        
        document.body.appendChild(clone);
        
        // Wait for images in clone to be ready
        setTimeout(() => {
            html2canvas(clone, {
                scale: 1, 
                width: 1080, 
                height: 1080, 
                useCORS: true, 
                allowTaint: true, 
                backgroundColor: null 
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'News_' + Date.now() + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                document.body.removeChild(clone);
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-75');
            });
        }, 800);
    }
</script>
@endsection