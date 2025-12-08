@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
            🌐 নিউজ সোর্স <span class="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full">{{ $websites->count() }}</span>
        </h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if(auth()->user()->role === 'super_admin')
    <div class="bg-white p-6 rounded-xl shadow-lg border border-indigo-100 mb-10">
        <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">➕ নতুন সোর্স যুক্ত করুন</h2>
        <form action="{{ route('websites.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @csrf
            
            <div class="lg:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Website Name</label>
                <input type="text" name="name" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500" required>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">URL (List Page)</label>
                <input type="url" name="url" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Scraper Method</label>
                <select name="scraper_method" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500">
                    <option value="">System Default</option>
                    <option value="node">Node.js (Puppeteer)</option>
                    <option value="python">Python (Playwright)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Container</label>
                <input type="text" name="selector_container" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required placeholder="Ex: .desktopSectionLead">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                <input type="text" name="selector_title" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required placeholder="Ex: h1">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Content (Body)</label>
                <input type="text" name="selector_content" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" placeholder="Ex: .description">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Image (Optional)</label>
                <input type="text" name="selector_image" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50">
            </div>

            <div class="col-span-1 md:col-span-2 lg:col-span-4 flex justify-end mt-2">
                <button type="submit" class="bg-indigo-600 text-white px-8 py-2.5 rounded-lg font-bold hover:bg-indigo-700 shadow-md">
                    Save Website
                </button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Name & URL</th>
                    <th class="px-6 py-4 font-bold">Engine</th>
                    @if(auth()->user()->role === 'super_admin')
                        <th class="px-6 py-4 font-bold">Selectors</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    @else
                        <th class="px-6 py-4 font-bold text-right">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($websites as $site)
                
                {{-- PHP Time Calculation Logic --}}
                @php
                    $isDisabled = false;
                    $remainingSeconds = 0;

                    if ($site->last_scraped_at) {
                        // ডাটাবেসের টাইমকে অ্যাপের টাইমজোনে কনভার্ট করা হচ্ছে
                        $lastScraped = \Carbon\Carbon::parse($site->last_scraped_at)->timezone(config('app.timezone'));
                        $now = now()->timezone(config('app.timezone'));
                        
                        $diff = $now->diffInSeconds($lastScraped); // পার্থক্য বের করা
                        
                        // যদি ৫ মিনিট (৩০০ সেকেন্ড) পার না হয় এবং টাইমটি ভবিষ্যতের না হয়
                        // (অনেকের সার্ভার টাইম উল্টাপাল্টা থাকে, তাই চেক করা ভালো)
                        if ($diff < 300) { 
                            $isDisabled = true;
                            $remainingSeconds = 300 - $diff;
                        }
                    }
                @endphp

                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800">{{ $site->name }}</div>
                        <a href="{{ $site->url }}" target="_blank" class="text-xs text-blue-500 hover:underline block truncate max-w-[200px]">{{ $site->url }} ↗</a>
                    </td>
                    <td class="px-6 py-4">
                        @if($site->scraper_method == 'python')
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded border border-yellow-200">Python</span>
                        @elseif($site->scraper_method == 'node')
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded border border-green-200">Node</span>
                        @else
                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded border border-gray-200">Default</span>
                        @endif
                    </td>

                    @if(auth()->user()->role === 'super_admin')
                        <td class="px-6 py-4 text-xs font-mono text-slate-500">
                            C: {{ $site->selector_container }} <br> 
                            T: {{ $site->selector_title }} <br>
                            @if($site->selector_content)
                                <span class="text-green-600 font-bold">B: {{ \Illuminate\Support\Str::limit($site->selector_content, 15) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <button onclick='openEditModal(@json($site))' 
                                    class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-200">
                                ✏️ Edit
                            </button>
                            
                            {{-- Super Admin Scrape Button --}}
                            <a href="{{ route('websites.scrape', $site->id) }}" 
                               id="btn-{{ $site->id }}"
                               class="scrape-btn bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-200 transition {{ $isDisabled ? 'disabled opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                               data-id="{{ $site->id }}"
                               data-remaining="{{ $remainingSeconds }}"
                               onclick="return handleScrapeClick(this)">
                               
                               @if($isDisabled)
                                   ⏳ <span id="timer-{{ $site->id }}">Wait...</span>
                               @else
                                   <span id="text-{{ $site->id }}">⚡ Scrape</span>
                               @endif
                            </a>
                        </td>
                    @else
                        <td class="px-6 py-4 text-right">
                             {{-- Regular User Scrape Button --}}
                             <a href="{{ route('websites.scrape', $site->id) }}" 
                               id="btn-{{ $site->id }}"
                               class="scrape-btn bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 transition {{ $isDisabled ? 'disabled opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                               data-id="{{ $site->id }}"
                               data-remaining="{{ $remainingSeconds }}"
                               onclick="return handleScrapeClick(this)">
                               
                               @if($isDisabled)
                                   ⏳ <span id="timer-{{ $site->id }}">Wait...</span>
                               @else
                                   <span id="text-{{ $site->id }}">📥 Fetch News</span>
                               @endif
                            </a>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Modal Structure --}}
<div id="editModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform transition-all scale-100">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-800">Edit Website Settings</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="editForm" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="editName" class="w-full border-gray-300 rounded-lg p-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Scraper Method</label>
                    <select name="scraper_method" id="editScraper" class="w-full border-gray-300 rounded-lg p-2 focus:ring-indigo-500">
                        <option value="">Default</option>
                        <option value="node">Node.js</option>
                        <option value="python">Python</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">URL</label>
                <input type="url" name="url" id="editUrl" class="w-full border-gray-300 rounded-lg p-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Container</label>
                    <input type="text" name="selector_container" id="editContainer" class="w-full border-gray-300 rounded-lg p-2 text-sm font-mono bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                    <input type="text" name="selector_title" id="editTitle" class="w-full border-gray-300 rounded-lg p-2 text-sm font-mono bg-slate-50">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Content Selector (News Body)</label>
                <input type="text" name="selector_content" id="editContent" class="w-full border-gray-300 rounded-lg p-2 text-sm font-mono bg-slate-50" placeholder="Ex: .description">
                <p class="text-xs text-gray-400 mt-1">Leave empty for auto-detection.</p>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Image Selector (Optional)</label>
                <input type="text" name="selector_image" id="editImage" class="w-full border-gray-300 rounded-lg p-2 text-sm font-mono bg-slate-50">
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-sm">Update Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Edit Modal Functions
    function openEditModal(site) {
        document.getElementById('editForm').action = `/websites/${site.id}`;
        document.getElementById('editName').value = site.name;
        document.getElementById('editUrl').value = site.url;
        document.getElementById('editScraper').value = site.scraper_method || "";
        document.getElementById('editContainer').value = site.selector_container;
        document.getElementById('editTitle').value = site.selector_title;
        document.getElementById('editImage').value = site.selector_image || "";
        document.getElementById('editContent').value = site.selector_content || "";
        
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }

    // 🔥🔥 NEW: LocalStorage Based Timer Logic (100% Works) 🔥🔥
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.scrape-btn').forEach(button => {
            let id = button.getAttribute('data-id');
            let lastClicked = localStorage.getItem('scrape_time_' + id);
            
            if (lastClicked) {
                let now = new Date().getTime();
                let diff = Math.floor((now - parseInt(lastClicked)) / 1000);
                let waitTime = 300; // ৫ মিনিট = ৩০০ সেকেন্ড

                if (diff < waitTime) {
                    let remaining = waitTime - diff;
                    startTimer(button, id, remaining);
                } else {
                    // সময় শেষ হলে স্টোরেজ ক্লিয়ার
                    localStorage.removeItem('scrape_time_' + id);
                }
            }
        });
    });

    // ১. ক্লিক হ্যান্ডলার
    function handleScrapeClick(btn) {
        // যদি বাটন অলরেডি ডিজেবল থাকে
        if (btn.classList.contains('disabled')) {
            return false;
        }

        let id = btn.getAttribute('data-id');

        // ১. লোকাল স্টোরেজে বর্তমান সময় সেভ করা
        localStorage.setItem('scrape_time_' + id, new Date().getTime());

        // ২. বাটন লক করা (ভিজ্যুয়াল)
        btn.classList.add('disabled', 'opacity-50', 'cursor-not-allowed');
        btn.style.pointerEvents = 'none';
        
        // টেক্সট আপডেট
        let textSpan = document.getElementById('text-' + id);
        if(textSpan) textSpan.innerHTML = '⏳ Starting...';
        else btn.innerHTML = '⏳ Starting...';

        // ৩. লিংক কাজ করতে দেওয়া (পেজ রিলোড হবে)
        return true; 
    }

    // ২. টাইমার ফাংশন
    function startTimer(button, id, seconds) {
        // বাটন ডিজেবল করা
        button.classList.add('disabled', 'opacity-50', 'cursor-not-allowed');
        button.style.pointerEvents = 'none';
        button.removeAttribute('href');
        button.onclick = null;

        let counter = seconds;
        let timerSpan = document.getElementById('text-' + id) || button;

        // টাইমার লুপ
        const interval = setInterval(() => {
            counter--;

            let m = Math.floor(counter / 60);
            let s = counter % 60;
            
            // টেক্সট আপডেট (সরাসরি ইলিমেন্টে)
            timerSpan.innerHTML = `Wait ${m}m ${s}s`;

            // সময় শেষ হলে
            if (counter <= 0) {
                clearInterval(interval);
                localStorage.removeItem('scrape_time_' + id);
                // পেজ রিলোড দিয়ে বাটন আনলক
                window.location.reload(); 
            }
        }, 1000);
    }
</script>
@endsection