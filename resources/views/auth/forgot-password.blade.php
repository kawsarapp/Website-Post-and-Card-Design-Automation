<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Newsmanage24</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <header class="fixed top-0 w-full bg-white shadow-sm py-4 px-6 z-50 text-center">
        <h1 class="text-xl md:text-2xl font-bold text-indigo-600">Newsmanage24</h1>
    </header>

    <main class="flex-grow flex items-center justify-center p-4 mt-20">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl w-full max-w-[420px] border border-gray-200">

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800">পাসওয়ার্ড ভুলে গেছেন?</h2>
                <p class="text-sm text-gray-500 mt-1">ইমেইল দিন, আমরা Reset Link পাঠাবো।</p>
            </div>

            @if (session('status'))
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm font-bold text-center">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="yourname@email.com"
                           class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg">
                    📧 Reset Link পাঠান
                </button>
            </form>

            <div class="mt-5 text-center">
                <a href="{{ route('login') }}" class="text-sm text-indigo-600 font-bold hover:underline">
                    ← লগইন পেজে ফিরে যান
                </a>
            </div>
        </div>
    </main>
</body>
</html>
