import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import AdblockerPlugin from 'puppeteer-extra-plugin-adblocker';
import fs from 'fs';
import path from 'path';

// ১. প্লাগিন সেটআপ
puppeteer.use(StealthPlugin());
puppeteer.use(AdblockerPlugin({ blockTrackers: true }));

const url = process.argv[2];
const outputFile = process.argv[3];
const cookiePath = path.resolve('scraper_cookies.json'); // কুকি সেভ করার ফাইল

if (!url || !outputFile) {
    console.error("Usage: node scraper-engine.js <url> <outputFile>");
    process.exit(1);
}

// র‍্যান্ডম ফাংশন
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

(async () => {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: "new",
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-blink-features=AutomationControlled',
                '--disable-features=IsolateOrigins,site-per-process',
                '--disable-infobars',
                '--exclude-switches=enable-automation'
            ],
            ignoreDefaultArgs: ["--enable-automation"]
        });

        const page = await browser.newPage();
        
        // ২. ভিউপোর্ট এবং স্মার্ট হেডার
        await page.setViewport({ width: 1920 + randomDelay(-50, 50), height: 1080 + randomDelay(-50, 50) });
        
        const userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];
        await page.setUserAgent(userAgents[Math.floor(Math.random() * userAgents.length)]);

        // 🔥 ৩. কুকি লোড করা (যদি থাকে)
        if (fs.existsSync(cookiePath)) {
            const cookiesString = fs.readFileSync(cookiePath);
            const cookies = JSON.parse(cookiesString);
            // বর্তমান ডোমেইনের সাথে মিল রেখে কুকি সেট করা
            const domainCookies = cookies.filter(c => url.includes(c.domain.replace(/^\./, '')));
            if (domainCookies.length > 0) {
                await page.setCookie(...domainCookies);
                console.log("🍪 Loaded saved cookies for faster access.");
            }
        }

        // ৪. পেজ লোড
        try {
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        } catch (e) {
            console.log("⚠️ Timeout, continuing...");
        }

        // ৫. ক্লাউডফ্লেয়ার চ্যালেঞ্জ বাইপাস (স্মার্ট ওয়েট)
        const title = await page.title();
        if (title.includes("Just a moment") || title.includes("Cloudflare")) {
            console.log("⚠️ Cloudflare Challenge Detected! Solving...");
            
            // হিউম্যান বিহেভিয়ার (মাউস মুভমেন্ট)
            try {
                await page.mouse.move(randomDelay(100, 500), randomDelay(100, 500));
                await page.mouse.down();
                await new Promise(r => setTimeout(r, randomDelay(200, 800)));
                await page.mouse.up();
                await page.mouse.move(randomDelay(100, 500), randomDelay(100, 500));
            } catch(e) {}

            // ১৫ সেকেন্ড পর্যন্ত অপেক্ষা
            await new Promise(r => setTimeout(r, 15000));
        }

        // 🔥 ৬. সফল হলে কুকি সেভ করা (ভবিষ্যতের জন্য)
        const currentCookies = await page.cookies();
        fs.writeFileSync(cookiePath, JSON.stringify(currentCookies, null, 2));
        console.log("💾 Cookies updated/saved.");

        // ৭. হার্ডকোর স্ক্রলিং
        await page.evaluate(async () => {
            await new Promise((resolve) => {
                let totalHeight = 0;
                const distance = 200;
                const timer = setInterval(() => {
                    const scrollHeight = document.body.scrollHeight;
                    window.scrollBy(0, distance);
                    totalHeight += distance;
                    if (totalHeight >= scrollHeight || totalHeight > 15000) {
                        clearInterval(timer);
                        resolve();
                    }
                }, 100);
            });
        });

        await new Promise(r => setTimeout(r, 2000));

        // ৮. DOM ক্লিনিং ও ইমেজ ফিক্স
        await page.evaluate(() => {
            document.querySelectorAll('img').forEach(img => {
                const possibleAttrs = ['data-src', 'data-original', 'data-lazy-src', 'data-full-url', 'src'];
                let bestSrc = '';
                for (const attr of possibleAttrs) {
                    const val = img.getAttribute(attr);
                    if (val && val.length > bestSrc.length && !val.startsWith('data:')) bestSrc = val;
                }
                if (bestSrc) {
                    if (bestSrc.includes('?') && /\.(jpg|jpeg|png|webp)/i.test(bestSrc)) bestSrc = bestSrc.split('?')[0];
                    img.setAttribute('src', bestSrc);
                }
            });
            const junkSelectors = ['.advertisement', '.ads', '[class*="popup"]', 'iframe', 'header', 'footer'];
            junkSelectors.forEach(sel => document.querySelectorAll(sel).forEach(el => el.remove()));
        });

        // ৯. ফাইনাল সেভ
        const html = await page.content();
        fs.writeFileSync(outputFile, html);

        await browser.close();
        process.exit(0);

    } catch (error) {
        console.error('❌ Puppeteer Error:', error.message);
        if (browser) await browser.close();
        process.exit(1);
    }
})();