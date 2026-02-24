<div class="fixed top-20 right-4 z-[9999] space-y-2">
    @if(session('success'))
        <div id="flash-success" class="flash-message bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 min-w-[300px]">
            <i class="fa-solid fa-check-circle text-lg"></i>
            <div>
                <h4 class="font-bold text-sm">সফল হয়েছে!</h4>
                <p class="text-xs text-emerald-100">{{ session('success') }}</p>
            </div>
            <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-white hover:text-emerald-100"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div id="flash-error" class="flash-message bg-rose-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 min-w-[300px]">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            <div>
                <h4 class="font-bold text-sm">ত্রুটি হয়েছে!</h4>
                <p class="text-xs text-rose-100">{{ session('error') }}</p>
            </div>
            <button onclick="document.getElementById('flash-error').remove()" class="ml-auto text-white hover:text-rose-100"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif
</div>