@extends('layouts.app')

@section('content')
{{-- TinyMCE লাইব্রেরি (যদি লেআউটে না থাকে তবে এখানে অবশ্যই লাগবে) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<div class="max-w-7xl mx-auto py-6">
    <h2 class="text-2xl font-bold text-gray-800 font-bangla mb-6">📢 প্রকাশিত নিউজসমূহ</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($published as $item)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full group">
        <div class="relative aspect-video overflow-hidden">
             <img src="{{ $item->thumbnail_url }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
             <span class="absolute top-2 right-2 bg-emerald-500 text-white text-[10px] px-2 py-1 rounded font-bold shadow-sm">LIVE</span>
        </div>
       
        <div class="p-5 flex flex-col flex-1">
            <h3 class="text-md font-bold leading-tight mb-2 font-bangla line-clamp-2">{{ $item->title }}</h3>
            <p class="text-xs text-gray-500 mb-4 line-clamp-3 font-bangla">{{ Str::limit(strip_tags($item->content), 100) }}</p>

            <div class="mt-auto space-y-2">
                {{-- লাইভ লিঙ্ক --}}
                @if($item->wp_post_id && optional($settings)->wp_url)
                    <a href="{{ rtrim($settings->wp_url, '/') }}/?p={{ $item->wp_post_id }}" target="_blank" class="block w-full text-center bg-indigo-50 text-indigo-700 py-2 rounded-lg text-xs font-bold border border-indigo-100">লাইভ দেখুন 🔗</a>
                @endif
                
                {{-- এডিট বাটন - এটি ক্লিক করলে ১ ক্রেডিট কাটবে --}}
                <button onclick="if(confirm('এটি আপডেট করলে ১ ক্রেডিট কাটা হবে। আপনি কি নিশ্চিত?')) fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" 
                        class="w-full bg-slate-900 text-white py-2.5 rounded-lg text-xs font-bold hover:bg-black transition shadow-md">
                    📝 নিউজ এডিট ও আপডেট
                </button>
            </div>
        </div>
    </div>
    @endforeach
    </div>
    <div class="mt-8">{{ $published->links() }}</div>
</div>

{{-- এডিট মোডাল (TinyMCE Enabled) --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200">
            <label class="block text-sm font-bold text-gray-700 mb-2">Feature Image</label>
            <div class="flex gap-4 items-start">
                <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border">
                    <img id="previewImageDisplay" src="" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <input type="file" id="newImageFile" accept="image/*" class="block w-full text-sm text-gray-500 ...">
                    <div class="text-xs text-gray-400 text-center mb-2">- OR -</div>
                    <input type="url" id="newImageUrl" placeholder="Paste image link here..." class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <input type="hidden" id="previewNewsId">
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 font-bangla text-lg">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">News Content</label>
                <textarea id="previewContent" rows="15" class="w-full border border-gray-300 rounded-lg"></textarea>
            </div>
            
            <div class="mb-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-bold text-gray-700">Categories</label>
                    <button type="button" onclick="fetchLiveCategories()" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded font-bold">🔄 Refresh</button>
                </div>
                <p class="text-xs text-gray-400 mb-2" id="catStatusText">ক্যাটাগরি লোড করতে Refresh ক্লিক করুন।</p>

                <div class="mb-3">
                    <select id="previewCategory" class="wp-cat-dropdown w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                        <option value="">-- Primary Category --</option>
                        @if(isset($settings->category_mapping) && is_array($settings->category_mapping))
                            @foreach($settings->category_mapping as $aiCat => $wpId)
                                @if(!empty($wpId)) <option value="{{ $wpId }}">{{ $aiCat }} (ID: {{ $wpId }})</option> @endif
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold">Cancel</button>
            <button onclick="saveDraftOnly()" id="btnSave" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold shadow">💾 Save Draft</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-bold shadow-lg">🚀 Publish Update</button>
        </div>
    </div>
</div>

<script>
    // ১. TinyMCE Init
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: '#previewContent',
            height: 400,
            plugins: 'link lists code table preview wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code preview',
            menubar: false, statusbar: true, branding: false
        });
    });

    // ২. নিউজ ডাটা ফেচ করা
    function fetchDraftContent(id, imageUrl) {
        const modal = document.getElementById('rewriteModal');
        const titleInput = document.getElementById('previewTitle');
        const imgDisplay = document.getElementById('previewImageDisplay');
        
        titleInput.value = "Loading...";
        imgDisplay.src = imageUrl || 'https://via.placeholder.com/150';
        if (tinymce.get('previewContent')) tinymce.get('previewContent').setContent("<p>Fetching content...</p>");

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('previewNewsId').value = id;

        fetch(`/news/${id}/get-draft`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    titleInput.value = data.title;
                    if (tinymce.get('previewContent')) tinymce.get('previewContent').setContent(data.content);
                } else {
                    alert("❌ Error: " + data.message);
                    closeRewriteModal();
                }
            }).catch(() => { alert("⚠️ সার্ভার এরর!"); closeRewriteModal(); });
    }

    // ৩. ড্রাফট হিসেবে সেভ করা (পাবলিশ হবে না)
    function saveDraftOnly() {
        const id = document.getElementById('previewNewsId').value;
        const btn = document.getElementById('btnSave');
        let formData = new FormData();
        formData.append('title', document.getElementById('previewTitle').value);
        formData.append('content', tinymce.get('previewContent').getContent());

        btn.innerText = "Saving...";
        btn.disabled = true;

        fetch(`/news/${id}/update-draft`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.success ? "✅ ড্রাফট সেভ হয়েছে" : "❌ " + data.message);
            if(data.success) window.location.reload();
        }).finally(() => { btn.innerText = "💾 Save Draft"; btn.disabled = false; });
    }

    // ৪. পাবলিশ আপডেট (এতে ১ ক্রেডিট কাটবে)
    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const btn = document.getElementById('btnPublish');
        let formData = new FormData();
        formData.append('title', document.getElementById('previewTitle').value);
        formData.append('content', tinymce.get('previewContent').getContent());
        formData.append('category', document.getElementById('previewCategory').value);

        const fileInput = document.getElementById('newImageFile');
        if (fileInput.files[0]) formData.append('image_file', fileInput.files[0]);

        btn.innerText = "Updating...";
        btn.disabled = true;

        fetch(`/news/${id}/publish-draft`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.success ? "✅ আপডেট সফল হয়েছে" : "❌ " + data.message);
            if(data.success) window.location.reload();
        }).finally(() => { btn.innerText = "🚀 Publish Update"; btn.disabled = false; });
    }

    // ৫. ক্যাটাগরি রিফ্রেশ করা
    function fetchLiveCategories() {
        const btn = document.querySelector('button[onclick="fetchLiveCategories()"]');
        btn.innerText = "⏳ Loading...";
        fetch('/settings/fetch-categories')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('previewCategory');
                select.innerHTML = '<option value="">-- Primary Category --</option>';
                data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name} (ID: ${cat.id})</option>`;
                });
                document.getElementById('catStatusText').innerText = "✅ আপডেট হয়েছে";
            }).finally(() => { btn.innerText = "🔄 Refresh"; });
    }

    function closeRewriteModal() {
        document.getElementById('rewriteModal').classList.add('hidden');
        document.getElementById('rewriteModal').classList.remove('flex');
    }
</script>
@endsection