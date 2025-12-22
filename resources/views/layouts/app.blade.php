<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SubEditorBD - Premium News SaaS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', 'Hind Siliguri', sans-serif; }
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(99, 102, 241, 0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>

<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen flex flex-col relative selection:bg-indigo-100 selection:text-indigo-700 custom-scrollbar">

    <div class="fixed inset-0 -z-10 h-full w-full bg-white [background:radial-gradient(125%_125%_at_50%_10%,#fff_40%,#6366f108_100%)]"></div>

    {{-- 🔥 ADMIN IMPERSONATION BAR 🔥 --}}
    @if(session()->has('admin_impersonator_id'))
        <div class="bg-gradient-to-r from-red-600 to-rose-600 text-white text-center py-2.5 text-sm font-bold fixed top-0 w-full z-[9999] shadow-lg flex justify-center items-center gap-4 border-b border-red-700/30">
            <span class="flex items-center gap-2">
                <i class="fa-solid fa-user-secret animate-pulse"></i>
                আপনি এখন "{{ auth()->user()->name }}" হিসেবে আছেন
            </span>
            <a href="{{ route('stop.impersonate') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white px-4 py-1 rounded-full text-xs font-bold transition-all border border-white/40 uppercase">
                Return to Admin
            </a>
        </div>
        <div class="h-10"></div>
    @endif

    {{-- 📱 Mobile Top Header --}}
    <div class="lg:hidden glass-nav sticky top-0 z-50 px-4 py-3 flex justify-between items-center border-b border-slate-200/50">
        <span class="font-bold text-lg text-indigo-600">SubEditorBD</span>
        @auth
            <div class="flex items-center gap-3">
                <a href="{{ route('credits.index') }}" class="text-xs font-bold bg-amber-50 text-amber-700 px-2 py-1 rounded-lg border border-amber-200">
                    🪙 {{ auth()->user()->credits ?? 0 }}
                </a>
            </div>
        @endauth
    </div>

    {{-- 💻 Desktop Navigation --}}
    <nav class="hidden lg:block glass-nav border-b border-slate-200/50 sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex items-center gap-8">
                    <a href="{{ route('news.index') }}" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-white shadow-lg group-hover:rotate-6 transition-all duration-300">
                            <i class="fa-solid fa-newspaper text-lg"></i>
                        </div>
                        <span class="font-bold text-xl tracking-tight">SubEditor<span class="text-indigo-600">BD</span></span>
                    </a>

                    @auth
                    <div class="flex items-center bg-slate-100/60 p-1 rounded-xl gap-1">
                        @php
                            $linkStyle = "px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2";
                            $active = "bg-white shadow-sm text-indigo-600 ring-1 ring-black/5";
                            $inactive = "text-slate-500 hover:text-indigo-600 hover:bg-white/50";
                        @endphp

                        <a href="{{ route('news.index') }}" class="{{ $linkStyle }} {{ request()->routeIs('news.index') ? $active : $inactive }}">
                            Feed
                        </a>

                        {{-- AI Drafts Permission --}}
                        @if(auth()->user()->hasPermission('can_ai'))
                        <a href="{{ route('news.drafts') }}" class="{{ $linkStyle }} {{ request()->routeIs('news.drafts') ? $active : $inactive }}">
                            AI Drafts
                        </a>
                        @endif

                        {{-- Sources Permission --}}
                        @if(auth()->user()->hasPermission('can_scrape'))
                        <a href="{{ route('websites.index') }}" class="{{ $linkStyle }} {{ request()->routeIs('websites.*') ? $active : $inactive }}">
                            Observed
                        </a>
                        @endif
                        
                        @if(auth()->user()->role === 'super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="{{ $linkStyle }} {{ request()->routeIs('admin.dashboard') ? $active : 'text-rose-600 hover:bg-rose-50' }}">
                                <i class="fa-solid fa-shield-halved text-xs"></i> Admin
                            </a>
                            <a href="{{ route('admin.post-history') }}" class="{{ $linkStyle }} {{ request()->routeIs('admin.post-history') ? $active : 'text-slate-500' }}">ইতিহাস</a>
                        @endif
                    </div>
                    @endauth
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        <div class="flex items-center gap-4 border-r border-slate-200 pr-4">
                            <a href="{{ route('credits.index') }}" class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 px-3 py-1.5 rounded-full text-xs font-bold hover:bg-amber-100 transition-all">
                                🪙 {{ auth()->user()->credits ?? 0 }}
                            </a>

                            @php
                                $limit = auth()->user()->daily_post_limit ?? 20;
                                $used = auth()->user()->todays_post_count ?? 0; 
                                $percent = min(($used / $limit) * 100, 100);
                            @endphp
                            <div class="w-28 cursor-help" title="Usage: {{ $used }}/{{ $limit }}">
                                <div class="flex justify-between text-[10px] font-bold uppercase mb-1">
                                    <span class="text-slate-400">Post Limit</span>
                                    <span class="text-indigo-600">{{ $used }}/{{ $limit }}</span>
                                </div>
                                <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Notifications & Settings --}}
                        <div class="flex items-center gap-2">
                            @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
                            
                            {{-- Bell notification only if has AI permission --}}
                            @if(auth()->user()->hasPermission('can_ai'))
                            <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 rounded-xl text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                                <i class="fa-regular fa-bell text-lg"></i>
                                @if($unreadCount > 0)
                                    <span class="absolute top-1.5 right-1.5 h-4 w-4 flex items-center justify-center bg-rose-500 text-white text-[9px] font-bold rounded-full ring-2 ring-white">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </a>
                            @endif

                            @if(auth()->user()->role === 'super_admin' || session()->has('admin_impersonator_id'))
                                <a href="{{ route('settings.index') }}" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all" title="Settings">
                                    <i class="fa-solid fa-sliders text-lg"></i>
                                </a>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
                            <div class="text-right leading-tight">
                                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-tighter">{{ auth()->user()->role }}</p>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all border border-slate-200 shadow-sm">
                                    <i class="fa-solid fa-power-off"></i>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- 🔥 Role & Permission-Based Dashboard Bar (Contextual) 🔥 --}}
    @auth
    <div class="max-w-7xl mx-auto px-4 mt-6 hidden lg:block">
        <div class="glass-card rounded-2xl p-2.5 flex flex-wrap items-center gap-4 border border-indigo-100/50 shadow-sm">
            
            {{-- Management View (Permissions: manage_reporters or super_admin) --}}
            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                <div class="flex items-center gap-2 px-3 py-1.5 bg-rose-50/50 rounded-xl border border-rose-100/50">
                    <span class="text-rose-600 font-bold text-[10px] uppercase tracking-widest pr-2 border-r border-rose-200">Management</span>
                    <a href="{{ route('manage.reporters.index') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 px-1">প্রতিনিধি লিস্ট</a>
                    <a href="{{ route('manage.reporters.news') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 px-1">রিপোর্ট কার্ড</a>
                    @if(auth()->user()->role === 'super_admin')
                    <a href="{{ route('admin.post-history') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 px-1">ইতিহাস</a>
                    @endif
                </div>
            @endif

            {{-- Reporter View --}}
            @if(auth()->user()->role === 'reporter')
                <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 rounded-xl border border-emerald-100">
                    <span class="text-emerald-600 font-bold text-[10px] uppercase tracking-widest pr-2 border-r border-emerald-200">Reporter Panel</span>
                    <a href="{{ route('reporter.news.create') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">➕ খবর পাঠান</a>
                    <a href="{{ route('reporter.news.index') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-600 px-1">আমার খবরসমূহ</a>
                </div>
            @endif

            {{-- Quick Actions (Permission: can_direct_publish) --}}
            @if(auth()->user()->hasPermission('can_direct_publish'))
            <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-xl ml-auto">
                <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest pr-2 border-r border-slate-200">Quick Actions</span>
                <a href="{{ route('news.create') }}" class="text-sm font-bold text-indigo-600 hover:underline">Manual Post</a>
            </div>
            @endif
        </div>
    </div>
    @endauth

    <main class="flex-grow container mx-auto mt-6 px-4 pb-32 lg:pb-12">
        @yield('content')
    </main>

    {{-- 📱 Mobile Bottom Navigation (Responsive Grid based on Permissions) --}}
    @auth
        @php
            // গ্রিড কলাম সংখ্যা নির্ধারণ (Dynamic Grid)
            $cols = 1; // মেনু বাটন সবসময় থাকবে
            $cols++; // ফিড বাটন সবসময় থাকবে
            if(auth()->user()->hasPermission('can_direct_publish')) $cols++;
            if(auth()->user()->hasPermission('can_ai')) $cols++;
            
            $gridClass = match($cols) {
                2 => 'grid-cols-2',
                3 => 'grid-cols-3',
                4 => 'grid-cols-4',
                5 => 'grid-cols-5',
                default => 'grid-cols-4'
            };
        @endphp

        <div class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 w-[92%] z-50">
            <div class="glass-nav rounded-2xl grid {{ $gridClass }} items-center px-2 py-3 border border-indigo-100 shadow-2xl shadow-indigo-200/40">
                
                {{-- Feed --}}
                <a href="{{ route('news.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.index') ? 'text-indigo-600' : 'text-slate-400' }}">
                    <i class="fa-solid fa-house text-lg"></i>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Feed</span>
                </a>

                {{-- Create (Permission: can_direct_publish) --}}
                @if(auth()->user()->hasPermission('can_direct_publish'))
                <a href="{{ route('news.create') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.create') ? 'text-indigo-600' : 'text-slate-400' }}">
                    <div class="w-10 h-10 -mt-9 bg-indigo-600 text-white rounded-full flex items-center justify-center shadow-lg border-4 border-white">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span class="text-[9px] font-bold uppercase mt-1">Create</span>
                </a>
                @endif

                {{-- AI Draft (Permission: can_ai) --}}
                @if(auth()->user()->hasPermission('can_ai'))
                <a href="{{ route('news.drafts') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.drafts') ? 'text-indigo-600' : 'text-slate-400' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-lg"></i>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">AI Draft</span>
                </a>
                @endif

                {{-- Mobile Menu --}}
                <div class="relative group flex flex-col items-center">
                    <button id="mobileMenuBtn" class="flex flex-col items-center gap-1 text-slate-400">
                        <i class="fa-solid fa-bars text-lg"></i>
                        <span class="text-[9px] font-bold uppercase tracking-tighter">Menu</span>
                    </button>
                    <div id="mobileDropdown" class="hidden absolute bottom-14 right-0 w-48 bg-white rounded-xl shadow-xl border border-slate-100 p-2">
                        
                        {{-- Sources (Permission: can_scrape) --}}
                        @if(auth()->user()->hasPermission('can_scrape'))
                        <a href="{{ route('websites.index') }}" class="block px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-lg border-b border-slate-50 mb-1">
                            <i class="fa-solid fa-globe mr-2 text-xs"></i> Sources
                        </a>
                        @endif

                        <a href="{{ route('credits.index') }}" class="block px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-lg">
                            <i class="fa-solid fa-coins mr-2 text-xs"></i> Buy Credits
                        </a>

                        @if(auth()->user()->role === 'reporter')
                            <a href="{{ route('reporter.news.index') }}" class="block px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-lg">
                                <i class="fa-solid fa-file-pen mr-2 text-xs"></i> My Reports
                            </a>
                        @endif

                        @if(auth()->user()->hasPermission('manage_reporters'))
                            <a href="{{ route('manage.reporters.index') }}" class="block px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-lg">
                                <i class="fa-solid fa-users-gear mr-2 text-xs"></i> Reporters
                            </a>
                        @endif

                        <form action="{{ route('logout') }}" method="POST" class="mt-1 border-t border-slate-100">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-lg">
                                <i class="fa-solid fa-power-off mr-2 text-xs"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endauth

    <footer class="bg-white border-t border-slate-200 py-10 mt-10 hidden lg:block">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-sm text-slate-400">
            <p>&copy; {{ date('Y') }} <span class="font-bold text-slate-700">SubEditorBD</span>. Crafted for Digital Journalists.</p>
        </div>
    </footer>
    
    <script>
        // Mark Read Logic
        function markRead() {
            fetch('{{ route("notifications.read") }}').catch(err => console.error(err));
        }

        // Mobile Menu Toggle
        const menuBtn = document.getElementById('mobileMenuBtn');
        const dropdown = document.getElementById('mobileDropdown');
        if(menuBtn) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if(!dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>