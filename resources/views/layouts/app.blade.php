<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsmanage24 - Smart News Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #6366f1; }
        body { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; background-color: #f8fafc; }
        .glass-nav { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border: 1px solid rgba(99, 102, 241, 0.08); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 20px; }
        
        /* Dropdown Animation */
        .dropdown-content { display: none; opacity: 0; transform: scale(0.95) translateY(-10px); transition: all 0.2s ease; }
        .dropdown-content.active { display: block; opacity: 1; transform: scale(1) translateY(0); }
    </style>
</head>

<body class="text-slate-800 antialiased min-h-screen flex flex-col relative custom-scrollbar">

    {{-- 🔥 1. ADMIN IMPERSONATION BAR --}}
    @if(session()->has('admin_impersonator_id'))
        <div class="bg-slate-900 text-white py-2.5 text-xs font-bold fixed top-0 w-full z-[10000] flex justify-center items-center gap-4">
            <span><i class="fa-solid fa-user-secret text-rose-400 mr-2"></i>আপনি এখন "{{ auth()->user()->name }}" হিসেবে আছেন</span>
            <a href="{{ route('stop.impersonate') }}" class="bg-rose-500 px-4 py-1 rounded-full text-[10px] uppercase transition-colors hover:bg-rose-600">Return to Admin</a>
        </div>
        <div class="h-10"></div>
    @endif

    {{-- 💻 2. DESKTOP NAVIGATION --}}
    <nav class="hidden lg:block glass-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
            
            {{-- Left Side: Logo & Main Menus --}}
            <div class="flex items-center gap-8">
                <a href="{{ route('news.index') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg"><i class="fa-solid fa-bolt"></i></div>
                    <span class="font-bold text-xl tracking-tight text-slate-900">Newsmanage<span class="text-indigo-600">24</span></span>
                </a>

                @auth
                <div class="flex items-center bg-slate-100/50 p-1 rounded-xl border border-slate-200/50">
                    <a href="{{ route('news.index') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('news.index') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Feed</a>
                    
                    @if(auth()->user()->hasPermission('can_direct_publish'))
                    <a href="{{ route('news.create') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('news.create') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Create</a>
                    @endif

                    @if(auth()->user()->hasPermission('can_ai'))
                    <a href="{{ route('news.drafts') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('news.drafts') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">AI Drafts</a>
                    @endif

                    @if(auth()->user()->hasPermission('can_scrape'))
                    <a href="{{ route('websites.index') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('websites.*') ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-indigo-600' }}">Observed</a>
                    @endif
                </div>
                @endauth
            </div>

            {{-- Right Side: Profile & Extra Menus --}}
            <div class="flex items-center gap-3">
                @auth
                    {{-- Usage Statistics --}}
                    <div class="hidden xl:flex items-center gap-3 border-r border-slate-200 pr-4">
                        @php
                            $limit = auth()->user()->daily_post_limit ?? 20;
                            $used = auth()->user()->todays_post_count ?? 0; 
                            $percent = min(($used / $limit) * 100, 100);
                        @endphp
                        <div class="w-24">
                            <div class="flex justify-between text-[8px] font-black uppercase text-slate-400 mb-1">
                                <span>Limit</span> <span class="text-indigo-600">{{ $used }}/{{ $limit }}</span>
                            </div>
                            <div class="h-1 w-full bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('credits.index') }}" class="bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full border border-amber-200 text-xs font-extrabold">🪙 {{ auth()->user()->credits ?? 0 }}</a>
                    </div>

                    {{-- Notification Bell --}}
                    @if(auth()->user()->hasPermission('can_ai'))
                    <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                        <i class="fa-regular fa-bell text-lg"></i>
                        @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
                        @if($unreadCount > 0)
                        <span class="absolute top-1.5 right-1.5 h-4 w-4 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center ring-2 ring-white">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    @endif

                    {{-- 3-Dot Management Menu (All Admin/Management Links here) --}}
                    <div class="relative ml-1">
                        <button id="dotMenuBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all border border-slate-200">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        
                        <div id="dotDropdown" class="dropdown-content absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-2xl border border-slate-100 p-2 z-[100]">
                            <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">Management</p>
                            
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                                <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-users text-indigo-500"></i> প্রতিনিধি লিস্ট
                                </a>
                                <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-chart-line text-indigo-500"></i> রিপোর্ট কার্ড
                                </a>
                            @endif

                            @if(auth()->user()->role === 'super_admin')
                                <div class="my-2 border-t border-slate-100"></div>
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-shield-halved"></i> Admin Dashboard
                                </a>
                                <a href="{{ route('admin.post-history') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-clock-rotate-left"></i> পোস্ট ইতিহাস
                                </a>
                                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-sliders"></i> Settings (সেটিংস)
                                </a>
                            @endif

                            <div class="my-2 border-t border-slate-100"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-rose-500 hover:bg-rose-50 rounded-xl text-left">
                                    <i class="fa-solid fa-power-off"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- User Profile --}}
                    <div class="flex items-center gap-3 ml-2 pl-3 border-l border-slate-200">
                        <div class="text-right leading-tight">
                            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">{{ auth()->user()->role }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-indigo-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-600 font-bold text-xs uppercase">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- 🔥 3. REPORTER CONTEXTUAL BAR --}}
    @auth
    @if(auth()->user()->role === 'reporter')
    <div class="max-w-7xl mx-auto px-6 mt-6 hidden lg:block">
        <div class="glass-card rounded-2xl p-3 flex items-center gap-2">
            <span class="text-[10px] font-black text-emerald-600 uppercase px-3 border-r border-emerald-200 tracking-widest">Reporter Panel</span>
            <a href="{{ route('reporter.news.create') }}" class="text-sm font-bold text-emerald-700 px-4 py-1.5 hover:bg-emerald-50 rounded-xl transition-all">➕ খবর পাঠান</a>
            <a href="{{ route('reporter.news.index') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-600 px-4 py-1.5 rounded-xl">আমার খবরসমূহ</a>
        </div>
    </div>
    @endif
    @endauth

    {{-- 4. MAIN CONTENT AREA --}}
    <main class="flex-grow container mx-auto mt-6 px-4 pb-32 lg:pb-12">
        @yield('content')
    </main>

    {{-- 📱 5. MOBILE BOTTOM NAVIGATION --}}
    @auth
        @php
            $cols = 2; // Feed & Menu
            if(auth()->user()->hasPermission('can_direct_publish')) $cols++;
            if(auth()->user()->hasPermission('can_ai')) $cols++;
            $gridClass = match($cols) { 2 => 'grid-cols-2', 3 => 'grid-cols-3', 4 => 'grid-cols-4', default => 'grid-cols-4' };
        @endphp

        <div class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 w-[92%] z-[100]">
            <div class="glass-nav rounded-2xl grid {{ $gridClass }} items-center px-2 py-3 border border-indigo-100 shadow-2xl">
                
                {{-- Mobile Feed --}}
                <a href="{{ route('news.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.index') ? 'text-indigo-600' : 'text-slate-400' }}">
                    <i class="fa-solid fa-house-chimney text-lg"></i>
                    <span class="text-[9px] font-bold uppercase">Feed</span>
                </a>

                {{-- Mobile Create (Central Button) --}}
                @if(auth()->user()->hasPermission('can_direct_publish'))
                <a href="{{ route('news.create') }}" class="flex flex-col items-center">
                    <div class="w-12 h-12 -mt-10 bg-indigo-600 text-white rounded-full flex items-center justify-center shadow-lg border-4 border-[#f8fafc] group-active:scale-95 transition-transform">
                        <i class="fa-solid fa-plus text-xl"></i>
                    </div>
                    <span class="text-[9px] font-bold uppercase mt-1 text-slate-600">Create</span>
                </a>
                @endif

                {{-- Mobile AI Draft --}}
                @if(auth()->user()->hasPermission('can_ai'))
                <a href="{{ route('news.drafts') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.drafts') ? 'text-indigo-600' : 'text-slate-400' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-lg"></i>
                    <span class="text-[9px] font-bold uppercase">AI Draft</span>
                </a>
                @endif

                {{-- Mobile Menu Trigger --}}
                <button id="mobileMenuBtn" class="flex flex-col items-center gap-1 text-slate-400">
                    <i class="fa-solid fa-bars-staggered text-lg"></i>
                    <span class="text-[9px] font-bold uppercase">Menu</span>
                </button>

                {{-- MOBILE DROPDOWN (Contains Everything Else) --}}
                <div id="mobileDropdown" class="hidden absolute bottom-16 right-0 w-64 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="p-3 max-h-[75vh] overflow-y-auto custom-scrollbar">
                        
                        {{-- Publishing & Content --}}
                        <div class="mb-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase px-2 mb-2 tracking-widest">Content</p>
                            <a href="{{ route('news.index') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-list-check text-indigo-500 w-5"></i> News Feed
                            </a>
                            <a href="{{ route('news.create') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-circle-plus text-emerald-500 w-5"></i> Create (নিউজ তৈরি)
                            </a>
                        </div>

                        {{-- Reporter/Management --}}
                        <div class="mb-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase px-2 mb-2 tracking-widest">Operations</p>
                            @if(auth()->user()->role === 'reporter')
                            <a href="{{ route('reporter.news.create') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-paper-plane text-sky-500 w-5"></i> খবর পাঠান
                            </a>
                            <a href="{{ route('reporter.news.index') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-file-invoice text-slate-400 w-5"></i> আমার রিপোর্ট
                            </a>
                            @endif
                            
                            @if(auth()->user()->hasPermission('manage_reporters') || auth()->user()->role === 'super_admin')
                            <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-users-gear text-rose-500 w-5"></i> প্রতিনিধি লিস্ট
                            </a>
                            <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-chart-simple text-rose-500 w-5"></i> রিপোর্ট কার্ড
                            </a>
                            @endif

                            @if(auth()->user()->hasPermission('can_scrape'))
                            <a href="{{ route('websites.index') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-earth-asia text-blue-500 w-5"></i> Observed Sources
                            </a>
                            @endif
                        </div>

                        {{-- System & Administrative --}}
                        <div class="pt-3 border-t border-slate-100">
                            @if(auth()->user()->role === 'super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 p-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-xl">
                                <i class="fa-solid fa-lock text-center w-5"></i> Admin Dashboard
                            </a>
                            <a href="{{ route('admin.post-history') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-history text-center w-5"></i> পোস্ট ইতিহাস
                            </a>
                            <a href="{{ route('settings.index') }}" class="flex items-center gap-3 p-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-gear text-center w-5"></i> Settings (সেটিংস)
                            </a>
                            @endif

                            <a href="{{ route('credits.index') }}" class="flex items-center justify-between p-2.5 text-sm font-bold text-slate-700 hover:bg-amber-50 rounded-xl">
                                <span class="flex items-center gap-3"><i class="fa-solid fa-coins text-amber-500"></i> Credits</span>
                                <span class="text-amber-700">{{ auth()->user()->credits ?? 0 }}</span>
                            </a>

                            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 p-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-xl text-left">
                                    <i class="fa-solid fa-power-off w-5 text-center"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endauth

    <script>
        // Desktop 3-Dot Menu Toggle
        const dotBtn = document.getElementById('dotMenuBtn');
        const dotDropdown = document.getElementById('dotDropdown');
        if(dotBtn) {
            dotBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dotDropdown.classList.toggle('active');
            });
        }

        // Mobile Menu Toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const mobileDropdown = document.getElementById('mobileDropdown');
        if(mobileBtn) {
            mobileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                mobileDropdown.classList.toggle('hidden');
            });
        }

        // Close dropdowns on outside click
        document.addEventListener('click', (e) => {
            if(dotDropdown && !dotDropdown.contains(e.target)) dotDropdown.classList.remove('active');
            if(mobileDropdown && !mobileDropdown.contains(e.target)) mobileDropdown.classList.add('hidden');
        });

        function markRead() { fetch('{{ route("notifications.read") }}').catch(err => console.error(err)); }
    </script>
</body>
</html>