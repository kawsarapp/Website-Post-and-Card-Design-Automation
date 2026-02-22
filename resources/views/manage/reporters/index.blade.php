@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap');
    .font-bangla { font-family: 'Hind Siliguri', sans-serif; }
    
    /* মোবাইল রেসপন্সিভ টেবিল ফিক্স */
    @media (max-width: 640px) {
        .mobile-card-table thead { display: none; }
        .mobile-card-table tr { 
            display: block; 
            margin-bottom: 1.5rem; 
            border: 1px solid #f1f5f9; 
            border-radius: 1.5rem; 
            background: #fff; 
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .mobile-card-table td { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0.75rem 0; 
            border-bottom: 1px solid #f8fafc;
        }
        .mobile-card-table td:last-child { border-bottom: none; }
        .mobile-card-table td::before { 
            content: attr(data-label); 
            font-weight: 800; 
            color: #94a3b8; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
        }
    }
    
    /* Modal Animation */
    .modal-enter { opacity: 0; transform: scale(0.95) translateY(10px); }
    .modal-enter-active { opacity: 1; transform: scale(1) translateY(0); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
</style>

<div class="min-h-screen bg-gray-50/50 py-6 sm:py-10 px-3 sm:px-6 lg:px-8 font-bangla">
    <div class="max-w-6xl mx-auto">
        
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-800 flex items-center gap-3">
                    <span class="p-3 bg-indigo-100 text-indigo-600 rounded-2xl shadow-sm">
                        <i class="fa-solid fa-users-gears"></i>
                    </span>
                    আমার প্রতিনিধিগণ
                </h2>
                <p class="text-slate-500 mt-2 text-sm font-medium">নিউজ পোর্টালের সকল প্রতিনিধির তথ্য ও এক্সেস কন্ট্রোল</p>
            </div>
            
            <button onclick="openModal()" class="w-full sm:w-auto bg-indigo-600 text-white px-6 py-3.5 rounded-2xl font-bold hover:bg-indigo-700 transition-all transform active:scale-95 shadow-xl shadow-indigo-100 flex items-center justify-center gap-2 group">
                <i class="fa-solid fa-user-plus group-hover:rotate-12 transition-transform"></i>
                নতুন প্রতিনিধি যোগ করুন
            </button>
        </div>

        {{-- Table Container --}}
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/40 overflow-hidden border border-slate-100">
            @if($reporters->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse mobile-card-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-8 py-5 text-xs font-black uppercase text-slate-400 tracking-widest">নাম ও ইমেইল</th>
                            <th class="px-8 py-5 text-xs font-black uppercase text-slate-400 tracking-widest">তৈরির তারিখ</th>
                            <th class="px-8 py-5 text-xs font-black uppercase text-slate-400 tracking-widest text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($reporters as $rep)
                            <tr class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="px-8 py-6" data-label="প্রতিনিধি">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 border border-indigo-200 flex items-center justify-center text-indigo-600 font-black text-xl shadow-sm">
                                            {{ mb_substr($rep->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-base">{{ $rep->name }}</div>
                                            <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                                <i class="fa-regular fa-envelope text-slate-400"></i> {{ $rep->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6" data-label="তারিখ">
                                    <span class="bg-slate-50 text-slate-600 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center w-fit gap-1.5 border border-slate-100">
                                        <i class="fa-regular fa-calendar-check text-indigo-400"></i>
                                        {{ $rep->created_at->format('d M, Y') }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center" data-label="অ্যাকশন">
                                    <form action="{{ route('manage.reporters.destroy', $rep->id) }}" method="POST" 
                                          onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই প্রতিনিধিকে রিমুভ করতে চান?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-4 py-2 bg-white text-rose-500 rounded-xl font-black text-[10px] uppercase tracking-wider hover:bg-rose-50 hover:border-rose-200 transition-all border border-slate-200 flex items-center gap-2 mx-auto shadow-sm">
                                            <i class="fa-solid fa-user-xmark"></i>
                                            রিমুভ করুন
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            {{-- Empty State (If no reporters) --}}
            <div class="p-16 sm:p-24 text-center flex flex-col items-center justify-center">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border-8 border-white shadow-sm">
                    <i class="fa-solid fa-user-shield text-4xl text-slate-300"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">কোনো প্রতিনিধি নেই!</h3>
                <p class="text-slate-500 font-medium mb-8 max-w-sm">আপনার নিউজ পোর্টালে এখনো কোনো প্রতিনিধি বা রিপোর্টার যুক্ত করা হয়নি।</p>
                
                <button onclick="openModal()" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-6 py-2.5 rounded-xl font-bold transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> প্রথম প্রতিনিধি যোগ করুন
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- প্রতিনিধি যোগ করার পপআপ মডাল --}}
<div id="modalOverlay" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] backdrop-blur-sm px-4 transition-opacity duration-300 opacity-0">
    <div id="addReporterModal" class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden modal-enter p-1">
        
        {{-- Modal Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-8 rounded-t-[1.8rem] text-center relative overflow-hidden">
            <div class="absolute -top-4 -right-4 p-4 opacity-10 transform rotate-12">
                <i class="fa-solid fa-id-badge text-8xl text-white"></i>
            </div>
            <h3 class="text-2xl font-black text-white relative z-10">নতুন প্রতিনিধি</h3>
            <p class="text-indigo-100 text-xs mt-2 relative z-10 uppercase tracking-widest font-bold">অ্যাকাউন্ট ইনফরমেশন পূরণ করুন</p>
        </div>

        {{-- Modal Body --}}
        <form action="{{ route('manage.reporters.store') }}" method="POST" class="p-8 space-y-5">
            @csrf
            <div class="space-y-4">
                <div class="group">
                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">পূর্ণ নাম <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="name" placeholder="যেমন: আব্দুল করিম" required 
                               class="w-full border-2 border-slate-100 rounded-2xl p-3.5 pl-11 bg-slate-50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">ইমেইল ঠিকানা <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" placeholder="example@mail.com" required 
                               class="w-full border-2 border-slate-100 rounded-2xl p-3.5 pl-11 bg-slate-50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="group">
                    <div class="flex justify-between items-center mb-2 ml-1">
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest">পাসওয়ার্ড <span class="text-rose-500">*</span></label>
                        <span id="passError" class="text-[10px] text-rose-500 font-bold hidden">কমপক্ষে ৮ অক্ষর হতে হবে</span>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" id="passwordInput" placeholder="••••••••" required minlength="8"
                               class="w-full border-2 border-slate-100 rounded-2xl p-3.5 pl-11 pr-12 bg-slate-50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                        
                        {{-- Password Toggle Button --}}
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
                <button type="button" onclick="closeModal()" 
                        class="order-2 sm:order-1 w-full sm:w-auto px-6 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition-all">
                    বাতিল
                </button>
                <button type="submit" id="submitBtn"
                        class="order-1 sm:order-2 w-full sm:w-auto bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm shadow-lg shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i> তৈরি করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const overlay = document.getElementById('modalOverlay');
    const modal = document.getElementById('addReporterModal');
    const passInput = document.getElementById('passwordInput');
    const submitBtn = document.getElementById('submitBtn');
    const passError = document.getElementById('passError');

    // Modal Handlers
    function openModal() {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        // Trigger reflow
        void overlay.offsetWidth;
        overlay.classList.remove('opacity-0');
        modal.classList.add('modal-enter-active');
    }

    function closeModal() {
        overlay.classList.add('opacity-0');
        modal.classList.remove('modal-enter-active');
        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 300);
    }

    // Close on clicking outside
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
    });

    // Password Visibility Toggle
    function togglePassword() {
        const icon = document.getElementById('toggleIcon');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Real-time Password Validation
    passInput.addEventListener('input', function() {
        if (this.value.length > 0 && this.value.length < 8) {
            this.classList.replace('focus:border-indigo-500', 'focus:border-rose-500');
            this.classList.replace('border-slate-100', 'border-rose-300');
            passError.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            this.classList.replace('focus:border-rose-500', 'focus:border-indigo-500');
            this.classList.replace('border-rose-300', 'border-slate-100');
            passError.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    });
</script>
@endsection