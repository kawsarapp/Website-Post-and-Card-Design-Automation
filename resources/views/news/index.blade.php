@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
</style>

{{-- Header & Stats (Same as before) --}}
{{-- ... --}}

@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
        {{ session('success') }}
    </div>
@endif

{{-- News Grid Section --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mt-6">
    @foreach($newsItems as $item)
    <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden transform hover:-translate-y-1 relative">
        
        {{-- Status Badge --}}
        @if($item->is_posted)
            <div class="absolute top-3 right-3 z-20 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                ✅ POSTED
            </div>
        @elseif($item->status == 'processing')
            <div class="absolute top-3 right-3 z-20 bg-yellow-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm animate-pulse">
                ⏳ PROCESSING
            </div>
        @elseif($item->status == 'draft')
            <div class="absolute top-3 right-3 z-20 bg-purple-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                📝 DRAFT READY
            </div>
        @endif

        {{-- Image --}}
        <div class="h-48 overflow-hidden relative bg-gray-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            @else
                <div class="flex items-center justify-center h-full bg-slate-100 text-slate-400">📷 No Image</div>
            @endif
            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-xs font-bold px-2 py-1 rounded-md text-indigo-700 shadow-sm z-10">
                {{ $item->website->name ?? 'Unknown Source' }}
            </span>
        </div>
       
        {{-- Content --}}
        <div class="p-5 flex flex-col flex-1">
            <h3 class="text-lg font-bold leading-snug mb-3 text-gray-800 font-bangla line-clamp-2 group-hover:text-indigo-600 transition-colors">
                {{-- ড্রাফট থাকলে AI টাইটেল দেখাবে, না হলে আসল টাইটেল --}}
                {{ $item->ai_title ?? $item->title }}
            </h3>
            
            <div class="text-xs text-gray-500 flex items-center gap-2 mb-4">
                <span class="bg-gray-100 px-2 py-1 rounded">📅 {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}</span>
            </div>

            <div class="mt-auto grid grid-cols-2 gap-2">
                {{-- Studio Button --}}
                <a href="{{ route('news.studio', $item->id) }}" 
                   class="col-span-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-2.5 rounded-lg text-sm font-bold hover:shadow-lg transition flex items-center justify-center gap-2 active:scale-95">
                    🎨 ডিজাইন করুন
                </a>
                
                {{-- Action Buttons Logic --}}
                @if($item->is_posted)
                    <button class="col-span-2 bg-green-50 text-green-600 py-2 rounded-lg border border-green-200 text-sm font-semibold cursor-default opacity-75">
                        Already Posted
                    </button>

                @elseif($item->status == 'publishing')
                    <button class="col-span-2 bg-blue-50 text-blue-600 py-2 rounded-lg border border-blue-200 text-sm font-semibold animate-pulse cursor-wait">
                        🚀 Publishing...
                    </button>

                @elseif($item->status == 'draft')
                    {{-- ৩. ড্রাফট রেডি হলে রিভিউ বাটন --}}
                    <button type="button" 
                        onclick="openPublishModal({{ $item->id }})"
                        class="col-span-2 bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition text-xs font-bold flex items-center justify-center gap-1 shadow-sm">
                        📝 Review & Publish
                    </button>

                @elseif($item->status == 'processing')
                    {{-- ২. প্রসেসিং অবস্থায় বাটন ডিজেবল --}}
                    <button disabled class="col-span-2 bg-yellow-100 text-yellow-700 py-2 rounded-lg text-xs font-bold cursor-wait opacity-80">
                        ⏳ AI Writing...
                    </button>

                @else
                    {{-- ১. নতুন নিউজ: AI Post বাটন --}}
                    <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="col-span-2">
                        @csrf
                        <button type="submit" 
                            class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-900 transition text-xs font-bold flex items-center justify-center gap-1 shadow-sm">
                            🤖 AI Post
                        </button>
                    </form>
                @endif
                
                <a href="{{ $item->original_link }}" target="_blank" class="col-span-2 text-xs text-center text-gray-400 hover:text-indigo-500 mt-1">
                    🔗 মূল খবর
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-8">
    {{ $newsItems->links() }}
</div>

{{-- PUBLISH MODAL --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg">📝 Review Draft & Publish</h3>
            <button onclick="closeRewriteModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto flex-1">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500 font-bangla text-lg text-gray-900 bg-white">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Content</label>
                <textarea id="previewContent" rows="10" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500 font-bangla text-sm text-gray-900 bg-white"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Category ID</label>
                <input type="number" id="previewCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-gray-900 bg-white" placeholder="Optional">
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300">Cancel</button>
            <button onclick="confirmPublish()" id="btnPublish" class="px-6 py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-2">
                🚀 Publish Now
            </button>
        </div>
    </div>
</div>

<script>
    // ড্রাফট লোড করা (AI Call হবে না)
    function openPublishModal(id) {
        const modal = document.getElementById('rewriteModal');
        const titleInput = document.getElementById('previewTitle');
        const contentInput = document.getElementById('previewContent');
        const idInput = document.getElementById('previewNewsId');
        const btn = document.getElementById('btnPublish');

        titleInput.value = "Loading...";
        contentInput.value = "Fetching draft...";
        idInput.value = id;
        btn.disabled = true;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // ডাটাবেস থেকে সেভ করা ড্রাফট আনা
        fetch(`/news/${id}/get-draft`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
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

    // ফাইনাল পাবলিশ
    function confirmPublish() {
        const id = document.getElementById('previewNewsId').value;
        const title = document.getElementById('previewTitle').value;
        const content = document.getElementById('previewContent').value;
        const category = document.getElementById('previewCategory').value;
        const btn = document.getElementById('btnPublish');

        btn.innerText = "Publishing...";
        btn.disabled = true;

        fetch(`/news/${id}/confirm-publish`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title, content, category })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("✅ পাবলিশিং শুরু হয়েছে!");
                location.reload(); 
            } else {
                alert("❌ Failed: " + data.message);
                btn.innerText = "🚀 Publish Now";
                btn.disabled = false;
            }
        });
    }

    function closeRewriteModal() {
        document.getElementById('rewriteModal').classList.add('hidden');
        document.getElementById('rewriteModal').classList.remove('flex');
    }
</script>
@endsection