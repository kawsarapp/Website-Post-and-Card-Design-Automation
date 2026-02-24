@extends('layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    /* Scrollbar for Modals */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    {{-- Header Section --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">👥 Staff Analytics & Management</h1>
            <p class="text-sm text-slate-500 mt-1">আপনার স্টাফ এবং রিপোর্টারদের কাজের হিসাব ও পারফরম্যান্স</p>
        </div>
        
        <div class="flex items-center gap-4">
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold">
                Limit: {{ $staffs->count() }} / {{ $admin->staff_limit }}
            </span>
            @if($staffs->count() < $admin->staff_limit)
                <button onclick="openAddStaffModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md flex items-center gap-2 transition-colors w-full md:w-auto justify-center">
                    <i class="fa-solid fa-user-plus"></i> Add New Staff
                </button>
            @else
                <button disabled class="bg-gray-300 text-gray-500 px-5 py-2.5 rounded-xl font-bold text-sm shadow-md flex items-center gap-2 cursor-not-allowed w-full md:w-auto justify-center" title="Staff limit reached!">
                    <i class="fa-solid fa-user-plus"></i> Limit Reached
                </button>
            @endif
        </div>
    </div>

    {{-- 📊 স্টাফদের পারফরম্যান্স গ্রিড --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($staffs as $staff)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden stat-card flex flex-col">
            
            {{-- Header Info --}}
            <div class="p-5 border-b border-slate-50 flex items-start justify-between bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl uppercase border-2 border-white shadow-sm">
                        {{ substr($staff->name, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg leading-tight">{{ $staff->name }}</h3>
                        <p class="text-xs text-slate-500">{{ $staff->email }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded text-[10px] font-bold uppercase tracking-wider">
                            {{ $staff->role ?? 'Staff' }}
                        </span>
                    </div>
                </div>
                
                {{-- 3-Dot Action Menu --}}
                <div class="relative group">
                    <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-slate-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10 origin-top-right">
                        
                        <button type="button" onclick='openSourceModal("{{ $staff->id }}", "{{ $staff->name }}", @json($staff->accessibleWebsites->pluck("id")))' class="w-full text-left block px-4 py-2 text-sm text-slate-600 hover:bg-emerald-50 hover:text-emerald-600">
                            <i class="fa-solid fa-earth-asia w-5"></i> Allowed Sites
                        </button>
                        
                        <button type="button" onclick='openTemplateModal("{{ $staff->id }}", "{{ $staff->name }}", @json($staff->settings->allowed_templates ?? []), "{{ $staff->settings->default_template ?? "" }}")' class="w-full text-left block px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-800">
                            <i class="fa-solid fa-palette w-5"></i> Templates
                        </button>

                        <button type="button" onclick='openPermissionModal("{{ $staff->id }}", "{{ $staff->name }}", @json($staff->permissions ?? []))' class="w-full text-left block px-4 py-2 text-sm text-slate-600 hover:bg-pink-50 hover:text-pink-600">
                            <i class="fa-solid fa-shield-halved w-5"></i> Permissions
                        </button>
                        
                        <div class="border-t border-slate-50 my-1"></div>
                        <form action="{{ route('client.staff.destroy', $staff->id) }}" method="POST" onsubmit="return confirm('সত্যিই ডিলিট করতে চান?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                <i class="fa-solid fa-trash-can w-5"></i> Delete Staff
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 📈 Analytics Grid --}}
            <div class="p-5 flex-grow">
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-3">Performance Analytics</h4>
                <div class="grid grid-cols-2 gap-3">
                    
                    {{-- Published --}}
                    <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100/50">
                        <div class="flex justify-between items-start">
                            <i class="fa-solid fa-check-circle text-emerald-500 mt-1"></i>
                            <span class="text-2xl font-black text-emerald-600">{{ $staff->total_published ?? 0 }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-emerald-600/70 uppercase mt-1">Published</p>
                    </div>

                    {{-- Drafts --}}
                    <div class="bg-amber-50 rounded-xl p-3 border border-amber-100/50">
                        <div class="flex justify-between items-start">
                            <i class="fa-solid fa-file-pen text-amber-500 mt-1"></i>
                            <span class="text-2xl font-black text-amber-600">{{ $staff->total_drafts ?? 0 }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-amber-600/70 uppercase mt-1">Drafts/Pending</p>
                    </div>

                    {{-- AI Rewrites --}}
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100/50">
                        <div class="flex justify-between items-start">
                            <i class="fa-solid fa-robot text-blue-500 mt-1"></i>
                            <span class="text-2xl font-black text-blue-600">{{ $staff->ai_rewrites ?? 0 }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-blue-600/70 uppercase mt-1">AI Rewrites</p>
                    </div>

                    {{-- Credits Used --}}
                    <div class="bg-rose-50 rounded-xl p-3 border border-rose-100/50">
                        <div class="flex justify-between items-start">
                            <i class="fa-solid fa-coins text-rose-500 mt-1"></i>
                            <span class="text-2xl font-black text-rose-600">{{ $staff->credits_used ?? 0 }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-rose-600/70 uppercase mt-1">Credits Used</p>
                    </div>

                </div>
            </div>
            
            {{-- Footer Status --}}
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-100 text-xs flex justify-between items-center text-slate-500">
                <span>Joined: {{ $staff->created_at->format('M d, Y') }}</span>
                <span class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-emerald-500"></div> Active</span>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl border border-slate-200 border-dashed p-10 text-center">
            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto text-indigo-300 mb-4">
                <i class="fa-solid fa-users text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">কোনো স্টাফ নেই</h3>
            <p class="text-sm text-slate-500">আপনার প্যানেলে এখনো কোনো স্টাফ বা রিপোর্টার যুক্ত করা হয়নি।</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ================= MODALS ================= --}}

{{-- 1. Add Staff Modal --}}
<div id="addStaffModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Add New Staff</h3>
            <button onclick="closeAddStaffModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form action="{{ route('client.staff.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Staff Name</label>
                <input type="text" name="name" class="w-full border rounded-lg p-2.5 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Login ID)</label>
                <input type="email" name="email" class="w-full border rounded-lg p-2.5 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" class="w-full border rounded-lg p-2.5 focus:ring-indigo-500" required minlength="6">
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold w-full">Create Staff</button>
            </div>
        </form>
    </div>
</div>

{{-- 2. Permissions Modal --}}
<div id="permissionModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Permissions: <span id="staffName" class="text-pink-600"></span></h3>
            <button onclick="closePermissionModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form id="permissionForm" method="POST" class="p-6">
            @csrf @method('PUT')
            <p class="text-xs text-gray-500 mb-3 bg-yellow-50 p-2 rounded">⚠️ You can only assign permissions that you currently have.</p>
            <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                @php
                    $adminPerms = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
                    $allFeatures = [
                    'can_scrape'         => '🌐 News Scraper Access',
                    'can_direct_publish' => '📝 Direct Create (News Feed)',
                    'can_ai'             => '🤖 AI Content Rewriter',
                    'can_studio'         => '🎨 Studio Design Access',
                    'can_auto_post'      => '🚀 Automation & Auto Post',
                    'can_manage_staff'   => '👥 Client can create Sub-Users/Staff',
                    'manage_reporters'   => '👥 Reporter Management',
                    'reporter_direct'    => '✍️ Reporter Direct Publish',
                    ];
                @endphp
                
                @foreach($allFeatures as $key => $label)
                    @if(in_array($key, $adminPerms))
                        <label class="flex items-center space-x-3 p-2 bg-gray-50 rounded border cursor-pointer hover:bg-pink-50 transition">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" class="form-checkbox text-pink-600 rounded p-check">
                            <span class="text-sm font-bold text-gray-700">{{ $label }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
            <div class="flex justify-end pt-4 mt-2 border-t">
                <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded-lg font-bold w-full">Save Permissions</button>
            </div>
        </form>
    </div>
</div>

{{-- 3. Sources Modal --}}
<div id="sourceModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Sources: <span id="sourceStaffName" class="text-emerald-600"></span></h3>
            <button onclick="closeSourceModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form id="sourceForm" method="POST" class="p-6">
            @csrf @method('PUT')
            <p class="text-xs text-gray-500 mb-3 bg-yellow-50 p-2 rounded">⚠️ Assign websites your staff can scrape news from.</p>
            <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                @foreach($adminWebsites as $site)
                    <label class="flex items-center space-x-3 p-2 bg-gray-50 rounded border cursor-pointer hover:bg-emerald-50 transition">
                        <input type="checkbox" name="websites[]" value="{{ $site->id }}" class="form-checkbox text-emerald-600 rounded s-check">
                        <span class="text-sm font-bold text-gray-700">{{ $site->name }}</span>
                    </label>
                @endforeach
                @if($adminWebsites->isEmpty())
                    <p class="text-sm text-red-500 font-bold text-center">You don't have any sources assigned.</p>
                @endif
            </div>
            <div class="flex justify-end pt-4 mt-2 border-t">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-bold w-full transition">Save Sources</button>
            </div>
        </form>
    </div>
</div>

{{-- 4. Templates Modal --}}
<div id="templateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Templates: <span id="templateStaffName" class="text-slate-600"></span></h3>
            <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form id="templateForm" method="POST" class="p-6">
            @csrf @method('PUT')
            <p class="text-xs text-gray-500 mb-3 bg-yellow-50 p-2 rounded">⚠️ Only templates you have access to are shown here.</p>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Default Template</label>
                <select name="default_template" id="defaultTemplateSelect" class="w-full border rounded-lg p-2.5 focus:ring-slate-500 text-sm outline-none">
                    @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                        @if(in_array($key, $adminTemplates))
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <label class="block text-sm font-bold text-gray-700 mb-2">Allowed Templates</label>
            <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                    @if(in_array($key, $adminTemplates))
                        <label class="flex items-center space-x-2 p-2 bg-gray-50 rounded border cursor-pointer hover:bg-slate-100 transition">
                            <input type="checkbox" name="templates[]" value="{{ $key }}" class="form-checkbox text-slate-600 rounded t-check">
                            <span class="text-xs font-bold text-gray-700 truncate" title="{{ $name }}">{{ $name }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
            
            <div class="flex justify-end pt-4 mt-2 border-t">
                <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-6 py-2 rounded-lg font-bold w-full transition">Save Templates</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Add Staff ---
    function openAddStaffModal() {
        document.getElementById('addStaffModal').classList.remove('hidden');
        document.getElementById('addStaffModal').classList.add('flex');
    }
    function closeAddStaffModal() {
        document.getElementById('addStaffModal').classList.add('hidden');
        document.getElementById('addStaffModal').classList.remove('flex');
    }

    // --- Permissions ---
    function openPermissionModal(id, name, perms) {
        document.getElementById('staffName').innerText = name;
        document.getElementById('permissionForm').action = `/client/staff/${id}/permissions`;
        document.querySelectorAll('.p-check').forEach(cb => { cb.checked = Array.isArray(perms) && perms.includes(cb.value); });
        document.getElementById('permissionModal').classList.remove('hidden');
        document.getElementById('permissionModal').classList.add('flex');
    }
    function closePermissionModal() {
        document.getElementById('permissionModal').classList.add('hidden');
        document.getElementById('permissionModal').classList.remove('flex');
    }

    // --- Sources ---
    function openSourceModal(id, name, websites) {
        document.getElementById('sourceStaffName').innerText = name;
        document.getElementById('sourceForm').action = `/client/staff/${id}/websites`;
        document.querySelectorAll('.s-check').forEach(cb => { cb.checked = Array.isArray(websites) && websites.includes(parseInt(cb.value)); });
        document.getElementById('sourceModal').classList.remove('hidden');
        document.getElementById('sourceModal').classList.add('flex');
    }
    function closeSourceModal() {
        document.getElementById('sourceModal').classList.add('hidden');
        document.getElementById('sourceModal').classList.remove('flex');
    }

    // --- Templates ---
    function openTemplateModal(id, name, templates, defaultTemplate) {
        document.getElementById('templateStaffName').innerText = name;
        document.getElementById('templateForm').action = `/client/staff/${id}/templates`;
        
        document.querySelectorAll('.t-check').forEach(cb => { cb.checked = Array.isArray(templates) && templates.includes(cb.value); });
        
        const select = document.getElementById('defaultTemplateSelect');
        if(select && defaultTemplate) select.value = defaultTemplate;

        document.getElementById('templateModal').classList.remove('hidden');
        document.getElementById('templateModal').classList.add('flex');
    }
    function closeTemplateModal() {
        document.getElementById('templateModal').classList.add('hidden');
        document.getElementById('templateModal').classList.remove('flex');
    }
</script>
@endsection