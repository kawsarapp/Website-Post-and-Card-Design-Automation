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
    <div class="group relative flex flex-col h-full bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
        
        {{-- Status Badge --}}
        <div class="absolute top-3 right-3 z-20">
            @if($item->status == 'published')
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm backdrop-blur-md">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    Published
                </span>
            @elseif($item->status == 'publishing')
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-700 border border-blue-200 shadow-sm animate-pulse">
                    🚀 Sending...
                </span>
            @elseif($item->status == 'processing')
                 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 border border-amber-200 shadow-sm animate-pulse">
                    ⏳ AI Writing...
                </span>
            @elseif($item->status == 'failed')
                 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 border border-red-200 shadow-sm">
                    ❌ Failed
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600 border border-gray-200 shadow-sm">
                    📝 Draft
                </span>
            @endif
        </div>

        {{-- Image & Delete Action --}}
        <div class="relative w-full aspect-video overflow-hidden bg-gray-50">
             <img src="{{ $item->thumbnail_url ?? asset('images/placeholder.png') }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
             
             {{-- Overlay Gradient --}}
             <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

             {{-- Delete Button (Visible on Hover) --}}
             <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত এই নিউজটি মুছে ফেলতে চান?');" class="absolute top-3 left-3 z-30 opacity-0 group-hover:opacity-100 transition-all duration-300 transform -translate-x-2 group-hover:translate-x-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-white/90 hover:bg-red-500 hover:text-white text-red-500 p-2 rounded-full shadow-lg backdrop-blur-sm transition-colors duration-200" title="Delete Draft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path></svg>
                </button>
            </form>
        </div>
       
        {{-- Content Body --}}
        <div class="p-5 flex flex-col flex-1">
            {{-- Website Tag --}}
            <div class="mb-3">
                <span class="inline-block bg-indigo-50 text-indigo-600 border border-indigo-100 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    {{ $item->website->name ?? '📌 Custom Post' }}
                </span>
            </div>

            {{-- Title --}}
            <h3 class="text-[17px] font-bold leading-tight text-gray-900 font-bangla line-clamp-2 mb-2 group-hover:text-indigo-600 transition-colors duration-200" title="{{ $item->ai_title ?? $item->title }}">
                {{ $item->ai_title ?? $item->title }}
            </h3>
            
            {{-- Excerpt --}}
            <p class="text-xs text-gray-500 mb-4 line-clamp-3 font-bangla leading-relaxed flex-1">
                {{ Str::limit(strip_tags($item->ai_content ?? $item->content), 120) }}
            </p>

            {{-- Footer Actions --}}
            <div class="mt-auto pt-4 border-t border-gray-100">
                @if($item->status == 'published')
                    {{-- Published View --}}
                    <div class="flex items-center justify-between bg-emerald-50/50 rounded-lg p-2 border border-emerald-100">
                        <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Posted
                        </span>
                        @if($item->wp_post_id && optional($settings)->wp_url)
                            <a href="{{ rtrim($settings->wp_url, '/') }}/?p={{ $item->wp_post_id }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1 transition-colors">
                                লাইভ দেখুন 🔗
                            </a>
                        @else
                           <span class="text-[10px] text-gray-400 font-medium">No Link</span>
                        @endif
                    </div>

                @elseif($item->status == 'processing' || $item->status == 'publishing')
                    {{-- Processing State --}}
                    <div class="w-full bg-gray-50 text-gray-500 py-2.5 rounded-lg text-xs font-bold flex items-center justify-center gap-2 border border-gray-100 cursor-wait">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        প্রসেসিং হচ্ছে...
                    </div>

                @else
                    {{-- Edit & Publish Button --}}
                    <button type="button" 
                        onclick="openPublishModal({{ $item->id }}, '{{ addslashes($item->ai_title ?? $item->title) }}', `{{ base64_encode($item->ai_content ?? $item->content) }}`, '{{ $item->thumbnail_url }}')"
                        class="w-full group/btn relative flex items-center justify-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white py-2.5 rounded-lg transition-all duration-300 text-xs font-bold shadow-md hover:shadow-lg hover:shadow-indigo-500/30 overflow-hidden">
                        
                        <span class="relative z-10 flex items-center gap-2">
                           <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                           Edit & Publish
                        </span>
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
        
		
	      {{-- 🔥 IMAGE UPDATE SECTION --}}
            <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200">
                <label class="block text-sm font-bold text-gray-700 mb-2">Feature Image</label>
                
                <div class="flex gap-4 items-start">
                    {{-- Current Image Preview --}}
                    <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border">
                        <img id="previewImageDisplay" src="" class="w-full h-full object-cover">
                    </div>

                    {{-- Inputs --}}
                    <div class="flex-1">
                        {{-- File Input --}}
                        <input type="file" id="newImageFile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-2">
                        
                        {{-- OR Divider --}}
                        <div class="text-xs text-gray-400 text-center mb-2">- OR -</div>

                        {{-- URL Input --}}
                        <input type="url" id="newImageUrl" placeholder="Paste image link here..." class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>

		
		
		
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
    function openPublishModal(id, title, encodedContent, imageUrl) {
        let content = "";
        try {
            content = decodeURIComponent(escape(atob(encodedContent)));
        } catch (e) {
            console.error("Decoding error", e);
            content = "Error loading content.";
        }
        
        // আইডি সেট করা
        document.getElementById('previewNewsId').value = id;
        document.getElementById('previewTitle').value = title;
        document.getElementById('previewContent').value = content;
        
        // ইমেজ প্রিভিউ সেট করা
        const imgDisplay = document.getElementById('previewImageDisplay');
        imgDisplay.src = imageUrl ? imageUrl : 'https://via.placeholder.com/150?text=No+Image';
        
        // ইনপুট রিসেট করা
        document.getElementById('newImageFile').value = ""; 
        document.getElementById('newImageUrl').value = ""; 
        document.getElementById('previewCategory').value = ""; 

        // মডাল ওপেন
        const modal = document.getElementById('rewriteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Publish Logic
    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const btn = document.getElementById('btnPublish');

        // 🔥 FormData তৈরি (ফাইল পাঠানোর জন্য জরুরি)
        let formData = new FormData();
        formData.append('title', document.getElementById('previewTitle').value);
        formData.append('content', document.getElementById('previewContent').value);
        formData.append('category', document.getElementById('previewCategory').value);

        // ফাইল সিলেক্ট করা থাকলে সেটা পাঠাবো
        const fileInput = document.getElementById('newImageFile');
        if (fileInput.files[0]) {
            formData.append('image_file', fileInput.files[0]);
        }
        
        // অথবা ইউআরএল দেওয়া থাকলে সেটা পাঠাবো
        const urlInput = document.getElementById('newImageUrl');
        if (urlInput.value) {
            formData.append('image_url', urlInput.value);
        }

        // বাটন লোডিং স্ট্যাটাস
        btn.innerText = "Publishing...";
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        fetch(`/news/${id}/publish-draft`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                // নোট: FormData ব্যবহার করলে 'Content-Type': 'application/json' দেওয়া যাবে না!
            },
            body: formData // JSON.stringify এর বদলে formData
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