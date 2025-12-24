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
            shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
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
            
            <button onclick="document.getElementById('addReporterModal').classList.remove('hidden')" 
                    class="w-full sm:w-auto bg-indigo-600 text-white px-6 py-3.5 rounded-2xl font-bold hover:bg-indigo-700 transition-all transform active:scale-95 shadow-xl shadow-indigo-100 flex items-center justify-center gap-2">
                <i class="fa-solid fa-user-plus"></i>
                নতুন প্রতিনিধি যোগ করুন
            </button>
        </div>

        {{-- Table Container --}}
        <div class="bg-white rounded-[2rem] shadow-2xl shadow-slate-200/40 overflow-hidden border border-slate-100">
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
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
                                            {{ substr($rep->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-base">{{ $rep->name }}</div>
                                            <div class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                                <i class="fa-regular fa-envelope"></i> {{ $rep->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6" data-label="তারিখ">
                                    <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-xs font-bold flex items-center w-fit gap-1">
                                        <i class="fa-regular fa-calendar-check"></i>
                                        {{ $rep->created_at->format('d M, Y') }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center" data-label="অ্যাকশন">
                                    <form action="{{ route('manage.reporters.destroy', $rep->id) }}" method="POST" 
                                          onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই প্রতিনিধিকে রিমুভ করতে চান?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                class="px-4 py-2 bg-rose-50 text-rose-500 rounded-xl font-black text-[10px] uppercase tracking-wider hover:bg-rose-500 hover:text-white transition-all border border-rose-100 flex items-center gap-2 mx-auto">
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
            
            {{-- Empty State (If no reporters) --}}
            @if($reporters->isEmpty())
                <div class="p-20 text-center">
                    <div class="text-slate-200 text-6xl mb-4"><i class="fa-solid fa-users-slash"></i></div>
                    <p class="text-slate-400 font-bold">এখনো কোনো প্রতিনিধি যুক্ত করা হয়নি।</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- প্রতিনিধি যোগ করার পপআপ মডাল --}}
<div id="addReporterModal" class="fixed inset-0 bg-slate-900/80 hidden items-center justify-center z-[100] backdrop-blur-md px-4 transition-all duration-300">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden transform transition-all scale-100 p-1">
        
        {{-- Modal Header --}}
        <div class="bg-indigo-600 px-8 py-8 rounded-t-[2.2rem] text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-user-plus text-6xl text-white"></i>
            </div>
            <h3 class="text-2xl font-black text-white relative z-10">নতুন প্রতিনিধি</h3>
            <p class="text-indigo-100 text-xs mt-2 relative z-10 opacity-80 uppercase tracking-widest font-bold">অ্যাকাউন্ট ইনফরমেশন পূরণ করুন</p>
        </div>

        {{-- Modal Body --}}
        <form action="{{ route('manage.reporters.store') }}" method="POST" class="p-8 space-y-5">
            @csrf
            <div class="space-y-4">
                <div class="group">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">পূর্ণ নাম</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-300"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="name" placeholder="যেমন: আব্দুল করিম" required 
                               class="w-full border-2 border-slate-50 rounded-2xl p-3.5 pl-11 bg-slate-50/50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">ইমেইল ঠিকানা</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-300"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" placeholder="example@mail.com" required 
                               class="w-full border-2 border-slate-50 rounded-2xl p-3.5 pl-11 bg-slate-50/50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">পাসওয়ার্ড</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-300"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" placeholder="••••••••" required 
                               class="w-full border-2 border-slate-50 rounded-2xl p-3.5 pl-11 bg-slate-50/50 outline-none focus:border-indigo-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                <button type="button" onclick="document.getElementById('addReporterModal').classList.add('hidden')" 
                        class="order-2 sm:order-1 w-full sm:w-auto px-6 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-xl transition-all">
                    বাতিল
                </button>
                <button type="submit" 
                        class="order-1 sm:order-2 w-full sm:w-auto bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all">
                    তৈরি করুন
                </button>
            </div>
        </form>
    </div>
</div>
@endsection