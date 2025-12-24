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
    
    /* মোবাইল টেবিল ফিক্স */
    @media (max-width: 640px) {
        .mobile-table-card thead { display: none; }
        .mobile-table-card tr { display: block; margin-bottom: 1rem; border: 1px solid #f1f5f9; border-radius: 1.5rem; background: #fff; padding: 1rem; }
        .mobile-table-card td { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border: none; }
        .mobile-table-card td::before { content: attr(data-label); font-weight: 800; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; }
    }
</style>

<div class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8 font-bangla bg-gray-50/30 min-h-screen">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8">
        <div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight">📊 রিপোর্টার নিউজ রিপোর্ট</h2>
            <p class="text-slate-500 mt-1 text-sm font-medium">প্রতিনিধিদের পাঠানো খবরের গ্যালারি ও পাবলিশিং কন্ট্রোল</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-2xl shadow-sm border border-slate-100 w-full sm:w-auto text-center">
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">মোট নিউজ: {{ $news->total() }}</span>
        </div>
    </div>

    {{-- Main Table Section --}}
    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden transition-all duration-300">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse mobile-table-card">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400">থাম্বনেইল</th>
                        <th class="px-6 py-5 text-xs font-bold uppercase text-slate-400">প্রেরক ও সময়</th>
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

                    <tr class="transition-colors duration-200 {{ $isProcessed ? 'bg-emerald-50/20' : 'hover:bg-slate-50/50' }}">
                        <td class="px-6 py-4" data-label="থাম্বনেইল">
                            <div class="relative w-20 h-14 sm:w-24 sm:h-16 rounded-xl overflow-hidden shadow-sm border border-slate-200 group">
                                <img src="{{ $item->thumbnail_url }}" class="w-full h-full object-cover">
                                @if(is_array($extraImages) && count($extraImages) > 0)
                                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-[10px] text-white font-black">+{{ count($extraImages) }} ছবি</span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4" data-label="প্রেরক">
                            <div class="font-bold text-sm {{ $isProcessed ? 'text-slate-400' : 'text-slate-700' }}">
                                {{ $item->reporter->name ?? $item->reporter_name_manual }}
                            </div>
                            <div class="text-[10px] text-indigo-500 font-bold uppercase mt-1 flex items-center gap-1">
                                <i class="fa-regular fa-clock"></i> {{ $item->created_at->format('d M, h:i A') }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4" data-label="নিউজ">
                            <div class="font-bold text-base sm:text-lg leading-snug line-clamp-1 {{ $isProcessed ? 'text-slate-400 line-through decoration-slate-300' : 'text-slate-800' }}">
                                {{ $item->title }}
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-lg font-bold">
                                    <i class="fa-solid fa-location-dot mr-1 text-rose-400"></i> {{ $item->location ?? 'N/A' }}
                                </span>
                                @if($isProcessed)
                                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-lg font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> সম্পন্ন
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center" data-label="অ্যাকশন">
                            <div class="flex flex-wrap justify-center sm:justify-center gap-2">
                                @if($isProcessed)
                                    <a href="{{ route('news.drafts') }}" class="w-full sm:w-auto px-4 py-2 bg-slate-800 text-white rounded-xl font-bold text-xs hover:bg-black transition-all text-center">ড্রাফটে দেখুন</a>
                                @elseif($item->locked_by_user_id)
                                    <button disabled class="w-full sm:w-auto px-4 py-2 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs flex items-center justify-center gap-2 cursor-not-allowed border border-slate-200">
                                        <i class="fa-solid fa-lock text-[10px]"></i> ব্যস্ত
                                    </button>
                                @else
                                    <button onclick="openEditModal('{{ $item->id }}')" class="w-full sm:w-auto px-4 py-2 bg-amber-50 text-amber-600 rounded-xl font-bold text-xs hover:bg-amber-100 border border-amber-100 transition-all shadow-sm">
                                        ✏️ রিভিউ
                                    </button>
                                    <a href="{{ route('news.studio', $item->id) }}" class="w-full sm:w-auto px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-xs hover:bg-indigo-100 border border-indigo-100 transition-all text-center">🎨 ডিজাইন</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="p-6 bg-slate-50/50 border-t border-slate-100">
            {{ $news->links() }}
        </div>
    </div>
</div>

{{-- ৩. নিউজ এডিট ও গ্যালারি মোডাল --}}
<div id="editModal" class="fixed inset-0 bg-slate-900/90 hidden items-center justify-center z-[100] backdrop-blur-md px-2 sm:px-4">
    <div class="bg-white rounded-[2rem] sm:rounded-[3rem] shadow-2xl w-full max-w-6xl max-h-[96vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0 modal-container">
        
        {{-- Modal Header --}}
        <div class="px-6 py-4 sm:px-10 sm:py-6 border-b flex justify-between items-center bg-white sticky top-0 z-20">
            <div>
                <h3 class="font-black text-xl sm:text-2xl text-slate-800">📝 নিউজ রিভিউ প্যানেল</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 hidden sm:block">রিপোর্টারের তথ্য যাচাই করুন</p>
            </div>
            <button onclick="closeEditModal()" class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all text-2xl">&times;</button>
        </div>
        
        {{-- Modal Body --}}
        <div class="overflow-y-auto p-5 sm:p-10 flex-grow custom-scrollbar bg-white">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
                
                {{-- বাম পাশ: গ্যালারি ও তথ্য --}}
                <div class="lg:col-span-5 space-y-6">
                    <div class="group">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">সংবাদ গ্যালারি</label>
                        <div class="relative aspect-video rounded-3xl overflow-hidden bg-slate-100 border-4 border-slate-50 shadow-lg group-hover:shadow-indigo-100 transition-all">
                            <img id="modalMainImg" src="" class="w-full h-full object-cover img-zoom" onclick="window.open(this.src)">
                        </div>
                        <div class="grid grid-cols-4 gap-3 mt-4" id="extraImagesGrid"></div>
                    </div>

                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 space-y-4 shadow-sm">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                            <span class="text-xs font-bold text-slate-400">লোকেশন:</span>
                            <span class="text-sm font-black text-slate-700 bg-white px-3 py-1 rounded-lg" id="infoLocation">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-400">সোর্স লিংক:</span>
                            <a href="#" id="infoSource" target="_blank" class="text-xs font-black text-indigo-600 truncate max-w-[150px] hover:underline flex items-center gap-1">
                                সোর্স দেখুন <i class="fa-solid fa-external-link text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ডান পাশ: এডিটর --}}
                <div class="lg:col-span-7 space-y-6">
                    <form id="editForm">
                        @csrf
                        <input type="hidden" id="editNewsId">
                        <div class="space-y-6">
                            <div class="group">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 transition-colors group-focus-within:text-indigo-600">শিরোনাম</label>
                                <input type="text" id="editTitle" class="w-full border-2 border-slate-50 rounded-2xl p-4 sm:p-5 outline-none font-bold text-slate-700 text-lg sm:text-xl shadow-sm bg-slate-50/50 focus:border-indigo-500 focus:bg-white transition-all">
                            </div>

                            <div class="p-4 sm:p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm animate-bounce"><i class="fa-solid fa-robot"></i></div>
                                    <div class="text-center sm:text-left">
                                        <p class="text-xs font-black text-indigo-900">AI স্মার্ট রাইটার</p>
                                        <p class="text-[10px] text-indigo-400 font-bold">এক ক্লিকেই নিউজ রিরাইট করুন</p>
                                    </div>
                                </div>
                                <button type="button" onclick="triggerAI()" id="aiBtn" class="w-full sm:w-auto bg-indigo-600 text-white px-6 py-3 rounded-xl font-black text-xs hover:bg-indigo-700 transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                                    <span>✨ রিরাইট করুন</span>
                                </button>
                            </div>

                            <div class="group">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 transition-colors group-focus-within:text-indigo-600">বিস্তারিত খবর</label>
                                <div class="rounded-2xl overflow-hidden border-2 border-slate-50 focus-within:border-indigo-500 transition-all">
                                    <textarea id="editContent"></textarea>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">ক্যাটাগরি</label>
                                    <div class="relative">
                                        <select id="editCategory" class="w-full border-2 border-slate-50 rounded-2xl p-4 bg-slate-50/50 outline-none font-bold text-slate-600 shadow-sm appearance-none focus:border-indigo-500 focus:bg-white transition-all cursor-pointer"></select>
                                        <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">ট্যাগস</label>
                                    <input type="text" id="editTags" class="w-full border-2 border-slate-50 rounded-2xl p-4 bg-slate-50/50 outline-none font-bold text-slate-600 shadow-sm focus:border-indigo-500 focus:bg-white transition-all" placeholder="কমা দিয়ে লিখুন...">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-6 sm:px-10 sm:py-8 border-t bg-slate-50/50 flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 sticky bottom-0 z-20">
            <button onclick="closeEditModal()" class="w-full sm:w-auto px-8 py-4 text-slate-500 font-black text-sm hover:bg-slate-200 rounded-2xl transition-all order-3 sm:order-1">বাতিল</button>
            <button onclick="saveDraftOnly()" id="draftBtn" class="w-full sm:w-auto px-8 py-4 bg-slate-700 text-white font-black text-sm rounded-2xl hover:bg-slate-800 transition-all flex items-center justify-center gap-2 order-2">
                <i class="fa-solid fa-floppy-disk"></i> ড্রাফটে সেভ
            </button>
            <button onclick="submitFinalPublish()" id="publishBtn" class="w-full sm:w-auto px-10 py-4 bg-indigo-600 text-white font-black text-sm rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all flex items-center justify-center gap-2 order-1 sm:order-3">
                <i class="fa-solid fa-paper-plane"></i> পাবলিশ করুন
            </button>
        </div>
    </div>
</div>

<script>
    // ১. TinyMCE সেটআপ (মোবাইল অপ্টিমাইজড)
    tinymce.init({
        selector: '#editContent',
        height: 300,
        plugins: 'lists link code wordcount autoresize',
        toolbar: 'undo redo | bold italic | bullist numlist | link code | removeformat',
        menubar: false,
        branding: false,
        mobile: {
            menubar: false,
            toolbar: 'undo redo bold italic bullist link'
        },
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri&display=swap'); body { font-family: 'Hind Siliguri', sans-serif; font-size: 16px; padding: 20px; }"
    });

    // ২. মোডাল কন্ট্রোল ফাংশন
    function openEditModal(newsId) {
        const modal = document.getElementById('editModal');
        const container = modal.querySelector('.modal-container');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // এনিমেশন স্টার্ট
        setTimeout(() => {
            container.classList.remove('scale-95', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');
        }, 10);
        
        fetch(`/news/${newsId}/get-draft`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('editNewsId').value = newsId;
                document.getElementById('editTitle').value = data.title || '';
                
                if (tinymce.get('editContent')) {
                    tinymce.get('editContent').setContent(data.content || '');
                }

                document.getElementById('modalMainImg').src = data.image_url || '';
                document.getElementById('infoLocation').innerText = data.location || 'N/A';
                document.getElementById('infoSource').href = data.original_link || '#';
                document.getElementById('editTags').value = data.tags_string || '';

                const extraGrid = document.getElementById('extraImagesGrid');
                extraGrid.innerHTML = '';
                if(data.extra_images && data.extra_images.length > 0) {
                    data.extra_images.forEach(img => {
                        extraGrid.innerHTML += `
                            <div class="aspect-square rounded-xl overflow-hidden border border-slate-100 shadow-sm hover:shadow-md transition-all">
                                <img src="${img}" class="w-full h-full object-cover img-zoom" onclick="window.open(this.src)">
                            </div>`;
                    });
                }

                const catSelect = document.getElementById('editCategory');
                catSelect.innerHTML = '<option value="">ক্যাটাগরি সিলেক্ট করুন</option>';
                if (data.categories) {
                    Object.entries(data.categories).forEach(([name, id]) => {
                        catSelect.innerHTML += `<option value="${id}">${name}</option>`;
                    });
                }
            } else {
                alert(data.message);
                closeEditModal();
            }
        })
        .catch(err => {
            alert("ডেটা লোড করতে সমস্যা হয়েছে।");
            closeEditModal();
        });
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        const container = modal.querySelector('.modal-container');
        const id = document.getElementById('editNewsId').value;

        // এনিমেশন রিভার্স
        container.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            
            if(id) {
                fetch(`/news/${id}/unlock`).then(() => location.reload());
            } else {
                location.reload();
            }
        }, 300);
    }

    // AI ও ড্রাফট ফাংশন (সেম লজিক, জাস্ট UX ইমপ্রুভমেন্ট)
    function triggerAI() {
        const id = document.getElementById('editNewsId').value;
        const btn = document.getElementById('aiBtn');
        const original = btn.innerHTML;
        
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI রিরাইট করছে...';

        fetch(`/news/${id}/process-ai`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('AI প্রসেসিং শুরু হয়েছে। পেজটি অটো রিফ্রেশ হবে।');
                location.reload();
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = original;
            }
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
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> প্রসেসিং...';

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