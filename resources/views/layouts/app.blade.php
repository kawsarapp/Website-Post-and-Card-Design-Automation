<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name', 'Newsmanage24') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Tailwind & Icons --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Page Specific Styles --}}
    @stack('styles')

    <style>
        :root { --primary: #6366f1; }
        body { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; background-color: #f8fafc; -webkit-tap-highlight-color: transparent; }
        .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
        .glass-sheet { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(25px); border-top: 1px solid rgba(226, 232, 240, 1); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        
        /* Flash Message Animation */
        .flash-message { animation: slideIn 0.5s ease-out forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>

<body class="text-slate-800 antialiased min-h-screen flex flex-col relative custom-scrollbar bg-slate-50">

    {{-- 🔥 Included Components (Partials) --}}
    @include('layouts.partials.alerts')
    @include('layouts.partials.impersonate')
    @include('layouts.partials.mobile-nav')
    @include('layouts.partials.desktop-nav')

    {{-- MAIN CONTENT AREA --}}
    <main class="flex-grow container mx-auto mt-4 px-4 pb-24 lg:pb-12 lg:mt-6 pt-14 lg:pt-0">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="mt-auto py-6 text-center text-slate-400 text-xs hidden lg:block">
        <p>© {{ date('Y') }} Newsmanage24 | <span class="text-indigo-500 font-bold">v{{ Cache::get('github_version', '1.0.0') }}</span></p>
    </footer>

    {{-- Scripts --}}
    @include('layouts.partials.scripts')

</body>
</html>