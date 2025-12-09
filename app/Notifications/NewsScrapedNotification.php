<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewsScrapedNotification extends Notification
{
    use Queueable;

    protected $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "✅ স্ক্র্যাপিং সম্পন্ন! {$this->count} টি নতুন নিউজ পাওয়া গেছে।",
            'url' => route('news.drafts'),
            'type' => 'success'
        ];
    }
}