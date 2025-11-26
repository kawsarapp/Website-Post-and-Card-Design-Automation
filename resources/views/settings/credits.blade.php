@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-10">
    
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white shadow-xl mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">💰 ক্রেডিট হিস্ট্রি</h1>
            <p class="opacity-90 mt-2">আপনার খরচের বিবরণ এবং লিমিট</p>
        </div>
        <div class="text-right">
            <div class="text-4xl font-bold">{{ $user->credits }}</div>
            <div class="text-sm opacity-80 uppercase tracking-wider">বর্তমান ব্যালেন্স</div>
            <div class="mt-2 inline-block bg-white/20 px-3 py-1 rounded text-xs">
                ডেইলি লিমিট: {{ $user->daily_post_limit }} টি
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold">
                <tr>
                    <th class="px-6 py-4">বিবরণ</th>
                    <th class="px-6 py-4">টাইপ</th>
                    <th class="px-6 py-4 text-center">খরচ/আয়</th>
                    <th class="px-6 py-4 text-right">সময়</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($histories as $history)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-medium text-gray-800">
                        {{ $history->description }}
                        <div class="text-xs text-gray-400 mt-0.5">Balance after: {{ $history->balance_after }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($history->action_type == 'auto_post')
                            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">🤖 Auto</span>
                        @elseif($history->action_type == 'manual_post')
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">🚀 Manual</span>
                        @else
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">➕ Recharged</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center font-bold {{ $history->credits_change < 0 ? 'text-red-500' : 'text-green-500' }}">
                        {{ $history->credits_change > 0 ? '+' : '' }}{{ $history->credits_change }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-500">
                        {{ $history->created_at->format('d M, h:i A') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">
            {{ $histories->links() }}
        </div>
    </div>
</div>
@endsection