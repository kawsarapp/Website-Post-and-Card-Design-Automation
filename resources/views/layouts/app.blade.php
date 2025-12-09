<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Scraper & Card Maker</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
        .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <nav class="glass-nav border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('news.index') }}" class="flex items-center gap-2">
                            <span class="text-2xl">📰</span>
                            <span class="font-bold text-xl text-indigo-700 tracking-tight">NewsCard Pro</span>
                        </a>
                    </div>

                    <div class="hidden space-x-6 sm:-my-px sm:ml-10 sm:flex">
                        <a href="{{ route('news.index') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('news.index') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-semibold transition">
                            News Feed
                        </a>
                        
                        <a href="{{ route('news.drafts') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('news.drafts') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-semibold transition">
                            📝 Drafts
                        </a>
						
						<a href="{{ route('news.create') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('news.create') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-semibold transition">
                            📝 Create News
                        </a>
                        
                        <a href="{{ route('websites.index') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('websites.*') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-semibold transition">
                            🌐 Sources
                        </a>

                        <a href="{{ route('settings.index') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('settings.*') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-semibold transition">
                            ⚙️ Settings
                        </a>

                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold text-red-600 hover:text-red-800">
                                ⚡ Admin
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-4">

                    @auth
                        {{-- Credits Badge --}}
                        <a href="/credits" class="hidden sm:flex items-center gap-1 bg-gradient-to-r from-yellow-100 to-amber-100 border border-amber-200 text-amber-800 px-3 py-1.5 rounded-full text-sm font-bold shadow-sm hover:shadow hover:scale-105 transition">
                            <span>🪙</span>
                            <span>Credits: {{ auth()->user()->credits ?? 0 }}</span>
                        </a>

                        {{-- ✅ NEW: Daily Post Limit Display (Desktop) --}}
                        @if(auth()->user()->role !== 'super_admin')
                            @php
                                $limit = auth()->user()->daily_post_limit ?? 20;
                                // User Model এ 'getTodaysPostCountAttribute' থাকতে হবে
                                $used = auth()->user()->todays_post_count ?? 0; 
                                $isLimitReached = $used >= $limit;
                            @endphp
                            
                            <div class="hidden sm:flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-bold shadow-sm border transition cursor-help
                                {{ $isLimitReached ? 'bg-red-50 border-red-200 text-red-700' : 'bg-blue-50 border-blue-200 text-blue-700' }}" 
                                title="Today's Post Limit: {{ $used }} used out of {{ $limit }}">
                                <span>📊</span>
                                <span>Limit: {{ $used }} / {{ $limit }}</span>
                            </div>
                        @endif

                        {{-- নোটিফিকেশন আইকন আপডেট --}}
							@php
								$unreadCount = auth()->user()->unreadNotifications->count();
							@endphp

							<a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative cursor-pointer group hover:scale-110 transition" title="Notifications">
								<span class="text-xl text-gray-600 group-hover:text-indigo-600">🔔</span>
								@if($unreadCount > 0)
									<span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white animate-pulse">
										{{ $unreadCount }}
									</span>
								@endif
							</a>

                        <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
                            <span class="text-sm font-bold text-gray-700 hidden md:block">
                                {{ auth()->user()->name }}
                            </span>
                            
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs bg-white text-gray-500 hover:text-red-600 border border-gray-200 px-3 py-1.5 rounded-lg font-bold transition hover:bg-red-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}" class="text-sm bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                            Login
                        </a>
                    @endguest

                </div>
            </div>
        </div>
    </nav>

    @auth
        {{-- Mobile Bottom Navigation --}}
        <div class="sm:hidden fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 flex justify-around items-center p-3 text-[10px] font-bold z-40 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <a href="{{ route('news.index') }}" class="flex flex-col items-center gap-1 text-gray-500 hover:text-indigo-600 {{ request()->routeIs('news.index') ? 'text-indigo-600' : '' }}">
                <span class="text-lg">📰</span> News
            </a>
            <a href="{{ route('news.drafts') }}" class="flex flex-col items-center gap-1 text-gray-500 hover:text-indigo-600 {{ request()->routeIs('news.drafts') ? 'text-indigo-600' : '' }}">
                <span class="text-lg">📝</span> Drafts
            </a>
            
            <a href="#" class="flex flex-col items-center gap-1 text-amber-600 hover:text-amber-700">
                <span class="text-lg">🪙</span> 
                <span>{{ auth()->user()->credits ?? 0 }}</span>
            </a>

            {{-- ✅ NEW: Daily Limit (Mobile) --}}
            @if(auth()->user()->role !== 'super_admin')
                <div class="flex flex-col items-center gap-1 text-blue-600">
                    <span class="text-lg">📊</span>
                    <span class="text-[9px]">
                        {{ auth()->user()->todays_post_count ?? 0 }}/{{ auth()->user()->daily_post_limit ?? 20 }}
                    </span>
                </div>
            @endif

            <a href="{{ route('settings.index') }}" class="flex flex-col items-center gap-1 text-gray-500 hover:text-indigo-600 {{ request()->routeIs('settings.*') ? 'text-indigo-600' : '' }}">
                <span class="text-lg">⚙️</span> Settings
            </a>
        </div>
    @endauth

    <div class="container mx-auto mt-6 px-4 pb-24 sm:pb-8 min-h-[calc(100vh-180px)]">
        
        @if(session('success'))
            <div class="max-w-4xl mx-auto bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg shadow-sm flex items-center gap-3 mb-6 animate-fade-in-down">
                <span class="text-xl">✅</span>
                <p class="font-medium text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-4xl mx-auto bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-sm flex items-center gap-3 mb-6 animate-fade-in-down">
                <span class="text-xl">⚠️</span>
                <p class="font-medium text-sm">{{ session('error') }}</p>
            </div>
        @endif

        @yield('content')
        
    </div>

    <footer class="bg-white border-t border-gray-200 py-8 hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
            <div class="mb-4 md:mb-0">
                &copy; {{ date('Y') }} <span class="font-bold text-indigo-600">NewsCard Pro</span>. All rights reserved.
            </div>
            <div class="flex gap-6">
                <a href="#" class="hover:text-gray-900">Privacy Policy</a>
                <a href="#" class="hover:text-gray-900">Terms of Service</a>
                <a href="#" class="hover:text-gray-900">Support</a>
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