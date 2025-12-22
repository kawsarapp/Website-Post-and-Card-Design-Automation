@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">👥 আমার প্রতিনিধিগণ (Reporters)</h2>
        {{-- নতুন প্রতিনিধি যোগ করার বাটন --}}
        <button onclick="document.getElementById('addReporterModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md">
            + নতুন প্রতিনিধি যোগ করুন
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">নাম ও ইমেইল</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">তৈরির তারিখ</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500 text-center">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($reporters as $rep)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $rep->name }}</div>
                            <div class="text-xs text-gray-500">{{ $rep->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $rep->created_at->format('d M, Y') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{-- প্রতিনিধি ডিলিট করার ফর্ম --}}
                            <form action="{{ route('manage.reporters.destroy', $rep->id) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider">রিমুভ করুন</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- প্রতিনিধি যোগ করার পপআপ মডাল --}}
<div id="addReporterModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold mb-4">নতুন প্রতিনিধি অ্যাকাউন্ট</h3>
        <form action="{{ route('manage.reporters.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="text" name="name" placeholder="নাম" required class="w-full border rounded-lg p-2.5">
            <input type="email" name="email" placeholder="ইমেইল" required class="w-full border rounded-lg p-2.5">
            <input type="password" name="password" placeholder="পাসওয়ার্ড" required class="w-full border rounded-lg p-2.5">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addReporterModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">বাতিল</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold">তৈরি করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection