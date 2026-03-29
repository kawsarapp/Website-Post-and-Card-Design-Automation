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
            // Bangladesh news portals (specific)
            '.dNewsDesc',          // samakal.com
            '.single_news',        // kalerkantho.com
            '.cat-post-body',      // various BD portals
            '.jw_article_body',    // jugantor, others
            '.details-content',    // dhakapost, others
            '.story-element-text', // prothomalo
            // Generic fallbacks
            '.article-details-body', '.content-details', '.news-article-text',
            '#news-content', '.details-text', '.article-content',
            'div[itemprop="articleBody"]', '.article-details', '#details',
            '.details', 'article', '.post-content', '.entry-content',
            '.section-content', '.post-body', '.td-post-content'
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

                // 🔥 Fallback: if no <p> content (div-based sites like kalerkantho)
                if (empty($text)) {
                    $container->filter('div, span')->each(function (Crawler $node) use (&$text) {
                        // Only grab leaf-level text nodes with enough content
                        $nodeText = strip_tags(trim($node->html()));
                        if (strlen($nodeText) > 30 && !$this->isGarbageText($nodeText)) {
                            // Avoid double-counting parent divs that contain child divs
                            $hasChildDivs = $node->filter('div, p')->count() > 0;
                            if (!$hasChildDivs) {
                                $text .= "<p>" . $nodeText . "</p>\n";
                            }
                        }
                    });
                }

                if (strlen($text) > $maxLength) {
                    $maxLength = strlen($text);
                    $bestContent = $text;
                }
            }
        }
        return !empty($bestContent) ? clean($bestContent) : null;
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

        // 1. Check Custom Selector from Dashboard
        if (!empty($customSelectors['image']) && $crawler->filter($customSelectors['image'])->count() > 0) {
            $imgNode = $crawler->filter($customSelectors['image'])->first();
            $imageUrl = $imgNode->attr('data-gl-src') ?? $imgNode->attr('data-original') ?? $imgNode->attr('data-src') ?? $imgNode->attr('src') ?? $imgNode->attr('content');
        }

        // 2. Try JSON-LD Structured Data
        if (!$imageUrl) {
            $jsonLd = $this->extractFromJsonLD($crawler);
            if (!empty($jsonLd['image'])) {
                $img = $jsonLd['image'];
                $src = null;
                if (is_string($img)) {
                    $src = $img;
                } elseif (is_array($img)) {
                    if (isset($img['url']) && is_string($img['url'])) {
                        $src = $img['url'];
                    } elseif (isset($img[0]) && is_string($img[0])) {
                        $src = $img[0];
                    } elseif (isset($img[0]['url']) && is_string($img[0]['url'])) {
                        $src = $img[0]['url'];
                    }
                }
                
                if ($src && filter_var($src, FILTER_VALIDATE_URL) && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            }
        }

        // 3. Try High-Priority DOM Selectors (Jagonews, BBC, Generic featured images)
        if (!$imageUrl) {
            $highPrioritySelectors = [
                '.featured-image img', '.featured-img img', '.photo-feature img',
                'article figure img', 'article picture img', 'main figure img',
                '.main-image img', '.story-element-image img', '.single-post-image img',
                '.td-post-featured-image img', '.post-thumbnail img', 'figure.image img'
            ];
            foreach ($highPrioritySelectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $imgNode = $crawler->filter($selector)->first();
                    
                    // Prothom Alo uses data-gl-src and srcset
                    $src = $imgNode->attr('data-gl-src') ?? $imgNode->attr('data-original') ?? $imgNode->attr('data-src') ?? $imgNode->attr('src');
                    
                    if (!$src || str_contains($src, 'data:image')) {
                        // Check if it's inside a picture tag
                        $parent = $imgNode->closest('picture');
                        if ($parent && $parent->count() > 0 && $parent->filter('source')->count() > 0) {
                            $srcset = $parent->filter('source')->first()->attr('srcset') ?? $parent->filter('source')->first()->attr('data-srcset');
                            if ($srcset) {
                                $src = explode(' ', trim($srcset))[0];
                            }
                        } else {
                            // Check direct srcset
                            $srcset = $imgNode->attr('srcset') ?? $imgNode->attr('data-srcset');
                            if ($srcset) {
                                $src = explode(' ', trim($srcset))[0];
                            }
                        }
                    }

                    if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                        $imageUrl = $src;
                        break;
                    }
                }
            }
        }

        // 4. Try OG tags as Fallback (Prone to branding images, but good fallback)
        if (!$imageUrl) {
            $imageSelectors = ['meta[property="og:image"]', 'meta[name="twitter:image"]', 'meta[itemprop="image"]', 'link[rel="image_src"]'];
            foreach ($imageSelectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $content = $crawler->filter($selector)->attr('content') ?? $crawler->filter($selector)->attr('href');
                    if ($content && filter_var($content, FILTER_VALIDATE_URL) && !$this->isGarbageImage($content) && !str_contains($content, 'branded_bengali')) {
                        $imageUrl = $content;
                        break;
                    }
                }
            }
        }

        // 5. Broad sweep generic img catch
        if (!$imageUrl) {
            $crawler->filter('article img, .post-content img, .details img, .news-details img, .hdl img, .barta-content img, .pic img, .photo img')->each(function (Crawler $node) use (&$imageUrl) {
                if ($imageUrl) return; 
                $src = $node->attr('data-gl-src') ?? $node->attr('data-original') ?? $node->attr('data-src') ?? $node->attr('src');
                if (!$src || str_contains($src, 'data:image')) {
                    $parent = $node->closest('picture');
                    if ($parent && $parent->count() > 0 && $parent->filter('source')->count() > 0) {
                        $srcset = $parent->filter('source')->first()->attr('srcset') ?? $parent->filter('source')->first()->attr('data-srcset');
                        if ($srcset) $src = explode(' ', trim($srcset))[0];
                    } else {
                        $srcset = $node->attr('srcset') ?? $node->attr('data-srcset');
                        if ($srcset) $src = explode(' ', trim($srcset))[0];
                    }
                }
                if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            });
        }

        // Resolve relative URLs
        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $root = $parsedUrl['scheme'] . '://' . (($parsedUrl['host']) ?? '');
            if ($root && !str_starts_with($imageUrl, '//')) {
                $imageUrl = rtrim($root, '/') . '/' . ltrim($imageUrl, '/');
            } elseif (str_starts_with($imageUrl, '//')) {
                $imageUrl = $parsedUrl['scheme'] . ':' . $imageUrl;
            }
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