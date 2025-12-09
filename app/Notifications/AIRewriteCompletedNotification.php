<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AIRewriteCompletedNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $newsId;

    public function __construct($title, $newsId)
    {
        $this->title = $title;
        $this->newsId = $newsId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "🤖 AI রিরাইট সম্পন্ন: " . mb_substr($this->title, 0, 30) . "...",
            'url' => route('news.studio', $this->newsId),
            'type' => 'info'
        ];
    }
}