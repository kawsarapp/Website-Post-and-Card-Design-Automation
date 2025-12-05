<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\News; // আপনার টার্গেট সাইটের নিউজ মডেলের নাম

Route::post('/external-news-post', function (Request $request) {
    
    // ১. টোকেন চেক (সিকিউরিটির জন্য)
    // এই টোকেনটি অবশ্যই আপনার SaaS প্যানেলে দেওয়া টোকেনের সাথে মিলতে হবে
    $mySecretToken = 'MY_SECRET_KEY_123'; 

    if ($request->input('token') !== $mySecretToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // ২. নিউজ সেভ করা (আপনার ডাটাবেস টেবিলের কলাম অনুযায়ী নামগুলো ঠিক করবেন)
    // যেমন: আপনার টেবিলে যদি 'body' এর বদলে 'details' থাকে, তবে $request->content এর ভ্যালু সেখানে দেবেন।
    
    // উদাহরণ:
    // $news = new News();
    // $news->title = $request->input('title');
    // $news->description = $request->input('content'); 
    // $news->image = $request->input('image_url');
    // $news->category = $request->input('category_name');
    // $news->save();

    // টেস্ট করার জন্য আপাতত লগ করে দেখতে পারেন
    \Log::info('News Received via API:', $request->all());

    return response()->json(['success' => true, 'message' => 'News received successfully!']);
});