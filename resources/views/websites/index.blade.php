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
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            ➕ নতুন সোর্স যুক্ত করুন <span class="text-xs text-red-500">(Admin Only)</span>
        </h2>
        
        <form action="{{ route('websites.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @csrf
            
            <div class="col-span-1 md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Website Name</label>
                    <input type="text" name="name" placeholder="e.g. Prothom Alo" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Website URL</label>
                    <input type="url" name="url" placeholder="https://example.com" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Container Selector</label>
                <input type="text" name="selector_container" placeholder=".news-item" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Title Selector</label>
                <input type="text" name="selector_title" placeholder="h2.title" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase">Image Selector</label>
                <input type="text" name="selector_image" placeholder=".img-thumb" class="w-full border-gray-300 rounded-lg text-sm font-mono bg-slate-50">
            </div>

            <div class="col-span-1 md:col-span-2 lg:col-span-3 flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md">
                    Save Website
                </button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Name</th>
                    <th class="px-6 py-4 font-bold">Link</th>
                    
                    @if(auth()->user()->role === 'super_admin')
                        <th class="px-6 py-4 font-bold">Selectors (Tech)</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    @endif
                    
                    @if(auth()->user()->role !== 'super_admin')
                        <th class="px-6 py-4 font-bold text-right">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($websites as $site)
                <tr class="hover:bg-slate-50 transition group">
                    <td class="px-6 py-4 font-bold text-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                {{ substr($site->name, 0, 1) }}
                            </span>
                            {{ $site->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-blue-600 hover:underline">
                        <a href="{{ $site->url }}" target="_blank">{{ parse_url($site->url, PHP_URL_HOST) }} ↗</a>
                    </td>

                    @if(auth()->user()->role === 'super_admin')
                        <td class="px-6 py-4 text-xs font-mono text-slate-500">
                            <div class="bg-slate-100 px-2 py-1 rounded inline-block mb-1">C: {{ $site->selector_container }}</div><br>
                            <div class="bg-slate-100 px-2 py-1 rounded inline-block">T: {{ $site->selector_title }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('websites.scrape', $site->id) }}" class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-200">
                                    ⚡ Scrape
                                </a>
                                </div>
                        </td>
                    @else
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('websites.scrape', $site->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 shadow-sm">
                                📥 Fetch News
                            </a>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if($websites->isEmpty())
            <div class="p-10 text-center text-gray-400">
                <p class="text-lg">কোনো সোর্স পাওয়া যায়নি। এডমিনের এপ্রুভালের অপেক্ষায় থাকুন।</p>
            </div>
        @endif
    </div>
</div>
@endsection