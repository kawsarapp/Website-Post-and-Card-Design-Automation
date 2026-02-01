@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">⚙️ প্রোফাইল ও সেটিংস</h1>
            <p class="text-gray-500 mt-1">আপনার নিউজ কার্ড এবং অটোমেশন কনফিগারেশন</p>
        </div>
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-xl shadow-lg text-center">
            <p class="text-xs opacity-80 uppercase tracking-wider">বর্তমান ব্যালেন্স</p>
            <p class="text-2xl font-bold">{{ auth()->user()->credits }} <span class="text-sm font-normal">Credits</span></p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2" role="alert">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    {{-- ১. প্রোফাইল আপডেট সেকশন --}}
    <form action="{{ route('settings.update-profile') }}" method="POST" class="mb-8">
        @csrf
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                👤 আমার প্রোফাইল
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">আপনার নাম</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">ইমেইল (লগিন ইউজারনেম)</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">নতুন পাসওয়ার্ড</label>
                    <input type="password" name="password" placeholder="পরিবর্তন করতে চাইলে লিখুন..." 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন</label>
                    <input type="password" name="password_confirmation" placeholder="একই পাসওয়ার্ড আবার লিখুন" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>
            <div class="mt-4 text-right">
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-gray-900 transition shadow">
                    প্রোফাইল আপডেট করুন
                </button>
            </div>
        </div>
    </form>

    {{-- ২. মূল সেটিংস ফর্ম শুরু (সবগুলো সেকশন এই এক ফর্মের ভেতর থাকবে) --}}
    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
        @csrf

        {{-- 🔥 প্রক্সি সেটিংস কার্ড --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                🌐 Proxy Settings (Premium Scraping)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Proxy Host (IP/Domain)</label>
                    <input type="text" name="proxy_host" class="w-full border-gray-300 rounded-lg shadow-sm" 
                           value="{{ $settings->proxy_host ?? '' }}" placeholder="e.g. as.smartproxy.net">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Proxy Port</label>
                    <input type="text" name="proxy_port" class="w-full border-gray-300 rounded-lg shadow-sm" 
                           value="{{ $settings->proxy_port ?? '' }}" placeholder="e.g. 3120">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Proxy Username</label>
                    <input type="text" name="proxy_username" class="w-full border-gray-300 rounded-lg shadow-sm" 
                           value="{{ $settings->proxy_username ?? '' }}">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Proxy Password</label>
                    <input type="password" name="proxy_password" class="w-full border-gray-300 rounded-lg shadow-sm" 
                           value="{{ $settings->proxy_password ?? '' }}">
                </div>
            </div>
        </div>

        {{-- ৩. ব্র্যান্ডিং সেকশন --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                🎨 ব্র্যান্ডিং <span class="text-xs font-normal text-gray-400">(নিউজ কার্ডের জন্য)</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">ব্র্যান্ড নাম (e.g. Dhaka Post)</label>
                    <input type="text" name="brand_name" value="{{ old('brand_name', $settings->brand_name ?? 'My News') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">ডিফল্ট কালার থিম</label>
                    <select name="default_theme_color" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="red" {{ ($settings->default_theme_color ?? '') == 'red' ? 'selected' : '' }}>Red (Breaking)</option>
                        <option value="blue" {{ ($settings->default_theme_color ?? '') == 'blue' ? 'selected' : '' }}>Blue (Standard)</option>
                        <option value="green" {{ ($settings->default_theme_color ?? '') == 'green' ? 'selected' : '' }}>Green (Sports/Islamic)</option>
                        <option value="purple" {{ ($settings->default_theme_color ?? '') == 'purple' ? 'selected' : '' }}>Purple (Lifestyle)</option>
                        <option value="black" {{ ($settings->default_theme_color ?? '') == 'black' ? 'selected' : '' }}>Black (Dark)</option>
                    </select>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">লোগো URL (অপশনাল)</label>
                    <input type="url" name="logo_url" value="{{ old('logo_url', $settings->logo_url ?? '') }}" placeholder="https://example.com/logo.png" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <p class="text-xs text-gray-500 mt-1">আপনি স্টুডিও থেকেও লোগো আপলোড করতে পারেন।</p>
                </div>
            </div>
        </div>

        {{-- 🔥 WordPress কানেকশন --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg shadow-sm">Required</div>
            
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    🔗 WordPress কানেকশন
                </h2>
                <button type="button" onclick="testWordPress()" class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold border border-gray-300">
                    ⚡ Test Connection
                </button>
            </div>
            
            <p id="wp_status_msg" class="text-xs font-bold mb-4"></p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">ওয়েবসাইট লিংক (URL)</label>
                    <input type="url" id="wp_url" name="wp_url" value="{{ old('wp_url', $settings->wp_url ?? '') }}" placeholder="https://mywebsite.com" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">ইউজারনেম (Username)</label>
                    <input type="text" id="wp_username" name="wp_username" value="{{ old('wp_username', $settings->wp_username ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">App Password</label>
                    <input type="password" id="wp_app_password" name="wp_app_password" value="{{ old('wp_app_password', $settings->wp_app_password ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="abcd efgh ijkl mnop">
                    <p class="text-xs text-gray-500 mt-1">WP Admin > Users > Profile > Application Passwords এ গিয়ে তৈরি করুন।</p>
                </div>
            </div>
        </div>
        
        {{-- 🔥 LARAVEL CONNECTION SECTION --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mt-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg shadow-sm">Laravel API</div>
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                🚀 Laravel Website কানেকশন
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">ওয়েবসাইট লিংক (API URL)</label>
                    <input type="url" name="laravel_site_url" value="{{ old('laravel_site_url', $settings->laravel_site_url ?? '') }}" 
                           placeholder="https://mylaravelnews.com" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <p class="text-xs text-gray-500 mt-1">শুধুমাত্র ডোমেইন লিংক দিন। আমরা অটোমেটিক <code>/api/external-news-post</code> এ হিট করব।</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">API Token (Secret Key)</label>
                    <input type="text" name="laravel_api_token" value="{{ old('laravel_api_token', $settings->laravel_api_token ?? '') }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="যেকোনো গোপন পাসওয়ার্ড দিন">
                </div>
                
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer bg-gray-50 px-4 py-2 rounded border border-gray-200 w-full">
                        <input type="hidden" name="post_to_laravel" value="0">
                        <input type="checkbox" name="post_to_laravel" value="1" {{ ($settings->post_to_laravel ?? false) ? 'checked' : '' }} class="toggle-checkbox w-5 h-5 text-indigo-600 rounded">
                        <span class="font-bold text-gray-700">Enable Posting to Laravel</span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             {{-- 🔥 Route Prefix Input --}}
             <div>
                  <label class="block text-sm font-bold text-gray-700 mb-1">নিউজ লিংক প্রিফিক্স (Route Prefix)</label>
                  <div class="flex items-center">
                      <span class="bg-gray-100 border border-r-0 border-gray-300 px-3 py-2 rounded-l text-gray-500 text-sm">/</span>
                      <input type="text" name="laravel_route_prefix" value="{{ old('laravel_route_prefix', $settings->laravel_route_prefix ?? 'news') }}" 
                             class="w-full border-gray-300 rounded-r shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" 
                             placeholder="news, post, article">
                  </div>
                  <p class="text-xs text-gray-500 mt-1">উদাহরণ: আপনার সাইট যদি <code>site.com/post/123</code> হয়, তবে এখানে <b>post</b> লিখুন।</p>
             </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            {{-- Facebook --}}
            <div class="bg-white p-5 rounded-lg shadow border border-blue-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-lg text-blue-700 flex items-center gap-2">
                        <i class="fab fa-facebook"></i> Facebook Page Setup
                    </h3>
                    <button type="button" onclick="testFacebook()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 transition font-bold border border-blue-200">
                        ⚡ Test Connection
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700">Page ID</label>
                    <input type="text" id="fb_page_id" name="fb_page_id" value="{{ $settings->fb_page_id ?? '' }}" 
                           class="w-full border p-2 rounded text-sm" placeholder="Example: 100089...">
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700">Page Access Token</label>
                    <textarea id="fb_access_token" name="fb_access_token" rows="2" 
                              class="w-full border p-2 rounded text-sm" placeholder="Enter long-lived token here...">{{ $settings->fb_access_token ?? '' }}</textarea>
                    
                    <p id="fb_status_msg" class="text-xs mt-2 font-bold"></p>
                    
                    <p class="text-[10px] text-gray-400 mt-1">
                        <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-blue-500 hover:underline">Get Token via Graph API</a>
                    </p>
                    <input type="checkbox" name="fb_comment_link" {{ $settings->fb_comment_link ? 'checked' : '' }}>
                </div>
            </div>
            
            {{-- Telegram --}}
            <div class="bg-white p-5 rounded-lg shadow border border-sky-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-lg text-sky-600 flex items-center gap-2">
                        <i class="fab fa-telegram"></i> Telegram Channel
                    </h3>
                    <button type="button" onclick="testTelegram()" class="text-xs bg-sky-100 text-sky-700 px-3 py-1 rounded hover:bg-sky-200 transition font-bold border border-sky-200">
                        ⚡ Test Connection
                    </button>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700">Bot Token</label>
                    <input type="text" id="telegram_bot_token" name="telegram_bot_token" value="{{ $settings->telegram_bot_token ?? '' }}" 
                           class="w-full border p-2 rounded text-sm" placeholder="Ex: 123456:ABC-DEF...">
                    <p class="text-[10px] text-gray-400">BotFather থেকে পাওয়া টোকেন দিন।</p>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700">Channel ID</label>
                    <input type="text" id="telegram_channel_id" name="telegram_channel_id" value="{{ $settings->telegram_channel_id ?? '' }}" 
                           class="w-full border p-2 rounded text-sm" placeholder="Ex: -100123456789">
                    
                    <p id="tg_status_msg" class="text-xs mt-2 font-bold"></p>
                    
                    <p class="text-[10px] text-gray-400 mt-1">বটকে চ্যানেলের অ্যাডমিন করতে ভুলবেন না।</p>
                </div>
            </div>
        </div>
        
        <div class="mt-4 bg-white p-4 rounded shadow">
            <h3 class="font-bold mb-3">Auto Post Preferences</h3>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="post_to_fb" value="0">
                    <input type="checkbox" name="post_to_fb" value="1" {{ $settings->post_to_fb ? 'checked' : '' }} class="toggle-checkbox">
                    <span>Facebook</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="post_to_telegram" value="0">
                    <input type="checkbox" name="post_to_telegram" value="1" {{ $settings->post_to_telegram ? 'checked' : '' }} class="toggle-checkbox">
                    <span>Telegram</span>
                </label>
            </div>
        </div>
                
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    📂 ক্যাটাগরি ম্যাপিং
                </h2>
                <button type="button" id="refresh-cat-btn" onclick="fetchWPCategories(true)" class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-100 border border-indigo-200 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh Categories
                </button>
            </div>

            <p class="text-sm text-gray-500 mb-6 bg-blue-50 p-3 rounded border border-blue-100">
                💡 বাম পাশে আমাদের ক্যাটাগরি এবং ডান পাশে আপনার ওয়েবসাইটের ক্যাটাগরি সিলেক্ট করুন। যাতে নিউজ সঠিক জায়গায় পোস্ট হয়।
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                @php
                    $aiCategories = [
                        'Politics', 'International', 'Sports', 'Cricket', 'Football', 
                        'Entertainment', 'Technology', 'Economy', 'Business', 
                        'Bangladesh', 'National', 'Crime', 'Education', 'Health', 
                        'Lifestyle', 'Religion', 'Travel', 'Jobs', 'Opinion', 
                        'Feature', 'Others', 'Science', 'Environment', 'Weather', 
                        'Agriculture', 'Startup', 'Finance', 'Stock Market', 'Banking', 
                        'Law & Justice', 'Defense', 'Cyber Security', 'AI & Robotics', 
                        'Gadgets', 'Mobile', 'Automobile', 'Real Estate', 'Energy', 
                        'Tourism', 'Food & Recipe', 'Fashion', 'Art & Culture', 
                        'History', 'Women', 'Youth', 'Editorial', 'Breaking News', 
                        'Exclusive', 'Investigation', 'Human Rights', 'Social Issues', 
                        'Public Health', 'Mental Health', 'Child Care', 'Parenting', 
                        'Senior Citizens', 'Immigration', 'Expat Life', 'Remittance', 
                        'Development', 'Infrastructure', 'Rural Life', 'Urban Life', 
                        'Local News', 'City News', 'Media & Press', 'Telecom', 
                        'Internet', 'E-Commerce', 'Digital Lifestyle', 'Gaming', 
                        'E-Sports', 'Movies', 'Music', 'TV & OTT', 'Books & Literature' 
                    ];
                    $savedMapping = $settings->category_mapping ?? [];
                @endphp

                @foreach($aiCategories as $cat)
                    <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded transition">
                        <span class="w-1/3 text-sm font-bold text-gray-700">{{ $cat }}</span>
                        <div class="w-2/3 relative">
                            <select name="category_mapping[{{ $cat }}]" class="wp-cat-selector w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select WP Category</option>
                            </select>
                            <input type="hidden" class="saved-val" value="{{ $savedMapping[$cat] ?? '' }}">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                ✈️ টেলিগ্রাম নোটিফিকেশন
            </h2>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">চ্যানেল আইডি (Channel ID)</label>
                <input type="text" name="telegram_channel_id" value="{{ old('telegram_channel_id', $settings->telegram_channel_id ?? '') }}" placeholder="-100xxxxxxxxxx" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                <p class="text-xs text-gray-500 mt-1">আপনার বটকে চ্যানেলে এডমিন করুন এবং চ্যানেল আইডি দিন।</p>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-8 py-3 rounded-xl font-bold text-lg hover:shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                💾 সেটিংস সেভ করুন
            </button>
        </div>
    </form>
</div>

<script>
    // ১. ক্যাটাগরি ফেচ করা (Cache logic সহ)
    function fetchWPCategories(forceRefresh = false) {
        const btn = document.getElementById('refresh-cat-btn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '⏳ Loading...';
        btn.disabled = true;

        // যদি forceRefresh true হয়, তবে URL-এ refresh=1 যোগ হবে
        let url = "{{ route('settings.fetch-categories') }}";
        if (forceRefresh) {
            url += "?refresh=1";
        }
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                } else {
                    populateDropdowns(data);
                    if(forceRefresh) alert('✅ ক্যাটাগরি লিস্ট আপডেট করা হয়েছে!');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection Failed! Please check Settings.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    // ২. ড্রপডাউন পপুলেট করা
    function populateDropdowns(categories) {
        const selectors = document.querySelectorAll('.wp-cat-selector');
        selectors.forEach(select => {
            const savedVal = select.nextElementSibling.value;
            let options = '<option value="">Select Category</option>';
            
            if (Array.isArray(categories)) {
                categories.forEach(cat => {
                    const isSelected = (cat.id == savedVal) ? 'selected' : '';
                    options += `<option value="${cat.id}" ${isSelected}>${cat.name} (ID: ${cat.id})</option>`;
                });
            }
            select.innerHTML = options;
        });
    }

    // ৩. কানেকশন টেস্ট ফাংশনগুলো (WordPress, FB, Telegram)
    function genericTest(type, data, statusId, btn) {
        const statusMsg = document.getElementById(statusId);
        const originalBtnText = btn.innerHTML;

        btn.innerHTML = "Checking...";
        btn.disabled = true;
        statusMsg.innerHTML = "⏳ Connecting...";

        fetch(`/settings/test/${type}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            statusMsg.innerText = data.message;
            statusMsg.className = data.success ? "text-xs font-bold mt-2 text-green-600" : "text-xs font-bold mt-2 text-red-600";
        })
        .finally(() => {
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
        });
    }

    // বাটন ক্লিক ইভেন্টগুলো
    function testWordPress() {
        genericTest('wordpress', {
            wp_url: document.getElementById('wp_url').value,
            wp_username: document.getElementById('wp_username').value,
            wp_app_password: document.getElementById('wp_app_password').value
        }, 'wp_status_msg', document.activeElement);
    }

    function testFacebook() {
        genericTest('facebook', {
            fb_page_id: document.getElementById('fb_page_id').value,
            fb_access_token: document.getElementById('fb_access_token').value
        }, 'fb_status_msg', document.activeElement);
    }

    function testTelegram() {
        genericTest('telegram', {
            telegram_bot_token: document.getElementById('telegram_bot_token').value,
            telegram_channel_id: document.getElementById('telegram_channel_id').value
        }, 'tg_status_msg', document.activeElement);
    }

    // ৪. পেজ লোড হলে অটোমেটিক ক্যাটাগরি লোড (ক্যাশ থেকে আসবে)
    document.addEventListener('DOMContentLoaded', () => {
        @if(($settings->wp_url && $settings->wp_username) || ($settings->laravel_site_url && $settings->laravel_api_token))
            fetchWPCategories(false); 
        @endif
    });
</script>

@endsection