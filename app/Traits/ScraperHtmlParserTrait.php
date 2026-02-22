<?php

namespace App\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait ScraperHtmlParserTrait
{
    protected function processHtml($html, $url, $customSelectors)
    {
        if (!mb_detect_encoding($html, 'UTF-8', true)) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        $crawler = new Crawler($html);
        $this->cleanGarbage($crawler);

        $data = [
            'title'      => $this->extractTitle($crawler, $customSelectors),
            'image'      => $this->extractImage($crawler, $url, $customSelectors),
            'body'       => null,
            'source_url' => $url
        ];

        // 1. Manual/Dashboard Custom Selector Extraction (PRIORITY #1)
        if (!empty($customSelectors['content']) && $crawler->filter($customSelectors['content'])->count() > 0) {
            $data['body'] = $this->extractBodyManually($crawler, [$customSelectors['content']]);
        }

        // 2. JSON-LD Extraction (If Custom Selector failed)
        if (empty($data['body'])) {
            $jsonLdData = $this->extractFromJsonLD($crawler);
            if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
                $data['body'] = $this->formatText($jsonLdData['articleBody']);
                if (empty($data['image']) && !empty($jsonLdData['image'])) {
                    $img = $jsonLdData['image'];
                    $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
                }
            }
        }

        // 3. Fallback Smart Extraction
        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, []);
        }

        return !empty($data['body']) ? $data : null;
    }

    private function extractBodyManually(Crawler $crawler, $specificSelectors = [])
    {
        $selectors = !empty($specificSelectors) ? $specificSelectors : [
            '.story-element-text', '.article-details-body', '.jw_article_body',
            '.content-details', '.news-article-text', '#news-content',
            '.details-text', '.article-content', 'div[itemprop="articleBody"]', 
            '.article-details', '#details', '.details', 'article', '.post-content', 
            '.entry-content', '.section-content', '.post-body', '.td-post-content'
        ];
        
        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $container = $crawler->filter($selector)->first(); // শুধু প্রথম ম্যাচটাই নেব
                $this->removeJunkElements($container);

                $text = "";
                $container->filter('p, h3, h4, h5, h6, ul, blockquote, div.content-text, div.text-gray-800')->each(function (Crawler $node) use (&$text) {
                    $tag = $node->nodeName();
                    $rawHtml = trim($node->html());
                    $cleanText = strip_tags($rawHtml, '<b><strong><a><i><em>'); 

                    if (strlen(strip_tags($cleanText)) < 5 || $this->isGarbageText(strip_tags($cleanText))) return;

                    if (in_array($tag, ['h3', 'h4', 'h5', 'h6'])) {
                        $text .= "<h3>" . $cleanText . "</h3>\n";
                    } elseif ($tag === 'ul') {
                        $text .= "<ul>" . $cleanText . "</ul>\n";
                    } elseif ($tag === 'blockquote') {
                        $text .= "<blockquote>" . $cleanText . "</blockquote>\n";
                    } else {
                        $text .= "<p>" . $cleanText . "</p>\n";
                    }
                });

                if (strlen($text) > $maxLength) {
                    $maxLength = strlen($text);
                    $bestContent = $text;
                }
            }
        }
        return !empty($bestContent) ? $bestContent : null;
    }

    private function extractTitle(Crawler $crawler, $customSelectors)
    {
        if (!empty($customSelectors['title']) && $crawler->filter($customSelectors['title'])->count() > 0) {
            return trim($crawler->filter($customSelectors['title'])->first()->text());
        }

        if ($crawler->filter('meta[property="og:title"]')->count() > 0) return trim($crawler->filter('meta[property="og:title"]')->attr('content'));
        if ($crawler->filter('meta[name="twitter:title"]')->count() > 0) return trim($crawler->filter('meta[name="twitter:title"]')->attr('content'));
        if ($crawler->filter('h1')->count() > 0) return trim($crawler->filter('h1')->first()->text());
        return "Untitled News";
    }

    private function extractImage(Crawler $crawler, $url, $customSelectors)
    {
        $imageUrl = null;

        if (!empty($customSelectors['image']) && $crawler->filter($customSelectors['image'])->count() > 0) {
            $imgNode = $crawler->filter($customSelectors['image'])->first();
            $imageUrl = $imgNode->attr('data-original') ?? $imgNode->attr('data-src') ?? $imgNode->attr('src') ?? $imgNode->attr('content');
        }
        
        if (!$imageUrl) {
            $imageSelectors = ['meta[property="og:image"]', 'meta[name="twitter:image"]', 'meta[itemprop="image"]', 'link[rel="image_src"]'];
            foreach ($imageSelectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $content = $crawler->filter($selector)->attr('content') ?? $crawler->filter($selector)->attr('href');
                    if ($content && filter_var($content, FILTER_VALIDATE_URL) && !$this->isGarbageImage($content)) {
                        $imageUrl = $content;
                        break;
                    }
                }
            }
        }

        if (!$imageUrl) {
            $crawler->filter('article img, .post-content img, .details img')->each(function (Crawler $node) use (&$imageUrl) {
                if ($imageUrl) return; 
                $src = $node->attr('data-original') ?? $node->attr('data-src') ?? $node->attr('src');
                if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            });
        }

        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $imageUrl = rtrim($root, '/') . '/' . ltrim($imageUrl, '/');
        }
        return $imageUrl;
    }

    private function extractFromJsonLD($crawler) {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');
            foreach ($scripts as $script) {
                $json = json_decode($script->nodeValue, true);
                if (isset($json['articleBody'])) return $json;
                if (isset($json['@graph'])) {
                    foreach ($json['@graph'] as $item) {
                        if (isset($item['articleBody'])) return $item;
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }
}