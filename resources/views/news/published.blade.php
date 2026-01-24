@extends('layouts.app')

@section('content')
{{-- TinyMCE লাইব্রেরি --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    .tox-tinymce-aux { z-index: 99999 !important; }
</style>

<div class="max-w-7xl mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 font-bangla flex items-center gap-2">
            📢 প্রকাশিত নিউজসমূহ
            <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-1 rounded-full border border-emerald-200">{{ $published->total() }}</span>
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($published as $item)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full group hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        
        {{-- Image Area --}}
        <div class="relative aspect-video overflow-hidden bg-slate-100">
             @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
             @else
                <div class="flex items-center justify-center h-full text-slate-300 font-bold text-xs uppercase">No Image</div>
             @endif
             
             {{-- Live Badge --}}
             <span class="absolute top-3 right-3 bg-emerald-500/90 backdrop-blur text-white text-[10px] px-2.5 py-1 rounded-md font-bold shadow-sm border border-white/20 flex items-center gap-1">
                 <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> LIVE
             </span>
        </div>
       
        {{-- Body --}}
        <div class="p-5 flex flex-col flex-1">
            <h3 class="text-[16px] font-bold leading-snug mb-2 font-bangla line-clamp-2 text-slate-800 group-hover:text-indigo-600 transition-colors">
                {{ $item->title }}
            </h3>
            
            <p class="text-xs text-slate-500 mb-4 line-clamp-3 font-bangla leading-relaxed">
                {{ Str::limit(strip_tags($item->content), 100) }}
            </p>

            {{-- Published Date --}}
            <div class="mt-auto mb-4 text-[10px] text-slate-400 font-medium flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ $item->updated_at->format('d M, Y h:i A') }}
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-2 pt-4 border-t border-dashed border-slate-100">
                
                {{-- 🔥 ১. Open Studio Button (Added Here) --}}
                <a href="{{ route('news.studio', $item->id) }}" 
                   class="w-full bg-slate-50 hover:bg-white text-slate-600 border border-slate-200 hover:border-purple-300 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group/studio hover:shadow-sm">
                    <svg class="w-4 h-4 text-purple-500 group-hover/studio:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    DESIGN STUDIO
                </a>

                {{-- ২. Live Link --}}
                @if($item->live_url)
                    <a href="{{ $item->live_url }}" target="_blank" class="block w-full text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-600 py-2.5 rounded-xl text-xs font-bold border border-indigo-100 transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        View Live Post
                    </a>
                @endif
                
                {{-- ৩. Edit Button --}}
                <button onclick="if(confirm('সতর্কতা: এটি আপডেট করলে ১ ক্রেডিট কাটা হবে। আপনি কি নিশ্চিত?')) fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" 
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl text-xs font-bold transition shadow-lg shadow-slate-200 hover:shadow-slate-300 flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit & Update
                </button>
            </div>
        </div>
    </div>
    @endforeach
    </div>
    <div class="mt-8">{{ $published->links() }}</div>
</div>

{{-- EDIT MODAL (Previous Logic) --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-white px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">📝 Update Published News</h3>
            <button onclick="closeRewriteModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Feature Image</label>
                <div class="flex gap-4 items-start">
                    <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border">
                        <img id="previewImageDisplay" src="" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" id="newImageFile" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-2">
                        <div class="text-[10px] text-gray-400 text-center mb-1">- OR -</div>
                        <input type="url" id="newImageUrl" placeholder="Paste image link here..." class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 font-bangla text-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Content</label>
                <textarea id="previewContent" rows="15" class="w-full border border-gray-300 rounded-lg"></textarea>
            </div>
            
            <div class="mb-2">
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase">Category</label>
                    <button type="button" onclick="fetchLiveCategories()" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-bold hover:bg-indigo-100">🔄 Refresh List</button>
                </div>
                <select id="previewCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 bg-white shadow-sm">
                    <option value="">-- Select Category --</option>
                    @if(isset($settings->category_mapping) && is_array($settings->category_mapping))
                        @foreach($settings->category_mapping as $aiCat => $wpId)
                            @if(!empty($wpId)) <option value="{{ $wpId }}">{{ $aiCat }} (ID: {{ $wpId }})</option> @endif
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg font-bold hover:bg-gray-200 text-sm">Cancel</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold shadow-lg hover:bg-indigo-700 text-sm flex items-center gap-2">
                🚀 Update Now
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: '#previewContent',
            height: 400,
            plugins: 'link lists code wordcount',
            toolbar: 'undo redo | blocks | bold italic | bullist numlist | link | code',
            menubar: false, statusbar: true
        });
    });

    function fetchDraftContent(id, imageUrl) {
        const modal = document.getElementById('rewriteModal');
        const titleInput = document.getElementById('previewTitle');
        const imgDisplay = document.getElementById('previewImageDisplay');
        const contentArea = tinymce.get('previewContent');
        
        titleInput.value = "Loading...";
        imgDisplay.src = imageUrl || 'https://via.placeholder.com/150';
        if(contentArea) contentArea.setContent("<p>Loading...</p>");

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('previewNewsId').value = id;

        fetch(`/news/${id}/get-draft`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    titleInput.value = data.title;
                    if(contentArea) contentArea.setContent(data.content);
                } else {
                    alert("❌ Error: " + data.message);
                    closeRewriteModal();
                }
            }).catch(() => { alert("⚠️ Network Error!"); closeRewriteModal(); });
    }

    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const btn = document.getElementById('btnPublish');
        let formData = new FormData();
        
        formData.append('title', document.getElementById('previewTitle').value);
        formData.append('content', tinymce.get('previewContent').getContent());
        formData.append('category', document.getElementById('previewCategory').value);

        const fileInput = document.getElementById('newImageFile');
        if (fileInput.files[0]) formData.append('image_file', fileInput.files[0]);
        else if(document.getElementById('newImageUrl').value) formData.append('image_url', document.getElementById('newImageUrl').value);

        btn.innerHTML = "Updating...";
        btn.disabled = true;

        fetch(`/news/${id}/publish-draft`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.success ? "✅ আপডেট সফল হয়েছে! কিছুক্ষণের মধ্যে লাইভ হবে।" : "❌ " + data.message);
            if(data.success) window.location.reload();
        })
        .catch(() => alert("Failed to update."))
        .finally(() => { btn.innerHTML = "🚀 Update Now"; btn.disabled = false; });
    }

    function fetchLiveCategories() {
        const btn = document.querySelector('button[onclick="fetchLiveCategories()"]');
        btn.innerText = "⏳...";
        fetch('/settings/fetch-categories')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('previewCategory');
                select.innerHTML = '<option value="">-- Select Category --</option>';
                data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name} (ID: ${cat.id})</option>`;
                });
                alert("Categories Refreshed!");
            }).finally(() => { btn.innerText = "🔄 Refresh List"; });
    }

    function closeRewriteModal() {
        document.getElementById('rewriteModal').classList.add('hidden');
        document.getElementById('rewriteModal').classList.remove('flex');
    }
</script>
@endsection