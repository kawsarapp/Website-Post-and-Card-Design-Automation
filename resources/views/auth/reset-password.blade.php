<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Newsmanage24</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <header class="fixed top-0 w-full bg-white shadow-sm py-4 px-6 z-50 text-center">
        <h1 class="text-xl md:text-2xl font-bold text-indigo-600">Newsmanage24</h1>
    </header>

    <main class="flex-grow flex items-center justify-center p-4 mt-20">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl w-full max-w-[420px] border border-gray-200">

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800">নতুন পাসওয়ার্ড সেট করুন</h2>
                <p class="text-sm text-gray-500 mt-1">কমপক্ষে ৮ অক্ষরের পাসওয়ার্ড দিন।</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">নতুন পাসওয়ার্ড</label>
                    <input type="password" name="password" required minlength="8"
                           placeholder="••••••••"
                           class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">পাসওয়ার্ড নিশ্চিত করুন</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           placeholder="••••••••"
                           class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <button type="submit"
                        class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-lg">
                    ✅ পাসওয়ার্ড পরিবর্তন করুন
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
