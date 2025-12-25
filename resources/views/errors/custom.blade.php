<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>দুঃখিত - কিছু ভুল হয়েছে</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { text-align: center; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="error-card">
        <h1 class="display-1 fw-bold text-danger">⚠️</h1>
        <h2 class="mb-3">দুঃখিত, কোনো একটি সমস্যা হয়েছে!</h2>
        <p class="text-muted">আমাদের কারিগরি দল বিষয়টি দেখছে। দয়া করে কিছুক্ষণ পর আবার চেষ্টা করুন।</p>
        <a href="{{ url('/') }}" class="btn btn-primary px-4">হোম পেজে ফিরে যান</a>
    </div>
</body>
</html>