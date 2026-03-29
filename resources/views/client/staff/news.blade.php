@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center shadow-sm">
            <span class="text-3xl font-black text-slate-700">{{ $stats['total'] }}</span>
            <p class="text-xs font-bold text-slate-400 uppercase mt-1">Total News</p>
        </div>
        <div class="bg-emerald-50 rounded-xl border border-emerald-100 p-4 text-center shadow-sm">
            <span class="text-3xl font-black text-emerald-600">{{ $stats['published'] }}</span>
            <p class="text-xs font-bold text-emerald-500 uppercase mt-1">Published (All-time)</p>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-100 p-4 text-center shadow-sm">
            <span class="text-3xl font-black text-amber-600">{{ $stats['draft'] }}</span>
            <p class="text-xs font-bold text-amber-500 uppercase mt-1">Drafts (All-time)</p>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-100 p-4 shadow-sm text-left">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-bold text-blue-500 uppercase">আজকের রিপোর্ট</span>
                <span class="text-xl font-black text-blue-600">{{ $stats['today_total'] }}</span>
            </div>
            <div class="grid grid-cols-2 gap-1 text-[10px] font-semibold text-slate-600 mt-2">
                <div class="bg-white/50 px-2 py-1 rounded">✅ Publish: <span class="text-emerald-600">{{ $stats['today_published'] }}</span></div>
                <div class="bg-white/50 px-2 py-1 rounded">🤖 AI Rewrite: <span class="text-blue-600">{{ $stats['today_rewritten'] }}</span></div>
                <div class="bg-white/50 px-2 py-1 rounded mt-1 col-span-2">✍️ Custom/Manual: <span class="text-purple-600">{{ $stats['today_custom'] }}</span></div>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <th class="px-4 py-3 font-bold">#</th>
                        <th class="px-4 py-3 font-bold">শিরোনাম</th>
                        <th class="px-4 py-3 font-bold">সোর্স/প্রেরক</th>
                        <th class="px-4 py-3 font-bold">স্ট্যাটাস</th>
                        <th class="px-4 py-3 font-bold">পোস্ট</th>
                        <th class="px-4 py-3 font-bold">তারিখ</th>
                        <th class="px-4 py-3 font-bold text-right">লিঙ্ক</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($news as $item)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $news->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-sm font-semibold text-slate-800 truncate" title="{{ $item->title }}">
                                {{ $item->title ?? $item->ai_title ?? '(শিরোনাম নেই)' }}
                            </p>
                            @if($item->is_rewritten)
                                <span class="text-[10px] bg-blue-50 text-blue-600 border border-blue-100 px-1.5 py-0.5 rounded font-bold">🤖 AI Rewritten</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($item->website)
                                <span class="text-slate-600 font-bold bg-slate-100 px-2 py-0.5 rounded whitespace-nowrap"><i class="fa-solid fa-robot mr-1 text-slate-400"></i>Observed <span class="text-[10px] font-normal text-slate-500">({{ $item->website->name }})</span></span>
                            @elseif($item->reporter)
                                <span class="text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded whitespace-nowrap"><i class="fa-solid fa-user-pen mr-1"></i>Reporter <span class="text-[10px] font-normal text-indigo-400">({{ $item->reporter->name }})</span></span>
                            @else
                                <span class="text-purple-600 font-bold bg-purple-50 px-2 py-0.5 rounded whitespace-nowrap"><i class="fa-solid fa-pen-to-square mr-1"></i>Custom</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $status = $item->status ?? 'draft';
                                $colors = [
                                    'published' => 'bg-emerald-100 text-emerald-700',
                                    'draft'     => 'bg-amber-100 text-amber-700',
                                    'pending'   => 'bg-blue-100 text-blue-700',
                                ];
                                $color = $colors[$status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $color }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($item->is_posted)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">✅ Posted</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500">⏳ Not yet</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                            {{ $item->created_at->format('d M, H:i') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($item->original_link)
                                    <a href="{{ $item->original_link }}" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs" title="Original Source">
                                        <i class="fa-solid fa-link"></i>
                                    </a>
                                @endif
                                @if($item->liveUrl)
                                    <a href="{{ $item->liveUrl }}" target="_blank" class="text-emerald-500 hover:text-emerald-700 text-xs" title="Published URL">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-16 text-slate-400">
                                <i class="fa-solid fa-newspaper text-4xl mb-3 block text-slate-200"></i>
                                <p class="font-bold">কোনো নিউজ পাওয়া যায়নি</p>
                                <p class="text-xs mt-1">এই স্টাফ এখনো কোনো নিউজ তৈরি করেননি বা ফিল্টারের সাথে মিল নেই।</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($news->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $news->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
