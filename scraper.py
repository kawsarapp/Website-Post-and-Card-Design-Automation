import sys
import json
import io
import re
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

# ইনপুট আর্গুমেন্ট হ্যান্ডলিং
try:
    target_url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

# 🔥 প্রক্সি আর্গুমেন্ট চেক (যদি পাঠানো হয়)
proxy_url = sys.argv[2] if len(sys.argv) > 2 else None

# ==========================================
# 🚀 FAST REQUEST (Browser Impersonation + Proxy)
# ==========================================
def get_html(url, proxy=None):
    try:
        proxies = {"http": proxy, "https": proxy} if proxy else None
        
        response = requests.get(
            url,
            impersonate="chrome120", 
            timeout=60,
            proxies=proxies,
            headers={
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language': 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Upgrade-Insecure-Requests': '1',
                'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'Sec-Ch-Ua-Mobile': '?0',
                'Sec-Ch-Ua-Platform': '"Windows"'
            }
        )
        if response.status_code == 200:
            if response.encoding is None or response.encoding == 'ISO-8859-1':
                response.encoding = response.apparent_encoding
            return response.text
    except Exception as e:
        pass
    return None

# ==========================================
# 🧹 SMART CLEANER (Garbage Removal)
# ==========================================
def clean_html(soup):
    for tag in soup(['script', 'style', 'iframe', 'nav', 'footer', 'header', 'form', 'svg', 'noscript']):
        tag.decompose()

    garbage_selectors = [
        '.advertisement', '.ads', '.ad-container', '.social-share', 
        '.share-buttons', '.related-news', '.read-more', '.tags', 
        '.author-bio', '.sidebar', '.comments', '.meta-info', 
        '[class*="taboola"]', '[id*="taboola"]', '[class*="popup"]',
        '.fb-comments', '#disqus_thread', '.print-only'
    ]
    
    for selector in garbage_selectors:
        for tag in soup.select(selector):
            tag.decompose()
            
    return soup

# ==========================================
# 🧠 INTELLIGENT EXTRACTION (Trafilatura + Schema)
# ==========================================
def extract_data(html, base_url):
    soup = BeautifulSoup(html, 'html.parser')
    
    # ১. টাইটেল এক্সট্রাকশন
    title = ""
    if soup.find('h1'):
        title = soup.find('h1').get_text(strip=True)
    elif soup.title:
        title = soup.title.string

    # ২. JSON-LD (Schema.org) থেকে ডাটা
    image = None
    schema_body = None
    
    ld_json = soup.find_all('script', type='application/ld+json')
    for script in ld_json:
        try:
            if not script.string: continue
            data = json.loads(script.string)
            
            if '@graph' in data:
                for item in data['@graph']:
                    if item.get('@type') in ['NewsArticle', 'Article', 'BlogPosting']:
                        data = item
                        break
            
            # ইমেজ খোঁজা
            if 'image' in data:
                img_data = data['image']
                if isinstance(img_data, dict):
                    image = img_data.get('url')
                elif isinstance(img_data, list):
                    image = img_data[0]
                elif isinstance(img_data, str):
                    image = img_data
            
            # বডি খোঁজা
            if 'articleBody' in data:
                schema_body = data['articleBody']
                
        except:
            pass

    # ৩. ইমেজ ফলব্যাক
    if not image:
        og_image = soup.find('meta', property='og:image')
        if og_image:
            image = og_image.get('content')
        else:
            main_area = soup.select_one('article, [itemprop="articleBody"], .post-content, #content, .details')
            target = main_area if main_area else soup
            
            for img in target.find_all('img'):
                src = img.get('src') or img.get('data-src') or img.get('data-original')
                # লোগো বা ছোট আইকন বাদ
                if src and not any(x in src.lower() for x in ['logo', 'icon', 'avatar', 'svg']):
                    image = urljoin(base_url, src)
                    break

    # ৪. বডি কন্টেন্ট এক্সট্রাকশন (Trafilatura)
    clean_soup = clean_html(soup)
    cleaned_html_str = str(clean_soup)
    
    body_text = trafilatura.extract(
        cleaned_html_str, 
        include_images=False, 
        include_comments=False, 
        favor_precision=True,
        target_language='bn' 
    )
    
    # ফলব্যাক: যদি Trafilatura ফেইল করে
    if not body_text:
        if schema_body:
            body_text = schema_body
        else:
            paragraphs = clean_soup.find_all('p')
            body_text = "\n\n".join([p.get_text(strip=True) for p in paragraphs if len(p.get_text(strip=True)) > 40])

    # ৫. HTML ফরম্যাটিং
    formatted_body = ""
    if body_text:
        for para in body_text.split('\n'):
            clean_para = para.strip()
            if len(clean_para) > 5: # খুব ছোট লাইন বাদ
                formatted_body += f"<p>{clean_para}</p>"

    return {
        "title": title,
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
        
        if data['title'] and (data['body'] or len(data['body']) > 100):
            print(json.dumps(data, ensure_ascii=False))
        else:
            print(json.dumps({"error": "Content extraction failed or empty"}))
    else:
        print(json.dumps({"error": "Failed to retrieve HTML"}))

except Exception as e:
    print(json.dumps({"error": str(e)}))