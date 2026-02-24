<nav class="hidden lg:block glass-nav sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
        
        <div class="flex items-center gap-8">
            <a href="{{ auth()->user()->role === 'reporter' ? route('reporter.news.index') : route('news.index') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg"><i class="fa-solid fa-bolt"></i></div>
                <span class="font-bold text-xl tracking-tight text-slate-900">Newsmanage<span class="text-indigo-600">24</span></span>
            </a>

            @auth
            @if(auth()->user()->role === 'reporter')
                <div class="flex items-center bg-slate-100/50 p-1 rounded-xl border border-slate-200/50">
                    <a href="{{ route('reporter.news.create') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold {{ request()->routeIs('reporter.news.create') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                        <i class="fa-solid fa-plus"></i> খবর পাঠান
                    </a>
                    <a href="{{ route('reporter.news.index') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold {{ request()->routeIs('reporter.news.index') ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                        <i class="fa-solid fa-list-ul"></i> আমার খবরসমূহ
                    </a>
                </div>
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
                <div class="hidden xl:flex items-center gap-3 border-r border-slate-200 pr-4">
                    <div class="text-[10px] font-black uppercase text-slate-400">
                        Limit: <span class="text-indigo-600">{{ auth()->user()->todays_post_count ?? 0 }}/{{ auth()->user()->daily_post_limit ?? 20 }}</span>
                    </div>
                    @if(auth()->user()->role !== 'reporter')
                    <a href="{{ route('credits.index') }}" class="bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-100">🪙 {{ auth()->user()->credits ?? 0 }}</a>
                    @endif
                </div>

                @if(auth()->user()->role !== 'reporter')
                    <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                        <i class="fa-regular fa-bell text-lg"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute top-1.5 right-1.5 h-2 w-2 bg-rose-500 rounded-full"></span>
                        @endif
                    </a>
                @endif

                {{-- 3-DOT MENU / DROPDOWN --}}
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
                        
                        @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters') || auth()->user()->hasPermission('can_manage_staff'))
                            <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">Team Management</p>
                            
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                            <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-users text-indigo-500 w-5"></i> Reporter List
                            </a>
                            <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-clipboard-list text-indigo-500 w-5"></i> Reporter News
                            </a>
                            @endif

                            {{-- 🔥 Staff Management Link Added Here --}}
                            @if(auth()->user()->hasPermission('can_manage_staff'))
                            <a href="{{ route('client.staff.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 rounded-xl">
                                <i class="fa-solid fa-users-gear text-indigo-500 w-5"></i> Staff Management
                            </a>
                            @endif
                            <div class="my-2 border-t border-slate-100"></div>
                        @endif

                        @if(auth()->user()->role !== 'reporter')
                            <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">System</p>
                            
                            @if(auth()->user()->role === 'super_admin')
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-shield-halved text-rose-500 w-5"></i> Dashboard
                                </a>
                            @endif
                            
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('can_history'))
                                <a href="{{ route('admin.post-history') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-clock-rotate-left text-slate-400 w-5"></i> History
                                </a>
                            @endif

                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('can_settings'))
                                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-xl">
                                    <i class="fa-solid fa-sliders text-slate-400 w-5"></i> Settings
                                </a>
                            @endif
                            <div class="my-2 border-t border-slate-100"></div>
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