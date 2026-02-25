@extends('layouts.app')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    .input-focus-effect {
        transition: all 0.3s ease;
    }
    .input-focus-effect:focus-within {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.1), 0 8px 10px -6px rgba(79, 70, 229, 0.1);
    }
</style>

<div class="max-w-5xl mx-auto py-6 sm:py-10 px-3 sm:px-4 font-bangla">
    <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 sm:px-10 py-6 sm:py-8 text-center sm:text-left relative overflow-hidden">
            <i class="fa-solid fa-feather absolute -right-6 -top-6 text-9xl text-white/10 transform -rotate-12 pointer-events-none"></i>
            <h2 class="text-2xl sm:text-3xl font-black text-white flex items-center justify-center sm:justify-start gap-3 relative z-10">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-sm">
                    <i class="fa-solid fa-paper-plane text-white text-lg"></i>
                </div>
                নতুন সংবাদ তৈরি করুন
            </h2>
            <p class="text-indigo-100 text-sm mt-2 font-medium relative z-10">সঠিক তথ্য ও ছবি দিয়ে ফর্মটি পূরণ করুন</p>
        </div>

        <form action="{{ route('reporter.news.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-10 space-y-8" id="newsForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                
                {{-- শিরোনাম --}}
                <div class="md:col-span-2 input-focus-effect">
                    <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-wide">খবরের শিরোনাম <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required 
                           class="w-full border-2 border-slate-200 rounded-2xl p-4 sm:p-5 outline-none focus:border-indigo-500 bg-slate-50 focus:bg-white transition-all text-lg font-bold text-slate-800 placeholder-slate-400" 
                           placeholder="আকর্ষণীয় একটি শিরোনাম লিখুন...">
                </div>

                {{-- লোকেশন --}}
                <div class="input-focus-effect">
                    <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-wide">ঘটনার স্থান / লোকেশন</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" name="location" value="{{ old('location') }}" 
                               class="w-full border-2 border-slate-200 rounded-2xl p-4 pl-11 outline-none focus:border-indigo-500 bg-slate-50 focus:bg-white transition-all font-semibold text-slate-700 placeholder-slate-400" 
                               placeholder="যেমন: ঢাকা, মিরপুর">
                    </div>
                </div>

                {{-- প্রতিনিধির নাম --}}
                <div>
                    <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-wide">প্রতিনিধির নাম</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fa-solid fa-user-check"></i></span>
                        <input type="text" value="{{ auth()->user()->name }}" 
                               class="w-full border-2 border-slate-100 rounded-2xl p-4 pl-11 bg-slate-100 font-bold text-slate-500 cursor-not-allowed" 
                               readonly>
                    </div>
                </div>
            </div>

            {{-- 🖼️ মেইন ইমেজ আপলোড সেকশন (স্মুথ ডিজাইন) --}}
            <div class="bg-slate-50/50 p-6 sm:p-8 rounded-[2rem] border border-slate-100 input-focus-effect">
                <label class="block text-sm font-black text-slate-700 mb-4 uppercase tracking-wide">প্রধান ছবি (সর্বোচ্চ ৩ মেগাবাইট) <span class="text-rose-500">*</span></label>
                
                <div class="relative w-full md:w-2/3 lg:w-1/2 aspect-video border-2 border-dashed border-indigo-300 hover:border-indigo-500 rounded-[2rem] overflow-hidden bg-white hover:bg-indigo-50/30 transition-all group cursor-pointer mx-auto md:mx-0 shadow-sm hover:shadow-md">
                    
                    <input type="file" name="image_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20 img-input" data-preview="preview_main">
                    
                    {{-- Placeholder --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-indigo-400 group-hover:text-indigo-600 transition-colors z-10" id="upload_placeholder">
                        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl"></i>
                        </div>
                        <span class="text-sm font-bold">ছবি আপলোড করতে ক্লিক করুন</span>
                        <span class="text-[10px] font-medium text-slate-400 mt-1">JPEG, PNG, WEBP (Max: 3MB)</span>
                    </div>

                    {{-- Preview Image (Hidden by default) --}}
                    <img id="preview_main" src="" class="w-full h-full object-cover hidden z-10 relative">
                </div>
            </div>

            {{-- 🚫 এক্সট্রা ইমেজসমূহ (আপাতত হাইড করে রাখা হয়েছে) --}}
            <div class="hidden">
                @for($i = 1; $i <= 4; $i++)
                    <input type="file" name="extra_image_{{ $i }}" class="img-input" data-preview="preview_extra_{{ $i }}">
                    <img id="preview_extra_{{ $i }}" src="">
                @endfor
            </div>

            {{-- বিস্তারিত খবর (TinyMCE) --}}
            <div class="input-focus-effect">
                <label class="block text-sm font-black text-slate-700 mb-3 uppercase tracking-wide">বিস্তারিত খবর লিখুন <span class="text-rose-500">*</span></label>
                <div class="rounded-2xl overflow-hidden border-2 border-slate-200 focus-within:border-indigo-500 transition-all shadow-sm">
                    <textarea name="content" id="news_content" rows="12" class="w-full">{{ old('content') }}</textarea>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" id="submitBtn" class="w-full bg-indigo-600 text-white py-5 rounded-[1.5rem] font-black text-lg shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-3">
                    <i class="fa-solid fa-paper-plane"></i> নিউজটি জমা দিন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // TinyMCE Initialization
    tinymce.init({
        selector: '#news_content',
        height: 500,
        plugins: 'lists link code wordcount',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link code | removeformat',
        menubar: false,
        branding: false,
        statusbar: false,
        setup: function (editor) {
            editor.on('submit', function (e) { tinymce.triggerSave(); });
        },
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap'); body { font-family: 'Hind Siliguri', sans-serif; font-size: 17px; line-height: 1.6; color: #334155; padding: 15px; }"
    });

    // Image Upload Preview & Validation Logic
    document.querySelectorAll('.img-input').forEach(input => {
        input.onchange = function() {
            const [file] = this.files;
            if (file) {
                // File Size Validation (3 MB)
                if (file.size > 3 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ফাইল অনেক বড়!',
                        text: 'দয়া করে ৩ মেগাবাইটের চেয়ে ছোট ছবি আপলোড করুন।',
                        confirmButtonColor: '#4f46e5'
                    });
                    this.value = '';
                    return;
                }
                
                // Show Preview & Hide Placeholder
                const previewImg = document.getElementById(this.dataset.preview);
                previewImg.src = URL.createObjectURL(file);
                previewImg.classList.remove('hidden');
                
                const placeholder = document.getElementById('upload_placeholder');
                if(placeholder) placeholder.classList.add('hidden');
            }
        }
    });

    // Submit Action Loader
    document.getElementById('newsForm').onsubmit = function() {
        tinymce.triggerSave(); // Ensure TinyMCE content is saved
        
        // Change button state to loading
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.classList.add('cursor-not-allowed', 'opacity-80');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> আপলোড হচ্ছে... দয়া করে অপেক্ষা করুন';
    };
</script>
@endsection