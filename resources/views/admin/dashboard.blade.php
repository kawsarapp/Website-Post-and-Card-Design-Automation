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
                        
                        {{-- Edit User Button --}}
                        <button onclick="openEditUserModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->email }}')" 
                                class="bg-yellow-500 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-600 shadow-sm flex items-center justify-center gap-1" 
                                title="Edit Name/Email/Pass">
                            ✏️ Edit
                        </button>
                            
                        {{-- 1. Sources Button --}}
                        <button onclick='openSourceModal("{{ $user->id }}", "{{ $user->name }}", @json($user->accessibleWebsites->pluck("id")))' 
                                class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-700 flex items-center gap-1 shadow-sm" 
                                title="Manage News Sources">
                            🌐 <span class="hidden md:inline">Sources</span>
                        </button>

                        {{-- 2. Templates Button --}}
                        <button onclick='openTemplateModal("{{ $user->id }}", "{{ $user->name }}", @json($user->settings->allowed_templates ?? []), "{{ $user->settings->default_template ?? "dhaka_post_card" }}")' 
                                class="bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800 flex items-center gap-1 shadow-sm" 
                                title="Manage Templates">
                            🎨 <span class="hidden md:inline">Templates</span>
                        </button>

                        {{-- 3. Scraper Settings Button --}}
                        <button onclick="openScraperModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->settings->scraper_method ?? '' }}')" 
                                class="bg-purple-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-purple-700 shadow-sm flex items-center justify-center gap-1" 
                                title="Scraper Settings">
                            🤖
                        </button>

                        {{-- 4. Permissions Button --}}
                        <button onclick='openPermissionModal("{{ $user->id }}", "{{ $user->name }}", @json($user->permissions ?? []))' 
                                class="bg-pink-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold hover:bg-pink-700 shadow-sm flex items-center justify-center gap-1" 
                                title="User Permissions">
                            🔐
                        </button>

                        {{-- 5. Add Credit Form --}}
                        <form action="{{ route('admin.users.credits', $user->id) }}" method="POST" class="flex items-center">
                            @csrf
                            <input type="number" name="amount" placeholder="+Cr" class="w-12 text-xs border border-slate-300 rounded-l-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                            <button type="submit" class="bg-indigo-600 text-white text-xs px-2 py-1.5 rounded-r-lg hover:bg-indigo-700 font-bold shadow-sm">Add</button>
                        </form>
                        
                        <a href="{{ route('admin.users.login-as', $user->id) }}" 
                           class="bg-yellow-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-yellow-600 ml-2"
                           onclick="return confirm('আপনি কি এই ইউজার হিসেবে লগইন করতে চান?')">
                           🔑 Login
                        </a>

                        {{-- 6. Block/Unblock --}}
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

    {{-- MODALS SECTION --}}

    {{-- 1. Template Modal --}}
    <div id="templateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform scale-100 transition-transform">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Manage Templates for <span id="modalUserName" class="text-indigo-600"></span></h3>
                <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form id="templateForm" method="POST" class="p-6">
                @csrf
                
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Default Template</label>
                    <select name="default_template" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white">
                        @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Allowed Templates</label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50 custom-scrollbar">
                        @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                            <label class="flex items-center space-x-3 p-2 bg-white rounded border border-gray-100 cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 transition">
                                <input type="checkbox" name="templates[]" value="{{ $key }}" class="form-checkbox text-indigo-600 rounded w-4 h-4 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 font-medium">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeTemplateModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition text-sm shadow-md">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Limit Modal --}}
    <div id="limitModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden transform transition-all">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Set Limit for <span id="limitModalUserName" class="text-blue-600"></span></h3>
                <button onclick="closeLimitModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form id="limitForm" method="POST" class="p-6">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Daily Post Limit</label>
                    <input type="number" name="limit" id="limitInput" min="1" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none text-center text-xl font-bold text-gray-700" required>
                    <p class="text-xs text-gray-400 mt-2 text-center">এই ইউজার দিনে সর্বোচ্চ কয়টি পোস্ট করতে পারবে।</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeLimitModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-md text-sm">Update Limit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Source Modal --}}
    <div id="sourceModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Manage Sources for <span id="sourceModalUserName" class="text-emerald-600"></span></h3>
                <button onclick="closeSourceModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form id="sourceForm" method="POST" class="p-6">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Allowed Websites</label>
                    <div class="grid grid-cols-2 gap-3 max-h-60 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50 custom-scrollbar">
                        @foreach($allWebsites as $site)
                            <label class="flex items-center space-x-3 p-2 bg-white rounded border border-gray-100 cursor-pointer hover:bg-emerald-50 hover:border-emerald-200 transition">
                                <input type="checkbox" name="websites[]" value="{{ $site->id }}" class="form-checkbox text-emerald-600 rounded w-4 h-4 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">{{ $site->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center bg-yellow-50 p-2 rounded border border-yellow-100">⚠️ যেসব সোর্স সিলেক্ট করবেন, ইউজার শুধুমাত্র সেগুলো থেকেই নিউজ নিতে পারবে।</p>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeSourceModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition shadow-md text-sm">Save Access</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. Scraper Modal (New) --}}
    <div id="scraperModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden transform transition-all">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Scraper Config: <span id="scraperUserName" class="text-purple-600"></span></h3>
                <button onclick="closeScraperModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form id="scraperForm" method="POST" class="p-6">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Preferred Scraper Method</label>
                    <select name="scraper_method" id="scraperInput" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-purple-500 outline-none text-sm bg-white">
                        <option value="">Global Default</option>
                        <option value="node">Node.js (Puppeteer) - Fast ⚡</option>
                        <option value="python">Python (Playwright) - Stable 🐍</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-2">নির্দিষ্ট সাইটের জন্য স্ক্র্যাপার মেথড পরিবর্তন করতে পারেন।</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeScraperModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 transition shadow-md text-sm">Save Config</button>
                </div>
            </form>
        </div>
    </div>
	
	
	
	{{-- 5. Create User Modal --}}
    <div id="createUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Add New User</h3>
                <button onclick="closeCreateUserModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Credits</label>
                        <input type="number" name="credits" value="10" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Daily Limit</label>
                        <input type="number" name="daily_post_limit" value="10" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 shadow-md w-full">Create User</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 6. Edit User Modal (Name, Email, Password) --}}
    <div id="editUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Edit User Profile</h3>
                <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
            </div>
            
            <form id="editUserForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" id="editName" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-yellow-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" id="editEmail" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-yellow-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">New Password <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <input type="password" name="password" placeholder="Leave empty to keep current" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-yellow-500">
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-yellow-600 shadow-md w-full">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
	
	
	{{-- Permission Modal --}}
		<div id="permissionModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
			<div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
				<h3 class="font-bold text-gray-800">Set Permissions for <span id="permUserName" class="text-pink-600"></span></h3>
				<button onclick="closePermissionModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
			</div>
			
			<form id="permissionForm" method="POST" class="p-6">
				@csrf
				<div class="grid grid-cols-1 gap-3 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
					@php
						$perms = [
							'can_scrape'       => '🌐 News Scraper Access',
							'can_ai'           => '🤖 AI Content Rewriter',
							'can_studio'       => '🎨 Studio Design Access',
							'can_auto_post'    => '🚀 Automation & Auto Post',
							'manage_reporters' => '👥 Reporter Management',
							'reporter_direct'  => '✍️ Reporter Direct Publish'
						];
					@endphp

					@foreach($perms as $key => $label)
					<label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-pointer hover:bg-pink-50 transition">
						<input type="checkbox" name="permissions[]" value="{{ $key }}" class="form-checkbox text-pink-600 rounded">
						<span class="text-sm font-bold text-gray-700">{{ $label }}</span>
					</label>
					@endforeach
				</div>

				<div class="flex justify-end gap-3 pt-4 border-t mt-4">
					<button type="button" onclick="closePermissionModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold text-sm">Cancel</button>
					<button type="submit" class="px-6 py-2 bg-pink-600 text-white rounded-lg font-bold hover:bg-pink-700 shadow-md text-sm">Save Permissions</button>
				</div>
			</form>
		</div>
	</div>

</div>

<script>
    // --- Template Modal Script ---
    function openTemplateModal(userId, userName, allowedTemplates, defaultTemplate) {
        document.getElementById('modalUserName').innerText = userName;
        document.getElementById('templateForm').action = `/admin/users/${userId}/templates`;
        
        // Reset Checkboxes
        document.querySelectorAll('input[name="templates[]"]').forEach(el => el.checked = false);

        // Set Checked
        if (Array.isArray(allowedTemplates)) {
            allowedTemplates.forEach(val => {
                const checkbox = document.querySelector(`input[name="templates[]"][value="${val}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        // Set Default
        const select = document.querySelector('select[name="default_template"]');
        if(select) select.value = defaultTemplate;

        const modal = document.getElementById('templateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeTemplateModal() {
        const modal = document.getElementById('templateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    // --- Limit Modal Script ---
    function openLimitModal(userId, userName, currentLimit) {
        document.getElementById('limitModalUserName').innerText = userName;
        document.getElementById('limitInput').value = currentLimit;
        document.getElementById('limitForm').action = `/admin/users/${userId}/limit`;
        
        const modal = document.getElementById('limitModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeLimitModal() {
        const modal = document.getElementById('limitModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    // --- Source Modal Script ---
    function openSourceModal(userId, userName, assignedWebsites) {
        document.getElementById('sourceModalUserName').innerText = userName;
        document.getElementById('sourceForm').action = `/admin/users/${userId}/websites`;
        
        // Reset
        const checkboxes = document.querySelectorAll('#sourceForm input[name="websites[]"]');
        checkboxes.forEach(el => el.checked = false);

        // Set Checked
        if (Array.isArray(assignedWebsites)) {
            assignedWebsites.forEach(id => {
                const checkbox = document.querySelector(`#sourceForm input[value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        const modal = document.getElementById('sourceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSourceModal() {
        const modal = document.getElementById('sourceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // --- Scraper Modal Script (New) ---
    function openScraperModal(userId, userName, currentMethod) {
        document.getElementById('scraperUserName').innerText = userName;
        document.getElementById('scraperForm').action = `/admin/users/${userId}/scraper`;
        document.getElementById('scraperInput').value = currentMethod || "";
        
        const modal = document.getElementById('scraperModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeScraperModal() {
        const modal = document.getElementById('scraperModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
	
	
	
	// --- Create User Modal ---
    function openCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // --- Edit User Modal ---
    function openEditUserModal(userId, name, email) {
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editUserForm').action = `/admin/users/${userId}/update`;
        
        const modal = document.getElementById('editUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
	
	
	
	function openPermissionModal(userId, userName, userPerms) {
    document.getElementById('permUserName').innerText = userName;
    document.getElementById('permissionForm').action = `/admin/users/${userId}/permissions`;
    
    // Reset all checkboxes
    const checkboxes = document.querySelectorAll('#permissionForm input[name="permissions[]"]');
    checkboxes.forEach(cb => {
        cb.checked = Array.isArray(userPerms) && userPerms.includes(cb.value);
    });

    const modal = document.getElementById('permissionModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
	}

	function closePermissionModal() {
		const modal = document.getElementById('permissionModal');
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	}
	
	
	
</script>
@endsection