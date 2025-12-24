@extends('layouts.app')

@section('content')
{{-- ১. TinyMCE স্ক্রিপ্ট --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    .tox-tinymce-aux { z-index: 99999 !important; }
</style>

<div class="max-w-7xl mx-auto py-6">
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
        
        <div class="absolute top-3 right-3 z-20">
            @if($item->status == 'published')
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm backdrop-blur-md">Published</span>
            @elseif($item->status == 'publishing')
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-700 border border-blue-200 shadow-sm animate-pulse">🚀 Sending...</span>
            @elseif($item->status == 'processing')
                 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 border border-amber-200 shadow-sm animate-pulse">⏳ AI Writing...</span>
            @elseif($item->status == 'failed')
                 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 border border-red-200 shadow-sm" title="{{ $item->error_message }}">❌ Failed</span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600 border border-gray-200 shadow-sm">📝 Draft</span>
            @endif
        </div>

        <div class="relative w-full aspect-video overflow-hidden bg-gray-50">
             <img src="{{ $item->thumbnail_url ?? asset('images/placeholder.png') }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
             <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
             
             <form action="{{ route('news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('নিউজটি মুছতে চান?');" class="absolute top-3 left-3 z-30 opacity-0 group-hover:opacity-100 transition-all duration-300 transform -translate-x-2 group-hover:translate-x-0">
                @csrf @method('DELETE')
                <button type="submit" class="bg-white/90 hover:bg-red-500 hover:text-white text-red-500 p-2 rounded-full shadow-lg backdrop-blur-sm transition-colors duration-200"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path></svg></button>
            </form>
        </div>
       
        <div class="p-5 flex flex-col flex-1">
            <div class="mb-3"><span class="inline-block bg-indigo-50 text-indigo-600 border border-indigo-100 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">{{ $item->website->name ?? '📌 Custom' }}</span></div>
            <h3 class="text-[17px] font-bold leading-tight text-gray-900 font-bangla line-clamp-2 mb-2 group-hover:text-indigo-600 transition-colors duration-200" title="{{ $item->ai_title ?? $item->title }}">{{ $item->ai_title ?? $item->title }}</h3>
            <p class="text-xs text-gray-500 mb-4 line-clamp-3 font-bangla leading-relaxed flex-1">{{ Str::limit(strip_tags($item->ai_content ?? $item->content), 120) }}</p>

            <div class="mt-auto pt-4 border-t border-gray-100 space-y-2">
                @if($item->status != 'processing' && $item->status != 'publishing')
                <a href="{{ route('news.studio', $item->id) }}" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-2.5 rounded-lg text-xs font-bold hover:shadow-lg transition flex items-center justify-center gap-2 mb-2">🎨 ডিজাইন করুন</a>
                @endif

                @if($item->status == 'published')
                    <div class="flex items-center justify-between bg-emerald-50/50 rounded-lg p-2 border border-emerald-100">
                        <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">Posted</span>
                        @if($item->wp_post_id && optional($settings)->wp_url)
                            <a href="{{ rtrim($settings->wp_url, '/') }}/?p={{ $item->wp_post_id }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1 transition-colors">লাইভ দেখুন 🔗</a>
                        @else <span class="text-[10px] text-gray-400 font-medium">No Link</span> @endif
                    </div>

                @elseif($item->status == 'processing' || $item->status == 'publishing')
                    <div class="w-full bg-gray-50 text-gray-500 py-2.5 rounded-lg text-xs font-bold flex items-center justify-center gap-2 border border-gray-100 cursor-wait">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> প্রসেসিং হচ্ছে...
                    </div>

                @elseif($item->status == 'failed')
                    {{-- 🔥 FAILED STATE (RETRY & MANUAL FIX BUTTONS) --}}
                    <div class="bg-red-50 text-red-600 text-[10px] p-2 rounded border border-red-100 mb-2 font-bold text-center leading-tight" title="{{ $item->error_message }}">
                        ⚠️ {{ Str::limit($item->error_message ?? 'Unknown Error', 40) }}
                    </div>
                    
                    <div class="flex gap-2">
                        {{-- Retry AI Button --}}
                        <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-xs font-bold shadow transition flex items-center justify-center gap-1">
                                🔄 Retry AI
                            </button>
                        </form>

                        {{-- Manual Fix Button --}}
                        <button type="button" 
                            onclick="fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" 
                            class="px-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold shadow transition flex items-center justify-center" title="Manually Fix">
                            📝
                        </button>
                    </div>

                @else
                    {{-- Normal Draft State (Updated with AI Rewrite Button) --}}
                    <div class="flex gap-2">
                        <button type="button" 
                            onclick="fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" 
                            class="flex-1 group/btn relative flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-lg transition-all duration-300 text-xs font-bold shadow-md hover:shadow-lg hover:shadow-indigo-500/30 overflow-hidden">
                            <span class="relative z-10 flex items-center gap-2">Edit & Publish</span>
                        </button>

                        {{-- 🔥 AI Rewrite Option for Existing Draft --}}
                        <form action="{{ route('news.process-ai', $item->id) }}" method="POST" onsubmit="return confirm('এটি ১ ক্রেডিট কাটবে। আবার AI দিয়ে রিরাইট করতে চান?');">
                            @csrf
                            <button type="submit" class="px-3 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 py-2.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center" title="AI দিয়ে আবার লিখুন">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    </div>
    <div class="mt-8">{{ $drafts->links() }}</div>
</div>

{{-- ... বাকি সব কোড (Modal এবং Script) অপরিবর্তিত রাখা হয়েছে ... --}}

{{-- PUBLISH MODAL (TinyMCE Enabled) --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200">
            <label class="block text-sm font-bold text-gray-700 mb-2">Feature Image</label>
            <div class="flex gap-4 items-start">
                <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border">
                    <img id="previewImageDisplay" src="" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <input type="file" id="newImageFile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-2">
                    <div class="text-xs text-gray-400 text-center mb-2">- OR -</div>
                    <input type="url" id="newImageUrl" placeholder="Paste image link here..." class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 font-bangla text-lg text-gray-900 shadow-sm transition">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">News Content (Rich Text)</label>
                {{-- 🔥 TinyMCE Editor Target --}}
                <textarea id="previewContent" rows="15" class="w-full border border-gray-300 rounded-lg"></textarea>
            </div>
            
            <div class="mb-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-bold text-gray-700">Categories</label>
                    <button type="button" onclick="fetchLiveCategories()" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-200 transition font-bold">🔄 Refresh</button>
                </div>
                <p class="text-xs text-gray-400 mb-2" id="catStatusText">লাইভ ক্যাটাগরি পেতে Refresh বাটনে ক্লিক করুন।</p>

                <div class="mb-3">
                    <label class="text-xs font-bold text-indigo-600 block mb-1">Primary Category</label>
                    <select id="previewCategory" class="wp-cat-dropdown w-full border border-gray-300 rounded-lg p-2.5 text-gray-900 shadow-sm bg-white">
                        <option value="">-- Primary Category --</option>
                        @if(isset($settings->category_mapping) && is_array($settings->category_mapping))
                            @foreach($settings->category_mapping as $aiCat => $wpId)
                                @if(!empty($wpId)) <option value="{{ $wpId }}">{{ $aiCat }} (ID: {{ $wpId }})</option> @endif
                            @endforeach
                        @endif
                    </select>
                </div>

                <label class="text-xs font-bold text-gray-500 block mb-1">Additional Categories</label>
                <div class="grid grid-cols-2 gap-3 p-3 bg-gray-100 rounded-lg border border-gray-200">
                    @for ($i = 1; $i <= 4; $i++) 
                        <div><select id="extraCategory{{ $i }}" class="wp-cat-dropdown w-full border border-gray-300 rounded p-1.5 text-xs bg-white"><option value="">-- Select --</option></select></div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Cancel</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow-lg flex items-center gap-2 transition transform active:scale-95">
                🚀 Publish Now
            </button>
        </div>
    </div>
</div>

<script>
    // TinyMCE Init
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: '#previewContent',
            height: 400,
            plugins: 'link lists code table preview wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code preview',
            menubar: false,
            statusbar: true,
            branding: false
        });
    });

    function fetchDraftContent(id, imageUrl) {
        const modal = document.getElementById('rewriteModal');
        const titleInput = document.getElementById('previewTitle');
        
        titleInput.value = "Loading...";
        if (tinymce.get('previewContent')) {
            tinymce.get('previewContent').setContent("<p>Fetching content...</p>");
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('previewNewsId').value = id;
        document.getElementById('previewImageDisplay').src = imageUrl ? imageUrl : 'https://via.placeholder.com/150';

        fetch(`/news/${id}/get-draft`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    titleInput.value = data.title;
                    if (tinymce.get('previewContent')) {
                        tinymce.get('previewContent').setContent(data.content);
                    } else {
                        document.getElementById('previewContent').value = data.content;
                    }
                } else {
                    if (tinymce.get('previewContent')) tinymce.get('previewContent').setContent("Error loading content.");
                }
            })
            .catch(err => { console.error(err); });
    }

    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const btn = document.getElementById('btnPublish');
        let formData = new FormData();
        
        formData.append('title', document.getElementById('previewTitle').value);
        
        let content = "";
        if (tinymce.get('previewContent')) {
            content = tinymce.get('previewContent').getContent();
        } else {
            content = document.getElementById('previewContent').value;
        }
        formData.append('content', content);

        formData.append('category', document.getElementById('previewCategory').value);
        for (let i = 1; i <= 4; i++) {
            let el = document.getElementById(`extraCategory${i}`);
            if (el && el.value) formData.append('extra_categories[]', el.value);
        }

        const fileInput = document.getElementById('newImageFile');
        if (fileInput && fileInput.files[0]) formData.append('image_file', fileInput.files[0]);
        
        const urlInput = document.getElementById('newImageUrl');
        if (urlInput && urlInput.value) formData.append('image_url', urlInput.value);

        btn.innerText = "Publishing...";
        btn.disabled = true;

        fetch(`/news/${id}/publish-draft`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json' 
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("✅ " + data.message);
                window.location.href = "{{ route('news.index') }}"; 
            } else {
                alert("❌ Failed: " + data.message);
                btn.innerText = "🚀 Publish Now";
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert("⚠️ Error: " + err.message);
            btn.innerText = "🚀 Publish Now";
            btn.disabled = false;
        });
    }

    function fetchLiveCategories() {
        const btn = document.querySelector('button[onclick="fetchLiveCategories()"]');
        const statusText = document.getElementById('catStatusText');
        const allDropdowns = document.querySelectorAll('.wp-cat-dropdown');

        btn.innerText = "⏳ Loading...";
        btn.disabled = true;

        fetch('/settings/fetch-categories')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    statusText.innerText = "❌ ক্যাটাগরি লোড করা যায়নি।";
                } else {
                    allDropdowns.forEach(select => {
                        const defaultText = select.id === 'previewCategory' ? '-- Primary Category --' : '-- Select --';
                        select.innerHTML = `<option value="">${defaultText}</option>`;
                        data.forEach(cat => {
                            let option = document.createElement('option');
                            option.value = cat.id;
                            option.text = `${cat.name} (ID: ${cat.id})`;
                            select.appendChild(option);
                        });
                    });
                    statusText.innerText = "✅ ওয়ার্ডপ্রেস থেকে ক্যাটাগরি লোড হয়েছে।";
                }
            })
            .finally(() => {
                btn.innerText = "🔄 Refresh WP Categories";
                btn.disabled = false;
            });
    }

    function closeRewriteModal() {
        document.getElementById('rewriteModal').classList.add('hidden');
        document.getElementById('rewriteModal').classList.remove('flex');
        if (tinymce.get('previewContent')) {
            tinymce.get('previewContent').setContent('');
        }
    }
</script>
@endsection