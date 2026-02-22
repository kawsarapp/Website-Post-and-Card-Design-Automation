@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="max-w-6xl mx-auto py-6 sm:py-8 px-4">
    
    {{-- Top Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-indigo-600"></i>
                আমার খবরসমূহ
            </h1>
            <p class="text-slate-500 text-sm mt-1">আপনার পাঠানো সকল খবরের তালিকা এবং স্ট্যাটাস এখানে দেখুন।</p>
        </div>
        
        <a href="{{ route('reporter.news.create') }}" class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all transform active:scale-95">
            <i class="fa-solid fa-plus"></i>
            নতুন খবর পাঠান
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">মোট খবর</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $news->total() }}টি</h3>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">অপেক্ষমান</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $news->where('is_posted', false)->count() }}টি</h3>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">পাবলিশড</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $news->where('is_posted', true)->count() }}টি</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- News Table Section --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">খবর</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">লোকেশন</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">তারিখ</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">স্ট্যাটাস</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="news-table-body">
                    @forelse($news as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group" data-news-id="{{ $item->id }}" data-status="{{ $item->is_posted ? 'published' : 'pending' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-12 shrink-0 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 relative">
                                        <img src="{{ $item->thumbnail_url }}" alt="Thumbnail" class="w-full h-full object-cover">
                                        @if(!$item->is_posted)
                                            <div class="absolute inset-0 bg-black/10 flex items-center justify-center">
                                                <i class="fa-solid fa-spinner fa-spin text-white/70 text-sm"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="max-w-[250px]">
                                        <h4 class="font-bold text-slate-800 text-sm line-clamp-1 group-hover:text-indigo-600 transition-colors" title="{{ $item->title }}">
                                            {{ $item->title }}
                                        </h4>
                                        <p class="text-[10px] text-slate-400 mt-1">
                                            <i class="fa-solid fa-hashtag mr-1"></i> ID: #{{ $item->id }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold text-slate-600 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-slate-300"></i>
                                    {{ $item->location ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-500">
                                {{ $item->created_at->format('d M, Y') }} <br>
                                <span class="text-[10px] text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                            </td>
                            
                            {{-- Status Column --}}
                            <td class="px-6 py-4 status-container">
                                @if($item->is_posted && $item->live_url)
                                    <a href="{{ $item->live_url }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wide border border-emerald-100 hover:bg-emerald-100 transition-colors" title="Click to view live">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                        পাবলিশড (লিঙ্ক)
                                    </a>
                                @elseif($item->is_posted)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wide border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        পাবলিশড
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-wide border border-amber-100" title="অ্যাডমিন রিভিউ করছেন">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                                        অপেক্ষমান
                                    </span>
                                @endif
                            </td>
                            
                            {{-- Action Column --}}
                            <td class="px-6 py-4 text-right action-container">
                                <div class="flex justify-end gap-2">
                                    @if($item->is_posted && $item->live_url)
                                        <button onclick="copyToClipboard('{{ $item->live_url }}')" class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-slate-200" title="লিঙ্ক কপি করুন">
                                            <i class="fa-solid fa-copy text-sm"></i>
                                        </button>

                                        <a href="{{ $item->live_url }}" target="_blank" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100" title="লাইভ খবর দেখুন">
                                            <i class="fa-solid fa-globe text-sm"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-paper-plane text-5xl mb-4 opacity-30"></i>
                                    <p class="text-sm font-medium">আপনি এখনও কোনো খবর পাঠাননি।</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($news->hasPages())
            <div class="px-6 py-4 bg-slate-50/30 border-t border-slate-100">
                {{ $news->links() }}
            </div>
        @endif
    </div>
</div>

<script>
// --- 1. Copy to Clipboard with SweetAlert Toast ---
function copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        Toast.fire({
            icon: 'success',
            title: 'লাইভ লিঙ্ক কপি করা হয়েছে!'
        });
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

// --- 2. Real-time Status Polling (Auto Update) ---
// This checks if pending news got published by admin
document.addEventListener('DOMContentLoaded', function() {
    
    function checkPendingNews() {
        let pendingRows = document.querySelectorAll('tr[data-status="pending"]');
        let ids = Array.from(pendingRows).map(row => row.getAttribute('data-news-id'));

        if (ids.length === 0) return; // No pending news, stop polling

        fetch("{{ route('news.check-draft-updates') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            data.forEach(news => {
                // If status changed to published or has live_url
                if (news.status === 'published' || news.is_posted === 1 || news.live_url) {
                    
                    let row = document.querySelector(`tr[data-news-id="${news.id}"]`);
                    if(row) {
                        row.setAttribute('data-status', 'published');
                        
                        // Update Status Badge
                        let statusHtml = `
                            <a href="${news.live_url}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wide border border-emerald-100 hover:bg-emerald-100 transition-colors" title="Click to view live">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                পাবলিশড (লিঙ্ক)
                            </a>
                        `;
                        row.querySelector('.status-container').innerHTML = statusHtml;

                        // Update Action Buttons
                        let actionHtml = `
                            <div class="flex justify-end gap-2">
                                <button onclick="copyToClipboard('${news.live_url}')" class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-slate-200" title="লিঙ্ক কপি করুন">
                                    <i class="fa-solid fa-copy text-sm"></i>
                                </button>
                                <a href="${news.live_url}" target="_blank" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100" title="লাইভ খবর দেখুন">
                                    <i class="fa-solid fa-globe text-sm"></i>
                                </a>
                            </div>
                        `;
                        row.querySelector('.action-container').innerHTML = actionHtml;

                        // Remove spinner from image
                        let spinner = row.querySelector('.fa-spinner');
                        if(spinner) spinner.parentElement.remove();
                    }
                }
            });
        })
        .catch(err => console.error("Auto Update Error:", err));
    }

    // Check every 10 seconds
    setInterval(checkPendingNews, 10000);
});
</script>
@endsection