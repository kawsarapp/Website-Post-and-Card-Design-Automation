<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Post; // আপনার Post মডেল ব্যবহার করছি

Route::post('/external-news-post', function (Request $request) {
    
    // ১. সিকিউরিটি টোকেন চেক
    $mySecretToken = 'MY_SUPER_SECRET_PASSWORD_2025'; // আপনার দেওয়া টোকেনটি এখানে থাকবে
    if ($request->input('token') !== $mySecretToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    try {
        // ২. আপনার 'posts' টেবিলে ডাটা ম্যাপ করা
        $post = new Post();

        // --- আপনার স্ক্রিনশট অনুযায়ী কলাম ম্যাপিং ---
        
        $post->title = $request->input('title');
        
        // আপনার টেবিলে কলামের নাম 'content' (Row 6)
        $post->content = $request->input('content'); 
        
        // আপনার টেবিলে কলামের নাম 'image' (Row 8)
        $post->image = $request->input('image_url'); 
        
        // --- ডিফল্ট ভ্যালু সেটআপ ---
        // যেহেতু এগুলো নাল (Null) রাখা যাবে না বা ডিফল্ট ভ্যালু দরকার
        
        $post->user_id = 1;     // ডিফল্ট এডমিন আইডি (Row 2)
        $post->author_id = 1;   // ডিফল্ট অথর আইডি (Row 3)
        
        // ক্যাটাগরি আইডি অবশ্যই দিতে হবে (Row 4)
        // Sender থেকে আইডি না আসলে ডিফল্ট '1' দিচ্ছি
        $post->category_id = $request->input('category_ids')[0] ?? 1; 

        $post->status = 'published'; // ডিফল্ট 'draft' ছিল, তাই 'published' করে দিচ্ছি (Row 12)
        $post->published_at = now(); // এখনকার সময়

        $post->save();

        return response()->json([
            'success' => true, 
            'message' => 'Post saved successfully to posts table!',
            'post_id' => $post->id
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Database Error: ' . $e->getMessage()
        ], 500);
    }
});