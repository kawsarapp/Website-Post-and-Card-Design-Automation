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

# 🔥 1. WINDOWS CONSOLE ENCODING FIX
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# ইনপুট আর্গুমেন্ট চেক
try:
    url = sys.argv[1]
except IndexError:
    print(json.dumps({"error": "No URL provided"}))
    sys.exit(1)

# --- CONFIGURATION: EMBEDDED PUPPETEER SCRIPT ---
# আমরা আপনার দেওয়া Node.js কোডটি এখানে এম্বেড করেছি। 
# প্রয়োজন হলে পাইথন এটি একটি টেম্প ফাইল হিসেবে তৈরি করে রান করবে।
PUPPETEER_CODE = r"""
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];

if (!url || !outputFile) {
    process.exit(1);
}

(async () => {
  const browser = await puppeteer.launch({
    headless: "new",
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--disable-gpu',
      '--window-size=1920,1080',
      '--disable-infobars',
      '--exclude-switches=enable-automation'
    ]
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // ১. ফাস্ট লোডিং + অ্যাড ব্লক
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        const type = req.resourceType();
        const blockTypes = ['image', 'media', 'font', 'stylesheet', 'websocket', 'manifest'];
        const blockDomains = ['googleads', 'doubleclick', 'analytics', 'facebook', 'tracker', 'adsystem'];
        
        if (blockTypes.includes(type) || blockDomains.some(d => req.url().includes(d))) {
            req.abort();
        } else {
            req.continue();
        }
    });

    // ২. রিয়েল ইউজার এজেন্ট
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    // ৩. পেজ লোড
    try { 
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 }); 
    } catch (e) { }

    // ৪. 🔥 হিউম্যান স্ক্রলিং
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                const distance = Math.floor(Math.random() * (300 - 100 + 1)) + 100;
                
                if (Math.random() < 0.1) {
                    window.scrollBy(0, -100);
                } else {
                    window.scrollBy(0, distance);
                    totalHeight += distance;
                }

                if (totalHeight >= scrollHeight || totalHeight > 15000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 200);
        });
    });

    // ৫. ইমেজ সোর্স ফিক্স
    await page.evaluate(() => {
        document.querySelectorAll('meta[property="og:image"]').forEach(e => e.remove());
        document.querySelectorAll('img').forEach(img => {
            let bestSrc = img.getAttribute('data-original') || 
                          img.getAttribute('data-full-url') || 
                          img.getAttribute('data-src') || 
                          img.getAttribute('src');
            
            if (bestSrc) {
                if (bestSrc.includes('?')) bestSrc = bestSrc.split('?')[0];
                img.setAttribute('src', bestSrc);
            }
        });
    });

    // ৬. সেভ
    await new Promise(r => setTimeout(r, 1000));
    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    
    await browser.close();
    process.exit(0);

  } catch (error) {
    if (browser) await browser.close();
    process.exit(1);
  }
})();
"""

# --- HELPER FUNCTIONS ---

def get_html_advanced(target_url):
    """
    Priority 1: Fast Python Request (Cloudflare/Bot Bypass)
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
                'Upgrade-Insecure-Requests': '1',
                'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'Sec-Ch-Ua-Mobile': '?0',
                'Sec-Ch-Ua-Platform': '"Windows"',
                'Sec-Fetch-Site': 'none',
                'Sec-Fetch-Mode': 'navigate',
                'Sec-Fetch-User': '?1',
                'Sec-Fetch-Dest': 'document'
            }
        )
        if response.status_code == 200:
            if response.encoding is None or response.encoding == 'ISO-8859-1':
                response.encoding = response.apparent_encoding
            return response.text
        return None
    except Exception as e:
        return None

def get_html_via_puppeteer_fallback(target_url):
    """
    Priority 2: Node.js Puppeteer Fallback for JS Heavy Sites
    """
    try:
        # Create temp JS file
        with tempfile.NamedTemporaryFile(delete=False, suffix='.mjs', mode='w', encoding='utf-8') as js_file:
            js_file.write(PUPPETEER_CODE)
            js_path = js_file.name

        # Create temp output HTML file
        with tempfile.NamedTemporaryFile(delete=False, suffix='.html') as html_file:
            html_out_path = html_file.name

        # Execute Node.js
        # Assumes 'node' is in system PATH
        process = subprocess.run(['node', js_path, target_url, html_out_path], capture_output=True, text=True)
        
        html_content = ""
        if process.returncode == 0 and os.path.exists(html_out_path):
            with open(html_out_path, 'r', encoding='utf-8') as f:
                html_content = f.read()
        
        # Cleanup
        try:
            os.remove(js_path)
            os.remove(html_out_path)
        except:
            pass
            
        return html_content if html_content else None

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
        'loader', 'spinner', 'placeholder', 'pixel', 'blank', 'avatar', 
        'author', 'share', 'profile', 'widget', 'tracking', 'gif'
    ]
    if any(x in img_lower for x in garbage_keywords):
        return False
    return True

def clean_and_resolve_url(base_url, img_url):
    """
    প্যারামিটার রিমুভ করে এবং অ্যাবসলিউট URL বানায়
    """
    if not img_url: return None
    
    if '?' in img_url:
        clean = img_url.split('?')[0]
        if re.search(r'\.(jpg|jpeg|png|webp|avif)$', clean, re.IGNORECASE):
            img_url = clean

    return urljoin(base_url, img_url)

def extract_data_from_html(html_content, target_url):
    """
    HTML থেকে টাইটেল, বডি এবং ইমেজ বের করার লজিক
    """
    soup = BeautifulSoup(html_content, 'html.parser')
    
    output = {
        "title": "No Title",
        "body": "",
        "image": None,
        "source_url": target_url
    }

    # --- A. TITLE EXTRACTION ---
    if soup.find('h1'):
        output["title"] = soup.find('h1').get_text(strip=True)
    elif soup.title:
        output["title"] = soup.title.string

    # --- B. IMAGE EXTRACTION ---
    best_image = None
    
    # 1. JSON-LD
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
            if best_image: break
        except: pass

    # 2. Body Image
    if not best_image:
        article = soup.select_one('article, [itemprop="articleBody"], .article-details, #content, .news-details, .post-content')
        target = article if article else soup.body
        
        if target:
            images = target.find_all('img')
            for img in images:
                src = img.get('data-original') or img.get('data-full-url') or img.get('data-src') or img.get('src')
                if src and len(src) > 20 and is_valid_image(src):
                    width = img.get('width')
                    if width and width.isdigit() and int(width) < 200:
                        continue
                    best_image = src
                    break

    if best_image:
        output["image"] = clean_and_resolve_url(target_url, best_image)

    # --- C. BODY EXTRACTION ---
    result = trafilatura.extract(
        html_content, 
        include_images=False, 
        include_comments=False,
        favor_precision=True,
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
                if len(p) > 20 and "আরও পড়ুন" not in p and "Share" not in p: 
                    formatted_body += f"<p>{p}</p>"
            output["body"] = formatted_body

    # Fallback Body
    if not output["body"]:
            target = soup.select_one('article') if soup.select_one('article') else soup.body
            if target:
                paragraphs = target.find_all(['p', 'div'])
                temp_body = ""
                for p in paragraphs:
                    txt = p.get_text(strip=True)
                    if len(txt) > 40:
                        temp_body += f"<p>{txt}</p>"
                output["body"] = temp_body

    return output

# --- MAIN LOGIC ---

try:
    # ধাপ ১: দ্রুত Python Request চেষ্টা করা
    html_content = get_html_advanced(url)
    
    final_output = None
    
    if html_content:
        extracted = extract_data_from_html(html_content, url)
        # যদি Body খালি থাকে, তার মানে JS Loading দরকার
        if extracted["body"]:
            final_output = extracted
    
    # ধাপ ২: যদি ধাপ ১ ব্যর্থ হয় বা বডি খালি থাকে -> Puppeteer Fallback
    if not final_output:
        # এখানে Node.js স্ক্রিপ্ট কল হবে
        html_content_js = get_html_via_puppeteer_fallback(url)
        if html_content_js:
            final_output = extract_data_from_html(html_content_js, url)

    # OUTPUT
    if final_output and final_output["body"]:
        print(json.dumps(final_output, ensure_ascii=False))
    else:
        print(json.dumps({"error": "Content extracted is empty after trying both methods"}))

except Exception as e:
    print(json.dumps({"error": str(e)}))