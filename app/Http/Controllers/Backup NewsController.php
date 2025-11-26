<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NewsController extends Controller
{
    private $scraper;
    private $aiWriter;
    private $wpService;

    private $wpCategories = [
        'Politics' => 14, 'International' => 37, 'Sports' => 15, 
        'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1, 
        'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
    ];

    // সার্ভিস ইনজেকশন
    public function __construct(NewsScraperService $scraper, AIWriterService $aiWriter, WordPressService $wpService)
    {
        $this->scraper = $scraper;
        $this->aiWriter = $aiWriter;
        $this->wpService = $wpService;
    }

    public function index()
    {
        $newsItems = NewsItem::with('website')->orderBy('published_at', 'desc')->paginate(20);
        return view('news.index', compact('newsItems'));
    }

    public function studio($id)
    {
        $newsItem = NewsItem::with('website')->findOrFail($id);
        return view('news.studio', compact('newsItem'));
    }

    public function proxyImage(Request $request)
    {
        $url = $request->query('url');
        if (!$url) abort(404);
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(10)->get($url);
            return response($response->body())->header('Content-Type', $response->header('Content-Type'));
        } catch (\Exception $e) { abort(404); }
    }

    public function postToWordPress($id)
    {
        set_time_limit(300);
        $news = NewsItem::with('website')->findOrFail($id);

        if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

        try {
            // ১. স্ক্র্যাপ সার্ভিস কল
            if (empty($news->content) || strlen($news->content) < 150) {
                $content = $this->scraper->scrape($news->original_link);
                if ($content) {
                    $news->update(['content' => $this->cleanUtf8($content)]);
                } else {
                    return back()->with('error', 'স্ক্র্যাপার কন্টেন্ট পায়নি।');
                }
            }

            // ২. AI সার্ভিস কল
            $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
            $cleanText = $this->cleanUtf8($inputText);
            
            $aiResponse = $this->aiWriter->rewrite($cleanText);
            
            if (!$aiResponse) {
                $rewrittenContent = $news->content; // ফলব্যাক
                $categoryId = $this->wpCategories['Others'];
            } else {
                $rewrittenContent = $aiResponse['content'];
                $detectedCategory = $aiResponse['category'];
                $categoryId = $this->wpCategories[$detectedCategory] ?? $this->wpCategories['Others'];
            }

            // ৩. ইমেজ আপলোড
            $imageId = null;
            if ($news->thumbnail_url) {
                $upload = $this->wpService->uploadImage($news->thumbnail_url, $news->title);
                if ($upload['success']) {
                    $imageId = $upload['id'];
                } else {
                    $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $rewrittenContent;
                }
            }

            // ৪. ফাইনাল পোস্ট
            $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';
            $finalContent = $this->cleanUtf8($rewrittenContent . $credit);
            $finalTitle = $this->cleanUtf8($news->title);

            $wpPost = $this->wpService->publishPost($finalTitle, $finalContent, $categoryId, $imageId);

            if ($wpPost) {
                $news->update(['rewritten_content' => $finalContent, 'is_posted' => true, 'wp_post_id' => $wpPost['id']]);
                return back()->with('success', "পোস্ট পাবলিশ হয়েছে! ID: " . $wpPost['id']);
            } else {
                return back()->with('error', 'ওয়ার্ডপ্রেস পোস্ট ফেইল করেছে।');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    private function cleanUtf8($string) {
        if (is_string($string)) return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return $string;
    }
}