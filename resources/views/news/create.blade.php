@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 font-bangla">✍️ নতুন খবর তৈরি করুন</h2>
        <a href="{{ route('news.index') }}" class="text-gray-500 hover:text-gray-700 font-bold">← ফিরে যান</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        {{-- 🔥 ফর্মের এই অংশটি গুরুত্বপূর্ণ: enctype="multipart/form-data" --}}
        <form action="{{ route('news.store-custom') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            {{-- Title --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">খবরের শিরোনাম (Title)</label>
                <input type="text" name="title" required 
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 font-bangla text-lg"
                    placeholder="এখানে শিরোনাম লিখুন...">
            </div>

            {{-- Image Upload Section --}}
            <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Option A: Upload File --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">📷 ছবি আপলোড করুন</label>
                    <input type="file" name="image_file" accept="image/*"
                        class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-400 mt-1">ফরম্যাট: JPG, PNG, WEBP (Max: 2MB)</p>
                </div>

                {{-- Option B: Image Link --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">🔗 অথবা, ছবির লিংক দিন</label>
                    <input type="url" name="image_url" 
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 text-sm"
                        placeholder="https://example.com/image.jpg">
                </div>
            </div>

            {{-- Content --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">বিস্তারিত খবর (Content)</label>
                <textarea name="content" required rows="8"
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 font-bangla"
                    placeholder="এখানে বিস্তারিত লিখুন..."></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-100">
                
                {{-- ১. ড্রাফট বাটন --}}
                <button type="submit" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-bold hover:bg-gray-200 transition">
                    💾 ড্রাফটে সেভ করুন
                </button>

                {{-- ২. AI বাটন (এটি আগের মতোই থাক) --}}
                <button type="submit" name="process_ai" value="1" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 shadow-md transition flex justify-center items-center gap-2">
                    🤖 সেভ + AI রিরাইট
                </button>

                {{-- 🔥 ৩. নতুন ডাইরেক্ট পাবলিশ বাটন --}}
                <button type="submit" name="direct_publish" value="1" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow-md transition flex justify-center items-center gap-2">
                    🚀 সরাসরি পাবলিশ
                </button>
            </div>
			
        </form>
    </div>
</div>
@endsection