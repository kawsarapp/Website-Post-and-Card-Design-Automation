@extends('layouts.app')

@section('content')
    @include('news.studio.styles')

    <div class="fixed inset-0 bg-gray-100 z-50 flex flex-col font-bangla">
        
        <div class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm z-30 shrink-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('news.index') }}" class="flex items-center gap-1 text-gray-500 hover:text-gray-800 transition font-bold text-sm bg-gray-100 px-3 py-1.5 rounded-lg">
                    ← Back
                </a>
                <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    🎨 নিউজ স্টুডিও <span class="text-sm bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-normal">Pro</span>
                </h1>
            </div>
            <div>
                 <button id="downloadBtn" onclick="downloadCard()" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:shadow-lg transition flex items-center gap-2">
                    📥 ডাউনলোড ইমেজ
                </button>
            </div>
        </div>

        <div class="flex flex-1 overflow-hidden">
            @include('news.studio.sidebar')
            @include('news.studio.preview')
        </div>
    </div>

    @include('news.studio.templates')  
    @include('news.studio.scripts')
@endsection