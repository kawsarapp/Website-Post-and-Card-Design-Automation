import sys
import json
import io
import time
from urllib.parse import urljoin
import trafilatura
from curl_cffi import requests
from bs4 import BeautifulSoup

# ==========================================
# 🔥 UNIVERSAL ENCODING FIX (Windows/Linux)
# ==========================================
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')
else:
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

try:
    target_url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

proxy_url = sys.argv[2] if len(sys.argv) > 2 else None

# ==========================================
# 🚀 ADVANCED REQUEST WITH RETRY (Production Grade)
# ==========================================
def get_html(url, proxy=None, retries=2):
    proxies = {"http": proxy, "https": proxy} if proxy else None
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language': 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="121", "Google Chrome";v="121"',
        'Sec-Ch-Ua-Mobile': '?0',
        'Sec-Ch-Ua-Platform': '"Windows"'
    }

    for attempt in range(retries):
        try:
            response = requests.get(
                url, impersonate="chrome120", timeout=30, proxies=proxies, headers=headers
            )
            if response.status_code == 200:
                if response.encoding is None or response.encoding == 'ISO-8859-1':
                    response.encoding = response.apparent_encoding
                return response.text
            elif response.status_code in [403, 429, 503]:
                time.sleep(2) # Block খেলে ২ সেকেন্ড ওয়েট করে আবার ট্রাই করবে
        except Exception:
            time.sleep(1)
    return None

# ==========================================
# 🧹 AGGRESSIVE JUNK CLEANER (News Boundaries)
# ==========================================
def clean_html(soup):
    # ভয়ংকর সব গারবেজ ট্যাগ রিমুভ
    for tag in soup(['script', 'style', 'iframe', 'nav', 'footer', 'header', 'form', 'svg', 'noscript', 'aside', 'menu']):
        tag.decompose()

    # কমন নিউজ পোর্টালের অ্যাড, রিলেটেড নিউজ ও সোশ্যাল শেয়ারের ক্লাস
    garbage_selectors = [
        '.related-news', '.read-more', '.more-news', '.also-read', '.author-info',
        '.advertisement', '.ads', '.ad-box', '.ad-container', '.social-share', 
        '.share-buttons', '.author-bio', '.tags', '.meta', '.breadcrumb',
        '.print-only', '.video-container', '.embed-code', '.newsletter',
        '[class*="related"]', '[id*="related"]', '[class*="taboola"]', '[id*="taboola"]',
        '.comments', '.comment-list', '.post-meta', '.social-links', '.caption', 
        'figcaption', '.source-link', '.news-update', '.google-news', '.fb-comments'
    ]
    
    for selector in garbage_selectors:
        for tag in soup.select(selector):
            tag.decompose()
            
    return soup

# ==========================================
# 🧠 INTELLIGENT EXTRACTION ENGINE
# ==========================================
def extract_data(html, base_url):
    soup = BeautifulSoup(html, 'html.parser')
    
    # ১. স্মার্ট টাইটেল এক্সট্রাকশন (Prioritize OpenGraph)
    title = ""
    if soup.find('meta', property='og:title'):
        title = soup.find('meta', property='og:title').get('content')
    elif soup.find('meta', attrs={'name': 'twitter:title'}):
        title = soup.find('meta', attrs={'name': 'twitter:title'}).get('content')
    elif soup.find('h1'):
        title = soup.find('h1').get_text(strip=True)
    elif soup.title:
        title = soup.title.string

    # ২. JSON-LD (Schema) ডাটা
    image = None
    schema_body = None
    
    for script in soup.find_all('script', type='application/ld+json'):
        try:
            if not script.string: continue
            data = json.loads(script.string)
            
            if '@graph' in data:
                for item in data['@graph']:
                    if item.get('@type') in ['NewsArticle', 'Article', 'BlogPosting']:
                        data = item
                        break
            
            if 'image' in data:
                img_data = data['image']
                image = img_data.get('url') if isinstance(img_data, dict) else (img_data[0] if isinstance(img_data, list) else img_data)
            
            if 'articleBody' in data:
                schema_body = data['articleBody']
        except:
            pass

    # ৩. স্ট্রিক্ট ইমেজ ফলব্যাক (Anti-Branding)
    if not image:
        if soup.find('meta', property='og:image'):
            image = soup.find('meta', property='og:image').get('content')
        elif soup.find('meta', attrs={'name': 'twitter:image'}):
            image = soup.find('meta', attrs={'name': 'twitter:image'}).get('content')
        else:
            main_area = soup.select_one('article, [itemprop="articleBody"], .post-content, .details-content, #content')
            target = main_area if main_area else soup
            
            # লোগো বা ফালতু ইমেজ ব্লক করার স্ট্রিক্ট লিস্ট
            bad_img_keywords = [
                'logo', 'icon', 'avatar', 'svg', 'profile', 'ad-', 'banner', 'share', 
                'button', 'facebook', 'twitter', 'whatsapp', 'placeholder', 'default', 
                'lazy', 'blank', 'spinner', 'thumbs', '300x250', 'branding', 'bg-'
            ]
            
            for img in target.find_all('img'):
                src = img.get('src') or img.get('data-src') or img.get('data-original')
                if src and not any(x in src.lower() for x in bad_img_keywords):
                    # সাইজ চেক (যদি width/height দেওয়া থাকে, ছোট ইমেজ বাদ)
                    width = img.get('width')
                    if width and width.isdigit() and int(width) < 300:
                        continue
                    image = urljoin(base_url, src)
                    break

    # ৪. বডি এক্সট্রাকশন
    clean_soup = clean_html(soup)
    cleaned_html_str = str(clean_soup)
    
    # Trafilatura (AI based extraction)
    body_text = trafilatura.extract(
        cleaned_html_str, 
        include_images=False, 
        include_comments=False, 
        favor_precision=True,
        target_language='bn' 
    )
    
    # Fallback Mechanism
    if not body_text:
        if schema_body:
            body_text = schema_body
        else:
            # শুধু নির্দিষ্ট কন্টেইনার থেকে প্যারাগ্রাফ নেবে
            main_article = clean_soup.select_one('article, [itemprop="articleBody"], .article-details, .details-text, .post-content')
            target_soup = main_article if main_article else clean_soup
            paragraphs = target_soup.find_all('p')
            body_text = "\n\n".join([p.get_text(strip=True) for p in paragraphs if len(p.get_text(strip=True)) > 30])

    # ৫. HTML ফরম্যাটিং ও গারবেজ ফিল্টার
    formatted_body = ""
    # বাংলার সব ফালতু টেক্সট কাটার লিস্ট
    bad_texts = [
        'শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us', 'Read more', 
        'বিজ্ঞাপন', 'আরো পড়ুন', 'আরও পড়ুন', 'গুগল নিউজ', 'টেলিগ্রাম চ্যানেল', 
        'হোয়াটসঅ্যাপ', 'ফেসবুক পেজ', 'ইউটিউব চ্যানেল', 'টুইটার', 'ভিডিওটি দেখতে', 
        'ছবি: সংগৃহীত', 'সূত্র:', 'আমাদের ফলো করুন', 'এমটিআই', 'ঢাকা পোস্ট', 'আমার দেশের খবর',
        'আরও খবর পেতে', 'ক্লিক করুন', 'বিস্তারিত জানতে'
    ]

    if body_text:
        for para in body_text.split('\n'):
            clean_para = para.strip()
            # লাইন ছোট হলে এবং তাতে ফালতু কি-ওয়ার্ড থাকলে বাদ
            if len(clean_para) > 10: 
                is_garbage = any(bt in clean_para for bt in bad_texts)
                # যদি লাইনের দৈর্ঘ্য ১৫০ ক্যারেক্টারের কম হয় এবং গারবেজ থাকে, তবেই কাটবে (যাতে বড় প্যারাগ্রাফ ভুলে না কাটে)
                if not (is_garbage and len(clean_para) < 150):
                    formatted_body += f"<p>{clean_para}</p>"

    return {
        "title": title.strip() if title else "Untitled News",
        "body": formatted_body,
        "image": image,
        "source_url": base_url
    }

# ==========================================
# 🏁 MAIN EXECUTION
# ==========================================
try:
    html_content = get_html(target_url, proxy_url)
    
    if html_content:
        data = extract_data(html_content, target_url)
        
        # ভ্যালিডেশন: টাইটেল থাকতে হবে এবং বডি অন্তত ১০০ ক্যারেক্টারের হতে হবে
        if data['title'] and (data['body'] and len(data['body']) > 100):
            print(json.dumps(data, ensure_ascii=False))
        else:
            print(json.dumps({"error": "Content extraction failed or empty. Body length: " + str(len(data.get('body', '')))}))
    else:
        print(json.dumps({"error": "Failed to retrieve HTML or Blocked by Site"}))

except Exception as e:
    print(json.dumps({"error": str(e)}))