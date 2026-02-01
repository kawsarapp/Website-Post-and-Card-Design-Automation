<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex flex-col items-center justify-center text-center">
    <h1 class="text-9xl font-bold text-indigo-100">404</h1>
    <p class="text-2xl font-bold text-gray-800 mt-4">পেজটি খুঁজে পাওয়া যাচ্ছে না!</p>
    <p class="text-gray-500 mt-2">আপনি যে লিংকটি খুঁজছেন তা হয়তো ডিলিট হয়েছে বা ভুল টাইপ করেছেন।</p>
    <a href="{{ route('news.index') }}" class="mt-6 px-6 py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition">
        ড্যাশবোর্ডে ফিরে যান
    </a>
</body>
</html>