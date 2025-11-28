import redis
import json
import os
import time
from datetime import datetime
from playwright.sync_api import sync_playwright
import mysql.connector
from dotenv import load_dotenv

# .env ফাইল লোড করা (প্যারেন্ট ফোল্ডার থেকে)
load_dotenv(os.path.join(os.path.dirname(__file__), '../.env'))

# Redis কানেকশন
redis_client = redis.Redis(host='127.0.0.1', port=6379, db=0)

# MySQL কানেকশন ফাংশন
def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv('DB_HOST'),
        user=os.getenv('DB_USERNAME'),
        password=os.getenv('DB_PASSWORD'),
        database=os.getenv('DB_DATABASE')
    )

def scrape_and_save(task):
    url = task['url']
    website_id = task['website_id']
    user_id = task['user_id']
    selectors = task['selectors'] # {container, title, image}

    print(f"🕷️ Scraping: {url}")

    with sync_playwright() as p:
        # ব্রাউজার লঞ্চ (Stealth Mode)
        browser = p.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled"])
        context = browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"
        )
        page = context.new_page()

        # রিসোর্স ব্লক (ইমেজ/ফন্ট লোড হবে না - স্পিড বাড়বে)
        page.route("**/*", lambda route: route.abort() if route.request.resource_type in ["image", "media", "font", "stylesheet"] else route.continue_())

        try:
            page.goto(url, timeout=60000, wait_until="domcontentloaded")
            
            # কন্টেইনার আসার জন্য অপেক্ষা
            try:
                page.wait_for_selector(selectors['container'], timeout=10000)
            except:
                print("⚠️ Selector timeout, trying anyway...")

            # স্মার্ট স্ক্রল
            page.evaluate("window.scrollBy(0, 1000)")
            time.sleep(1)

            # ডাটা এক্সট্রাকশন
            news_data = page.evaluate(f"""
                (selectors) => {{
                    const items = [];
                    const containers = document.querySelectorAll(selectors.container);
                    
                    containers.forEach(el => {{
                        const titleEl = el.querySelector(selectors.title);
                        const linkEl = el.querySelector('a') || el.closest('a');
                        let imgEl = el.querySelector(selectors.image || 'img');
                        
                        if (titleEl && linkEl) {{
                            let imgUrl = null;
                            if(imgEl) {{
                                imgUrl = imgEl.getAttribute('src') || imgEl.getAttribute('data-src');
                            }}

                            items.push({{
                                title: titleEl.innerText.trim(),
                                link: linkEl.href,
                                image: imgUrl
                            }});
                        }}
                    }});
                    return items;
                }}
            """, selectors)

            # ডাটাবেসে সেভ করা
            db = get_db_connection()
            cursor = db.cursor()
            
            count = 0
            for news in news_data:
                now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                
                # ডুপ্লিকেট চেক করে ইনসার্ট
                sql = """
                INSERT INTO news_items (user_id, website_id, title, thumbnail_url, original_link, published_at, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)
                """
                val = (user_id, website_id, news['title'], news['image'], news['link'], now, now, now)
                
                try:
                    cursor.execute(sql, val)
                    count += 1
                except mysql.connector.Error as err:
                    pass 

            db.commit()
            cursor.close()
            db.close()
            
            print(f"✅ Saved {count} news items.")

        except Exception as e:
            print(f"❌ Error scraping {url}: {e}")
        finally:
            browser.close()

print("👷 Python Worker Started... Waiting for jobs on 'scrape_queue'")

while True:
    # Redis থেকে জব আসার অপেক্ষা
    _, data = redis_client.blpop('scrape_queue')
    
    try:
        task = json.loads(data)
        scrape_and_save(task)
    except Exception as e:
        print(f"❌ Job Error: {e}")