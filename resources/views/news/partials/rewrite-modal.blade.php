{{-- PUBLISH MODAL --}}
<div id="rewriteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="mb-5 bg-white p-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">নিউজ এডিট ও পাবলিশ</h3>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1">
                <input type="hidden" id="previewNewsId">
                <div class="mb-5 bg-white p-3 rounded-lg border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Feature Image</label>
                    <div class="flex gap-4 items-start">
                        <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded overflow-hidden border relative group">
                            <img id="previewImageDisplay" src="" class="w-full h-full object-cover">
                            <button onclick="resetImage()" class="absolute top-1 right-1 bg-red-600 text-white p-1 rounded-full text-xs opacity-0 group-hover:opacity-100 transition shadow">✕</button>
                        </div>
                        <div class="flex-1">
                            <input type="file" id="newImageFile" onchange="previewSelectedImage(this)" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-2">
                            <div class="text-xs text-gray-400 text-center mb-2">- OR -</div>
                            <input type="url" id="newImageUrl" oninput="previewImageUrl(this.value)" placeholder="Paste image link here..." class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                    <input type="text" id="previewTitle" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 font-bangla text-lg text-gray-900 shadow-sm transition">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">News Content (Rich Text)</label>
                    <textarea id="previewContent" rows="15" class="w-full border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>

            <div class="w-full lg:w-80 flex flex-col gap-5 h-auto lg:h-[92vh] lg:overflow-y-auto lg:sticky lg:top-4 pr-2">
                {{-- 🚀 SEO & Meta Data Card --}}
                <div class="bg-white border border-indigo-200 rounded-xl shadow-sm overflow-hidden flex-shrink-0">
                    <div class="bg-indigo-600 text-white px-4 py-3 flex justify-between items-center">
                        <h5 class="m-0 font-bold text-sm flex items-center gap-2">🚀 SEO Score</h5>
                        <span class="bg-white text-indigo-700 px-2 py-0.5 rounded text-xs font-bold">
                            <span id="seo-score">0</span>/100
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div id="seo-progress" class="bg-red-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Focus Keywords</label>
                            <input type="text" id="focus_keyword" class="seo-input w-full border border-gray-300 rounded p-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="e.g. বাংলাদেশ, রাজনীতি">
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Meta Description <span class="text-gray-400 font-normal">(<span id="meta-count">0</span>/160)</span></label>
                            <textarea id="meta_description" class="seo-input w-full border border-gray-300 rounded p-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" rows="3" maxlength="160" placeholder="নিউজের মূল সারসংক্ষেপ..."></textarea>
                        </div>
                        <hr class="my-4">
                        <h6 class="text-sm font-bold text-gray-800 mb-2">🔗 ইন্টারনাল লিংক সাজেশন</h6>
                        <div class="flex gap-2 mb-2">
                            <input type="text" id="link-search-keyword" class="flex-1 border border-gray-300 rounded p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="কী-ওয়ার্ড লিখুন...">
                            <button type="button" class="bg-gray-800 text-white px-3 py-2 rounded text-xs font-bold hover:bg-gray-700 transition" onclick="fetchRelatedLinks()">খুঁজুন</button>
                        </div>
                        <div id="link-suggestions" class="flex flex-col gap-2 mb-3 hidden max-h-48 overflow-y-auto pr-1"></div>
                        <div class="bg-gray-50 p-3 rounded border border-gray-200 mt-2">
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Manual Link</label>
                            <input type="text" id="manual-link-text" class="w-full border border-gray-300 rounded p-1.5 text-xs mb-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="এখানে টাইটেল লিখুন...">
                            <input type="url" id="manual-link-url" class="w-full border border-gray-300 rounded p-1.5 text-xs mb-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="https://...">
                            <div class="flex gap-2">
                                <button type="button" class="flex-1 bg-indigo-600 text-white py-1.5 rounded text-xs font-bold hover:bg-indigo-700 transition" onclick="addManualLink('normal')">🔗 Link</button>
                                <button type="button" class="flex-1 bg-emerald-600 text-white py-1.5 rounded text-xs font-bold hover:bg-emerald-700 transition" onclick="addManualLink('readmore')">📖 আরও পড়ুন</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Categories & Hashtags --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex-shrink-0">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Hashtags</label>
                        <input type="text" id="previewHashtags" placeholder="#News #Bangladesh" class="w-full border border-gray-300 rounded-lg p-2 text-sm text-blue-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold text-indigo-600 mb-1">Primary Category</label>
                        <select id="previewCategory" class="wp-cat-dropdown w-full border border-gray-300 rounded-lg p-2.5 text-gray-900 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <label class="text-xs font-bold text-gray-500 block mb-1 mt-3">Additional Categories</label>
                    <div class="grid grid-cols-2 gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                        @for ($i = 1; $i <= 4; $i++)
                            <select id="extraCategory{{ $i }}" class="wp-cat-dropdown w-full border border-gray-300 rounded p-1.5 text-[11px] bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Select --</option>
                            </select>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white px-6 py-4 border-t flex justify-end gap-3">
            <button onclick="closeRewriteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Cancel</button>
            <button onclick="saveDraftOnly()" id="btnSave" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow flex items-center gap-2 transition">💾 Save Draft</button>
            <button onclick="publishDraft()" id="btnPublish" class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 shadow-lg flex items-center gap-2 transition">🚀 Publish Now</button>
        </div>
    </div>
</div>