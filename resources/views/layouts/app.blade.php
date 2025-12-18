<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Scraper & Card Maker</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Inter', 'Hind Siliguri', sans-serif; 
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fadeInDown 0.4s ease-out forwards;
        }

        /* Glass Effects */
        .glass-nav { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
        }
        .glass-dock {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col relative selection:bg-indigo-100 selection:text-indigo-700">

    <div class="fixed inset-0 -z-10 h-full w-full bg-white [background:radial-gradient(125%_125%_at_50%_10%,#fff_40%,#6366f110_100%)]"></div>

    <nav class="glass-nav border-b border-indigo-50/50 sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex items-center gap-8">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('news.index') }}" class="flex items-center gap-2 group">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200 group-hover:shadow-indigo-300 transition-all duration-300 group-hover:scale-105">
                                <span class="text-lg">📰</span>
                            </div>
                            <span class="font-bold text-xl bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent tracking-tight group-hover:from-indigo-600 group-hover:to-violet-600 transition-all">
                                SubEditor<span class="font-extrabold">BD</span>
                            </span>
                        </a>
                    </div>

                    <div class="hidden md:flex space-x-1">
                        @php
                            $navClass = "inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-indigo-50";
                            $activeClass = "bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-200";
                            $inactiveClass = "text-slate-600 hover:text-indigo-600";
                        @endphp

                        <a href="{{ route('news.index') }}" class="{{ $navClass }} {{ request()->routeIs('news.index') ? $activeClass : $inactiveClass }}">
                           Feed
                        </a>
                        
                        <a href="{{ route('news.drafts') }}" class="{{ $navClass }} {{ request()->routeIs('news.drafts') ? $activeClass : $inactiveClass }}">
                           Drafts
                        </a>

                        <a href="{{ route('news.create') }}" class="{{ $navClass }} {{ request()->routeIs('news.create') ? $activeClass : $inactiveClass }}">
                           Create
                        </a>
                        
                        <a href="{{ route('websites.index') }}" class="{{ $navClass }} {{ request()->routeIs('websites.*') ? $activeClass : $inactiveClass }}">
                           Sources
                        </a>

                        <a href="{{ route('admin.post-history') }}" class="{{ $navClass }} {{ request()->routeIs('admin.post-history') ? $activeClass : $inactiveClass }}">
                            History
                        </a>

                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-bold text-rose-600 hover:bg-rose-50 transition-colors">
                                Admin
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-3 sm:gap-4">

                    @auth
                        <a href="/credits" class="hidden sm:flex items-center gap-1.5 bg-white border border-amber-200 text-amber-700 px-3 py-1.5 rounded-full text-xs font-bold shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-300">
                            <span class="text-lg leading-none">🪙</span>
                            <span>{{ auth()->user()->credits ?? 0 }}</span>
                        </a>

                        @if(auth()->user()->role !== 'super_admin')
                            @php
                                $limit = auth()->user()->daily_post_limit ?? 20;
                                $used = auth()->user()->todays_post_count ?? 0; 
                                $isLimitReached = $used >= $limit;
                                $percent = ($used / $limit) * 100;
                            @endphp
                            
                            <div class="hidden sm:flex flex-col w-24 gap-1 group cursor-help" title="Limit: {{ $used }}/{{ $limit }}">
                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                    <span class="{{ $isLimitReached ? 'text-rose-500' : 'text-slate-500' }}">Daily</span>
                                    <span class="{{ $isLimitReached ? 'text-rose-600' : 'text-indigo-600' }}">{{ $used }}/{{ $limit }}</span>
                                </div>
                                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isLimitReached ? 'bg-rose-500' : 'bg-indigo-500' }}" style="width: {{ $percent > 100 ? 100 : $percent }}%"></div>
                                </div>
                            </div>
                        @endif

                        @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
                        <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 rounded-full text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1.5 right-1.5 h-4 w-4 flex items-center justify-center bg-rose-500 text-white text-[9px] font-bold rounded-full ring-2 ring-white animate-bounce">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <div class="hidden md:flex items-center gap-3 pl-3 border-l border-slate-200">
                             <div class="flex flex-col text-right">
                                <span class="text-sm font-bold text-slate-700 leading-tight">{{ auth()->user()->name }}</span>
                                <a href="{{ route('settings.index') }}" class="text-[10px] text-slate-400 hover:text-indigo-500 font-medium">Settings</a>
                            </div>
                            
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Logout">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        
                        <a href="{{ route('settings.index') }}" class="md:hidden p-2 text-slate-500 hover:text-indigo-600">
                             <span class="sr-only">Settings</span>
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                         </a>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}" class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200 text-sm">
                            Login
                        </a>
                    @endguest

                </div>
            </div>
        </div>
    </nav>

    @auth
        <div class="sm:hidden fixed bottom-4 left-4 right-4 z-50">
            <div class="glass-dock rounded-2xl grid grid-cols-4 items-center px-2 py-3 border border-white/50 shadow-xl">
                
                <a href="{{ route('news.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.index') ? 'text-indigo-600 font-semibold' : 'text-slate-400' }} transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    <span class="text-[10px]">Feed</span>
                </a>
                
                <a href="{{ route('news.create') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.create') ? 'text-indigo-600 font-semibold' : 'text-slate-500' }} transition-all duration-200">
                     <div class="{{ request()->routeIs('news.create') ? 'bg-indigo-100 p-1.5 rounded-lg text-indigo-700' : '' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                     </div>
                     <span class="text-[10px]">Create</span>
                </a>
                
                <a href="{{ route('news.drafts') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('news.drafts') ? 'text-indigo-600 font-semibold' : 'text-slate-400' }} transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span class="text-[10px]">Drafts</span>
                </a>

                <a href="{{ route('websites.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('websites.*') ? 'text-indigo-600 font-semibold' : 'text-slate-400' }} transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    <span class="text-[10px]">Source</span>
                </a>

            </div>
        </div>
    @endauth

    <main class="flex-grow container mx-auto mt-6 px-4 pb-32 sm:pb-12">
        
        @if(session('success'))
            <div class="max-w-4xl mx-auto bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 px-6 py-4 rounded-r-lg shadow-sm flex items-center gap-4 mb-8 animate-fade-in-down">
                <div class="bg-emerald-100 p-2 rounded-full">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm">Success</h3>
                    <p class="text-sm opacity-90">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-4xl mx-auto bg-rose-50 border-l-4 border-rose-500 text-rose-800 px-6 py-4 rounded-r-lg shadow-sm flex items-center gap-4 mb-8 animate-fade-in-down">
                <div class="bg-rose-100 p-2 rounded-full">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm">Error</h3>
                    <p class="text-sm opacity-90">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @yield('content')
        
    </main>

    <footer class="bg-white border-t border-slate-200 py-10 hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-sm text-slate-500">
            <div class="flex items-center gap-2 mb-4 md:mb-0">
                <span class="text-2xl">📰</span>
                <span>&copy; {{ date('Y') }} <span class="font-bold text-slate-800">SubEditorBD</span>. Crafted for creators.</span>
            </div>
            <div class="flex gap-8 font-medium">
                <a href="#" class="hover:text-indigo-600 transition">Privacy</a>
                <a href="#" class="hover:text-indigo-600 transition">Terms</a>
                <a href="#" class="hover:text-indigo-600 transition">Support</a>
            </div>
        </div>
    </footer>
    
    <script>
        function markRead() {
            fetch('{{ route("notifications.read") }}');
        }
    </script>

</body>
</html>