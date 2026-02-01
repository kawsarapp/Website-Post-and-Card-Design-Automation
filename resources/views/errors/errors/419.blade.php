@extends('layouts.app_guest') {{-- অথবা আপনার লগইন লেআউট --}}

@section('content')
<div class="h-screen flex flex-col items-center justify-center text-center bg-gray-100">
    <h1 class="text-6xl font-bold text-gray-300">419</h1>
    <h2 class="text-2xl font-bold text-gray-800 mt-4">সেশন মেয়াদোত্তীর্ণ হয়েছে!</h2>
    <p class="text-gray-600 mt-2">নিরাপত্তার স্বার্থে আপনার সেশন শেষ হয়ে গেছে। দয়া করে আবার লগইন করুন।</p>
    <a href="{{ route('login') }}" class="mt-6 px-6 py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition">
        লগইন করুন
    </a>
</div>
@endsection