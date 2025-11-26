@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">⚙️ প্রোফাইল ও সেটিংস</h1>
        <div class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg font-bold">
            💰 ক্রেডিট ব্যালেন্স: {{ auth()->user()->credits }} / {{ auth()->user()->total_credits_limit }}
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
        @csrf

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2">🎨 ব্র্যান্ডিং (নিউজ কার্ডের জন্য)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ব্র্যান্ড নাম (e.g. Dhaka Post)</label>
                    <input type="text" name="brand_name" value="{{ old('brand_name', $settings->brand_name ?? 'My News') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ডিফল্ট কালার থিম</label>
                    <select name="default_theme_color" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="red" {{ ($settings->default_theme_color ?? '') == 'red' ? 'selected' : '' }}>Red (Breaking)</option>
                        <option value="blue" {{ ($settings->default_theme_color ?? '') == 'blue' ? 'selected' : '' }}>Blue (Standard)</option>
                        <option value="green" {{ ($settings->default_theme_color ?? '') == 'green' ? 'selected' : '' }}>Green (Sports/Islamic)</option>
                        <option value="purple" {{ ($settings->default_theme_color ?? '') == 'purple' ? 'selected' : '' }}>Purple (Lifestyle)</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">লোগো URL (অপশনাল)</label>
                    <input type="url" name="logo_url" value="{{ old('logo_url', $settings->logo_url ?? '') }}" placeholder="https://example.com/logo.png" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">সরাসরি ইমেজের লিংক দিন।</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">গুরুত্বপূর্ণ</div>
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2">🔗 WordPress কানেকশন</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ওয়েবসাইট লিংক (URL)</label>
                    <input type="url" name="wp_url" value="{{ old('wp_url', $settings->wp_url ?? '') }}" placeholder="https://mywebsite.com" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ইউজারনেম (Username)</label>
                    <input type="text" name="wp_username" value="{{ old('wp_username', $settings->wp_username ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">App Password</label>
                    <input type="password" name="wp_app_password" value="{{ old('wp_app_password', $settings->wp_app_password ?? '') }}" class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="abcd efgh ijkl mnop">
                    <p class="text-xs text-gray-500 mt-1">WP Admin > Users > Profile > Application Passwords এ গিয়ে তৈরি করুন।</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2">✈️ টেলিগ্রাম সেটআপ</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">চ্যানেল আইডি (Channel ID)</label>
                <input type="text" name="telegram_channel_id" value="{{ old('telegram_channel_id', $settings->telegram_channel_id ?? '') }}" placeholder="-100xxxxxxxxxx" class="w-full border-gray-300 rounded-lg shadow-sm">
                <p class="text-xs text-gray-500 mt-1">আপনার বটকে চ্যানেলে এডমিন করুন এবং চ্যানেল আইডি দিন।</p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-8 py-3 rounded-lg font-bold text-lg hover:shadow-lg transition transform hover:-translate-y-1">
                💾 সেটিংস সেভ করুন
            </button>
        </div>
    </form>
</div>
@endsection