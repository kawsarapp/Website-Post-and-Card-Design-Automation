@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 bg-gray-100 min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">⚡ সুপার অ্যাডমিন প্যানেল</h1>
            <p class="text-slate-500 mt-1">সিস্টেম ওভারভিউ এবং ইউজার ম্যানেজমেন্ট</p>
        </div>
        <div class="mt-4 md:mt-0 bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-mono">
            Admin Mode
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="p-4 bg-blue-50 text-blue-600 rounded-xl text-2xl">👥</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">মোট ইউজার</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalUsers }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="p-4 bg-purple-50 text-purple-600 rounded-xl text-2xl">📰</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">জেনারেটেড নিউজ</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalNews }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl text-2xl">🌐</div>
            <div>
                <p class="text-slate-500 text-sm font-bold uppercase">কানেক্টেড সাইট</p>
                <h3 class="text-3xl font-bold text-slate-800">{{ $totalWebsites }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 class="text-lg font-bold text-slate-700">👤 ইউজার লিস্ট</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">নাম ও ইমেইল</th>
                        <th class="px-6 py-4 font-bold text-center">ক্রেডিট</th>
                        <th class="px-6 py-4 font-bold text-center">স্ট্যাটাস</th>
                        <th class="px-6 py-4 font-bold">জয়েনিং ডেট</th>
                        <th class="px-6 py-4 font-bold text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold">
                                {{ $user->credits }} Left
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($user->is_active)
                                <span class="text-green-600 text-xs font-bold bg-green-100 px-2 py-1 rounded">Active</span>
                            @else
                                <span class="text-red-600 text-xs font-bold bg-red-100 px-2 py-1 rounded">Banned</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $user->created_at->format('d M, Y') }}
                        </td>

                        <td class="px-6 py-4 text-right flex justify-end gap-2 items-center">
                            
                            <button onclick='openTemplateModal(
                                    "{{ $user->id }}", 
                                    "{{ $user->name }}", 
                                    @json($user->settings->allowed_templates ?? []), 
                                    "{{ $user->settings->default_template ?? "dhaka_post_card" }}"
                                )' 
                                class="bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800 flex items-center gap-1" 
                                title="Manage Templates">
                                🎨 <span class="hidden md:inline">Templates</span>
                            </button>

                            <form action="{{ route('admin.users.credits', $user->id) }}" method="POST" class="flex items-center">
                                @csrf
                                <input type="number" name="amount" placeholder="+Cr" class="w-14 text-xs border border-slate-300 rounded-l-lg px-2 py-1.5 focus:outline-none" required>
                                <button type="submit" class="bg-indigo-600 text-white text-xs px-2 py-1.5 rounded-r-lg hover:bg-indigo-700 font-bold">Add</button>
                            </form>

                            <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold border {{ $user->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}" onclick="return confirm('Are you sure?')">
                                    {{ $user->is_active ? 'Block' : 'Unblock' }}
                                </button>
                            </form>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="p-4">
            {{ $users->links() }}
        </div>
    </div>

    <div id="templateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
            <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Manage Templates for <span id="modalUserName" class="text-indigo-600"></span></h3>
                <button onclick="closeTemplateModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            </div>
            
            <form id="templateForm" method="POST" class="p-6">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Default Template</label>
                    <select name="default_template" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-indigo-500">
                        @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Allowed Templates (Check to enable)</label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border rounded bg-gray-50">
                        @foreach(\App\Models\UserSetting::AVAILABLE_TEMPLATES as $key => $name)
                            <label class="flex items-center space-x-2 p-2 bg-white rounded border cursor-pointer hover:bg-indigo-50">
                                <input type="checkbox" name="templates[]" value="{{ $key }}" class="form-checkbox text-indigo-600 rounded">
                                <span class="text-sm text-gray-700">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeTemplateModal()" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg font-bold hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function openTemplateModal(userId, userName, allowedTemplates, defaultTemplate) {
        // ১. নাম এবং ফর্ম অ্যাকশন সেট করা
        document.getElementById('modalUserName').innerText = userName;
        document.getElementById('templateForm').action = `/admin/users/${userId}/templates`;
        
        // ২. সব চেকবক্স আগে রিসেট (Uncheck) করা
        document.querySelectorAll('input[name="templates[]"]').forEach(el => el.checked = false);

        // ৩. সেভ করা টেমপ্লেটগুলো চেক (Tick) করা
        if (Array.isArray(allowedTemplates)) {
            allowedTemplates.forEach(val => {
                // ভ্যালু অনুযায়ী চেকবক্স খুঁজে বের করা
                const checkbox = document.querySelector(`input[name="templates[]"][value="${val}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        // ৪. ডিফল্ট টেমপ্লেট সিলেক্ট করা
        const select = document.querySelector('select[name="default_template"]');
        if(select) {
            select.value = defaultTemplate;
        }

        // ৫. মডাল ওপেন করা
        document.getElementById('templateModal').classList.remove('hidden');
        document.getElementById('templateModal').classList.add('flex');
    }

    function closeTemplateModal() {
        document.getElementById('templateModal').classList.add('hidden');
        document.getElementById('templateModal').classList.remove('flex');
    }
</script>
@endsection