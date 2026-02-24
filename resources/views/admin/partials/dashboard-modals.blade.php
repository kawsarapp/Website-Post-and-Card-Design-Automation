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
                <div class="grid grid-cols-2 gap-3 max-h-60 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50 custom-scrollbar">
                    @foreach($allWebsites as $site)
                        <label class="flex items-center space-x-3 p-2 bg-white rounded border border-gray-100 cursor-pointer hover:bg-emerald-50 hover:border-emerald-200 transition">
                            <input type="checkbox" name="websites[]" value="{{ $site->id }}" class="form-checkbox text-emerald-600 rounded w-4 h-4 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-gray-700">{{ $site->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeSourceModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg font-bold hover:bg-gray-200 transition text-sm">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition shadow-md text-sm">Save Access</button>
            </div>
        </form>
    </div>
</div>

{{-- 4. Scraper Modal --}}
<div id="scraperModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden transform transition-all">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-700">Scraper Config: <span id="scraperUserName" class="text-purple-600"></span></h3>
            <button onclick="closeScraperModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
        </div>
        <form id="scraperForm" method="POST" class="p-6">
            @csrf
            <div class="mb-6">
                <select name="scraper_method" id="scraperInput" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-purple-500 outline-none text-sm bg-white">
                    <option value="">Global Default</option>
                    <option value="node">Node.js (Puppeteer) - Fast ⚡</option>
                    <option value="python">Python (Playwright) - Stable 🐍</option>
                </select>
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
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500" required minlength="8">
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
            {{-- 🔥 Staff Limit Input (Create) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Max Staff Limit</label>
                <input type="number" name="staff_limit" value="0" min="0" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500">
                <p class="text-[10px] text-gray-400 mt-1">এই ক্লায়েন্ট সর্বোচ্চ কয়জন ইউজার/স্টাফ বানাতে পারবে (0 দিলে পারবে না)।</p>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 shadow-md w-full">Create User</button>
            </div>
        </form>
    </div>
</div>

{{-- 6. Edit User Modal --}}
<div id="editUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-800">Edit User Profile</h3>
            <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
        </div>
        <form id="editUserForm" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
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
            {{-- 🔥 Staff Limit Input (Edit) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Max Staff Limit</label>
                <input type="number" name="staff_limit" id="editStaffLimit" min="0" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-yellow-500">
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-yellow-600 shadow-md w-full">Update Profile</button>
            </div>
        </form>
    </div>
</div>

{{-- 7. Permission Modal --}}
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

                    'can_scrape'         => '🌐 News Scraper Access',
                    'can_direct_publish' => '📝 Direct Create (News Feed)',
                    'can_ai'             => '🤖 AI Content Rewriter',
                    'can_studio'         => '🎨 Studio Design Access',
                    'can_auto_post'      => '🚀 Automation & Auto Post',
                    'can_manage_staff'   => '👥 Client can create Sub-Users/Staff',
                    'manage_reporters'   => '👥 Reporter Management',
                    'reporter_direct'    => '✍️ Reporter Direct Publish',
                    'can_settings'       => '⚙️ Settings Page Access',
                    
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