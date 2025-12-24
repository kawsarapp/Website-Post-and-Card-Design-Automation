<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UpdateVersion extends Command
{
    protected $signature = 'app:update-version';
    protected $description = 'GitHub থেকে লেটেস্ট ভার্সন ট্যাগ আপডেট করবে';

		public function handle()
		{
			$token = env('GITHUB_TOKEN');
			$repo = 'kawsarapp/Website-Post-and-Card-Design-Automation';
			$url = "https://api.github.com/repos/{$repo}/releases/latest";

			// টোকেন সহ রিকোয়েস্ট পাঠানো হচ্ছে
			$response = \Illuminate\Support\Facades\Http::withToken($token)
						->get($url);

			if ($response->successful()) {
				$version = $response->json()['tag_name'];
				\Illuminate\Support\Facades\Cache::put('github_version', $version, 86400); // ২৪ ঘণ্টার জন্য ক্যাশ
				$this->info("✅ সিস্টেম ভার্সন আপডেট হয়েছে: " . $version);
			} else {
				$this->error("❌ গিটহাব এরর: " . $response->json()['message'] ?? 'Unknown error');
			}
		}
	
}