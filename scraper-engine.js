import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

// ১. স্টেলথ প্লাগিন সক্রিয় করা (Cloudflare এর যম)
puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];

if (!url || !outputFile) process.exit(1);

(async () => {
  const browser = await puppeteer.launch({
    headless: "new",
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
      '--disable-blink-features=AutomationControlled', // খুব গুরুত্বপূর্ণ: অটোমেশন ফ্ল্যাগ লুকানো
      '--window-size=1920,1080',
      '--disable-infobars',
      '--exclude-switches=enable-automation'
    ]
  });

  try {
    const page = await browser.newPage();
    
    // ২. রিয়েলিস্টিক ইউজার এজেন্ট সেট করা
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    // ৩. ভিউপোর্ট সেট করা
    await page.setViewport({ width: 1366, height: 768 });

    // ৪. কাস্টম হেডার (ভাষা এবং সিকিউরিটি)
    await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9,bn;q=0.8',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Ch-Ua-Platform': '"Windows"',
        'Sec-Fetch-Site': 'none',
        'Sec-Fetch-User': '?1',
    });

    // ৫. রিসোর্স অপটিমাইজেশন (দ্রুত লোড হওয়ার জন্য ফন্ট ও মিডিয়া ব্লক)
    // তবে স্ক্রিপ্ট ব্লক করা যাবে না কারণ Cloudflare চেক স্ক্রিপ্টের মাধ্যমে হয়।
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        const resourceType = req.resourceType();
        if (['font', 'media', 'stylesheet'].includes(resourceType)) {
            req.abort();
        } else {
            req.continue();
        }
    });

    // ৬. পেজ লোড (টাইমআউট বাড়িয়ে ৬০ সেকেন্ড)
    try {
        // networkidle2 মানে অন্তত ২টা কানেকশন বাকি থাকা পর্যন্ত অপেক্ষা (Kaler Kantho এর মতো ভারী সাইটের জন্য ভালো)
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (error) {
        console.log("Nav timeout, continuing anyway...");
    }

    // ✅ ৭. Cloudflare/Turnstile বাইপাস অপেক্ষা (সবচেয়ে গুরুত্বপূর্ণ ধাপ)
    // পেজ লোড হওয়ার পর ৫ সেকেন্ড অপেক্ষা করবে যাতে চেকিং শেষ হয়।
    await new Promise(r => setTimeout(r, 5000));

    // ৮. মাউস মুভমেন্ট (হিউম্যান বিহেভিয়ার সিমুলেশন)
    try {
        await page.mouse.move(100, 100);
        await page.mouse.move(200, 200, { steps: 10 });
        await page.mouse.move(Math.floor(Math.random() * 500), Math.floor(Math.random() * 500));
    } catch(e) {}

    // ৯. স্মার্ট স্ক্রলিং (Lazy Load ইমেজ লোড করার জন্য)
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 200;
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;

                // ৩০০০ পিক্সেল বা পেজের শেষ পর্যন্ত স্ক্রল করবে
                if (totalHeight >= scrollHeight || totalHeight > 3000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 200);
        });
    });

    // ১০. ইমেজ অ্যাট্রিবিউট ফিক্স (Lazy Loading এর data-src বের করা)
    await page.evaluate(() => {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const hiddenSrc = img.getAttribute('data-original') || img.getAttribute('data-src') || img.getAttribute('data-srcset');
            if (hiddenSrc) img.setAttribute('src', hiddenSrc);
        });
    });
    
    // সবশেষে ২ সেকেন্ড বিশ্রাম
    await new Promise(r => setTimeout(r, 2000));

    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    
    await browser.close();
    process.exit(0);

  } catch (error) {
    console.error('Puppeteer Error:', error);
    await browser.close();
    process.exit(1);
  }
})();