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
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="mainNewsGrid">
    @foreach($newsItems as $item)
    <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden transform hover:-translate-y-1 relative">
        
        {{-- Status Badge --}}
        @if($item->is_posted)
            <div class="absolute top-3 right-3 z-20 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1 backdrop-blur-md">
                ✅ POSTED
            </div>
        @elseif($item->status == 'processing')
            <div class="absolute top-3 right-3 z-20 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm animate-pulse flex items-center gap-1">
                ⏳ PROCESSING
            </div>
        @elseif($item->status == 'draft')
            <div class="absolute top-3 right-3 z-20 bg-purple-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                📝 DRAFT READY
            </div>
        @endif

        {{-- Image --}}
        <div class="h-48 overflow-hidden relative bg-gray-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            @else
                <div class="flex items-center justify-center h-full bg-slate-100 text-slate-400 flex-col gap-2">
                    <span class="text-2xl">📷</span>
                    <span class="text-xs">No Image</span>
                </div>
            @endif
            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-[10px] font-bold px-2 py-1 rounded-md text-indigo-700 shadow-sm z-10 uppercase tracking-wider">
                {{ $item->website->name ?? 'Unknown Source' }}
            </span>
        </div>
       
        {{-- Content --}}
        <div class="p-5 flex flex-col flex-1">
            <h3 class="text-[17px] font-bold leading-snug mb-3 text-gray-800 font-bangla line-clamp-2 group-hover:text-indigo-600 transition-colors" title="{{ $item->ai_title ?? $item->title }}">
                {{ $item->ai_title ?? $item->title }}
            </h3>
            
            <div class="text-[11px] text-gray-400 flex items-center gap-2 mb-4">
                <span class="bg-gray-50 border border-gray-100 px-2 py-1 rounded flex items-center gap-1">
                    🕒 {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}
                </span>
            </div>

            <div class="mt-auto grid grid-cols-2 gap-2 pt-4 border-t border-gray-50">
                {{-- Studio Button --}}
                <a href="{{ route('news.studio', $item->id) }}" 
                   class="col-span-2 bg-gradient-to-r from-indigo-50 to-indigo-100 text-indigo-700 border border-indigo-200 py-2.5 rounded-lg text-xs font-bold hover:shadow-md transition flex items-center justify-center gap-2 hover:bg-white">
                    🎨 ডিজাইন করুন
                </a>
                
                {{-- Action Buttons Logic --}}
                @if($item->is_posted)
                    <button class="col-span-2 bg-emerald-50 text-emerald-600 py-2 rounded-lg border border-emerald-100 text-xs font-bold cursor-default opacity-75 flex items-center justify-center gap-1">
                        Already Posted
                    </button>

                @elseif($item->status == 'publishing')
                    <button class="col-span-2 bg-blue-50 text-blue-600 py-2 rounded-lg border border-blue-100 text-xs font-bold animate-pulse cursor-wait flex items-center justify-center gap-1">
                        🚀 Publishing...
                    </button>

                @elseif($item->status == 'draft')
                    <button type="button" 
                        onclick="openPublishModal({{ $item->id }})"
                        class="col-span-2 bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition text-xs font-bold flex items-center justify-center gap-1 shadow-sm shadow-purple-200">
                        📝 Review & Publish
                    </button>

                @elseif($item->status == 'processing')
                    <button disabled class="col-span-2 bg-amber-50 text-amber-700 py-2 rounded-lg border border-amber-100 text-xs font-bold cursor-wait opacity-80 flex items-center justify-center gap-1">
                        ⏳ AI Writing...
                    </button>

                @else
                    <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="col-span-2">
                        @csrf
                        <button type="submit" 
                            class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-900 transition text-xs font-bold flex items-center justify-center gap-2 shadow-sm hover:shadow-lg">
                            🤖 AI Rewrite
                        </button>
                    </form>
                @endif
                
                <div class="col-span-2 flex justify-between items-center mt-1 px-1">
                    <a href="{{ $item->original_link }}" target="_blank" class="text-[10px] text-gray-400 hover:text-indigo-500 font-medium hover:underline flex items-center gap-1">
                        🔗 Source
                    </a>
                    
                    <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[10px] text-red-300 hover:text-red-500 font-bold transition p-1 rounded hover:bg-red-50">
                            🗑️ DEL
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
</script>

@endsection