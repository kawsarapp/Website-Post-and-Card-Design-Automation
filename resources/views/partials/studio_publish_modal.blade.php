{{-- Publish Modal --}}
<div id="studioPublishModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[100] backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in-up">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">🚀 Publish Settings</h3>
            <button onclick="closePublishModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                <input type="checkbox" id="modalSocialOnly" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer" onchange="toggleCategoryField(this.checked)">
                <div>
                    <label for="modalSocialOnly" class="font-bold text-gray-700 cursor-pointer select-none">Only Social Media</label>
                    <p class="text-xs text-gray-500">ওয়েবসাইটে পোস্ট হবে না, শুধু ফেসবুক/টেলিগ্রামে যাবে।</p>
                </div>
            </div>

            <div id="categoryFieldWrapper">
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-sm font-bold text-gray-700">Website Category</label>
                    <button type="button" onclick="refreshStudioCategories()" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-200 transition font-bold flex items-center gap-1 border border-indigo-200">
                        🔄 Refresh List
                    </button>
                </div>

                {{-- 🔥 অটো লোড হওয়া ড্রপডাউন --}}
                <select id="modalCategory" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">⏳ Loading Categories...</option>
                </select>
            </div>

            </div>

            @php
                $facebookPages = \App\Models\FacebookPage::where('is_active', true)->get();
            @endphp
            @if($facebookPages->count() > 0)
            <div id="facebookPageSelector">
                <label class="block text-sm font-bold text-gray-700 mb-1 flex items-center gap-2">
                    <i class="fab fa-facebook text-blue-600"></i> Publish to Facebook Page
                </label>
                <select id="modalFacebookPage" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-blue-50">
                    <option value="">-- Do Not Post to Facebook --</option>
                    @foreach($facebookPages as $page)
                        <option value="{{ $page->id }}" {{ $page->is_studio_default ? 'selected' : '' }}>
                            {{ $page->page_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Social Caption</label>
                <textarea id="modalCaption" rows="4" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Write something...">{{ $newsItem->ai_title ?? $newsItem->title }}</textarea>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closePublishModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-100">Cancel</button>
            <button onclick="confirmStudioPost()" id="btnFinalPost" class="px-6 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 shadow-lg flex items-center gap-2">
                ✈️ Confirm & Post
            </button>
        </div>
    </div>
</div>