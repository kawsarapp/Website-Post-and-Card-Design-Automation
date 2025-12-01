@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            📝 ড্রাফট তালিকা <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $drafts->total() }}</span>
        </h2>
        <a href="{{ route('news.index') }}" class="text-indigo-600 hover:underline font-bold text-sm">← Back to News Feed</a>
    </div>

    {{-- Draft Table --}}
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
        @if($drafts->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">News Title (AI Generated)</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Original Source</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Created At</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($drafts as $draft)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900 line-clamp-2">{{ $draft->ai_title ?? $draft->title }}</div>
                            <div class="text-xs text-gray-500 mt-1 line-clamp-1">{{ Str::limit(strip_tags($draft->ai_content), 100) }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-bold">
                                {{ $draft->website->name ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                            {{ $draft->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{-- 🔥 Edit & Publish Button --}}
                            <button onclick="openPublishModal({{ $draft->id }}, '{{ addslashes($draft->ai_title ?? $draft->title) }}', `{{ base64_encode($draft->ai_content ?? $draft->content) }}`)" 
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 transition shadow-sm flex items-center gap-1 ml-auto">
                                ✏️ Edit & Publish
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-4 border-t">
                {{ $drafts->links() }}
            </div>
        @else
            <div class="p-10 text-center text-gray-400">
                <p class="text-4xl mb-2">📭</p>
                <p>কোনো ড্রাফট পাওয়া যায়নি।</p>
                <a href="{{ route('news.index') }}" class="text-indigo-500 text-sm hover:underline mt-2 inline-block">নতুন নিউজ প্রসেস করুন</a>
            </div>
        @endif
    </div>
</div>

{{-- PUBLISH MODAL --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-green-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg">🚀 Final Review & Publish</h3>
            <button onclick="closeRewriteModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto flex-1">
            <input type="hidden" id="previewNewsId">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Title</label>
                <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 font-bangla text-lg text-gray-900">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Content</label>
                <textarea id="previewContent" rows="8" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 font-bangla text-sm text-gray-900"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Category ID</label>
                <input type="number" id="previewCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-gray-900" placeholder="Optional">
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300">Cancel</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-2">
                🚀 Publish Now
            </button>
        </div>
    </div>
</div>

<script>
    function openPublishModal(id, title, encodedContent) {
        // Base64 Decode to handle special chars and newlines
        let content = "";
        try {
            content = atob(encodedContent); // Decode base64
            // বাংলা ক্যারেক্টার ঠিক করার জন্য
            content = decodeURIComponent(escape(content));
        } catch (e) {
            console.error("Decoding error", e);
            content = "Error loading content.";
        }
        
        document.getElementById('previewNewsId').value = id;
        document.getElementById('previewTitle').value = title;
        document.getElementById('previewContent').value = content;
        
        document.getElementById('rewriteModal').classList.remove('hidden');
        document.getElementById('rewriteModal').classList.add('flex');
    }

    function publishDraft() {
        const id = document.getElementById('previewNewsId').value;
        const title = document.getElementById('previewTitle').value;
        const content = document.getElementById('previewContent').value;
        const category = document.getElementById('previewCategory').value;
        const btn = document.getElementById('btnPublish');

        btn.innerText = "Publishing...";
        btn.disabled = true;

        // ড্রাফট পাবলিশ রাউটে হিট করা
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
                window.location.href = "{{ route('news.index') }}"; // সাকসেস হলে নিউজ ফিডে নিয়ে যাবে
            } else {
                alert("❌ Failed: " + data.message);
                btn.innerText = "🚀 Publish Now";
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert("Network Error");
            btn.innerText = "🚀 Publish Now";
            btn.disabled = false;
        });
    }

    function closeRewriteModal() {
        document.getElementById('rewriteModal').classList.add('hidden');
        document.getElementById('rewriteModal').classList.remove('flex');
    }
</script>
@endsection