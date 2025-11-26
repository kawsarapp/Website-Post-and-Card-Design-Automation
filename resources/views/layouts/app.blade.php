<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Scraper & Card Maker</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <nav class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="{{ route('websites.index') }}" class="text-xl font-bold">📰 NewsGen AI</a>
            <div class="space-x-4">
                <a href="{{ route('websites.index') }}" class="hover:underline">Websites</a>
                <a href="{{ route('news.index') }}" class="hover:underline">All News</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-6 p-4">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>

</body>
</html>