@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <div class="bg-white p-6 rounded-lg shadow-md h-fit">
        <h2 class="text-xl font-bold mb-4 border-b pb-2">Add New Website</h2>
        
        <form action="{{ route('websites.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Website Name</label>
                <input type="text" name="name" placeholder="e.g. Dhaka Post" class="w-full border p-2 rounded mt-1" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Website URL</label>
                <input type="url" name="url" placeholder="https://www.dhakapost.com" class="w-full border p-2 rounded mt-1" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Main Container (CSS Class)</label>
                <input type="text" name="selector_container" placeholder="e.g. .news-item or .card" class="w-full border p-2 rounded mt-1" required>
                <p class="text-xs text-gray-500 mt-1">যে বক্সের ভেতরে পুরো নিউজটা আছে।</p>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Title Selector</label>
                <input type="text" name="selector_title" placeholder="e.g. h2 or .title" class="w-full border p-2 rounded mt-1" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Image Selector (Optional)</label>
                <input type="text" name="selector_image" placeholder="e.g. img" class="w-full border p-2 rounded mt-1">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Save Website
            </button>
        </form>
    </div>

    <div class="col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4 border-b pb-2">Connected Websites</h2>
        
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-3 border">Name</th>
                    <th class="p-3 border">URL</th>
                    <th class="p-3 border">Selectors</th>
                    <th class="p-3 border text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($websites as $website)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-semibold">{{ $website->name }}</td>
                    <td class="p-3 text-blue-500 text-sm truncate max-w-xs">
                        <a href="{{ $website->url }}" target="_blank">{{ $website->url }}</a>
                    </td>
                    <td class="p-3 text-xs text-gray-600">
                        <span class="bg-gray-200 px-1 rounded">Box: {{ $website->selector_container }}</span>
                        <span class="bg-gray-200 px-1 rounded">Title: {{ $website->selector_title }}</span>
                    </td>
                    <td class="p-3 text-center">
                        <a href="{{ route('websites.scrape', $website->id) }}" 
                           class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600 flex items-center justify-center gap-2">
                           🔄 Fetch News
                        </a>
                    </td>
                </tr>
                @endforeach

                @if($websites->isEmpty())
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-500">No websites added yet.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection