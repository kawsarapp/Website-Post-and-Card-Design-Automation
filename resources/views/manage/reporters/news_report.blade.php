@extends('layouts.app')

@section('content')
{{-- ১. স্ক্রিপ্ট ও ফন্ট --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .img-zoom:hover { transform: scale(1.05); transition: all 0.3s ease; cursor: zoom-in; }
</style>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-bangla">
    
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">📊 রিপোর্টার নিউজ রিপোর্ট</h2>
            <p class="text-slate-500 mt-2 font-medium">প্রতিনিধিদের পাঠানো খবরের গ্যালারি ও পাবলিশিং কন্ট্রোল</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-bold text-slate-400 uppercase">মোট নিউজ: {{ $news->total() }}</span>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400">থাম্বনেইল</th>
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400">প্রেরক ও সময়</th>
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400">শিরোনাম ও তথ্য</th>
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400 text-center">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($news as $item)
                    @php
                        $isProcessed = ($item->is_posted || in_array($item->status, ['publishing', 'published']));
                        $extraImages = json_decode($item->tags, true);
                    @endphp

                    <tr class="transition-colors duration-200 {{ $isProcessed ? 'bg-emerald-50/30' : 'hover:bg-slate-50/50' }}">
                        <td class="px-6 py-4">
                            <div class="relative w-20 h-14 rounded-xl overflow-hidden shadow-sm border border-slate-200 group">
                                <img src="{{ $item->thumbnail_url }}" class="w-full h-full object-cover">
                                @if(is_array($extraImages) && count($extraImages) > 0)
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-[10px] text-white font-bold">+{{ count($extraImages) }} ছবি</span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="font-bold {{ $isProcessed ? 'text-slate-400' : 'text-slate-700' }}">
                                {{ $item->reporter->name ?? $item->reporter_name_manual }}
                            </div>
                            <div class="text-[10px] text-indigo-500 font-bold uppercase mt-1">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $item->created_at->format('d M, h:i A') }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="font-bold text-lg leading-snug line-clamp-1 {{ $isProcessed ? 'text-slate-400 line-through decoration-slate-300' : 'text-slate-800' }}">
                                {{ $item->title }}
                            </div>
                            <div class="flex items-center gap-3 mt-1.5">
                                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md font-bold">
                                    <i class="fa-solid fa-location-dot mr-1 text-rose-400"></i> {{ $item->location ?? 'N/A' }}
                                </span>
                                @if($isProcessed)
                                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-md font-bold italic">✅ কাজ সম্পন্ন</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                @if($isProcessed)
                                    <a href="{{ route('news.drafts') }}" class="px-5 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-xs hover:bg-black transition-all">ড্রাফটে দেখুন</a>
                                @elseif($item->locked_by_user_id)
                                    <button disabled class="px-5 py-2.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs flex items-center gap-2 cursor-not-allowed">
                                        <i class="fa-solid fa-lock text-[10px]"></i> ব্যস্ত
                                    </button>
                                @else
                                    <button onclick="openEditModal('{{ $item->id }}')" class="px-5 py-2.5 bg-amber-50 text-amber-600 rounded-xl font-bold text-xs hover:bg-amber-100 border border-amber-100 transition-all shadow-sm">
                                        ✏️ এডিট ও রিভিউ
                                    </button>
                                    <a href="{{ route('news.studio', $item->id) }}" class="px-5 py-2.5 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-xs hover:bg-indigo-100 border border-indigo-100 transition-all">🎨 ডিজাইন</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $news->links() }}
        </div>
    </div>
</div>

{{-- ৩. নিউজ এডিট ও গ্যালারি মোডাল --}}
<div id="editModal" class="fixed inset-0 bg-slate-900/80 hidden items-center justify-center z-[100] backdrop-blur-md px-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col transform transition-all duration-300">
        
        <div class="px-10 py-6 border-b flex justify-between items-center bg-slate-50/80">
            <div>
                <h3 class="font-black text-2xl text-slate-800">📝 নিউজ রিভিউ প্যানেল</h3>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">রিপোর্টারের তথ্য যাচাই করুন</p>
            </div>
            <button onclick="closeEditModal()" class="w-12 h-12 flex items-center justify-center rounded-full bg-white shadow-sm border border-slate-100 text-slate-400 hover:text-rose-500 hover:rotate-90 transition-all text-2xl">&times;</button>
        </div>
        
        <div class="overflow-y-auto p-10 flex-grow custom-scrollbar bg-white">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                {{-- বাম পাশ: গ্যালারি ও তথ্য --}}
                <div class="lg:col-span-5 space-y-8">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">সংবাদ গ্যালারি</label>
                        <div class="relative h-64 rounded-3xl overflow-hidden bg-slate-100 border-4 border-slate-50 shadow-inner">
                            <img id="modalMainImg" src="" class="w-full h-full object-cover img-zoom" onclick="window.open(this.src)">
                        </div>
                        <div class="grid grid-cols-4 gap-3 mt-4" id="extraImagesGrid"></div>
                    </div>

                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-4">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs font-bold text-slate-400">লোকেশন:</span>
                            <span class="text-xs font-black text-slate-700" id="infoLocation">--</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs font-bold text-slate-400">সোর্স লিংক:</span>
                            <a href="#" id="infoSource" target="_blank" class="text-xs font-black text-indigo-600 truncate max-w-[150px]">সোর্স দেখুন</a>
                        </div>
                    </div>
                </div>

                {{-- ডান পাশ: এডিটর --}}
                <div class="lg:col-span-7 space-y-6">
                    <form id="editForm">
                        @csrf
                        <input type="hidden" id="editNewsId">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">শিরোনাম</label>
                                <input type="text" id="editTitle" class="w-full border-2 border-slate-50 rounded-2xl p-5 outline-none font-bold text-slate-700 text-xl shadow-sm bg-slate-50/50">
                            </div>

                            <div class="p-5 bg-indigo-50 rounded-2xl border border-indigo-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm"><i class="fa-solid fa-robot"></i></div>
                                    <p class="text-xs font-black text-indigo-900">AI স্মার্ট রাইটার</p>
                                </div>
                                <button type="button" onclick="triggerAI()" id="aiBtn" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-black text-xs hover:bg-indigo-700 transition-all active:scale-95">✨ রিরাইট করুন</button>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">বিস্তারিত খবর</label>
                                <textarea id="editContent"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">ক্যাটাগরি</label>
                                    <select id="editCategory" class="w-full border-2 border-slate-50 rounded-2xl p-4 bg-slate-50/50 outline-none font-bold text-slate-600 shadow-sm appearance-none"></select>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">ট্যাগস</label>
                                    <input type="text" id="editTags" class="w-full border-2 border-slate-50 rounded-2xl p-4 bg-slate-50/50 outline-none font-bold text-slate-600">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="px-10 py-8 border-t bg-slate-50/80 flex justify-end gap-3">
            <button onclick="closeEditModal()" class="px-8 py-4 text-slate-500 font-black text-sm hover:bg-slate-200 rounded-2xl transition-all">বাতিল</button>
            <button onclick="saveDraftOnly()" id="draftBtn" class="px-8 py-4 bg-slate-700 text-white font-black text-sm rounded-2xl hover:bg-slate-800 transition-all flex items-center gap-2">💾 ড্রাফটে সেভ</button>
            <button onclick="submitFinalPublish()" id="publishBtn" class="px-10 py-4 bg-indigo-600 text-white font-black text-sm rounded-2xl hover:bg-indigo-700 shadow-2xl transition-all flex items-center gap-2">🚀 পাবলিশ করুন</button>
        </div>
    </div>
</div>

<script>
    // ১. TinyMCE সেটআপ
    tinymce.init({
        selector: '#editContent',
        height: 350,
        plugins: 'lists link code wordcount autoresize',
        toolbar: 'undo redo | bold italic | bullist numlist | link code | removeformat',
        menubar: false,
        branding: false,
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri&display=swap'); body { font-family: 'Hind Siliguri', sans-serif; font-size: 16px; }"
    });

    // ২. মোডাল ফাংশন
    function openEditModal(newsId) {
        console.log("Opening Modal for News ID:", newsId); // Debugging
        
        fetch(`/news/${newsId}/get-draft`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('editNewsId').value = newsId;
                document.getElementById('editTitle').value = data.title || '';
                
                // TinyMCE কন্টেন্ট সেট করা
                if (tinymce.get('editContent')) {
                    tinymce.get('editContent').setContent(data.content || '');
                }

                // তথ্য ও গ্যালারি লোড
                document.getElementById('modalMainImg').src = data.image_url || '';
                document.getElementById('infoLocation').innerText = data.location || 'N/A';
                document.getElementById('infoSource').href = data.original_link || '#';
                document.getElementById('editTags').value = data.tags_string || '';

                // অতিরিক্ত ছবি গ্রিড
                const extraGrid = document.getElementById('extraImagesGrid');
                extraGrid.innerHTML = '';
                if(data.extra_images && data.extra_images.length > 0) {
                    data.extra_images.forEach(img => {
                        extraGrid.innerHTML += `<div class="h-16 rounded-xl overflow-hidden border border-slate-100 shadow-sm"><img src="${img}" class="w-full h-full object-cover img-zoom" onclick="window.open(this.src)"></div>`;
                    });
                }

                // ক্যাটাগরি পপুলেট
                const catSelect = document.getElementById('editCategory');
                catSelect.innerHTML = '<option value="">ক্যাটাগরি সিলেক্ট করুন</option>';
                if (data.categories) {
                    Object.entries(data.categories).forEach(([name, id]) => {
                        catSelect.innerHTML += `<option value="${id}">${name}</option>`;
                    });
                }

                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editModal').classList.add('flex');
            } else {
                alert(data.message); // Swal এর পরিবর্তে alert
                location.reload();
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("ডেটা লোড করতে সমস্যা হয়েছে। কনসোল চেক করুন।");
        });
    }

    function closeEditModal() {
        const id = document.getElementById('editNewsId').value;
        if(id) {
            fetch(`/news/${id}/unlock`).then(() => {
                document.getElementById('editModal').classList.add('hidden');
                location.reload();
            });
        } else {
            document.getElementById('editModal').classList.add('hidden');
        }
    }

    function triggerAI() {
        const id = document.getElementById('editNewsId').value;
        const btn = document.getElementById('aiBtn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'AI কাজ করছে...';

        fetch(`/news/${id}/process-ai`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            alert(data.success ? 'AI প্রসেসিং শুরু হয়েছে। অটো রিফ্রেশ হবে।' : data.message);
            location.reload();
        });
    }

    function saveDraftOnly() { sendPostRequest(`/news/${document.getElementById('editNewsId').value}/update-draft`, 'draftBtn'); }
    function submitFinalPublish() { sendPostRequest(`/news/${document.getElementById('editNewsId').value}/manual-publish`, 'publishBtn'); }

    function sendPostRequest(url, btnId) {
        const btn = document.getElementById(btnId);
        const originalText = btn.innerHTML;
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('title', document.getElementById('editTitle').value);
        formData.append('content', tinymce.get('editContent').getContent());
        formData.append('category', document.getElementById('editCategory').value);
        formData.append('tags', document.getElementById('editTags').value);

        btn.disabled = true;
        btn.innerHTML = 'প্রসেসিং...';

        fetch(url, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('ভুল: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
</script>
@endsection