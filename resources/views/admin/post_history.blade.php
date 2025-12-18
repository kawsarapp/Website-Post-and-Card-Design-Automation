@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
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

    {{-- TABLE SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold border-b">Date</th>
                    <th class="px-6 py-4 font-bold border-b">User</th>
                    <th class="px-6 py-4 font-bold border-b">Source</th>
                    <th class="px-6 py-4 font-bold border-b">Published To</th>
                    <th class="px-6 py-4 font-bold border-b">Title</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($allPosts as $post)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-500 text-xs whitespace-nowrap">
                        {{ $post->posted_at ? \Carbon\Carbon::parse($post->posted_at)->format('d M, Y h:i A') : '' }}
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-gray-700">
                        {{ $post->user->name ?? 'Unknown' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-[10px] font-bold border border-blue-100">
                            {{ $post->website->name ?? 'Direct Upload' }}
                        </span>
                    </td>
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
                    <td class="px-6 py-4 text-xs text-gray-800 font-medium line-clamp-1">
                        {{ Str::limit($post->ai_title ?? $post->title, 50) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $allPosts->links() }}
    </div>
</div>
@endsection