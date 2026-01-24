@extends('layouts.app')

@section('content')
{{-- 🔥 TinyMCE Script --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    .skeleton { background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
    .tox-tinymce-aux { z-index: 99999 !important; }
</style>

{{-- Header --}}
<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 font-bangla flex items-center gap-2">
        📰 আজকের তাজা খবর (Raw News)
        <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-200 shadow-sm">{{ $newsItems->total() }}</span>
    </h2>
    <div class="flex items-center gap-3">
        <div id="loadingIndicator" class="hidden items-center gap-2 text-indigo-600 text-sm font-bold bg-indigo-50 px-3 py-1.5 rounded-full border border-indigo-100 animate-pulse">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Scraping...
        </div>
        <button onclick="window.location.reload()" class="bg-white border border-gray-300 text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-bold flex items-center gap-1 shadow-sm">
            🔄 Refresh
        </button>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">{{ session('success') }}</div>
@endif

{{-- Main Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-2" id="mainNewsGrid">
    @foreach($newsItems as $item)
    <div id="news-card-{{ $item->id }}" class="group relative bg-white rounded-2xl transition-all duration-500 hover:-translate-y-2 flex flex-col h-full overflow-hidden border border-slate-100 hover:border-indigo-300/50 shadow-sm hover:shadow-xl">
        
        {{-- Status Badge --}}
        <div class="absolute top-4 right-4 z-20 flex flex-col items-end gap-2">
            @if($item->status == 'processing')
                <div class="bg-amber-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5 animate-pulse">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    AI WRITING...
                </div>
            @else
                <div class="bg-slate-600 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5 border border-white/20">
                    RAW NEWS
                </div>
            @endif
        </div>

        {{-- Image --}}
        <div class="h-48 overflow-hidden relative bg-slate-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent opacity-80"></div>
            @else
                <div class="flex items-center justify-center h-full bg-slate-50 text-slate-300 text-xs font-bold uppercase">No Image</div>
            @endif
            <div class="absolute bottom-3 left-3 z-10">
                <span class="bg-white/95 text-[10px] font-extrabold px-3 py-1 rounded-full text-slate-800 shadow-lg flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    {{ $item->website->name ?? 'UNKNOWN' }}
                </span>
            </div>
        </div>
        
        {{-- Body --}}
        <div class="p-5 flex flex-col flex-1 bg-white relative">
            <h3 class="text-[17px] font-bold leading-snug mb-3 text-slate-800 font-bangla line-clamp-2 group-hover:text-indigo-600">
                {{ $item->title }}
            </h3>
            
            <div class="text-[11px] font-medium text-slate-400 flex items-center justify-between mb-6">
                <span class="bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100">
                    {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}
                </span>
                <a href="{{ $item->original_link }}" target="_blank" class="text-slate-400 hover:text-indigo-500 flex items-center gap-1">
                    SOURCE <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>

            {{-- Action Buttons (Modified Logic) --}}
            <div class="mt-auto pt-4 border-t border-dashed border-slate-100 space-y-3">
                
                {{-- 1. Studio Button (Always Available) --}}
                <a href="{{ route('news.studio', $item->id) }}" class="w-full bg-slate-50 hover:bg-white text-slate-600 border border-slate-200 hover:border-indigo-300 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group/studio">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    OPEN STUDIO
                </a>

                @if($item->status == 'processing')
                    {{-- Processing State --}}
                    <button disabled class="w-full bg-slate-100 text-slate-400 py-2.5 rounded-xl border border-slate-200 text-xs font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        AI is Writing...
                    </button>
                @else
                    {{-- Default State: AI & Edit Only (No Publish) --}}
                    <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="col-span-2">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            {{-- AI Button --}}
                            <button onclick="startAiProcess({{ $item->id }}, this)" class="bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl font-bold text-xs shadow-lg flex items-center justify-center gap-1.5 border border-slate-700">
                                <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                AI MAGIC
                            </button>

                            {{-- Manual Edit Button --}}
                            <input type="hidden" id="raw-title-{{ $item->id }}" value="{{ $item->title }}">
                            <input type="hidden" id="raw-image-{{ $item->id }}" value="{{ $item->thumbnail_url }}">
                            <div id="raw-content-{{ $item->id }}" style="display:none;">{!! $item->content !!}</div>

                            <button onclick="openManualModal({{ $item->id }})" type="button" class="bg-white border-2 border-slate-200 text-slate-700 hover:border-indigo-600 hover:text-indigo-600 py-2.5 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                EDIT
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Delete --}}
                <div class="flex justify-end pt-2">
                    <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this news?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="opacity-40 hover:opacity-100 text-rose-500 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
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

<div class="mt-8">{{ $newsItems->links() }}</div>

{{-- MANUAL EDIT MODAL --}}
<div id="manualEditModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">📝 Edit & Save to Drafts</h3>
            <button onclick="closeManualModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1">
            <input type="hidden" id="manualNewsId">
            
            <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                <label class="block text-sm font-bold text-gray-700 mb-2">Image</label>
                <div class="flex gap-4 items-start">
                    <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border">
                        <img id="manualPreviewImg" src="" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        {{-- Disabled file input for now, just informative --}}
                        <p class="text-xs text-gray-500 mb-2">Images can be changed in Drafts/Studio.</p>
                        <input type="url" id="manualImageUrl" class="w-full border border-gray-300 rounded p-2 text-xs" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                <input type="text" id="manualTitle" class="w-full border border-gray-300 rounded-lg p-3 font-bold text-gray-800">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Content</label>
                <textarea id="manualContent" rows="15" class="w-full border border-gray-300 rounded-lg p-3 text-sm"></textarea>
            </div>
        </div>

        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeManualModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200">Cancel</button>
            {{-- 🔥 SAVE TO DRAFT BUTTON --}}
            <button onclick="submitManualDraft()" id="btnManualPub" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-lg flex items-center gap-2">
                💾 Save to Drafts
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('scraping')) startScrapingMonitor();
    });

    function initTinyMCE() {
        if (tinymce.get('manualContent')) tinymce.get('manualContent').remove();
        tinymce.init({
            selector: '#manualContent',
            height: 400,
            plugins: 'link lists code wordcount',
            toolbar: 'undo redo | blocks | bold italic | bullist numlist | link | code',
            menubar: false,
            statusbar: true
        });
    }

    // --- Scraping Monitor ---
    function startScrapingMonitor() {
        showLoading(); 
        let checkCount = 0;
        const poller = setInterval(() => {
            checkCount++;
            const forceWait = checkCount <= 3 ? 'true' : 'false';
            fetch(`{{ route('news.check-scrape-status') }}?force_wait=${forceWait}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.scraping && checkCount > 3) {
                        clearInterval(poller);
                        finishLoading();
                    }
                });
        }, 2000);
    }

    function showLoading() {
        const indicator = document.getElementById('loadingIndicator');
        if(indicator) indicator.classList.remove('hidden'); indicator.classList.add('flex');
        document.getElementById('mainNewsGrid')?.classList.add('opacity-50', 'pointer-events-none');
    }

    function finishLoading() {
        window.location.href = "{{ route('news.index') }}";
    }

    // --- Manual Edit Logic ---
    function openManualModal(id) {
        const title = document.getElementById(`raw-title-${id}`).value;
        const image = document.getElementById(`raw-image-${id}`).value;
        const content = document.getElementById(`raw-content-${id}`).innerHTML;
        
        document.getElementById('manualNewsId').value = id;
        document.getElementById('manualTitle').value = title;
        document.getElementById('manualContent').value = content;
        document.getElementById('manualPreviewImg').src = image || 'https://via.placeholder.com/150';
        document.getElementById('manualImageUrl').value = image;
        
        const modal = document.getElementById('manualEditModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            initTinyMCE();
            if(tinymce.get('manualContent')) tinymce.get('manualContent').setContent(content);
        }, 100);
    }

    function closeManualModal() {
        const modal = document.getElementById('manualEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (tinymce.get('manualContent')) tinymce.get('manualContent').remove();
    }

    function submitManualDraft() {
        const id = document.getElementById('manualNewsId').value;
        const title = document.getElementById('manualTitle').value;
        let content = tinymce.get('manualContent') ? tinymce.get('manualContent').getContent() : document.getElementById('manualContent').value;
        const btn = document.getElementById('btnManualPub');

        if(!title || !content) { alert("Title and Content required!"); return; }

        btn.disabled = true;
        btn.innerHTML = "Saving...";

        fetch(`/news/${id}/update-draft`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ title, content })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeManualModal();
                // Remove card from UI
                const card = document.getElementById(`news-card-${id}`);
                if (card) {
                    card.style.transition = "all 0.5s ease";
                    card.style.opacity = "0";
                    card.style.transform = "translateX(100px)";
                    setTimeout(() => card.remove(), 500);
                }
                alert("✅ Saved to Drafts!");
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = "💾 Save to Drafts";
        });
    }

    // --- AI Logic ---
    function startAiProcess(id, btn) {
        // Form submit automatically handles the request, 
        // Controller will set status to 'processing' and redirect back.
    }
</script>
@endsection