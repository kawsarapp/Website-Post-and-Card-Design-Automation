import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

// ১. স্টিলথ প্লাগিন (Cloudflare/Bot ডিটেকশন এড়াতে)
puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];

if (!url || !outputFile) {
    console.error("Usage: node scraper-engine.js <url> <outputFile>");
    process.exit(1);
}

// র‍্যান্ডম ডিলে ফাংশন (মানুষের মতো আচরণ)
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

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
    
    // ভিউপোর্ট ল্যাপটপের মতো সেট করা
    await page.setViewport({ width: 1920, height: 1080 });

    // ২. রিয়েল ব্রাউজার হেডার (Security Bypass)
    await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9,bn;q=0.8',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile': '?0',
        'Sec-Ch-Ua-Platform': '"Windows"'
    });

    // ৩. রিসোর্স ব্লক (ইমেজ ডাউনলোড ব্লক করে স্পিড বাড়ানো)
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        const resourceType = req.resourceType();
        // ইমেজ বা ফন্ট ডাউনলোড করার দরকার নেই, শুধু HTML স্ট্রাকচার দরকার
        if (['image', 'media', 'font', 'stylesheet', 'websocket'].includes(resourceType)) {
            req.abort();
        } else {
            req.continue();
        }
    });

    // ৪. ইউজার এজেন্ট
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    // ৫. পেজ লোড
    try { 
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 }); 
    } catch (e) {
        console.log("Warning: Page load timed out, proceeding to scrape...");
    }

    // ৬. মাউস মুভমেন্ট (Anti-Bot Trick)
    try {
        await page.mouse.move(100, 100);
        await page.mouse.down();
        await page.mouse.move(200, 200);
        await page.mouse.up();
    } catch (e) {}

    // ৭. স্মার্ট স্ক্রল (Lazy Load ইমেজ ট্যাগ লোড করার জন্য)
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 400;
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;

                // পেজ শেষ হলে বা ৬০০০ পিক্সেল স্ক্রল হলে থামা
                if (totalHeight >= scrollHeight || totalHeight > 6000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 100);
        });
    });

    // 🔥 ৮. DOM ম্যানিপুলেশন (Powerful Cleaning Logic) 🔥
    await page.evaluate(() => {
        // A. মেটা ট্যাগ রিমুভ করা (যাতে PHP স্ক্রিপ্ট এগুলো না পায়)
        // আমরা চাই PHP শুধু বডি ইমেজ বা JSON-LD ব্যবহার করুক
        const metasToRemove = document.querySelectorAll('meta[property="og:image"], meta[name="twitter:image"]');
        metasToRemove.forEach(meta => meta.remove());

        // B. ইমেজ প্রসেসিং (High Quality Force)
        const images = document.querySelectorAll('img');
        
        images.forEach(img => {
            // ১. হাই কোয়ালিটি সোর্স খোঁজা (Lazy Load Attribute)
            let bestSrc = 
                img.getAttribute('data-original') || 
                img.getAttribute('data-full-url') || 
                img.getAttribute('data-src') || 
                img.getAttribute('data-lazy-src') ||
                img.getAttribute('src');

            if (bestSrc) {
                // ২. প্যারামিটার রিমুভ (JS দিয়ে)
                // যেমন: image.jpg?width=300 -> image.jpg
                if (bestSrc.includes('?')) {
                    const parts = bestSrc.split('?');
                    // চেক করা যে এটি ইমেজ ফাইল এক্সটেনশন
                    if (parts[0].match(/\.(jpeg|jpg|png|webp|avif)$/i)) {
                        bestSrc = parts[0];
                    }
                }

                // ৩. মেইন src তে হাই-কোয়ালিটি লিংক বসানো
                // এতে PHP যখন HTML পড়বে, সে সরাসরি ক্লিন লিংক পাবে
                img.setAttribute('src', bestSrc);
            }
        });
    });

    // ৯. ফাইনাল HTML সেভ করা
    await new Promise(r => setTimeout(r, 1000)); // DOM আপডেটের জন্য একটু অপেক্ষা
    
    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    
    await browser.close();
    process.exit(0);

  } catch (error) {
    console.error('Puppeteer Engine Error:', error);
    if (browser) await browser.close();
    process.exit(1);
  }
})();