@if(session()->has('admin_impersonator_id'))
    <div class="bg-slate-900 text-white py-2 text-xs font-bold fixed top-0 w-full z-[10000] flex justify-center items-center gap-3 px-2 shadow-md">
        <span><i class="fa-solid fa-mask text-rose-400"></i> Mode: "{{ auth()->user()->name }}"</span>
        <a href="{{ route('stop.impersonate') }}" class="bg-rose-500 px-3 py-0.5 rounded text-[10px] uppercase hover:bg-rose-600">Stop</a>
    </div>
    <div class="h-8"></div>
@endif