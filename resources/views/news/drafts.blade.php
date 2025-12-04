@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
</style>

<div class="max-w-7xl mx-auto py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 font-bangla flex items-center gap-2">
            📝 ড্রাফট এবং প্রকাশিত নিউজ 
            <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $drafts->total() }}</span>
        </h2>
        <a href="{{ route('news.index') }}" class="text-indigo-600 hover:underline font-bold text-sm">← Back to News Feed</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Grid Layout --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($drafts as $item)
        <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col h-full overflow-hidden relative transition hover:shadow-lg">
            
            {{-- Status Badge --}}
            @if($item->status == 'published')
                <div class="absolute top-3 right-3 z-20 bg-green-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow flex items-center gap-1">
                    ✅ PUBLISHED
                </div>
            @elseif($item->status == 'publishing')
                <div class="absolute top-3 right-3 z-20 bg-blue-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow animate-pulse">
                    🚀 SENDING...
                </div>
            @elseif($item->status == 'processing')
                 <div class="absolute top-3 right-3 z-20 bg-yellow-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow animate-pulse">
                    ⏳ AI WRITING...
                </div>
            {{-- 🔥 নতুন: ফেইল স্ট্যাটাস যোগ করা হলো --}}
            @elseif($item->status == 'failed')
                 <div class="absolute top-3 right-3 z-20 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow flex items-center gap-1">
                    ❌ FAILED
                </div>
            @else
                <div class="absolute top-3 right-3 z-20 bg-purple-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow">
                    📝 DRAFT
                </div>
            @endif

            {{-- Image & Delete --}}
            <div class="h-40 overflow-hidden relative bg-gray-100 group">
                 <img src="{{ $item->thumbnail_url ?? asset('images/placeholder.png') }}" class="w-full h-full object-cover opacity-95 group-hover:scale-105 transition duration-500">
                 
                 <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত এই নিউজটি মুছে ফেলতে চান?');" class="absolute top-2 left-2 z-20">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-black/40 hover:bg-red-600 text-white p-1.5 rounded-full transition backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path></svg>
                    </button>
                </form>
            </div>
           
            {{-- Content --}}
            <div class="p-4 flex flex-col flex-1">
                <div class="mb-2">
                    <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border border-blue-100">
                        {{ $item->website->name ?? '📌 Custom Post' }}
                    </span>
                </div>

                <h3 class="text-base font-bold leading-snug mb-2 text-gray-800 font-bangla line-clamp-2" title="{{ $item->ai_title ?? $item->title }}">
                    {{ $item->ai_title ?? $item->title }}
                </h3>
                
                <p class="text-xs text-gray-500 mb-3 line-clamp-3 font-bangla leading-relaxed">
                    {{ Str::limit(strip_tags($item->ai_content ?? $item->content), 100) }}
                </p>

                <div class="mt-auto pt-3 border-t border-gray-50">
                    @if($item->status == 'published')
                        {{-- Published View --}}
                        <div class="bg-green-50 border border-green-200 rounded-lg p-2 text-center">
                            <p class="text-xs text-green-700 font-bold mb-1">সফলভাবে পোস্ট করা হয়েছে</p>
                            @if($item->wp_post_id && optional($settings)->wp_url)
                                <a href="{{ rtrim($settings->wp_url, '/') }}/?p={{ $item->wp_post_id }}" target="_blank" class="text-indigo-600 underline text-sm font-bold flex items-center justify-center gap-1 hover:text-indigo-800">
                                    🔗 লাইভ দেখুন
                                </a>
                            @else
                               <span class="text-xs text-gray-400">(Link unavailable)</span>
                            @endif
                        </div>

                    @elseif($item->status == 'processing' || $item->status == 'publishing')
                        <button disabled class="w-full bg-gray-100 text-gray-500 py-2 rounded-lg text-sm font-bold cursor-wait flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            কাজ চলছে...
                        </button>

                    @else
                        {{-- Edit & Publish Button --}}
                        <button type="button" 
                            onclick="openPublishModal({{ $item->id }}, '{{ addslashes($item->ai_title ?? $item->title) }}', `{{ base64_encode($item->ai_content ?? $item->content) }}`)"
                            class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-2 rounded-lg hover:shadow-lg hover:from-purple-700 hover:to-indigo-700 transition text-sm font-bold flex items-center justify-center gap-2">
                            ✏️ Edit & Publish
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($drafts->count() == 0)
        <div class="p-10 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
            <p class="text-4xl mb-2">📭</p>
            <p class="font-bangla text-lg">কোনো ড্রাফট পাওয়া যায়নি।</p>
            <a href="{{ route('news.index') }}" class="text-indigo-500 text-sm hover:underline mt-2 inline-block font-bold">নতুন নিউজ প্রসেস করুন</a>
        </div>
    @endif

    <div class="mt-8">
        {{ $drafts->links() }}
    </div>
</div>

{{-- PUBLISH MODAL --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden flex flex-col max-h-[90vh] transform transition-all scale-100">
        {{-- Modal Header --}}
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg flex items-center gap-2">🚀 Final Review & Publish</h3>
            <button onclick="closeRewriteModal()" class="text-white/80 hover:text-white text-2xl font-bold leading-none">&times;</button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 font-bangla text-lg text-gray-900 shadow-sm transition">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Content</label>
                <textarea id="previewContent" rows="10" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 font-bangla text-sm text-gray-900 shadow-sm leading-relaxed transition"></textarea>
            </div>
            
            {{-- 🔥 CATEGORY DROPDOWN --}}
            <div class="mb-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Category <span class="text-gray-400 font-normal text-xs">(Optional)</span></label>
                
                <select id="previewCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-gray-900 shadow-sm focus:ring-green-500 focus:border-green-500 bg-white">
                    <option value="">-- ক্যাটাগরি সিলেক্ট করুন --</option>
                    
                    {{-- সেটিংস থেকে ক্যাটাগরি পপুলেট করা হচ্ছে --}}
                    @if(isset($settings->category_mapping) && is_array($settings->category_mapping))
                        @foreach($settings->category_mapping as $aiCat => $wpId)
                            {{-- যদি WP ID সেট করা থাকে তবেই অপশন দেখাবে --}}
                            @if(!empty($wpId))
                                <option value="{{ $wpId }}">{{ $aiCat }} (ID: {{ $wpId }})</option>
                            @endif
                        @endforeach
                    @endif
                </select>
                <p class="text-xs text-gray-400 mt-1">সেটিংস পেজে ম্যাপ করা ক্যাটাগরিগুলো এখানে দেখাচ্ছে।</p>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Cancel</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow-lg hover:shadow-xl flex items-center gap-2 transition transform active:scale-95">
                🚀 Publish Now
            </button>
        </div>
    </div>
</div>

<script>
    // Open Modal Logic
    function openPublishModal(id, title, encodedContent) {
        let content = "";
        try {
            content = atob(encodedContent);
            try {
                content = decodeURIComponent(escape(content));
            } catch (e) {
                console.log("Encoding fallback used");
            }
        } catch (e) {
            console.error("Decoding error", e);
            content = "Error loading content.";
        }
        
        document.getElementById('previewNewsId').value = id;
        document.getElementById('previewTitle').value = title;
        document.getElementById('previewContent').value = content;
        
        // Reset Dropdown
        document.getElementById('previewCategory').value = ""; 

        const modal = document.getElementById('rewriteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Publish Logic
    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const title = document.getElementById('previewTitle').value;
        const content = document.getElementById('previewContent').value;
        // ইনপুট বক্সের বদলে এখন ড্রপডাউনের ভ্যালু নেওয়া হচ্ছে
        const category = document.getElementById('previewCategory').value; 
        const btn = document.getElementById('btnPublish');

        btn.innerText = "Publishing...";
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        fetch(`/news/${id}/publish-draft`, {
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
                alert("✅ " + data.message);
                window.location.href = "{{ route('news.index') }}"; 
            } else {
                alert("❌ Failed: " + data.message);
                resetButton();
            }
        })
        .catch(err => {
            console.error(err);
            alert("⚠️ Network Error. Please try again.");
            resetButton();
        });
    }

    function resetButton() {
        const btn = document.getElementById('btnPublish');
        btn.innerText = "🚀 Publish Now";
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    }

    function closeRewriteModal() {
        const modal = document.getElementById('rewriteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('rewriteModal');
        if (event.target == modal) {
            closeRewriteModal();
        }
    }
</script>
@endsection