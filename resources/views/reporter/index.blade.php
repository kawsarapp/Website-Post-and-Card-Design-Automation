@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    /* 📱 মোবাইল ভিউ এর জন্য কার্ড লেআউট ফিক্স */
    @media (max-width: 768px) {
        .responsive-table thead { display: none; }
        .responsive-table tr { 
            display: flex; flex-direction: column; 
            margin-bottom: 1rem; border: 1px solid #e2e8f0; 
            border-radius: 1rem; background: white; padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .responsive-table td { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 0.5rem 0; border: none; text-align: right;
            border-bottom: 1px dashed #f1f5f9;
        }
        .responsive-table td:last-child { border-bottom: none; padding-bottom: 0; }
        .responsive-table td::before { 
            content: attr(data-label); 
            font-weight: 800; color: #94a3b8; font-size: 0.7rem; 
            text-transform: uppercase; text-align: left;
        }
        .responsive-table td .flex-col { text-align: right; align-items: flex-end; }
        .action-cell { justify-content: flex-end !important; margin-top: 0.5rem; }
    }
</style>

<div class="max-w-7xl mx-auto py-6 sm:py-8 px-4 font-bangla min-h-screen bg-slate-50/30">
    
    {{-- Top Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 flex items-center gap-3">
                <span class="p-3 bg-indigo-100 text-indigo-600 rounded-2xl shadow-sm">
                    <i class="fa-solid fa-folder-open"></i>
                </span>
                আমার খবরসমূহ
            </h1>
            <p class="text-slate-500 text-sm mt-2 font-medium">আপনার পাঠানো সকল খবরের তালিকা এবং বর্তমান স্ট্যাটাস</p>
        </div>
        
        <a href="{{ route('reporter.news.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all transform hover:-translate-y-1 active:scale-95 group">
            <i class="fa-solid fa-feather-pointed group-hover:rotate-12 transition-transform"></i>
            নতুন খবর তৈরি করুন
        </a>
    </div>

    {{-- Stats Cards (Premium Look) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-[1.5rem] border border-slate-100 shadow-sm transition hover:shadow-lg hover:-translate-y-1 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-slate-50 rounded-full opacity-50 group-hover:scale-150 transition-transform"></div>
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-blue-100 relative z-10"><i class="fa-solid fa-layer-group"></i></div>
            <div class="relative z-10">
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">মোট খবর</p>
                <h3 class="text-2xl font-black text-slate-800 leading-none">{{ collect(method_exists($news, 'items') ? $news->items() : $news)->count() }} <span class="text-sm text-slate-400 font-bold">টি</span></h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-[1.5rem] border border-slate-100 shadow-sm transition hover:shadow-lg hover:-translate-y-1 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-50 rounded-full opacity-50 group-hover:scale-150 transition-transform"></div>
            <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-amber-100 relative z-10"><i class="fa-solid fa-hourglass-half fa-spin-pulse" style="--fa-animation-duration: 3s;"></i></div>
            <div class="relative z-10">
                <p class="text-amber-500/70 text-[10px] font-black uppercase tracking-widest mb-1">অপেক্ষমান</p>
                <h3 class="text-2xl font-black text-slate-800 leading-none">{{ collect(method_exists($news, 'items') ? $news->items() : $news)->where('is_posted', false)->count() }} <span class="text-sm text-slate-400 font-bold">টি</span></h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[1.5rem] border border-slate-100 shadow-sm transition hover:shadow-lg hover:-translate-y-1 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-50 rounded-full opacity-50 group-hover:scale-150 transition-transform"></div>
            <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-emerald-100 relative z-10"><i class="fa-solid fa-circle-check"></i></div>
            <div class="relative z-10">
                <p class="text-emerald-500/70 text-[10px] font-black uppercase tracking-widest mb-1">পাবলিশড</p>
                <h3 class="text-2xl font-black text-slate-800 leading-none">{{ collect(method_exists($news, 'items') ? $news->items() : $news)->where('is_posted', true)->count() }} <span class="text-sm text-slate-400 font-bold">টি</span></h3>
            </div>
        </div>
    </div>

    {{-- News Table Section --}}
    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto p-1">
            <table class="w-full text-left border-collapse responsive-table">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">সংবাদ ও ছবি</th>
                        <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">লোকেশন</th>
                        <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">সময়কাল</th>
                        <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">বর্তমান অবস্থা</th>
                        <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="news-table-body">
                    @forelse($news as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors group" data-news-id="{{ $item->id }}" data-status="{{ $item->is_posted ? 'published' : ($item->status === 'failed' ? 'failed' : 'pending') }}">
                            
                            {{-- Thumbnail & Title --}}
                            <td class="px-8 py-5" data-label="সংবাদ">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-14 shrink-0 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative group-hover:shadow-md transition-shadow">
                                        <img src="{{ $item->thumbnail_url }}" alt="Thumbnail" class="w-full h-full object-cover">
                                        @if(!$item->is_posted && $item->status !== 'failed')
                                            <div class="absolute inset-0 bg-black/20 flex items-center justify-center backdrop-blur-[1px]">
                                                <i class="fa-solid fa-circle-notch fa-spin text-white"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="max-w-[280px]">
                                        <h4 class="font-bold text-slate-800 text-sm line-clamp-2 leading-snug group-hover:text-indigo-600 transition-colors" title="{{ $item->title }}">
                                            {{ $item->title }}
                                        </h4>
                                        <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-wider">
                                            ID: <span class="text-slate-500">#{{ $item->id }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Location --}}
                            <td class="px-6 py-5" data-label="লোকেশন">
                                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200 flex items-center gap-1.5 w-fit">
                                    <i class="fa-solid fa-location-dot text-rose-400"></i>
                                    {{ $item->location ?? 'লোকেশন নেই' }}
                                </span>
                            </td>
                            
                            {{-- Date --}}
                            <td class="px-6 py-5" data-label="সময়কাল">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-700">{{ $item->created_at->format('d M, Y') }}</span>
                                    <span class="text-[10px] font-medium text-slate-400 mt-0.5 flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i> {{ $item->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- Status Column --}}
                            <td class="px-6 py-5 text-center status-container" data-label="অবস্থা">
                                @if($item->is_posted && $item->live_url)
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest border border-emerald-200 shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            পাবলিশড
                                        </span>
                                    </div>
                                @elseif($item->status === 'failed')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-[10px] font-black uppercase tracking-widest border border-rose-200" title="{{ $item->error_message }}">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        ফেইলড
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest border border-amber-200" title="অ্যাডমিন রিভিউ করছেন">
                                        <i class="fa-solid fa-hourglass-half fa-spin-pulse" style="--fa-animation-duration: 2s;"></i>
                                        অপেক্ষমান
                                    </span>
                                @endif
                            </td>
                            
                            {{-- Action Column --}}
                            <td class="px-8 py-5 text-right action-container action-cell" data-label="অ্যাকশন">
                                <div class="flex justify-end gap-2">
                                    @if($item->is_posted && $item->live_url)
                                        <button onclick="copyToClipboard('{{ $item->live_url }}')" class="w-9 h-9 flex items-center justify-center bg-white text-slate-500 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-slate-200 hover:border-indigo-200 tooltip" title="লিঙ্ক কপি করুন">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>

                                        <a href="{{ $item->live_url }}" target="_blank" class="w-9 h-9 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100 hover:shadow-emerald-200 tooltip" title="লাইভ দেখুন">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @else
                                        <span class="text-[10px] font-bold text-slate-300 italic">No Actions</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-16">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border-4 border-white shadow-sm">
                                        <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                                    </div>
                                    <h4 class="text-lg font-black text-slate-700 mb-1">কোনো খবর পাওয়া যায়নি!</h4>
                                    <p class="text-sm font-medium text-slate-400 max-w-xs">আপনি এখনও কোনো খবর তৈরি করেননি। নতুন খবর পাঠাতে উপরের বাটনটি ব্যবহার করুন।</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if(method_exists($news, 'hasPages') && $news->hasPages())
            <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100">
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
            toast: true, position: 'bottom-end',
            showConfirmButton: false, timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        Toast.fire({ icon: 'success', title: '<span style="font-family: \'Hind Siliguri\'; font-weight: bold;">লাইভ লিঙ্ক কপি করা হয়েছে!</span>' });
    }).catch(err => console.error('Failed to copy: ', err));
}

// --- 2. Real-time Status Polling (Smooth Auto Update) ---
document.addEventListener('DOMContentLoaded', function() {
    
    function checkPendingNews() {
        let pendingRows = document.querySelectorAll('tr[data-status="pending"]');
        let ids = Array.from(pendingRows).map(row => row.getAttribute('data-news-id'));

        if (ids.length === 0) return; // No pending news

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
                if (news.status === 'published' || news.is_posted === 1 || news.live_url) {
                    let row = document.querySelector(`tr[data-news-id="${news.id}"]`);
                    if(row) {
                        row.setAttribute('data-status', 'published');
                        
                        // Add fade out animation class temporarily
                        row.style.opacity = '0.5';
                        
                        setTimeout(() => {
                            // Update Status Badge
                            let statusHtml = `
                                <div class="inline-flex flex-col items-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest border border-emerald-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        পাবলিশড
                                    </span>
                                </div>
                            `;
                            row.querySelector('.status-container').innerHTML = statusHtml;

                            // Update Action Buttons
                            let actionHtml = `
                                <div class="flex justify-end gap-2">
                                    <button onclick="copyToClipboard('${news.live_url}')" class="w-9 h-9 flex items-center justify-center bg-white text-slate-500 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-slate-200 hover:border-indigo-200 tooltip" title="লিঙ্ক কপি করুন">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                    <a href="${news.live_url}" target="_blank" class="w-9 h-9 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100 hover:shadow-emerald-200 tooltip" title="লাইভ দেখুন">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            `;
                            row.querySelector('.action-container').innerHTML = actionHtml;

                            // Remove spinner from image smoothly
                            let spinner = row.querySelector('.fa-spinner');
                            if(spinner) {
                                spinner.parentElement.style.opacity = '0';
                                setTimeout(() => spinner.parentElement.remove(), 300);
                            }
                            
                            // Restore opacity
                            row.style.opacity = '1';
                        }, 300); // 300ms transition delay
                    }
                }
            });
        })
        .catch(err => console.error("Auto Update Error:", err));
    }

    // Check every 15 seconds to reduce server load slightly
    setInterval(checkPendingNews, 15000);
});
</script>
@endsection