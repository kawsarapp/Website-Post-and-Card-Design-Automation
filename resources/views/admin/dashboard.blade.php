@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 bg-gray-100 min-h-screen">

    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">⚡ সুপার অ্যাডমিন প্যানেল</h1>
            <p class="text-slate-500 mt-1">সিস্টেম ওভারভিউ এবং ইউজার ম্যানেজমেন্ট</p>
        </div>
        <div class="mt-4 md:mt-0">
            <span class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-mono shadow-md">
                Admin Mode
            </span>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Success!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-blue-50 text-blue-600 rounded-xl text-2xl">👥</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">মোট ইউজার</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalUsers }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-purple-50 text-purple-600 rounded-xl text-2xl">📰</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">জেনারেটেড নিউজ</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalNews }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl text-2xl">🌐</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">কানেক্টেড সাইট</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalWebsites }}</h3>
            </div>
        </div>
    </div>

    {{-- User Table Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 class="text-lg font-bold text-slate-700">👤 ইউজার লিস্ট</h2>
            <button onclick="openCreateUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 shadow flex items-center gap-2">
                ➕ নতুন ইউজার
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">নাম ও ইমেইল</th>
                        <th class="px-6 py-4 font-bold text-center">ক্রেডিট</th>
                        <th class="px-6 py-4 font-bold text-center">ডেইলি লিমিট</th>
                        <th class="px-6 py-4 font-bold text-center">স্ট্যাটাস</th>
                        <th class="px-6 py-4 font-bold">জয়েনিং ডেট</th>
                        <th class="px-6 py-4 font-bold text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold inline-block min-w-[60px]">
                                {{ $user->credits }} Left
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">
                                    {{ $user->daily_post_limit }} / Day
                                </span>
                                <button onclick="openLimitModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->daily_post_limit }}')" 
                                        class="text-gray-400 hover:text-blue-600 transition p-1 rounded hover:bg-gray-200" title="Edit Limit">
                                    ✏️
                                </button>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($user->is_active)
                                <span class="text-green-600 text-xs font-bold bg-green-100 px-2 py-1 rounded border border-green-200">Active</span>
                            @else
                                <span class="text-red-600 text-xs font-bold bg-red-100 px-2 py-1 rounded border border-red-200">Banned</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $user->created_at->format('d M, Y') }}
                        </td>

                        <td class="px-6 py-4 text-right flex justify-end gap-2 items-center flex-wrap">
                            
                            {{-- Edit Button (স্টাফ লিমিট পাস করা হলো) --}}
                            <button onclick="openEditUserModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->email }}', '{{ $user->staff_limit }}')" class="bg-yellow-500 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-600 shadow-sm flex items-center justify-center gap-1" title="Edit Profile">✏️ Edit</button>
                            
                            <button onclick='openSourceModal("{{ $user->id }}", "{{ $user->name }}", @json($user->accessibleWebsites->pluck("id")))' class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-700 flex items-center gap-1 shadow-sm" title="Manage News Sources">🌐 <span class="hidden md:inline">Sources</span></button>
                            <button onclick='openTemplateModal("{{ $user->id }}", "{{ $user->name }}", @json($user->settings->allowed_templates ?? []), "{{ $user->settings->default_template ?? "dhaka_post_card" }}")' class="bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800 flex items-center gap-1 shadow-sm" title="Manage Templates">🎨 <span class="hidden md:inline">Templates</span></button>
                            <button onclick="openScraperModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->settings->scraper_method ?? '' }}', '{{ $user->settings->auto_clean_days ?? 7 }}')" class="bg-purple-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-purple-700 shadow-sm flex items-center justify-center gap-1" title="Scraper Settings">🤖</button>
                            <button onclick='openPermissionModal("{{ $user->id }}", "{{ $user->name }}", @json($user->permissions ?? []))' class="bg-pink-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-pink-700 shadow-sm flex items-center justify-center gap-1" title="User Permissions">🔐</button>

                            <form action="{{ route('admin.users.credits', $user->id) }}" method="POST" class="flex items-center">
                                @csrf
                                <input type="number" name="amount" placeholder="+Cr" class="w-12 text-xs border border-slate-300 rounded-l-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                                <button type="submit" class="bg-indigo-600 text-white text-xs px-2 py-1.5 rounded-r-lg hover:bg-indigo-700 font-bold shadow-sm">Add</button>
                            </form>
                            
                            <a href="{{ route('admin.users.login-as', $user->id) }}" class="bg-yellow-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-yellow-600 ml-2" onclick="return confirm('আপনি কি এই ইউজার হিসেবে লগইন করতে চান?')">🔑 Login</a>

                            <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold border shadow-sm transition {{ $user->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}" onclick="return confirm('Are you sure?')">
                                    {{ $user->is_active ? 'Block' : 'Unblock' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="p-4 bg-gray-50 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>
</div>

@include('admin.partials.dashboard-modals')
@include('admin.partials.dashboard-scripts')

@endsection