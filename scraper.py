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
# Windows কনসোলে বাংলা টেক্সট প্রিন্ট করতে গেলে ক্রাশ করে, তাই এটা ফিক্স করা হলো।
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')
else:
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

# ইনপুট আর্গুমেন্ট চেক
try:
    target_url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

# ==========================================
# 🚀 FAST REQUEST (Browser Impersonation)
# ==========================================
def get_html(url):
    try:
        # লেটেস্ট ক্রোম ব্রাউজারের মতো আচরণ করবে
        response = requests.get(
            url,
            impersonate="chrome120", 
            timeout=30,
            headers={
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language': 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            }
        )
        if response.status_code == 200:
            # এনকোডিং অটো-ডিটেক্ট করা
            if response.encoding is None or response.encoding == 'ISO-8859-1':
                response.encoding = response.apparent_encoding
            return response.text
    except Exception as e:
        # সাইলেন্ট ফেইল, যাতে PHP পরের মেথড ট্রাই করতে পারে
        pass
    return None

# ==========================================
# 🧹 SMART CLEANER (Garbage Removal)
# ==========================================
def clean_html(soup):
    # অপ্রয়োজনীয় ট্যাগ রিমুভ করা
    for tag in soup(['script', 'style', 'iframe', 'nav', 'footer', 'header', 'form', 'svg', 'noscript']):
        tag.decompose()

    # কমন অ্যাডস এবং গার্বেজ ক্লাস রিমুভ করা
    garbage_selectors = [
        '.advertisement', '.ads', '.ad-container', '.social-share', 
        '.share-buttons', '.related-news', '.read-more', '.tags', 
        '.author-bio', '.sidebar', '.comments', '.meta-info', 
        '[class*="taboola"]', '[id*="taboola"]', '[class*="popup"]'
    ]
    
    for selector in garbage_selectors:
        for tag in soup.select(selector):
            tag.decompose()
            
    return soup

# ==========================================
# 🧠 INTELLIGENT EXTRACTION
# ==========================================
def extract_data(html, base_url):
    soup = BeautifulSoup(html, 'html.parser')
    
    # ১. টাইটেল এক্সট্রাকশন
    title = ""
    if soup.find('h1'):
        title = soup.find('h1').get_text(strip=True)
    elif soup.title:
        title = soup.title.string

    # ২. JSON-LD (Schema.org) থেকে ডাটা বের করা (সবচেয়ে নির্ভুল)
    image = None
    schema_body = None
    
    ld_json = soup.find_all('script', type='application/ld+json')
    for script in ld_json:
        try:
            data = json.loads(script.string)
            # গ্রাফ ফরম্যাট হ্যান্ডেল করা
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

    # ৩. ইমেজ ফলব্যাক (যদি JSON-LD তে না থাকে)
    if not image:
        # ওপেন গ্রাফ ইমেজ
        og_image = soup.find('meta', property='og:image')
        if og_image:
            image = og_image.get('content')
        else:
            # মেইন কন্টেন্ট এরিয়া থেকে ইমেজ খোঁজা
            main_area = soup.select_one('article, [itemprop="articleBody"], .post-content, #content, .details')
            target = main_area if main_area else soup
            
            for img in target.find_all('img'):
                src = img.get('src') or img.get('data-src')
                # লোগো বা ছোট আইকন বাদ দেওয়া
                if src and 'logo' not in src.lower() and 'icon' not in src.lower():
                    # রিলেটিভ পাথ ঠিক করা
                    image = urljoin(base_url, src)
                    break

    # ৪. বডি কন্টেন্ট এক্সট্রাকশন (Trafilatura - সেরা টেক্সট ক্লিনার)
    # প্রথমে স্যুপ ক্লিন করা
    clean_soup = clean_html(soup)
    cleaned_html_str = str(clean_soup)
    
    body_text = trafilatura.extract(
        cleaned_html_str, 
        include_images=False, 
        include_comments=False, 
        favor_precision=True,
        target_language='bn' # বাংলার জন্য অপটিমাইজড
    )
    
    # Trafilatura ফেইল করলে ফলব্যাক (Schema Body অথবা সাধারণ প্যারাগ্রাফ)
    if not body_text:
        if schema_body:
            body_text = schema_body
        else:
            # ম্যানুয়াল প্যারাগ্রাফ জয়েন
            paragraphs = clean_soup.find_all('p')
            body_text = "\n\n".join([p.get_text(strip=True) for p in paragraphs if len(p.get_text(strip=True)) > 40])

    # ৫. HTML ফরম্যাটিং (Line break to <p>)
    formatted_body = ""
    if body_text:
        # লাইন ব্রেক দিয়ে প্যারাগ্রাফ আলাদা করা
        for para in body_text.split('\n'):
            clean_para = para.strip()
            if len(clean_para) > 10:
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
    html_content = get_html(target_url)
    
    if html_content:
        data = extract_data(html_content, target_url)
        
        # ভ্যালিডেশন: টাইটেল বা বডি না থাকলে এরর
        if data['title'] and data['body']:
            print(json.dumps(data, ensure_ascii=False))
        else:
            # ডাটা না পেলে এম্পটি জেসন, যাতে PHP পরবর্তী স্টেপে যায়
            print(json.dumps({"error": "Content extraction failed"}))
    else:
        print(json.dumps({"error": "Failed to retrieve HTML"}))

except Exception as e:
    # যেকোন ক্রিটিক্যাল এররে JSON রিটার্ন
    print(json.dumps({"error": str(e)}))