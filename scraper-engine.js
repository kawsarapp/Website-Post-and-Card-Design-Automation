import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

// ১. স্টিলথ প্লাগিন সেটআপ
puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];

if (!url || !outputFile) {
    console.error("Usage: node scraper-engine.js <url> <outputFile>");
    process.exit(1);
}

// 🔥 BLOCK LIST (Script 2 থেকে)
const BLOCKED_RESOURCE_TYPES = ['image', 'media', 'font', 'stylesheet', 'websocket', 'manifest', 'other'];
const BLOCKED_DOMAINS = [
    'googlesyndication.com', 'doubleclick.net', 'google-analytics.com',
    'facebook.net', 'connect.facebook.net', 'googleads', 'g.doubleclick',
    'adnxs.com', 'advertising', 'ads', 'marketing', 'tracker', 'analytics',
    'taboola', 'outbrain', 'criteo', 'pubmatic', 'rubiconproject',
    'amazon-adsystem', 'smartadserver', 'popups', 'onesignal'
];

// র‍্যান্ডম ডিলে ফাংশন
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

(async () => {
  // ২. ব্রাউজার লঞ্চ কনফিগারেশন (উভয় স্ক্রিপ্ট এর বেস্ট সেটিংস)
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
      '--exclude-switches=enable-automation',
      '--disable-notifications', // Script 2
      '--disable-popup-blocking' // Script 2
    ]
  });

  try {
    const page = await browser.newPage();
    
    // ভিউপোর্ট সেটআপ
    await page.setViewport({ width: 1920, height: 1080 });

    // ৩. রিয়েল ব্রাউজার হেডার (Script 1 - Security Bypass এর জন্য জরুরি)
    await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9,bn;q=0.8',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile': '?0',
        'Sec-Ch-Ua-Platform': '"Windows"'
    });

    // ৪. স্মার্ট রিকোয়েস্ট ব্লকিং (Script 2 এর লজিক - ফাস্ট লোডিং)
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        const resourceType = req.resourceType();
        const requestUrl = req.url().toLowerCase();

        // ভারি রিসোর্স ব্লক
        if (BLOCKED_RESOURCE_TYPES.includes(resourceType)) {
            req.abort();
            return;
        }
        // অ্যাড এবং ট্র্যাকার ডোমেইন ব্লক
        if (BLOCKED_DOMAINS.some(domain => requestUrl.includes(domain))) {
            req.abort();
            return;
        }
        req.continue();
    });

    // ৫. ইউজার এজেন্ট
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    // ৬. পেজ লোড
    try { 
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 }); 
    } catch (e) {
        console.log("Warning: Page load timed out or incomplete, proceeding to scrape...");
    }

    // ৭. মাউস মুভমেন্ট (Anti-Bot Trick)
    try {
        await page.mouse.move(100, 100);
        await page.mouse.down();
        await page.mouse.move(200, 200);
        await page.mouse.up();
    } catch (e) {}

    // ৮. স্মার্ট স্ক্রল (Lazy Load ট্রিগার করার জন্য)
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 400;
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;
                // ৬০০০ পিক্সেল পর্যন্ত স্ক্রল করবে (Script 1 এর লজিক বেশি নিরাপদ)
                if (totalHeight >= scrollHeight || totalHeight > 6000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 100);
        });
    });

    // 🔥 ৯. DOM ম্যানিপুলেশন (Script 1 & 2 Merged) 🔥
    await page.evaluate(() => {
        // A. Junk Removal (Script 2) - ক্লিন কন্টেন্ট পাওয়ার জন্য
        const junkSelectors = [
            'header', 'footer', 'nav', 'aside', 'iframe', 
            '.advertisement', '.ads', '#ads', '.banner', 
            '.sidebar', '.comments', '.related-news', 
            '.share-buttons', '.social-media', 
            '[id^="google_ads"]', '[class*="popup"]'
        ];
        junkSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => el.remove());
        });

        // B. মেটা ট্যাগ রিমুভ (Script 1)
        const metasToRemove = document.querySelectorAll('meta[property="og:image"], meta[name="twitter:image"]');
        metasToRemove.forEach(meta => meta.remove());

        // C. ইমেজ প্রসেসিং (Script 1 এর অ্যাডভান্সড লজিক)
        const images = document.querySelectorAll('img');
        
        images.forEach(img => {
            // ১. হাই কোয়ালিটি সোর্স খোঁজা
            let bestSrc = 
                img.getAttribute('data-original') || 
                img.getAttribute('data-full-url') || 
                img.getAttribute('data-src') || 
                img.getAttribute('data-lazy-src') ||
                img.getAttribute('src');

            if (bestSrc) {
                // ২. প্যারামিটার রিমুভ (Script 1 Speciality)
                // যেমন: image.jpg?width=300 -> image.jpg
                if (bestSrc.includes('?')) {
                    const parts = bestSrc.split('?');
                    if (parts[0].match(/\.(jpeg|jpg|png|webp|avif)$/i)) {
                        bestSrc = parts[0];
                    }
                }
                // ৩. ক্লিন লিংক বসানো
                img.setAttribute('src', bestSrc);
            }
        });
    });

    // ১০. ফাইনাল HTML সেভ করা
    await new Promise(r => setTimeout(r, 1000)); // DOM আপডেটের অপেক্ষা
    
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