@extends('layouts.app')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<style>
    @import url('https://fonts.maateen.me/solaiman-lipi/font.css');
    .font-bangla { font-family: 'SolaimanLipi', Arial, sans-serif; }
    @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    .skeleton { background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
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
        <div data-news-id="{{ $item->id }}" data-status="{{ $item->status }}" data-status-msg="{{ $item->error_message }}" class="group relative flex flex-col h-full bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            
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

                    <div class="space-y-2">
                        @if($item->error_message)
                            <div class="{{ $item->status == 'failed' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' }} text-[10px] p-2 rounded border mb-2 font-bold text-center leading-tight" title="{{ $item->error_message }}">
                                {{ $item->status == 'failed' ? '⚠️ ' : '✅ ' }} {{ Str::limit($item->error_message, 50) }}
                            </div>
                        @endif

                        @if($item->status == 'published')
                            <div class="flex items-center justify-between bg-emerald-50/50 rounded-lg p-2 border border-emerald-100">
                                <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">Posted</span>
                                @if($item->wp_post_id && optional($settings)->wp_url)
                                    <a href="{{ rtrim($settings->wp_url, '/') }}/?p={{ $item->wp_post_id }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1 transition-colors">লাইভ দেখুন 🔗</a>
                                @else 
                                    <span class="text-[10px] text-gray-400 font-medium">No Link</span> 
                                @endif
                            </div>
                        @elseif($item->status == 'processing' || $item->status == 'publishing')
                            <div class="w-full bg-gray-50 text-gray-500 py-2.5 rounded-lg text-xs font-bold flex items-center justify-center gap-2 border border-gray-100 cursor-wait">
                                <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> 
                                প্রসেসিং হচ্ছে...
                            </div>
                        @elseif($item->status == 'failed')
                            <div class="flex gap-2">
                                <form action="{{ route('news.process-ai', $item->id) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-xs font-bold shadow transition flex items-center justify-center gap-1">🔄 Retry AI</button>
                                </form>
                                <button type="button" onclick="fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" class="px-4 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold shadow transition flex items-center justify-center" title="Manually Fix">📝</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <button type="button" onclick="fetchDraftContent({{ $item->id }}, '{{ $item->thumbnail_url }}')" class="flex-1 group/btn relative flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-lg transition-all duration-300 text-xs font-bold shadow-md hover:shadow-lg hover:shadow-indigo-500/30 overflow-hidden">
                                    <span class="relative z-10 flex items-center gap-2">Edit & Publish</span>
                                </button>
                                <form action="{{ route('news.process-ai', $item->id) }}" method="POST" onsubmit="return confirm('এটি ১ ক্রেডিট কাটবে। আবার AI দিয়ে রিরাইট করতে চান?');">
                                    @csrf
                                    <button type="submit" class="px-4 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 py-2.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center" title="AI দিয়ে আবার লিখুন"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                                </form>
                            </div>
                        @endif
                    </div>
                    
                    @if($item->status != 'published')
                        <button onclick="copyBossLink({{ $item->id }})" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg text-[11px] font-bold border border-blue-200 transition flex items-center justify-center gap-2">🔗 লিঙ্ক কপি করুন</button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    </div>
    <div class="mt-8">{{ $drafts->links() }}</div>
</div>

{{-- 🔥 সংযুক্ত (Included) ফাইলগুলো --}}
@include('news.partials.rewrite-modal')
@include('news.partials.drafts-scripts')

@endsection