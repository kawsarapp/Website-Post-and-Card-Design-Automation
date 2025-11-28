@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
            🌐 নিউজ সোর্স <span class="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full">{{ $websites->count() }}</span>
        </h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(auth()->user()->role === 'super_admin')
    <div class="bg-white p-6 rounded-xl shadow-lg border border-indigo-100 mb-10">
        <h2 class="text-xl font-bold text-gray-800 mb-4">➕ নতুন সোর্স যুক্ত করুন</h2>
        <form action="{{ route('websites.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Website Name</label>
                <input type="text" name="name" class="w-full border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">URL</label>
                <input type="url" name="url" class="w-full border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Scraper Method</label>
                <select name="scraper_method" class="w-full border-gray-300 rounded-lg">
                    <option value="">System Default</option>
                    <option value="node">Node.js (Puppeteer)</option>
                    <option value="python">Python (Playwright)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Container</label>
                <input type="text" name="selector_container" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Title</label>
                <input type="text" name="selector_title" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Image (Opt)</label>
                <input type="text" name="selector_image" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50">
            </div>
            <div class="col-span-1 md:col-span-2 lg:col-span-3 flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Name</th>
                    <th class="px-6 py-4 font-bold">Engine</th>
                    @if(auth()->user()->role === 'super_admin')
                        <th class="px-6 py-4 font-bold">Selectors</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    @else
                        <th class="px-6 py-4 font-bold text-right">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($websites as $site)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-bold text-gray-800">
                        <a href="{{ $site->url }}" target="_blank" class="text-blue-600 hover:underline">{{ $site->name }} ↗</a>
                    </td>
                    <td class="px-6 py-4">
                        @if($site->scraper_method == 'python')
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Python</span>
                        @elseif($site->scraper_method == 'node')
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Node</span>
                        @else
                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">Default</span>
                        @endif
                    </td>

                    @if(auth()->user()->role === 'super_admin')
                        <td class="px-6 py-4 text-xs font-mono text-slate-500">
                            C: {{ $site->selector_container }} <br> T: {{ $site->selector_title }}
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <button onclick='openEditModal(@json($site))' 
                                    class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-200">
                                ✏️ Edit
                            </button>
                            
                            <a href="{{ route('websites.scrape', $site->id) }}" class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-200">
                                ⚡ Scrape
                            </a>
                        </td>
                    @else
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('websites.scrape', $site->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700">
                                📥 Fetch News
                            </a>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-lg">Edit Website</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form id="editForm" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold mb-1">Name</label>
                    <input type="text" name="name" id="editName" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Scraper</label>
                    <select name="scraper_method" id="editScraper" class="w-full border rounded p-2">
                        <option value="">Default</option>
                        <option value="node">Node.js</option>
                        <option value="python">Python</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">URL</label>
                <input type="url" name="url" id="editUrl" class="w-full border rounded p-2">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Container</label>
                    <input type="text" name="selector_container" id="editContainer" class="w-full border rounded p-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Title</label>
                    <input type="text" name="selector_title" id="editTitle" class="w-full border rounded p-2 text-sm font-mono">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold uppercase mb-1">Image Selector</label>
                <input type="text" name="selector_image" id="editImage" class="w-full border rounded p-2 text-sm font-mono">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 rounded font-bold">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded font-bold">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(site) {
        document.getElementById('editForm').action = `/websites/${site.id}`;
        document.getElementById('editName').value = site.name;
        document.getElementById('editUrl').value = site.url;
        document.getElementById('editScraper').value = site.scraper_method || "";
        document.getElementById('editContainer').value = site.selector_container;
        document.getElementById('editTitle').value = site.selector_title;
        document.getElementById('editImage').value = site.selector_image || "";
        
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }
</script>
@endsection