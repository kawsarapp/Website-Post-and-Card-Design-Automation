<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name', 'Newsmanage24') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Tailwind & Icons --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Page Specific Styles --}}
    @stack('styles')

    <style>
        :root { --primary: #6366f1; }
        body { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; background-color: #f8fafc; -webkit-tap-highlight-color: transparent; }
        .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
        .glass-sheet { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(25px); border-top: 1px solid rgba(226, 232, 240, 1); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        
        /* Flash Message Animation */
        .flash-message { animation: slideIn 0.5s ease-out forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>

<body class="text-slate-800 antialiased min-h-screen flex flex-col relative custom-scrollbar bg-slate-50">

    {{-- 🔥 0. FLASH MESSAGES (Alerts) - ADDED --}}
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

    {{-- 🔥 1. IMPERSONATION BAR --}}
    @if(session()->has('admin_impersonator_id'))
        <div class="bg-slate-900 text-white py-2 text-xs font-bold fixed top-0 w-full z-[10000] flex justify-center items-center gap-3 px-2 shadow-md">
            <span><i class="fa-solid fa-mask text-rose-400"></i> Mode: "{{ auth()->user()->name }}"</span>
            <a href="{{ route('stop.impersonate') }}" class="bg-rose-500 px-3 py-0.5 rounded text-[10px] uppercase hover:bg-rose-600">Stop</a>
        </div>
        <div class="h-8"></div>
    @endif

    {{-- 📱 2. MOBILE TOP HEADER --}}
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

    {{-- 💻 3. DESKTOP NAVIGATION --}}
    <nav class="hidden lg:block glass-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
            
            <div class="flex items-center gap-8">
                <a href="{{ auth()->user()->role === 'reporter' ? route('reporter.news.index') : route('news.index') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg"><i class="fa-solid fa-bolt"></i></div>
                    <span class="font-bold text-xl tracking-tight text-slate-900">Newsmanage<span class="text-indigo-600">24</span></span>
                </a>

                @auth
                {{-- ✅ REPORTER MENU --}}
                @if(auth()->user()->role === 'reporter')
                    <div class="flex items-center bg-slate-100/50 p-1 rounded-xl border border-slate-200/50">
                        <a href="{{ route('reporter.news.create') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold {{ request()->routeIs('reporter.news.create') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                            <i class="fa-solid fa-plus"></i> খবর পাঠান
                        </a>
                        <a href="{{ route('reporter.news.index') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold {{ request()->routeIs('reporter.news.index') ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                            <i class="fa-solid fa-list-ul"></i> আমার খবরসমূহ
                        </a>
                    </div>
                {{-- ❌ ADMIN MENU --}}
                @else
                    <div class="flex items-center bg-slate-100/50 p-1 rounded-xl border border-slate-200/50">
                        <a href="{{ route('news.index') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('news.index') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Feed</a>
                        <a href="{{ route('news.published') }}" class="px-4 py-1.5 text-sm font-semibold {{ request()->routeIs('news.published') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Published</a>
                        
                        @if(auth()->user()->hasPermission('can_direct_publish'))
                        <a href="{{ route('news.create') }}" class="px-4 py-1.5 text-sm font-semibold {{ request()->routeIs('news.create') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Create</a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('can_ai'))
                        <a href="{{ route('news.drafts') }}" class="px-4 py-1.5 text-sm font-semibold {{ request()->routeIs('news.drafts') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Drafts</a>
                        @endif

                        @if(auth()->user()->hasPermission('can_scrape'))
                        <a href="{{ route('websites.index') }}" class="px-4 py-1.5 text-sm font-semibold {{ request()->routeIs('websites.*') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Observe</a>
                        @endif
                    </div>
                @endif
                @endauth
            </div>

            <div class="flex items-center gap-3">
                @auth
                    {{-- Limits --}}
                    <div class="hidden xl:flex items-center gap-3 border-r border-slate-200 pr-4">
                        <div class="text-[10px] font-black uppercase text-slate-400">
                            Limit: <span class="text-indigo-600">{{ auth()->user()->todays_post_count ?? 0 }}/{{ auth()->user()->daily_post_limit ?? 20 }}</span>
                        </div>
                        @if(auth()->user()->role !== 'reporter')
                        <a href="{{ route('credits.index') }}" class="bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-100">🪙 {{ auth()->user()->credits ?? 0 }}</a>
                        @endif
                    </div>

                    {{-- Notifications --}}
                    @if(auth()->user()->role !== 'reporter')
                        <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                            <i class="fa-regular fa-bell text-lg"></i>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-1.5 right-1.5 h-2 w-2 bg-rose-500 rounded-full"></span>
                            @endif
                        </a>
                    @endif

                    {{-- 3-DOT MENU --}}
                    <div class="relative ml-1">
                        <button id="dotMenuBtn" class="flex items-center gap-3 ml-2 pl-3 focus:outline-none hover:bg-slate-50 rounded-xl p-1 transition-colors">
                            <div class="text-right leading-tight">
                                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">{{ auth()->user()->role }}</p>
                            </div>
                            <div class="w-9 h-9 rounded-full bg-indigo-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-600 font-bold text-xs uppercase">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </button>
                        
                        <div id="dotDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-[100]">
                            
                            {{-- ADMIN: REPORTER MANAGEMENT --}}
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                                <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">Reporter Management</p>
                                <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                    <i class="fa-solid fa-users text-indigo-500 w-5"></i> Reporter List
                                </a>
                                <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                    <i class="fa-solid fa-clipboard-list text-indigo-500 w-5"></i> Reporter News
                                </a>
                                <div class="my-2 border-t border-slate-100"></div>
                            @endif

                            {{-- General Admin Links --}}
                            @if(auth()->user()->role !== 'reporter')
                                <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">System</p>
                                @if(auth()->user()->role === 'super_admin')
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-shield-halved text-rose-500 w-5"></i> Dashboard
                                </a>
								<div class="my-2 border-t border-slate-100"></div>
								<a href="{{ route('admin.post-history') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-shield-halved text-rose-500 w-5"></i> History
                                </a>
								<div class="my-2 border-t border-slate-100"></div>
								<a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-sliders text-slate-400 w-5"></i> Settings
                                </a>
                                @endif
                                
                                
                            @endif

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-rose-500 hover:bg-rose-50 rounded-xl text-left">
                                    <i class="fa-solid fa-power-off w-5"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- 4. MAIN CONTENT AREA --}}
    <main class="flex-grow container mx-auto mt-4 px-4 pb-24 lg:pb-12 lg:mt-6 pt-14 lg:pt-0">
        @yield('content')
    </main>

    {{-- 🔥 FOOTER (ADDED) --}}
    <footer class="mt-auto py-6 text-center text-slate-400 text-xs hidden lg:block">
		<p>© {{ date('Y') }} Newsmanage24 | <span class="text-indigo-500 font-bold">v{{ Cache::get('github_version', '1.0.0') }}</span></p>
    </footer>

    {{-- 📱 5. MOBILE BOTTOM NAVIGATION --}}
    @auth
    <div class="lg:hidden fixed bottom-0 left-0 w-full z-[90] pb-safe">
        
        {{-- ✅ A. REPORTER NAV --}}
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

        {{-- ❌ B. ADMIN NAV --}}
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
                {{-- User Info --}}
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

                {{-- ✅ REPORTER SHEET --}}
                @if(auth()->user()->role === 'reporter')
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('reporter.news.create') }}" class="p-4 rounded-2xl bg-indigo-50 flex flex-col items-center gap-2"><i class="fa-solid fa-paper-plane text-indigo-600 text-xl"></i><span class="text-xs font-bold">খবর পাঠান</span></a>
                        <a href="{{ route('reporter.news.index') }}" class="p-4 rounded-2xl bg-slate-50 flex flex-col items-center gap-2"><i class="fa-solid fa-list-ul text-slate-600 text-xl"></i><span class="text-xs font-bold">আমার খবর</span></a>
                    </div>

                {{-- ❌ ADMIN SHEET --}}
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('news.index') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center gap-2">
                            <i class="fa-solid fa-newspaper text-indigo-500 text-xl"></i><span class="text-xs font-bold">Feed</span>
                        </a>
                        <a href="{{ route('news.published') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center gap-2">
                            <i class="fa-solid fa-check-circle text-emerald-500 text-xl"></i><span class="text-xs font-bold">Published</span>
                        </a>
                    </div>
                    
                    {{-- ADMIN: REPORTER MANAGEMENT (MOBILE) --}}
                    @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Manage Team</p>
                        <div class="bg-indigo-50 rounded-2xl border border-indigo-100 overflow-hidden">
                            <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-4 p-4 hover:bg-indigo-100/50 border-b border-indigo-100">
                                <div class="w-8 h-8 rounded-lg bg-white text-indigo-600 flex items-center justify-center shadow-sm"><i class="fa-solid fa-users"></i></div>
                                <span class="font-bold text-sm text-slate-700">Reporter List</span>
                            </a>
                            <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-4 p-4 hover:bg-indigo-100/50">
                                <div class="w-8 h-8 rounded-lg bg-white text-indigo-600 flex items-center justify-center shadow-sm"><i class="fa-solid fa-clipboard-list"></i></div>
                                <span class="font-bold text-sm text-slate-700">Reporter News</span>
                            </a>
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
                    </div>
                @endif

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-slate-200 text-slate-600 font-bold py-3.5 rounded-xl hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-power-off"></i> Logout
                    </button>
                </form>
                
                {{-- Mobile Footer --}}
                <div class="text-center text-[10px] text-slate-300 pb-2">
                    &copy; Newsmanage24
                </div>
                <div class="h-6"></div>
            </div>
        </div>
    </div>
    @endauth

    {{-- Page Specific Scripts --}}
    @stack('scripts')

    <script>
        // Toggle Logic
        const dotBtn = document.getElementById('dotMenuBtn');
        const dotDropdown = document.getElementById('dotDropdown');
        if(dotBtn) {
            dotBtn.addEventListener('click', (e) => { e.stopPropagation(); dotDropdown.classList.toggle('hidden'); });
            document.addEventListener('click', (e) => { if (!dotBtn.contains(e.target) && !dotDropdown.contains(e.target)) dotDropdown.classList.add('hidden'); });
        }

        const mobileContainer = document.getElementById('mobileMenuContainer');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const mobileSheet = document.getElementById('mobileMenuSheet');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        let isMenuOpen = false;

        function toggleMobileMenu() {
            isMenuOpen = !isMenuOpen;
            if (isMenuOpen) {
                mobileContainer.classList.remove('hidden');
                setTimeout(() => { mobileOverlay.classList.remove('opacity-0'); mobileSheet.classList.remove('translate-y-full'); }, 10);
                document.body.style.overflow = 'hidden';
            } else {
                mobileOverlay.classList.add('opacity-0');
                mobileSheet.classList.add('translate-y-full');
                setTimeout(() => { mobileContainer.classList.add('hidden'); }, 300);
                document.body.style.overflow = '';
            }
        }
        if(mobileBtn) mobileBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleMobileMenu(); });

        // Auto hide flash messages
        setTimeout(() => {
            const success = document.getElementById('flash-success');
            const error = document.getElementById('flash-error');
            if(success) success.remove();
            if(error) error.remove();
        }, 4000);

        function markRead() { fetch('{{ route("notifications.read") }}').catch(e => console.error(e)); }
    </script>
</body>
</html>