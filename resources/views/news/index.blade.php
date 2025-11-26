@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
</style>

@if ($errors->any())
    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
        <p class="font-bold">সতর্কতা:</p>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Header Section --}}
<div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-gradient-to-r from-indigo-900 to-slate-900 p-6 rounded-2xl shadow-2xl text-white border border-indigo-700/50">
    <div>
        <h2 class="text-3xl font-bold font-bangla flex items-center gap-2">
            📰 নিউজ স্টুডিও প্রো <span class="text-xs bg-indigo-500 px-2 py-0.5 rounded-full uppercase">SaaS</span>
        </h2>
        
        @if($settings && $settings->is_auto_posting)
            <div class="mt-3 flex items-center gap-3 bg-indigo-900/50 p-2 rounded-lg border border-indigo-500/30">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-indigo-200 text-sm font-mono">নেক্সট পোস্ট:</span> 
                <span id="countdownTimer" class="font-bold text-white text-lg font-mono tracking-widest">গণনা হচ্ছে...</span>
            </div>
        @else
            <p class="text-gray-400 text-sm mt-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                অটোমেশন বর্তমানে বন্ধ আছে।
            </p>
        @endif
    </div>

    <div class="flex gap-4 mt-4 md:mt-0 items-center">
        <form action="{{ route('news.toggle-automation') }}" method="POST" class="flex items-center gap-0 bg-slate-800 rounded-lg border border-slate-600 overflow-hidden shadow-lg">
            @csrf
            
            <div class="relative group border-r border-slate-600">
                <input type="number" name="interval" 
                       value="{{ $settings->auto_post_interval ?? 10 }}" 
                       min="1" max="60" 
                       class="w-20 bg-slate-800 text-white text-center font-bold py-2.5 px-2 text-sm focus:bg-slate-700 outline-none transition-colors"
                       title="মিনিট সেট করুন"
                       {{ ($settings && $settings->is_auto_posting) ? 'disabled' : '' }}>
                <span class="absolute top-2.5 right-1 text-[10px] text-gray-400 font-sans">MIN</span>
            </div>

            <button type="submit" 
                class="px-5 py-2.5 font-bold text-sm uppercase tracking-wider transition-all duration-300 flex items-center gap-2
                {{ ($settings && $settings->is_auto_posting) 
                    ? 'bg-red-500 hover:bg-red-600 text-white shadow-[inset_0_0_10px_rgba(0,0,0,0.2)]' 
                    : 'bg-green-600 hover:bg-green-500 text-white' }}">
                
                @if($settings && $settings->is_auto_posting)
                    <span>🛑 STOP</span>
                @else
                    <span>🚀 START</span>
                @endif
            </button>
        </form>

        <a href="{{ route('settings.index') }}" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2.5 rounded-lg transition border border-white/10 backdrop-blur-md">
            ⚙️
        </a>
    </div>
</div>

{{-- Logic Block (Merged from Snippet 1) --}}
@if($settings && $settings->is_auto_posting)
    @php
        $intervalMinutes = $settings->auto_post_interval ?? 10;
        $lastPost = $settings->last_auto_post_at ? \Carbon\Carbon::parse($settings->last_auto_post_at) : now();
        $nextPost = $lastPost->addMinutes($intervalMinutes);
        
        // বাফার টাইম
        if($nextPost->isPast()) $nextPost = now()->addSeconds(2);
        $targetTimeStr = $nextPost->format('Y-m-d H:i:s');
    @endphp

    <script>
        let serverNow = new Date("{{ now()->format('Y-m-d H:i:s') }}").getTime();
        // শুরুতে পিএইচপি থেকে টাইম নিবে
        let targetTime = new Date("{{ $targetTimeStr }}").getTime();
        let isChecking = false; // সার্ভারে রিকোয়েস্ট পাঠানো হচ্ছে কিনা চেক করার জন্য

        const timer = setInterval(function() {
            serverNow += 1000;
            const distance = targetTime - serverNow;

            const timerElement = document.getElementById("countdownTimer");

            if (distance < 0) {
                // ১. টাইম শেষ, প্রসেসিং দেখাচ্ছে
                if(timerElement) {
                    timerElement.innerHTML = "PROCESSING...";
                    timerElement.className = "font-bold text-yellow-400 text-lg font-mono tracking-widest animate-pulse";
                }

                // ২. সার্ভারে চেক করা পোস্ট হয়েছে কিনা (AJAX)
                if (!isChecking) {
                    isChecking = true;
                    // ৫ সেকেন্ড পর পর চেক করবে
                    setTimeout(checkServerStatus, 5000); 
                }

            } else {
                // সাধারণ কাউন্টডাউন
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                if(timerElement) {
                    timerElement.className = "font-bold text-white text-lg font-mono tracking-widest";
                    timerElement.innerHTML = minutes + "m " + seconds + "s";
                }
            }
        }, 1000);

        // ✅ সার্ভারের সাথে কথা বলার ফাংশন
        function checkServerStatus() {
            fetch("{{ route('news.check-status') }}")
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'on') {
                        // সার্ভার থেকে নতুন টাইম আসবে
                        let newTargetTime = new Date(data.next_post_time).getTime();

                        // যদি সার্ভারের নতুন টাইম > আমাদের বর্তমান টার্গেট টাইম হয়
                        // তার মানে ডাটাবেস আপডেট হয়েছে!
                        if (newTargetTime > targetTime) {
                            console.log("New Post Detected! Updating Timer...");
                            targetTime = newTargetTime; // নতুন টাইম সেট করা হলো
                            
                            // প্রসেসিং টেক্সট সরিয়ে নরমাল করা
                            const timerElement = document.getElementById("countdownTimer");
                            if(timerElement) {
                                timerElement.classList.remove("text-yellow-400", "animate-pulse");
                            }
                        }
                    }
                    isChecking = false; // চেকিং শেষ, আবার চেক করার অনুমতি
                })
                .catch(error => {
                    console.error('Error:', error);
                    isChecking = false;
                });
        }
    </script>
@endif

{{-- News Grid Section --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($newsItems as $item)
    <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden transform hover:-translate-y-1 relative">
        
        @if($item->is_posted)
            <div class="absolute top-3 right-3 z-20 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                POSTED
            </div>
        @endif

        <div class="h-48 overflow-hidden relative bg-gray-100">
            @if($item->thumbnail_url)
                <img src="{{ $item->thumbnail_url }}" alt="Thumb" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            @else
                <div class="flex items-center justify-center h-full bg-slate-100 text-slate-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            @endif
            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-xs font-bold px-2 py-1 rounded-md text-indigo-700 shadow-sm z-10">
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
                    <button class="col-span-2 bg-green-50 text-green-600 py-2 rounded-lg border border-green-200 text-sm font-semibold cursor-default flex items-center justify-center gap-1 opacity-75">
                        ✅ Already Posted
                    </button>
                @else
                    <form action="{{ route('news.queue', $item->id) }}" method="POST" class="col-span-1">
                        @csrf
                        <button type="submit" 
                            class="w-full py-2 rounded-lg transition text-sm font-bold flex items-center justify-center gap-1 border
                            {{ $item->is_queued 
                                ? 'bg-orange-100 text-orange-600 border-orange-200 hover:bg-orange-200' 
                                : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200' }}"
                            title="{{ $item->is_queued ? 'Remove from Auto Post' : 'Add to Auto Post Priority' }}">
                            
                            @if($item->is_queued)
                                📌 Queued
                            @else
                                ➕ Queue
                            @endif
                        </button>
                    </form>

                    <form action="{{ route('news.post', $item->id) }}" method="POST" class="col-span-1">
                        @csrf
                        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-900 transition text-sm font-semibold flex items-center justify-center gap-1" onclick="return confirm('এখনই পোস্ট করতে চান?')">
                            🚀 Post
                        </button>
                    </form>
                @endif
                
                <a href="{{ $item->original_link }}" target="_blank" class="col-span-2 text-xs text-center text-gray-400 hover:text-indigo-500 mt-1">
                    🔗 মূল খবর দেখুন
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-8">
    {{ $newsItems->links() }}
</div>
@endsection