@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
</style>

<div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-gradient-to-r from-indigo-600 to-purple-700 p-6 rounded-2xl shadow-lg text-white">
    <div>
        <h2 class="text-3xl font-bold font-bangla">📰 নিউজ স্টুডিও প্রো</h2>
        <p class="text-indigo-100 text-sm opacity-90">Advanced Content Creator & Scraper</p>
    </div>
    <div class="flex gap-3 mt-4 md:mt-0">
        <a href="{{ route('websites.index') }}" class="bg-white/10 backdrop-blur-md border border-white/20 text-white px-5 py-2.5 rounded-lg hover:bg-white/20 transition flex items-center gap-2">
            ⚙️ Scraper Settings
        </a>
    </div>
</div>



<div class="flex flex-col md:flex-row justify-between items-center ...">
    <div>
        <h2 class="text-3xl font-bold font-bangla">📰 নিউজ স্টুডিও প্রো</h2>
        </div>
    
    <div class="flex gap-3 mt-4 md:mt-0 items-center">
    
    <form action="{{ route('news.toggle-automation') }}" method="POST" class="flex items-center gap-2 bg-white/10 p-1 pr-3 rounded-lg border border-white/20">
        @csrf
        @php
            $isAutoOn = \Illuminate\Support\Facades\Cache::get('auto_post_enabled', false);
            $savedInterval = \Illuminate\Support\Facades\Cache::get('auto_post_interval', 5);
        @endphp
        
        <div class="relative">
            <input type="number" name="interval" value="{{ $savedInterval }}" min="1" max="60" 
                   class="w-16 bg-white/20 text-white text-center font-bold rounded-md py-1.5 px-1 text-sm focus:ring-2 focus:ring-green-400 outline-none border border-transparent" 
                   title="কত মিনিট পর পর পোস্ট হবে?" {{ $isAutoOn ? 'disabled' : '' }}>
            <span class="absolute -top-2 -right-2 bg-indigo-600 text-[10px] px-1 rounded text-white">min</span>
        </div>

        <button type="submit" class="flex items-center gap-2 px-3 py-1.5 rounded-md transition font-bold text-xs uppercase tracking-wide {{ $isAutoOn ? 'bg-green-500 text-white shadow-[0_0_10px_rgba(34,197,94,0.6)]' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
            <span class="relative flex h-2.5 w-2.5">
              @if($isAutoOn)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
              @endif
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $isAutoOn ? 'bg-white' : 'bg-red-400' }}"></span>
            </span>
            {{ $isAutoOn ? 'ON' : 'OFF' }}
        </button>
    </form>

    <a href="{{ route('websites.index') }}" ...> ... </a>
</div>
</div>

@if(\Illuminate\Support\Facades\Cache::get('auto_post_enabled', false))
    <div class="bg-green-50 border-l-4 border-green-500 p-2 mb-6 rounded-r text-sm text-green-700 flex justify-between">
        <span>🤖 অটোমেশন মোড চালু আছে।</span>
        <span>পরবর্তী পোস্ট: {{ \Illuminate\Support\Facades\Cache::get('next_auto_post_time') ? \Carbon\Carbon::parse(\Illuminate\Support\Facades\Cache::get('next_auto_post_time'))->format('h:i:s A') : 'Processing...' }}</span>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($newsItems as $item)
    <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden transform hover:-translate-y-1">
        
        <div class="h-48 overflow-hidden relative bg-gray-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            @else
                <div class="flex items-center justify-center h-full bg-slate-100 text-slate-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            @endif
            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-xs font-bold px-2 py-1 rounded-md text-indigo-700 shadow-sm">
                {{ $item->website->name }}
            </span>
        </div>
       
        <div class="p-5 flex flex-col flex-1">
            <h3 class="text-lg font-bold leading-snug mb-3 text-gray-800 font-bangla line-clamp-2 group-hover:text-indigo-600 transition-colors">
                {{ $item->title }}
            </h3>
            
            <div class="text-xs text-gray-500 flex items-center gap-2 mb-4">
                <span class="bg-gray-100 px-2 py-1 rounded">📅 {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->diffForHumans() : 'Just now' }}</span>
            </div>

            <div class="mt-auto grid grid-cols-2 gap-2">
                <a href="{{ route('news.studio', $item->id) }}" 
                   class="col-span-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-2.5 rounded-lg text-sm font-bold hover:shadow-lg transition flex items-center justify-center gap-2 active:scale-95">
                    🎨 ডিজাইন করুন
                </a>
                
                @if($item->is_posted)
                    <button class="col-span-2 bg-green-50 text-green-600 py-2 rounded-lg border border-green-200 text-sm font-semibold cursor-default flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Posted
                    </button>
                @else
                    <form action="{{ route('news.post', $item->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-900 transition text-sm font-semibold flex items-center justify-center gap-1" onclick="return confirm('পোস্ট করতে চান?')">
                            🚀 WP Post
                        </button>
                    </form>
                    <a href="{{ $item->original_link }}" target="_blank" class="bg-gray-100 text-gray-600 py-2 rounded-lg hover:bg-gray-200 transition flex items-center justify-center border border-gray-200" title="Visit Link">
                        🔗
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-8">
    {{ $newsItems->links() }}
</div>
@endsection