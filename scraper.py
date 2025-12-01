import sys
import json
import io
import re
from urllib.parse import urljoin
import trafilatura
from curl_cffi import requests
from bs4 import BeautifulSoup

# 🔥 1. WINDOWS CONSOLE ENCODING FIX
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# ইনপুট আর্গুমেন্ট চেক
try:
    url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

# --- HELPER FUNCTIONS ---

def get_html_advanced(target_url):
    """
    Real Chrome Browser সেজে রিকোয়েস্ট পাঠাবে (Cloudflare Bypass)
    """
    try:
        response = requests.get(
            target_url, 
            impersonate="chrome120", 
            timeout=30,
            headers={
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.9,bn;q=0.8',
                'Referer': 'https://www.google.com/',
                'Upgrade-Insecure-Requests': '1'
            }
        )
        if response.status_code == 200:
            if response.encoding is None or response.encoding == 'ISO-8859-1':
                response.encoding = response.apparent_encoding
            return response.text
        return None
    except Exception as e:
        return None

def is_valid_image(img_url):
    """
    লোগো, আইকন বা গার্বেজ ফিল্টার
    """
    if not img_url: return False
    img_lower = img_url.lower()
    
    garbage_keywords = [
        'logo', 'icon', 'svg', 'button', 'sprite', 'ad-', 'banner', 
        'loader', 'spinner', 'placeholder', 'pixel', 'blank', 'avatar', 'author', 'share'
    ]
    if any(x in img_lower for x in garbage_keywords):
        return False
    return True

def clean_and_resolve_url(base_url, img_url):
    """
    প্যারামিটার রিমুভ করে এবং অ্যাবসলিউট URL বানায়
    """
    if not img_url: return None
    
    # প্যারামিটার রিমুভ (?width=600 -> clean)
    if '?' in img_url:
        clean = img_url.split('?')[0]
        if re.search(r'\.(jpg|jpeg|png|webp|avif)$', clean, re.IGNORECASE):
            img_url = clean

    return urljoin(base_url, img_url)

# --- MAIN LOGIC ---

try:
    html_content = get_html_advanced(url)
    
    if html_content:
        soup = BeautifulSoup(html_content, 'html.parser')
        
        final_output = {
            "title": "No Title",
            "body": "",
            "image": None,
            "source_url": url
        }

        # --- A. TITLE EXTRACTION ---
        if soup.find('h1'):
            final_output["title"] = soup.find('h1').get_text(strip=True)
        elif soup.title:
            final_output["title"] = soup.title.string

        # --- B. IMAGE EXTRACTION (BODY FIRST STRATEGY) ---
        
        best_image = None
        
        # 🔥 Priority 1: Body Image (আর্টিকেলের ভেতরের ছবি - সবচেয়ে নিরাপদ)
        # আমরা আগে বডি চেক করব, কারণ এখানকার ছবি সাধারণত ইউজার যা দেখে তাই (ক্লিন)
        article = soup.select_one('article, [itemprop="articleBody"], .article-details, #content, .news-details, .content-details, .story-element, .post-content')
        
        if article:
            images = article.find_all('img')
            for img in images:
                # ১. হাই কোয়ালিটি অ্যাট্রিবিউট আগে চেক
                src = img.get('data-original') or img.get('data-full-url') or img.get('data-src') or img.get('src')
                
                if src and len(src) > 20 and is_valid_image(src):
                    # ২. সাইজ চেক (খুব ছোট আইকন বাদ)
                    width = img.get('width')
                    if width and width.isdigit() and int(width) < 200:
                        continue
                    
                    best_image = src
                    break # প্রথম ভালো ইমেজ পেলেই ব্রেক

        # 🔥 Priority 2: JSON-LD (Fallback - যদি বডিতে ছবি না পাওয়া যায়)
        if not best_image:
            scripts = soup.find_all('script', type='application/ld+json')
            for script in scripts:
                try:
                    data = json.loads(script.string)
                    if isinstance(data, dict):
                        if 'image' in data:
                            img = data['image']
                            candidate = img['url'] if isinstance(img, dict) else (img[0] if isinstance(img, list) else img)
                            if candidate and is_valid_image(candidate):
                                best_image = candidate
                                break
                        
                        if '@graph' in data:
                            for item in data['@graph']:
                                if 'image' in item and 'url' in item['image']:
                                    candidate = item['image']['url']
                                    if is_valid_image(candidate):
                                        best_image = candidate
                                        break
                except: pass
                if best_image: break

        # ফাইনাল প্রসেসিং
        if best_image:
            final_output["image"] = clean_and_resolve_url(url, best_image)

        # --- C. BODY EXTRACTION ---
        result = trafilatura.extract(
            html_content, 
            include_images=False, 
            include_comments=False,
            output_format='json'
        )

        if result:
            data = json.loads(result)
            raw_text = data.get('text') or ""
            
            if raw_text:
                paragraphs = raw_text.split('\n')
                formatted_body = ""
                for p in paragraphs:
                    p = p.strip()
                    if len(p) > 20 and "আরও পড়ুন" not in p and "Share" not in p: 
                        formatted_body += f"<p>{p}</p>"
                final_output["body"] = formatted_body

        # Fallback Body
        if not final_output["body"]:
             target = article if 'article' in locals() and article else soup.body
             if target:
                 paragraphs = target.find_all(['p', 'div'])
                 temp_body = ""
                 for p in paragraphs:
                     txt = p.get_text(strip=True)
                     if len(txt) > 30:
                         temp_body += f"<p>{txt}</p>"
                 final_output["body"] = temp_body

        # OUTPUT
        if final_output["body"]:
            print(json.dumps(final_output, ensure_ascii=False))
        else:
            print(json.dumps({"error": "Content extracted is empty"}))

    else:
        print(json.dumps({"error": "Failed to fetch URL"}))

except Exception as e:
    print(json.dumps({"error": str(e)}))