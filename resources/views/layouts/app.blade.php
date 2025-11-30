<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Scraper & Card Maker</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-100 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('news.index') }}" class="font-bold text-xl text-indigo-600">
                            NewsCard Pro
                        </a>
                    </div>

                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <a href="{{ route('news.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-medium text-gray-900">
                            News Feed
                        </a>
                        
                        <a href="{{ route('websites.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Sources
                        </a>

                        <a href="{{ route('settings.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            ⚙️ Settings
                        </a>

                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold text-red-600 hover:text-red-800">
                                ⚡ Admin Panel
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-4">{{ auth()->user()->name ?? 'Guest' }}</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile / Secondary Nav (Optional) -->
    @auth
        <div class="bg-blue-600 text-white p-2 sm:hidden flex justify-around">
            <a href="{{ route('news.index') }}" class="hover:underline">News</a>
            <a href="{{ route('settings.index') }}" class="hover:underline">Settings</a>
            @if(auth()->user()->role === 'super_admin')
                <a href="{{ route('admin.dashboard') }}" class="text-red-500 font-bold hover:underline">⚡ Admin</a>
            @endif
        </div>
    @endauth

    <!-- Main Container -->
    <div class="container mx-auto mt-6 p-4">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
    </div>

</body>
</html>
