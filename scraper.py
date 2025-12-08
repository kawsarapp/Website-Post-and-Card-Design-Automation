import sys
import json
import io
import re
import os
import subprocess
import tempfile
from urllib.parse import urljoin
import trafilatura
from curl_cffi import requests
from bs4 import BeautifulSoup

# কনসোল এনকোডিং ফিক্স
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# ইনপুট চেক
try:
    url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

# --- HELPER 1: FAST PYTHON REQUEST ---
# --- HELPER 1: FAST PYTHON REQUEST ---
def get_html_fast(target_url):
    try:
        response = requests.get(
            target_url, 
            impersonate="chrome124", 
            timeout=30,
            follow_redirects=True, # 🔥 রিডাইরেক্ট ফলো করবে
            headers={
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language': 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding': 'gzip, deflate, br, zstd',
                'Referer': 'https://www.google.com/',
                'Upgrade-Insecure-Requests': '1',
                'Sec-Ch-Ua': '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
                'Sec-Ch-Ua-Mobile': '?0',
                'Sec-Ch-Ua-Platform': '"Windows"',
                'Sec-Fetch-Dest': 'document',
                'Sec-Fetch-Mode': 'navigate',
                'Sec-Fetch-Site': 'cross-site',
                'Sec-Fetch-User': '?1'
            }
        )
        if response.status_code == 200:
            if response.encoding is None:
                response.encoding = response.apparent_encoding
            return response.text
    except Exception as e:
        pass
    return None

# --- HELPER 2: HARDCORE PUPPETEER FALLBACK ---
def get_html_puppeteer(target_url):
    try:
        # টেম্প ফাইল তৈরি
        with tempfile.NamedTemporaryFile(delete=False, suffix='.html') as tmp:
            output_path = tmp.name

        # বর্তমান ফোল্ডার থেকে JS ফাইল খুঁজবে
        script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'scraper-engine.js')
        
        if not os.path.exists(script_path):
            return None

        # Node.js কল করা
        process = subprocess.run(
            ['node', script_path, target_url, output_path],
            capture_output=True, text=True
        )

        html_content = ""
        if process.returncode == 0 and os.path.exists(output_path):
            with open(output_path, 'r', encoding='utf-8') as f:
                html_content = f.read()

        # ক্লিনআপ
        if os.path.exists(output_path):
            os.remove(output_path)

        return html_content if len(html_content) > 500 else None
    except Exception:
        return None

# --- HELPER 3: INTELLIGENT EXTRACTION ---
def extract_content(html, base_url):
    soup = BeautifulSoup(html, 'html.parser')
    
    # ১. টাইটেল
    title = ""
    if soup.find('h1'):
        title = soup.find('h1').get_text(strip=True)
    elif soup.title:
        title = soup.title.string
    
    # ২. ইমেজ (Hardcore Logic)
    image = None
    # JSON-LD চেক
    ld_json = soup.find_all('script', type='application/ld+json')
    for script in ld_json:
        try:
            data = json.loads(script.string)
            if 'image' in data:
                img = data['image']
                image = img['url'] if isinstance(img, dict) else (img[0] if isinstance(img, list) else img)
                break
        except: pass
    
    # যদি JSON-LD তে না থাকে, তবে বডি থেকে খুঁজবে
    if not image:
        # মেইন কন্টেন্ট এরিয়া ডিটেক্ট করার চেষ্টা
        main_area = soup.select_one('article, [itemprop="articleBody"], .post-content, .entry-content, #content')
        target = main_area if main_area else soup
        
        images = target.find_all('img')
        for img in images:
            src = img.get('src')
            # ছোট আইকন বা লোগো বাদ দেওয়ার লজিক
            if src and 'logo' not in src.lower() and 'icon' not in src.lower() and len(src) > 20:
                # উইডথ চেক (যদি থাকে)
                width = img.get('width')
                if width and width.isdigit() and int(width) < 300:
                    continue 
                image = urljoin(base_url, src)
                break

    # ৩. বডি টেক্সট (Trafilatura - The Best Extractor)
    body = trafilatura.extract(html, include_images=False, include_comments=False, favor_precision=True)
    
    # Trafilatura ফেইল করলে ফলব্যাক
    if not body:
        paragraphs = soup.find_all('p')
        body = "\n\n".join([p.get_text(strip=True) for p in paragraphs if len(p.get_text(strip=True)) > 40])

    # HTML ফরম্যাটিং
    formatted_body = ""
    if body:
        for para in body.split('\n'):
            if len(para.strip()) > 20:
                formatted_body += f"<p>{para.strip()}</p>"

    return {
        "title": title,
        "body": formatted_body,
        "image": image,
        "source_url": base_url
    }

# --- MAIN EXECUTION ---
try:
    # ধাপ ১: ফাস্ট মেথড
    html = get_html_fast(url)
    data = None
    
    if html:
        extracted = extract_content(html, url)
        if extracted['body']:
            data = extracted

    # ধাপ ২: ফাস্ট মেথডে কাজ না হলে বা বডি না থাকলে -> Puppeteer
    if not data or not data['body']:
        html_js = get_html_puppeteer(url)
        if html_js:
            data = extract_content(html_js, url)

    # ফাইনাল আউটপুট
    if data:
        print(json.dumps(data, ensure_ascii=False))
    else:
        print(json.dumps({"error": "Failed to extract content"}))

except Exception as e:
    print(json.dumps({"error": str(e)}))