@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
    {{-- 🔥 PAGE HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            📜 পাবলিশড নিউজ <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">{{ $allPosts->total() }}</span>
        </h1>
    </div>

    {{-- 🔥 ADVANCED FILTER SECTION --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.post-history') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            
            {{-- Search Input --}}
            <div class="md:col-span-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Search Title..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
            </div>

            {{-- User Dropdown --}}
            <div>
                <select name="user_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    <option value="">👤 All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Website Dropdown --}}
            <div>
                <select name="website_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    <option value="">🌐 All Sources</option>
                    @foreach($websites as $web)
                        <option value="{{ $web->id }}" {{ request('website_id') == $web->id ? 'selected' : '' }}>
                            {{ $web->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg text-xs" title="Start Date">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-lg text-xs" title="End Date">
            </div>

            {{-- Filter Buttons --}}
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                    Filter
                </button>
                <a href="{{ route('admin.post-history') }}" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200" title="Reset">
                    ✖
                </a>
            </div>
        </form>
    </div>

    {{-- 🔥 TABLE SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold border-b">Date</th>
                        <th class="px-6 py-4 font-bold border-b">User</th>
                        <th class="px-6 py-4 font-bold border-b">Source</th>
                        <th class="px-6 py-4 font-bold border-b">Published To</th>
                        <th class="px-6 py-4 font-bold border-b">Title</th>
                        {{-- Social Status Header Moved Here --}}
                        <th class="px-6 py-4 font-bold border-b text-center">Social Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @foreach($allPosts as $post)
                    <tr class="hover:bg-gray-50 transition">
                        
                        {{-- Date --}}
                        <td class="px-6 py-4 text-gray-500 text-xs whitespace-nowrap">
                            {{ $post->posted_at ? \Carbon\Carbon::parse($post->posted_at)->format('d M, Y h:i A') : '' }}
                        </td>

                        {{-- User --}}
                        <td class="px-6 py-4 text-xs font-bold text-gray-700">
                            {{ $post->user->name ?? 'Unknown' }}
                        </td>

                        {{-- Source --}}
                        <td class="px-6 py-4">
                            <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-[10px] font-bold border border-blue-100">
                                {{ $post->website->name ?? 'Direct Upload' }}
                            </span>
                        </td>

                        {{-- Published To --}}
                        <td class="px-6 py-4">
                            @php
                                $settings = $post->user->settings ?? null;
                                $brand = $settings->brand_name ?? 'Unknown';
                                $liveLink = $post->live_url; 
                            @endphp
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-bold">{{ $brand }}</span>
                                @if($liveLink)
                                    <a href="{{ $liveLink }}" target="_blank" class="text-[10px] text-green-600 hover:underline flex items-center gap-1">
                                        Live Link ↗
                                    </a>
                                @else
                                    <span class="text-[10px] text-gray-400">Not Linked</span>
                                @endif
                            </div>
                        </td>

                        {{-- Title --}}
                        <td class="px-6 py-4 text-xs text-gray-800 font-medium line-clamp-1 max-w-xs">
                            {{ Str::limit($post->ai_title ?? $post->title, 50) }}
                        </td>

                        {{-- Social Status (Integrated Here) --}}
                        
						<td class="px-6 py-4 text-center">
							<div class="flex justify-center gap-3">
								
								{{-- Facebook Status --}}
								@if($post->fb_status == 'success')
									<span class="text-blue-600 text-lg" title="Published successfully">
										<i class="fab fa-facebook"></i> ✅
									</span>
								@elseif($post->fb_status == 'failed')
									{{-- 🔥🔥 FIX: গ্রুপ হোভার এবং ফুল মেসেজ --}}
									<div class="relative group">
										<span class="text-red-500 text-lg cursor-help">
											<i class="fab fa-facebook"></i> ❌
										</span>
										
										{{-- Tooltip Container --}}
										<div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:block z-50 w-64">
											<div class="bg-slate-800 text-white text-xs rounded p-2 shadow-xl border border-slate-600">
												{{-- এখানে পুরো এরর মেসেজ দেখানো হবে --}}
												<strong>FB Error:</strong> <br>
												{{ $post->fb_error }}
											</div>
											{{-- Arrow --}}
											<div class="w-3 h-3 bg-slate-800 transform rotate-45 absolute -bottom-1 left-1/2 -translate-x-1/2 border-b border-r border-slate-600"></div>
										</div>
									</div>
								@else
									<span class="text-gray-300 text-lg" title="Skipped / Pending">
										<i class="fab fa-facebook"></i> ⚪
									</span>
								@endif

								{{-- Telegram Status --}}
								@if($post->tg_status == 'success')
									<span class="text-sky-500 text-lg" title="Sent successfully">
										<i class="fab fa-telegram"></i> ✅
									</span>
								@elseif($post->tg_status == 'failed')
									<div class="relative group">
										<span class="text-red-500 text-lg cursor-help">
											<i class="fab fa-telegram"></i> ❌
										</span>
										<div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:block z-50 w-64">
											<div class="bg-slate-800 text-white text-xs rounded p-2 shadow-xl border border-slate-600">
												<strong>TG Error:</strong> <br>
												{{ $post->tg_error }}
											</div>
											<div class="w-3 h-3 bg-slate-800 transform rotate-45 absolute -bottom-1 left-1/2 -translate-x-1/2 border-b border-r border-slate-600"></div>
										</div>
									</div>
								@else
									<span class="text-gray-300 text-lg" title="Skipped / Pending">
										<i class="fab fa-telegram"></i> ⚪
									</span>
								@endif

							</div>
						</td>

                        {{-- End Social Status --}}

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔥 PAGINATION --}}
    <div class="mt-6">
        {{ $allPosts->links() }}
    </div>

</div>
@endsection