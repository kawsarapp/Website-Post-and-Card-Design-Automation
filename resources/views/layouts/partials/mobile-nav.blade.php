{{-- MOBILE TOP HEADER --}}
<div class="lg:hidden fixed top-0 w-full z-40 glass-nav h-14 flex items-center justify-between px-4 shadow-sm">
    <a href="{{ auth()->user()->role === 'reporter' ? route('reporter.news.index') : route('news.index') }}" class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white"><i class="fa-solid fa-bolt text-xs"></i></div>
        <span class="font-bold text-lg text-slate-900">News<span class="text-indigo-600">24</span></span>
    </a>
    @auth
    <div class="flex items-center gap-2">
        @if(auth()->user()->role !== 'reporter')
            <div class="bg-amber-50 text-amber-600 px-2 py-1 rounded-full text-xs font-bold border border-amber-100">
                🪙 {{ auth()->user()->credits ?? 0 }}
            </div>
        @endif
        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs border border-indigo-200 uppercase">
            {{ substr(auth()->user()->name, 0, 1) }}
        </div>
    </div>
    @endauth
</div>

{{-- MOBILE BOTTOM NAVIGATION & SHEET --}}
@auth
<div class="lg:hidden fixed bottom-0 left-0 w-full z-[90] pb-safe">
    @if(auth()->user()->role === 'reporter')
    <div class="glass-nav grid grid-cols-3 items-center h-16 border-t border-indigo-50 shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">
        <a href="{{ route('reporter.news.index') }}" class="flex flex-col items-center justify-center h-full gap-1 {{ request()->routeIs('reporter.news.index') ? 'text-indigo-600' : 'text-slate-400' }}">
            <i class="fa-solid fa-list-ul text-xl"></i><span class="text-[10px] font-bold">আমার খবর</span>
        </a>
        <div class="relative flex justify-center h-full items-center">
            <a href="{{ route('reporter.news.create') }}" class="absolute -top-6 bg-indigo-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg border-4 border-slate-50 active:scale-95 transition-transform">
                <i class="fa-solid fa-plus text-2xl"></i>
            </a>
            <span class="absolute bottom-1.5 text-[10px] font-bold text-slate-500">পাঠান</span>
        </div>
        <button id="mobileMenuBtn" class="flex flex-col items-center justify-center h-full gap-1 text-slate-400">
            <i class="fa-solid fa-bars text-xl"></i><span class="text-[10px] font-bold">মেনু</span>
        </button>
    </div>
    @else
    <div class="glass-nav grid grid-cols-4 items-center h-16 border-t border-indigo-50 shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">
        <a href="{{ route('news.index') }}" class="flex flex-col items-center justify-center h-full gap-1 {{ request()->routeIs('news.index') ? 'text-indigo-600' : 'text-slate-400' }}">
            <i class="fa-solid fa-house-chimney text-xl"></i><span class="text-[10px] font-bold">Feed</span>
        </a>
        <div class="relative flex justify-center h-full items-center">
            <a href="{{ route('news.create') }}" class="absolute -top-6 bg-slate-800 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg border-4 border-slate-50 active:scale-95 transition-transform">
                <i class="fa-solid fa-plus text-2xl"></i>
            </a>
            <span class="absolute bottom-1.5 text-[10px] font-bold text-slate-500">Create</span>
        </div>
        <a href="{{ route('news.drafts') }}" class="flex flex-col items-center justify-center h-full gap-1 {{ request()->routeIs('news.drafts') ? 'text-indigo-600' : 'text-slate-400' }}">
            <i class="fa-solid fa-wand-magic-sparkles text-xl"></i><span class="text-[10px] font-bold">AI</span>
        </a>
        <button id="mobileMenuBtn" class="flex flex-col items-center justify-center h-full gap-1 text-slate-400">
            <i class="fa-solid fa-bars-staggered text-xl"></i><span class="text-[10px] font-bold">Menu</span>
        </button>
    </div>
    @endif
</div>

<div id="mobileMenuContainer" class="hidden fixed inset-0 z-[100] lg:hidden">
    <div id="mobileOverlay" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0" onclick="toggleMobileMenu()"></div>
    <div id="mobileMenuSheet" class="absolute bottom-0 left-0 w-full glass-sheet rounded-t-3xl transform translate-y-full max-h-[85vh] overflow-y-auto pb-safe flex flex-col shadow-2xl">
        <div class="w-full flex justify-center pt-3 pb-1" onclick="toggleMobileMenu()"><div class="w-12 h-1.5 bg-slate-300 rounded-full"></div></div>

        <div class="p-5 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg uppercase">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div>
                        <p class="font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-bold text-indigo-500 uppercase">{{ auth()->user()->role }}</p>
                    </div>
                </div>
                @if(auth()->user()->role !== 'reporter')
                    <div class="text-right"><p class="font-black text-amber-500">🪙 {{ auth()->user()->credits ?? 0 }}</p></div>
                @endif
            </div>

            @if(auth()->user()->role === 'reporter')
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('reporter.news.create') }}" class="p-4 rounded-2xl bg-indigo-50 flex flex-col items-center gap-2"><i class="fa-solid fa-paper-plane text-indigo-600 text-xl"></i><span class="text-xs font-bold">খবর পাঠান</span></a>
                    <a href="{{ route('reporter.news.index') }}" class="p-4 rounded-2xl bg-slate-50 flex flex-col items-center gap-2"><i class="fa-solid fa-list-ul text-slate-600 text-xl"></i><span class="text-xs font-bold">আমার খবর</span></a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('news.index') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center gap-2">
                        <i class="fa-solid fa-newspaper text-indigo-500 text-xl"></i><span class="text-xs font-bold">Feed</span>
                    </a>
                    <a href="{{ route('news.published') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center gap-2">
                        <i class="fa-solid fa-check-circle text-emerald-500 text-xl"></i><span class="text-xs font-bold">Published</span>
                    </a>
                </div>
                
                @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters') || auth()->user()->hasPermission('can_manage_staff'))
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Manage Team</p>
                    <div class="bg-indigo-50 rounded-2xl border border-indigo-100 overflow-hidden">
                        
                        @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                        <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-4 p-4 hover:bg-indigo-100/50 border-b border-indigo-100">
                            <div class="w-8 h-8 rounded-lg bg-white text-indigo-600 flex items-center justify-center shadow-sm"><i class="fa-solid fa-users"></i></div>
                            <span class="font-bold text-sm text-slate-700">Reporter List</span>
                        </a>
                        <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-4 p-4 hover:bg-indigo-100/50 border-b border-indigo-100">
                            <div class="w-8 h-8 rounded-lg bg-white text-indigo-600 flex items-center justify-center shadow-sm"><i class="fa-solid fa-clipboard-list"></i></div>
                            <span class="font-bold text-sm text-slate-700">Reporter News</span>
                        </a>
                        @endif

                        {{-- 🔥 Staff Management Link (Mobile) --}}
                        @if(auth()->user()->hasPermission('can_manage_staff'))
                        <a href="{{ route('client.staff.index') }}" class="flex items-center gap-4 p-4 hover:bg-indigo-100/50">
                            <div class="w-8 h-8 rounded-lg bg-white text-indigo-600 flex items-center justify-center shadow-sm"><i class="fa-solid fa-users-gear"></i></div>
                            <span class="font-bold text-sm text-slate-700">Staff Management</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <div class="space-y-2">
                     @if(auth()->user()->role === 'super_admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-4 p-3 bg-white border border-slate-100 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center"><i class="fa-solid fa-shield-halved"></i></div>
                        <span class="font-bold text-sm text-slate-700">Admin Dashboard</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->hasPermission('can_scrape'))
                    <a href="{{ route('websites.index') }}" class="flex items-center gap-4 p-3 bg-white border border-slate-100 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fa-solid fa-earth-asia"></i></div>
                        <span class="font-bold text-sm text-slate-700">Observed Sites</span>
                    </a>
                    @endif

                    @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_settings'))
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-4 p-3 bg-white border border-slate-100 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <span class="font-bold text-sm text-slate-700">Settings</span>
                    </a>
                    @endif
                </div>
            @endif

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-slate-200 text-slate-600 font-bold py-3.5 rounded-xl hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-power-off"></i> Logout
                </button>
            </form>
            
            <div class="text-center text-[10px] text-slate-300 pb-2">
                &copy; Newsmanage24
            </div>
            <div class="h-6"></div>
        </div>
    </div>
</div>
@endauth