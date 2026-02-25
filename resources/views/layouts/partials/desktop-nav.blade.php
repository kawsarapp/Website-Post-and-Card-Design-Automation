<nav class="hidden lg:block glass-nav sticky top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
        
        <div class="flex items-center gap-6 xl:gap-8">
            <a href="{{ auth()->user()->role === 'reporter' ? route('reporter.news.index') : route('news.index') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg group-hover:scale-105 transition-transform"><i class="fa-solid fa-bolt"></i></div>
                <span class="font-bold text-xl tracking-tight text-slate-900 group-hover:text-indigo-700 transition-colors">Newsmanage<span class="text-indigo-600">24</span></span>
            </a>

            @auth
            @if(auth()->user()->role === 'reporter')
                <div class="flex items-center bg-slate-100/50 p-1.5 rounded-xl border border-slate-200/50 gap-1">
                    <a href="{{ route('reporter.news.create') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300 {{ request()->routeIs('reporter.news.create') ? 'bg-indigo-600 text-white shadow-md transform scale-[1.02]' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                        <i class="fa-solid fa-plus"></i> খবর পাঠান
                    </a>
                    <a href="{{ route('reporter.news.index') }}" class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300 {{ request()->routeIs('reporter.news.index') ? 'bg-white text-indigo-600 shadow-sm transform scale-[1.02]' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                        <i class="fa-solid fa-list-ul"></i> আমার খবরসমূহ
                    </a>
                </div>
            @else
                <div class="flex items-center bg-slate-100/50 p-1.5 rounded-xl border border-slate-200/50 gap-1">
                    <a href="{{ route('news.index') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('news.index') ? 'bg-white shadow-sm text-indigo-600 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-white' }}">Feed</a>
                    <a href="{{ route('news.published') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('news.published') ? 'bg-white shadow-sm text-indigo-600 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-white' }}">Published</a>
                    
                    @if(auth()->user()->hasPermission('can_direct_publish'))
                    <a href="{{ route('news.create') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('news.create') ? 'bg-white shadow-sm text-indigo-600 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-white' }}">Create</a>
                    @endif
                    
                    @if(auth()->user()->hasPermission('can_ai'))
                    <a href="{{ route('news.drafts') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('news.drafts') ? 'bg-white shadow-sm text-indigo-600 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-white' }}">Drafts</a>
                    @endif

                    @if(auth()->user()->hasPermission('can_scrape'))
                    <a href="{{ route('websites.index') }}" class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('websites.*') ? 'bg-white shadow-sm text-indigo-600 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-white' }}">Observe</a>
                    @endif

                    {{-- 🔥 NEW: Reporter News directly in Top Nav --}}
                    @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                    <div class="w-[1px] h-5 bg-slate-300 mx-1"></div>
                    <a href="{{ route('manage.reporters.news') }}" class="px-4 py-1.5 rounded-lg text-sm font-bold flex items-center gap-1.5 transition-all duration-200 {{ request()->routeIs('manage.reporters.news') ? 'bg-indigo-50 shadow-sm text-indigo-700 scale-[1.02]' : 'text-slate-500 hover:text-indigo-600 hover:bg-indigo-50/50' }}">
                        <i class="fa-solid fa-satellite-dish text-indigo-400"></i> Team News
                    </a>
                    @endif
                </div>
            @endif
            @endauth
        </div>

        <div class="flex items-center gap-3">
            @auth
                <div class="hidden xl:flex items-center gap-3 border-r border-slate-200 pr-4">
                    <div class="text-[10px] font-black uppercase text-slate-400 cursor-help" title="Today's Post Limit">
                        Limit: <span class="text-indigo-600">{{ auth()->user()->todays_post_count ?? 0 }}/{{ auth()->user()->daily_post_limit ?? 20 }}</span>
                    </div>
                    @if(auth()->user()->role !== 'reporter')
                    <a href="{{ route('credits.index') }}" class="bg-amber-50 hover:bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-100 transition-colors shadow-sm cursor-pointer hover:scale-105 transform">🪙 {{ auth()->user()->credits ?? 0 }}</a>
                    @endif
                </div>

                @if(auth()->user()->role !== 'reporter')
                    <a href="{{ route('news.drafts') }}" onclick="markRead()" class="relative p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition-all group">
                        <i class="fa-regular fa-bell text-lg group-hover:rotate-12 transition-transform"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute top-1.5 right-1.5 h-2 w-2 bg-rose-500 rounded-full shadow-[0_0_8px_rgba(244,63,94,0.8)] animate-pulse"></span>
                        @endif
                    </a>
                @endif

                {{-- 3-DOT MENU / DROPDOWN --}}
                <div class="relative ml-1">
                    <button id="dotMenuBtn" class="flex items-center gap-3 ml-2 pl-3 focus:outline-none hover:bg-slate-50 rounded-xl p-1.5 transition-all active:scale-95 group border border-transparent hover:border-slate-200">
                        <div class="text-right leading-tight">
                            <p class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ auth()->user()->name }}</p>
                            <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">{{ auth()->user()->role }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-600 font-bold text-xs uppercase group-hover:shadow-md transition-all">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </button>
                    
                    {{-- 🔥 UPDATE: Smooth Dropdown Animation CSS Classes added --}}
                    <div id="dotDropdown" class="hidden opacity-0 scale-95 transform transition-all duration-200 ease-out origin-top-right absolute right-0 mt-3 w-64 bg-white/95 backdrop-blur-xl rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] border border-slate-100 p-2 z-[100]">
                        
                        @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters') || auth()->user()->hasPermission('can_manage_staff'))
                            <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">Team Management</p>
                            
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage_reporters'))
                            <a href="{{ route('manage.reporters.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl transition-colors group">
                                <i class="fa-solid fa-users text-indigo-400 w-5 group-hover:text-indigo-600 transition-colors"></i> Reporter List
                            </a>
                            <a href="{{ route('manage.reporters.news') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl transition-colors group">
                                <i class="fa-solid fa-clipboard-list text-indigo-400 w-5 group-hover:text-indigo-600 transition-colors"></i> Reporter News
                            </a>
                            @endif

                            @if(auth()->user()->hasPermission('can_manage_staff'))
                            <a href="{{ route('client.staff.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl transition-colors group">
                                <i class="fa-solid fa-users-gear text-indigo-400 w-5 group-hover:text-indigo-600 transition-colors"></i> Staff Management
                            </a>
                            @endif
                            <div class="my-2 border-t border-slate-100"></div>
                        @endif

                        @if(auth()->user()->role !== 'reporter')
                            <p class="text-[10px] font-black text-slate-400 uppercase px-3 py-2 tracking-widest">System</p>
                            
                            @if(auth()->user()->role === 'super_admin')
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-rose-600 rounded-xl transition-colors group">
                                    <i class="fa-solid fa-shield-halved text-rose-400 w-5 group-hover:text-rose-600 transition-colors"></i> Dashboard
                                </a>
                            @endif
                            
                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('can_history'))
                                <a href="{{ route('admin.post-history') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-clock-rotate-left text-slate-400 w-5"></i> History
                                </a>
                            @endif

                            @if(auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('can_settings'))
                                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-xl transition-colors">
                                    <i class="fa-solid fa-sliders text-slate-400 w-5"></i> Settings
                                </a>
                            @endif
                            <div class="my-2 border-t border-slate-100"></div>
                        @endif

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-rose-500 hover:bg-rose-50 rounded-xl text-left transition-colors group">
                                <i class="fa-solid fa-power-off w-5 group-hover:animate-pulse"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</nav>