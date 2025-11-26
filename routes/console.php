<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// আপনার অটোমেশন কমান্ড (প্রতি মিনিটে চেক করবে)
Schedule::command('news:autopost')->everyMinute();