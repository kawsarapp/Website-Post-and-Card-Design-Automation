<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex flex-col items-center justify-center p-4">
    <div class="text-center">
        <div class="text-9xl font-extrabold text-red-100">500</div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-red-600 text-white px-2 text-sm rounded rotate-12">
            Server Error
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mt-4">ওহো! সার্ভারে কিছু সমস্যা হয়েছে।</h3>
        <p class="text-gray-500 mt-2 mb-6">আমাদের টেকনিক্যাল টিম বিষয়টি দেখছে। দয়া করে কিছুক্ষণ পর চেষ্টা করুন।</p>
        
        <a href="{{ url('/') }}" class="px-6 py-3 bg-gray-800 text-white rounded-lg font-bold hover:bg-black transition">
            🏠 হোমপেজে ফিরে যান
        </a>
        <button onclick="location.reload()" class="ml-2 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-100 transition">
            🔄 রিফ্রেশ করুন
        </button>
    </div>
</body>
</html>