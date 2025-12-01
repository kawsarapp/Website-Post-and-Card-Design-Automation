<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PostPublishedNotification extends Notification
{
    use Queueable;
    protected $newsTitle;

    public function __construct($newsTitle)
    {
        $this->newsTitle = $newsTitle;
    }

    public function via($notifiable)
    {
        return ['database']; // আমরা ডাটাবেস নোটিফিকেশন ব্যবহার করব
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'News Posted Successfully: ' . $this->newsTitle,
            'link' => route('news.index'), // বা নিউজ লিংক
            'time' => now()
        ];
    }
}