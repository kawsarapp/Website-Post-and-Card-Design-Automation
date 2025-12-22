@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:py-8 px-2 sm:px-4">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        {{-- Header --}}
        <div class="bg-indigo-600 px-6 py-5">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-indigo-200"></i>
                প্রতিনিধি প্যানেল: নতুন খবর পাঠান
            </h2>
            <p class="text-indigo-100 text-xs mt-1">সবগুলো তথ্য নির্ভুলভাবে পূরণ করে সাবমিট করুন।</p>
        </div>

        <form action="{{ route('reporter.news.store') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-8 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Title --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">খবরের শিরোনাম *</label>
                    <input type="text" name="title" required class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="শিরোনাম লিখুন...">
                </div>

                {{-- Location --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">আপনার এলাকা/লোকেশন</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <input type="text" name="location" class="w-full border border-gray-300 rounded-xl p-3 pl-10 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="যেমন: ঢাকা, মিরপুর">
                    </div>
                </div>

                {{-- Reporter Name --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">প্রতিনিধির নাম</label>
                    <input type="text" name="reporter_name" value="{{ auth()->user()->name }}" class="w-full border border-gray-300 rounded-xl p-3 bg-gray-50 font-semibold text-gray-600 cursor-not-allowed" readonly>
                </div>

                {{-- Image Upload --}}
                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-2">খবরের প্রধান ছবি (Max 10MB) *</label>
                    <input type="file" name="image_file" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                    <p class="text-[10px] text-gray-400 mt-1 italic">ইমেজ কম্প্রেস করা হবে না, সরাসরি অরিজিনাল ফাইল সেভ হবে।</p>
                </div>

                {{-- Image Caption --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">ছবির ক্যাপশন</label>
                    <input type="text" name="image_caption" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="ছবির বিস্তারিত বিবরণ...">
                </div>
            </div>

            {{-- Short Summary --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">সংক্ষিপ্ত সারসংক্ষেপ (Short Summary)</label>
                <textarea name="short_summary" rows="2" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="খবরটি সংক্ষেপে ২-৩ লাইনে লিখুন..."></textarea>
            </div>

            {{-- Body Content --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">বিস্তারিত খবর লিখুন *</label>
                <textarea name="content" rows="10" required class="w-full border border-gray-300 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none transition text-base" placeholder="খবরের বিস্তারিত এখানে টাইপ করুন..."></textarea>
            </div>

            {{-- Footer Section (Source & Tags) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-100 pt-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">সোর্স লিংক (যদি থাকে)</label>
                    <input type="url" name="source_url" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="https://example.com/source">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">ট্যাগস (কমা দিয়ে লিখুন)</label>
                    <input type="text" name="tags" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="তাজা খবর, রাজনীতি, বিনোদন">
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex flex-col sm:flex-row justify-end items-center gap-4 pt-4">
                <p class="text-[11px] text-gray-500 text-center sm:text-right italic">সাবমিট করার পর এটি ড্রাফট হিসেবে জমা হবে এবং আপনার অ্যাডমিন তা পাবলিশ করবেন।</p>
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all transform active:scale-95 flex items-center justify-center gap-2">
                    নিউজটি জমা দিন <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection