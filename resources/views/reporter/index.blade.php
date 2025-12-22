@extends('layouts.app')

@section('content')
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
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
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
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">অপেক্ষমান (Draft)</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $news->where('status', 'draft')->count() }}টি</h3>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
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
                <tbody class="divide-y divide-slate-50">
                    @forelse($news as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-12 shrink-0 rounded-lg overflow-hidden bg-slate-100 border border-slate-200">
                                        <img src="{{ $item->thumbnail_url }}" alt="Thumbnail" class="w-full h-full object-cover">
                                    </div>
                                    <div class="max-w-[250px]">
                                        <h4 class="font-bold text-slate-800 text-sm line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                            {{ $item->title }}
                                        </h4>
                                        <p class="text-[10px] text-slate-400 mt-1">
                                            <i class="fa-solid fa-link mr-1"></i> {{ Str::limit($item->original_link, 30) }}
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
                            <td class="px-6 py-4">
                                @if($item->is_posted)
                                    <a href="{{ $item->live_url }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wide border border-emerald-100 hover:bg-emerald-100 transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                        পাবলিশড (লিঙ্ক দেখুন)
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-wide border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                        অপেক্ষমান
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    {{-- নিউজ পাবলিশ হলে সরাসরি লাইভ লিঙ্ক বাটন --}}
                                    @if($item->is_posted && $item->live_url)
                                        <a href="{{ $item->live_url }}" target="_blank" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="লাইভ খবর দেখুন">
                                            <i class="fa-solid fa-earth-americas text-sm"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('news.studio', $item->id) }}" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="বিস্তারিত দেখুন">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- ... কোনো নিউজ না থাকলে খালি সেকশন (আগের কোড) ... --}}
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
@endsection