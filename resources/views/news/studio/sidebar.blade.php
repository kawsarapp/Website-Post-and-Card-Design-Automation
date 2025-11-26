<div class="w-[350px] bg-white border-r border-gray-200 flex flex-col overflow-y-auto custom-scrollbar shadow-xl z-20 shrink-0">
    <div class="p-5 space-y-6">
        
        <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">ডিজাইন টেমপ্লেট</label>
            <div class="relative">
                <select id="templateSelector" onchange="changeTemplate(this.value)" class="w-full pl-3 pr-10 py-3 bg-slate-50 border border-gray-200 rounded-lg text-sm font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                    
                    @php
                        // ইউজারের সেটিংস অথবা ডিফল্ট টেমপ্লেট লোড করা হচ্ছে
                        $allowed = $settings->allowed_templates ?? array_keys(\App\Models\UserSetting::AVAILABLE_TEMPLATES);
                        $allTemplates = \App\Models\UserSetting::AVAILABLE_TEMPLATES;
                    @endphp

                    @foreach($allTemplates as $key => $name)
                        @if(in_array($key, $allowed))
                            <option value="{{ $key }}" {{ ($settings->default_template ?? 'dhaka_post_card') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endif
                    @endforeach

                </select>
            </div>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
            <label class="text-xs font-bold text-slate-400 uppercase">হেডলাইন</label>
            <textarea id="inputTitle" class="w-full bg-white border border-slate-200 p-3 rounded-lg text-base h-28 focus:ring-2 focus:ring-indigo-500 outline-none font-bangla font-bold text-slate-800 resize-none" oninput="updateCard()">{{ $newsItem->title }}</textarea>
            
            <div class="flex items-center gap-2 pt-2 border-t border-dashed border-gray-200">
                <span class="text-xs text-slate-400">Size</span>
                <input type="range" min="20" max="120" value="58" class="flex-1 accent-indigo-600" oninput="updateFontSize(this.value)">
            </div>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
            <label class="text-xs font-bold text-slate-400 uppercase">ব্র্যান্ডিং</label>
            <div class="flex gap-2">
                    <input type="text" id="badgeTextInput" value="{{ $newsItem->website->name }}" placeholder="Topic" class="w-1/2 bg-white border p-2 rounded-lg text-sm font-bold text-red-600" oninput="updateBadgeText()">
                    <input type="text" id="brandInput" value="{{ $settings->brand_name ?? 'News Desk' }}" class="w-1/2 bg-white border p-2 rounded-lg text-sm font-bold text-slate-700" oninput="updateBrand()">
            </div>
            <div class="flex gap-2">
                <label class="flex-1 cursor-pointer bg-white border border-indigo-100 text-indigo-600 px-3 py-2 rounded-lg text-xs font-bold hover:bg-indigo-50 text-center flex items-center justify-center gap-1 transition">
                    <input type="file" id="logoInput" accept="image/*" onchange="uploadLogo()" class="hidden">
                    📤 Logo
                </label>
                <button onclick="resetLogo()" class="bg-white text-red-500 border border-red-100 px-3 rounded-lg hover:bg-red-50">✕</button>
            </div>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
            <label class="text-xs font-bold text-slate-400 uppercase">কালার থিম</label>
            <div class="flex justify-between gap-2">
                    <button onclick="setThemeColor('red')" class="w-8 h-8 rounded-full bg-red-600 ring-2 ring-offset-2 ring-transparent hover:ring-red-300"></button>
                    <button onclick="setThemeColor('blue')" class="w-8 h-8 rounded-full bg-blue-600 ring-2 ring-offset-2 ring-transparent hover:ring-blue-300"></button>
                    <button onclick="setThemeColor('emerald')" class="w-8 h-8 rounded-full bg-emerald-600 ring-2 ring-offset-2 ring-transparent hover:ring-emerald-300"></button>
                    <button onclick="setThemeColor('purple')" class="w-8 h-8 rounded-full bg-purple-600 ring-2 ring-offset-2 ring-transparent hover:ring-purple-300"></button>
                    <button onclick="setThemeColor('black')" class="w-8 h-8 rounded-full bg-black ring-2 ring-offset-2 ring-transparent hover:ring-gray-300"></button>
            </div>
        </div>
        
    </div>
</div>