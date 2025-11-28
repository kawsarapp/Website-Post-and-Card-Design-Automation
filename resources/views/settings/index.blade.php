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

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
        @csrf

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

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg shadow-sm">Required</div>
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                🔗 WordPress কানেকশন
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">ওয়েবসাইট লিংক (URL)</label>
                    <input type="url" name="wp_url" value="{{ old('wp_url', $settings->wp_url ?? '') }}" placeholder="https://mywebsite.com" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">ইউজারনেম (Username)</label>
                    <input type="text" name="wp_username" value="{{ old('wp_username', $settings->wp_username ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">App Password</label>
                    <input type="password" name="wp_app_password" value="{{ old('wp_app_password', $settings->wp_app_password ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="abcd efgh ijkl mnop">
                    <p class="text-xs text-gray-500 mt-1">WP Admin > Users > Profile > Application Passwords এ গিয়ে তৈরি করুন।</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    📂 ক্যাটাগরি ম্যাপিং
                </h2>
                <button type="button" onclick="fetchWPCategories()" class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-100 border border-indigo-200 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh Categories
                </button>
            </div>

            <p class="text-sm text-gray-500 mb-6 bg-blue-50 p-3 rounded border border-blue-100">
                💡 বাম পাশে আমাদের AI ক্যাটাগরি এবং ডান পাশে আপনার ওয়ার্ডপ্রেসের ক্যাটাগরি সিলেক্ট করুন। যাতে নিউজ সঠিক জায়গায় পোস্ট হয়।
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                @php
                    $aiCategories = [
                        'Politics', 'International', 'Sports', 'Entertainment', 
                        'Technology', 'Economy', 'Bangladesh', 'Crime', 'Others'
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
    function fetchWPCategories() {
        const btn = document.querySelector('button[onclick="fetchWPCategories()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ Loading...';
        btn.disabled = true;
        
        fetch("{{ route('settings.fetch-categories') }}")
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    btn.innerHTML = '❌ Error';
                } else {
                    populateDropdowns(data);
                    btn.innerHTML = '✅ Updated';
                }
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            })
            .catch(err => {
                console.error(err);
                alert('Failed to connect to WordPress. Please check URL and Credentials.');
                btn.innerHTML = '❌ Failed';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            });
    }

    function populateDropdowns(categories) {
        const selectors = document.querySelectorAll('.wp-cat-selector');
        
        selectors.forEach(select => {
            const savedVal = select.nextElementSibling.value; // হিডেন ইনপুট থেকে সেভ করা ভ্যালু
            
            let options = '<option value="">Select WP Category</option>';
            
            if (Array.isArray(categories)) {
                categories.forEach(cat => {
                    const isSelected = (cat.id == savedVal) ? 'selected' : '';
                    options += `<option value="${cat.id}" ${isSelected}>${cat.name}</option>`;
                });
            }
            
            select.innerHTML = options;
        });
    }

    // পেজ লোড হলে অটোমেটিক একবার ফেচ করবে (যদি ক্রেডেনশিয়াল থাকে)
    document.addEventListener('DOMContentLoaded', () => {
        @if($settings->wp_url && $settings->wp_username)
            fetchWPCategories();
        @endif
    });
</script>
@endsection