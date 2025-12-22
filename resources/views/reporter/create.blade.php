@extends('layouts.app')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>

<div class="max-w-5xl mx-auto py-6 sm:py-8 px-2 sm:px-4 font-bangla">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-indigo-600 px-6 py-5">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-indigo-200"></i>
                প্রতিনিধি প্যানেল: নতুন খবর পাঠান
            </h2>
        </div>

        <form action="{{ route('reporter.news.store') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-8 space-y-8" id="newsForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- শিরোনাম --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">খবরের শিরোনাম <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-xl p-3 outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- লোকেশন ও নাম --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-indigo-100">আপনার এলাকা/লোকেশন</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="w-full border border-gray-300 rounded-xl p-3 outline-none" placeholder="যেমন: ঢাকা, মিরপুর">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">প্রতিনিধির নাম</label>
                    <input type="text" value="{{ auth()->user()->name }}" class="w-full border border-gray-200 rounded-xl p-3 bg-gray-50 font-semibold" readonly>
                </div>
            </div>

            {{-- ইমেজ গ্যালারি সেকশন (৫টি ইমেজ বক্স) --}}
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                <label class="block text-sm font-bold text-slate-700 mb-4">খবরের ছবিসমূহ (সর্বোচ্চ ৩ মেগাবাইট প্রতিটি)</label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    {{-- প্রধান ছবি (একবারই থাকবে) --}}
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-[10px] font-black text-indigo-600 uppercase mb-2">প্রধান ছবি *</label>
                        <div class="relative h-32 border-2 border-dashed border-indigo-200 rounded-2xl overflow-hidden bg-white">
                            <input type="file" name="image_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 img-input" data-preview="preview_main">
                            <img id="preview_main" src="https://placehold.co/400x300?text=প্রধান+ছবি" class="w-full h-full object-cover">
                        </div>
                    </div>
                    {{-- অতিরিক্ত ৪টি ছবি --}}
                    @for($i = 1; $i <= 4; $i++)
                    <div class="col-span-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">ছবি {{ $i }}</label>
                        <div class="relative h-32 border border-dashed border-slate-300 rounded-2xl overflow-hidden bg-white">
                            <input type="file" name="extra_image_{{ $i }}" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 img-input" data-preview="preview_extra_{{ $i }}">
                            <img id="preview_extra_{{ $i }}" src="https://placehold.co/400x300?text=Extra" class="w-full h-full object-cover">
                        </div>
                    </div>
                    @endfor
                </div>
            </div>

            {{-- বিস্তারিত খবর (TinyMCE) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">বিস্তারিত খবর লিখুন <span class="text-red-500">*</span></label>
                <textarea name="content" id="news_content" rows="12" class="w-full border border-gray-300 rounded-xl p-4">{{ old('content') }}</textarea>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black shadow-xl hover:bg-indigo-700 transition-all">
                নিউজটি জমা দিন <i class="fa-solid fa-arrow-right ml-2"></i>
            </button>
        </form>
    </div>
</div>

<script>
    tinymce.init({
        selector: '#news_content',
        height: 480,
        plugins: 'lists link code wordcount',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link code | removeformat',
        menubar: false,
        branding: false,
        setup: function (editor) {
            editor.on('submit', function (e) { tinymce.triggerSave(); });
        },
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri&display=swap'); body { font-family: 'Hind Siliguri', sans-serif; font-size: 16px; }"
    });

    document.querySelectorAll('.img-input').forEach(input => {
        input.onchange = function() {
            const [file] = this.files;
            if (file) {
                if (file.size > 3 * 1024 * 1024) {
                    alert('ভুল: ছবি ৩ মেগাবাইটের বড়!');
                    this.value = '';
                    return;
                }
                document.getElementById(this.dataset.preview).src = URL.createObjectURL(file);
            }
        }
    });

    // ফর্ম সাবমিট করার আগে TinyMCE সেভ করা নিশ্চিত করা
    document.getElementById('newsForm').onsubmit = function() {
        tinymce.triggerSave();
    };
</script>
@endsection