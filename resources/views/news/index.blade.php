@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    /* Skeleton Animation */
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .skeleton {
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }
</style>

{{-- Header Section --}}
<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 font-bangla flex items-center gap-2">
        📰 আজকের তাজা খবর 
        <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-200 shadow-sm" id="newsCount">{{ $newsItems->total() }}</span>
    </h2>

    {{-- 🔥 Refresh & Loading Indicator --}}
    <div class="flex items-center gap-3">
        <div id="loadingIndicator" class="hidden items-center gap-2 text-indigo-600 text-sm font-bold bg-indigo-50 px-3 py-1.5 rounded-full border border-indigo-100 animate-pulse">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Fetching New News...
        </div>
        
        <button onclick="window.location.reload()" class="bg-white border border-gray-300 text-gray-600 hover:text-indigo-600 hover:border-indigo-300 px-3 py-2 rounded-lg shadow-sm transition text-sm font-bold flex items-center gap-1">
            🔄 Refresh
        </button>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in-down">
        {{ session('success') }}
    </div>
@endif

{{-- 🔥 SKELETON LOADER (Initially Hidden) --}}
<div id="skeletonGrid" class="hidden grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
    @for($i=0; $i<4; $i++)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-96 overflow-hidden">
        <div class="h-48 w-full skeleton"></div>
        <div class="p-5 space-y-3">
            <div class="h-4 skeleton rounded w-3/4"></div>
            <div class="h-4 skeleton rounded w-1/2"></div>
            <div class="mt-4 h-8 skeleton rounded w-full"></div>
            <div class="h-8 skeleton rounded w-full"></div>
        </div>
    </div>
    @endfor
</div>



{{-- Main News Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-2" id="mainNewsGrid">
    @foreach($newsItems as $item)
    <div id="news-card-{{ $item->id }}" class="group relative bg-white rounded-2xl transition-all duration-500 hover:-translate-y-2 flex flex-col h-full overflow-hidden border border-slate-100 hover:border-indigo-300/50 shadow-[0_2px_20px_-5px_rgba(0,0,0,0.05)] hover:shadow-[0_20px_40px_-10px_rgba(99,102,241,0.15)]">
        
        {{-- Glassy Status Badge --}}
        <div class="absolute top-4 right-4 z-20 flex flex-col items-end gap-2">
            @if($item->is_posted)
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg shadow-[0_4px_15px_-3px_rgba(16,185,129,0.4)] flex items-center gap-1.5 backdrop-blur-md border border-white/20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    PUBLISHED
                </div>
            @elseif($item->status == 'processing')
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg shadow-[0_4px_15px_-3px_rgba(245,158,11,0.4)] flex items-center gap-1.5 animate-pulse border border-white/20">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    AI PROCESSING
                </div>
            @elseif($item->status == 'draft')
                <div class="bg-gradient-to-r from-violet-600 to-indigo-600 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg shadow-[0_4px_15px_-3px_rgba(124,58,237,0.4)] flex items-center gap-1.5 border border-white/20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    DRAFT READY
                </div>
            @endif
        </div>

        {{-- Image Area with Gradient Overlay --}}
        <div class="h-48 overflow-hidden relative bg-slate-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                {{-- Premium Dark Gradient at bottom --}}
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent opacity-80"></div>
            @else
                <div class="flex items-center justify-center h-full bg-slate-50 relative overflow-hidden group-hover:bg-slate-100 transition-colors">
                    <div class="absolute inset-0 opacity-[0.05] bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:12px_12px]"></div>
                    <div class="z-10 flex flex-col items-center gap-2 text-slate-300">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-60">No Visual</span>
                    </div>
                </div>
            @endif
            
            {{-- Floating Source Badge --}}
            <div class="absolute bottom-3 left-3 z-10">
                <span class="bg-white/95 backdrop-blur text-[10px] font-extrabold px-3 py-1 rounded-full text-slate-800 shadow-lg flex items-center gap-1.5 border border-white/50">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    {{ $item->website->name ?? 'UNKNOWN' }}
                </span>
            </div>
        </div>
        
        {{-- Content Body --}}
        <div class="p-5 flex flex-col flex-1 bg-white relative">
            {{-- Title --}}
            <h3 class="text-[17px] font-bold leading-snug mb-3 text-slate-800 font-bangla line-clamp-2 group-hover:text-indigo-600 transition-colors">
                {{ $item->ai_title ?? $item->title }}
            </h3>
            
            {{-- Meta Info --}}
            <div class="text-[11px] font-medium text-slate-400 flex items-center justify-between mb-6">
                <span class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100 text-slate-500">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}
                </span>
                
                <a href="{{ $item->original_link }}" target="_blank" class="group/link flex items-center gap-1 text-slate-400 hover:text-indigo-500 transition-colors">
                    ORIGINAL
                    <svg class="w-3 h-3 group-hover/link:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>

            {{-- Action Footer --}}
            <div class="mt-auto pt-4 border-t border-dashed border-slate-100 space-y-3">
                
                {{-- Studio Button --}}
                <a href="{{ route('news.studio', $item->id) }}" 
                   class="relative w-full overflow-hidden bg-slate-50 hover:bg-white text-slate-600 border border-slate-200 hover:border-indigo-300 py-2.5 rounded-xl text-xs font-bold transition-all duration-300 flex items-center justify-center gap-2 group/studio shadow-sm hover:shadow-md">
                   <div class="absolute inset-0 w-1/2 h-full bg-gradient-to-r from-transparent via-white/50 to-transparent skew-x-12 -translate-x-full group-hover/studio:translate-x-[200%] transition-transform duration-1000"></div>
                   <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                   OPEN STUDIO
                </a>
                
                {{-- Action Logic --}}
                @if($item->is_posted)
                    <div class="w-full bg-emerald-50/50 text-emerald-600 py-2.5 rounded-xl border border-emerald-100 text-xs font-bold flex items-center justify-center gap-2 cursor-default">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Already Published
                    </div>

                @elseif($item->status == 'publishing')
                    <div class="w-full bg-blue-50/50 text-blue-600 py-2.5 rounded-xl border border-blue-100 text-xs font-bold flex items-center justify-center gap-2 cursor-wait">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Publishing...
                    </div>

                @elseif($item->status == 'draft')
                    <button type="button" 
                        onclick="openPublishModal({{ $item->id }})"
                        class="w-full bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white py-3 rounded-xl transition-all duration-300 text-xs font-extrabold flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transform hover:scale-[1.02]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        PUBLISH NOW
                    </button>

                @elseif($item->status == 'processing')
                    <button disabled class="w-full bg-slate-100 text-slate-400 py-2.5 rounded-xl border border-slate-200 text-xs font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                        Writing...
                    </button>

                @else
                    {{-- Default State: AI & Edit Buttons --}}
                    <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="col-span-2">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            {{-- AI Button (Premium Gradient) --}}
                            <button onclick="startAiProcess({{ $item->id }}, this)" 
                                class="bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl font-bold text-xs shadow-lg shadow-slate-500/20 transition-all duration-300 flex items-center justify-center gap-1.5 transform hover:scale-[1.02] border border-slate-700">
                                <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                AI MAGIC
                            </button>

                            {{-- Edit Button (Outlined) --}}
                            <button onclick="openManualModal({{ $item->id }}, '{{ addslashes($item->title) }}', `{{ addslashes(strip_tags($item->content)) }}`)" 
                                    type="button"
                                    class="bg-white border-2 border-slate-200 text-slate-700 hover:border-indigo-600 hover:text-indigo-600 py-2.5 rounded-xl font-bold text-xs transition-all duration-300 flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                EDIT
                            </button>
                        </div>
                    </form>
                @endif
                
                {{-- Delete Button (Minimal) --}}
                <div class="flex justify-end pt-2">
                    <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="opacity-40 hover:opacity-100 hover:bg-rose-50 text-rose-500 transition-all p-1.5 rounded-md flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Delete
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-8">
    {{ $newsItems->links() }}
</div>

{{-- PUBLISH MODAL (Same as before) --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden flex flex-col max-h-[90vh] transform scale-100 transition-transform">
        <div class="bg-white px-6 py-4 flex justify-between items-center border-b border-gray-100">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">📝 Review Draft</h3>
            <button onclick="closeRewriteModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 font-bangla text-lg text-gray-900 bg-white shadow-sm transition-shadow">
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Content</label>
                <textarea id="previewContent" rows="10" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 font-bangla text-sm text-gray-900 bg-white shadow-sm leading-relaxed transition-shadow"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">WP Category ID (Optional)</label>
                <input type="number" id="previewCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-gray-900 bg-white shadow-sm focus:ring-indigo-500" placeholder="e.g. 5">
            </div>
        </div>

        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
            <button onclick="confirmPublish()" id="btnPublish" class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow-lg hover:shadow-green-500/30 flex items-center gap-2 text-sm transition transform active:scale-95">
                🚀 Publish Now
            </button>
        </div>
    </div>
</div>





<div id="manualEditModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-fade-in-up">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">📝 Manual Edit & Publish</h3>
            <button onclick="closeManualModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        </div>
        
        <div class="p-6">
            <input type="hidden" id="manualNewsId">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                <input type="text" id="manualTitle" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-emerald-500 font-bold text-gray-800">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Content</label>
                <textarea id="manualContent" rows="10" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-emerald-500 text-sm"></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeManualModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200">Cancel</button>
                <button onclick="submitManualPublish()" id="btnManualPub" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 shadow-lg flex items-center gap-2">
                    🚀 Save & Publish
                </button>
            </div>
        </div>
    </div>
</div>





<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        const isServerScraping = @json($isScraping ?? false);

        if (urlParams.has('scraping') || isServerScraping) {
            startScrapingMonitor();
        }
    });

    // ==========================================
    // 🔥 PART 1: SCRAPING MONITOR & LOADING UI
    // ==========================================

    function startScrapingMonitor() {
        showLoading(); // শো লোডিং এবং স্কেলিটন

        let checkCount = 0;

        // প্রতি ২ সেকেন্ড পর পর চেক করবে
        const poller = setInterval(() => {
            checkCount++;

            // প্রথম ৩ বার (৬ সেকেন্ড) সার্ভার রেসপন্স যাই হোক, আমরা থামবো না (সেফটি)
            const forceWait = checkCount <= 3 ? 'true' : 'false';

			fetch(`{{ route('news.check-scrape-status') }}?force_wait=${forceWait}`)
                .then(res => res.json())
                .then(data => {
                    // যদি সার্ভার বলে scraping: false এবং আমাদের মিনিমাম চেক (৩ বার) শেষ হয়
                    if (!data.scraping && checkCount > 3) {
                        clearInterval(poller);
                        finishLoading();
                    }
                })
                .catch(err => console.error("Polling error:", err));
        }, 2000);
    }

    function showLoading() {
        const indicator = document.getElementById('loadingIndicator');
        if (indicator) {
            indicator.classList.remove('hidden');
            indicator.classList.add('flex');
            // স্পিনার সহ লোডিং টেক্সট
            indicator.innerHTML = `
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span id="loadingText" class="ml-2">Fetching New News... (Please wait)</span>
            `;
        }

        // স্কেলিটন গ্রিড দেখানো এবং মেইন গ্রিড ঝাপসা করা
        const skeleton = document.getElementById('skeletonGrid');
        const mainGrid = document.getElementById('mainNewsGrid');

        if (skeleton) {
            skeleton.classList.remove('hidden');
            skeleton.classList.add('grid');
        }
        if (mainGrid) {
            mainGrid.classList.add('opacity-50', 'pointer-events-none');
        }
    }

    function finishLoading() {
        const indicator = document.getElementById('loadingIndicator');
        if (indicator) {
            indicator.innerHTML = `
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-green-700 font-bold ml-2">Done! Reloading...</span>
            `;
            indicator.classList.replace('bg-indigo-50', 'bg-green-50');
            indicator.classList.replace('border-indigo-100', 'border-green-200');
            indicator.classList.remove('animate-pulse');
        }

        // ১ সেকেন্ড পর পেজ রিলোড
        setTimeout(() => {
            window.location.href = "{{ route('news.index') }}";
        }, 1000);
    }


    // ==========================================
    // 🔥 PART 2: DRAFT MODAL LOGIC
    // ==========================================

    function openPublishModal(id) {
        const modal = document.getElementById('rewriteModal');
        const titleInput = document.getElementById('previewTitle');
        const contentInput = document.getElementById('previewContent');
        const idInput = document.getElementById('previewNewsId');
        const btn = document.getElementById('btnPublish');

        // রিসেট UI
        titleInput.value = "Loading...";
        contentInput.value = "Fetching draft...";
        idInput.value = id;
        btn.disabled = true;

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`/news/${id}/get-draft`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    titleInput.value = data.title;
                    contentInput.value = data.content;
                } else {
                    titleInput.value = "Error";
                    contentInput.value = "Could not fetch draft.";
                }
                btn.disabled = false;
            })
            .catch(err => {
                console.error(err);
                titleInput.value = "Error";
                contentInput.value = "Network Error.";
                btn.disabled = false;
            });
    }

    function confirmPublish() {
        const id = document.getElementById('previewNewsId').value;
        const title = document.getElementById('previewTitle').value;
        const content = document.getElementById('previewContent').value;
        const category = document.getElementById('previewCategory').value;
        const btn = document.getElementById('btnPublish');

        btn.innerText = "Publishing...";
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        fetch(`/news/${id}/confirm-publish`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title,
                    content,
                    category
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("✅ পাবলিশিং শুরু হয়েছে!");
                    location.reload();
                } else {
                    alert("❌ Failed: " + data.message);
                    btn.innerText = "🚀 Publish Now";
                    btn.disabled = false;
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerText = "🚀 Publish Now";
                btn.disabled = false;
            });
    }

    function closeRewriteModal() {
        const modal = document.getElementById('rewriteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
	
	
	
	
	
	
	
	
	function openManualModal(id, title, content) {
        document.getElementById('manualNewsId').value = id;
        document.getElementById('manualTitle').value = title;
        document.getElementById('manualContent').value = content; // ট্যাগ স্ট্রিপ করা কন্টেন্ট আসবে
        
        const modal = document.getElementById('manualEditModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // মডাল বন্ধ করা
    function closeManualModal() {
        const modal = document.getElementById('manualEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ডাটা সাবমিট করা
    function submitManualPublish() {
        const id = document.getElementById('manualNewsId').value;
        const title = document.getElementById('manualTitle').value;
        const content = document.getElementById('manualContent').value;
        const btn = document.getElementById('btnManualPub');

        if(!title || !content) {
            alert("Title and Content cannot be empty!");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = "Processing...";

        fetch(`/news/${id}/manual-publish`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title: title, content: content })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // মডাল বন্ধ করা
                closeManualModal();
                
                // 🔥 ইনডেক্স পেজ থেকে কার্ড রিমুভ করা (এনিমেশন সহ)
                const card = document.getElementById(`news-card-${id}`);
                if (card) {
                    card.style.transition = "all 0.5s ease";
                    card.style.opacity = "0";
                    card.style.transform = "translateX(100px)";
                    setTimeout(() => card.remove(), 500);
                }
                alert("✅ " + data.message);
            } else {
                alert("❌ Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Something went wrong!");
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = "🚀 Save & Publish";
        });
    }
	
	
	
	
	
</script>

@endsection