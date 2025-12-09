import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import AdblockerPlugin from 'puppeteer-extra-plugin-adblocker';
import fs from 'fs';
import path from 'path';
import os from 'os';
import crypto from 'crypto';
import UserAgent from 'user-agents'; // র‍্যান্ডম ইউজার এজেন্টের জন্য

// 🔥 প্লাগিন সেটআপ (Cloudflare বাইপাস করার মূল চাবিকাঠি)
puppeteer.use(StealthPlugin());
puppeteer.use(AdblockerPlugin({ blockTrackers: true }));

// ইনপুট আর্গুমেন্ট গ্রহণ
const targetUrl = process.argv[2];
const outputFile = process.argv[3];

if (!targetUrl || !outputFile) {
    console.error("❌ Usage: node scraper-engine.js <url> <outputFile>");
    process.exit(1);
}

// 🔥 ইউনিক কুকি ফাইল লজিক (Concurrency Fix)
// URL থেকে ডোমেইন বের করে আলাদা হ্যাশ তৈরি হবে।
// ফলে প্রথম আলোর কুকি যুগান্তরে বা এক ইউজারের সেশন অন্য ইউজারে মিক্স হবে না।
const domainHash = crypto.createHash('md5').update(targetUrl).digest('hex');
const tempDir = os.tmpdir();
const cookiePath = path.join(tempDir, `cookie_${domainHash}.json`);

// হেল্পার ফাংশন: র‍্যান্ডম ডিলে (হিউম্যান বিহেভিয়ার)
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

(async () => {
    let browser;
    try {
        // ১. ব্রাউজার লঞ্চ কনফিগারেশন (Ultimate VPS Optimization)
        browser = await puppeteer.launch({
            headless: "new", // নতুন ফাস্ট হেডলেস মোড
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage', // VPS মেমোরি ক্রাশ ফিক্স
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-features=IsolateOrigins,site-per-process',
                '--blink-settings=imagesEnabled=true', // ইমেজ লোড হবে (URL পাওয়ার জন্য), কিন্তু রেন্ডার কমাবে
            ],
            ignoreDefaultArgs: ["--enable-automation"],
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined // যদি সিস্টেমে ক্রোম থাকে
        });

        const page = await browser.newPage();

        // ২. রিসোর্স অপটিমাইজেশন (Speed Boost 🚀)
        // ফন্ট, মিডিয়া এবং স্টাইলশিট ব্লক করা হবে যাতে পেজ দ্রুত লোড হয়
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const resourceType = req.resourceType();
            if (['font', 'media', 'stylesheet', 'other'].includes(resourceType)) {
                req.abort();
            } else {
                req.continue();
            }
        });

        // ৩. র‍্যান্ডম ইউজার এজেন্ট এবং ভিউপোর্ট
        const userAgent = new UserAgent({ deviceCategory: 'desktop' });
        await page.setUserAgent(userAgent.toString());
        await page.setViewport({
            width: 1920 + randomDelay(-100, 100),
            height: 1080 + randomDelay(-100, 100),
            deviceScaleFactor: 1,
            hasTouch: false,
            isLandscape: true,
            isMobile: false,
        });

        // ৪. কুকি রিস্টোর (আগের সেশন থাকলে ক্লাউডফ্লেয়ার বাইপাস সহজ হয়)
        if (fs.existsSync(cookiePath)) {
            try {
                const cookiesString = fs.readFileSync(cookiePath);
                const cookies = JSON.parse(cookiesString);
                // ভ্যালিডেশন: শুধু বর্তমান ডোমেইনের কুকি সেট হবে
                await page.setCookie(...cookies);
            } catch (e) {
                console.log("⚠️ Old cookie load failed, creating new session.");
            }
        }

        // ৫. পেজ লোড এবং নেভিগেশন
        try {
            await page.goto(targetUrl, { 
                waitUntil: 'domcontentloaded', 
                timeout: 60000 
            });
        } catch (e) {
            console.log("⚠️ Timeout hit, but proceeding to extract content...");
        }

        // ৬. Cloudflare / Bot Check বাইপাস লজিক
        const pageTitle = await page.title();
        if (pageTitle.includes("Just a moment") || pageTitle.includes("Cloudflare") || pageTitle.includes("Security Check")) {
            console.log("🛡️ Cloudflare detected! Waiting & Simulating Human...");
            
            // মাউস মুভমেন্ট সিমুলেশন
            await page.mouse.move(100, 100);
            await page.mouse.down();
            await page.mouse.move(200, 200);
            await page.mouse.up();
            
            await new Promise(r => setTimeout(r, 10000 + randomDelay(2000, 5000)));
        }

        // ৭. স্মার্ট অটো স্ক্রল (Lazy Load ইমেজ ও কন্টেন্ট পাওয়ার জন্য)
        await page.evaluate(async () => {
            await new Promise((resolve) => {
                let totalHeight = 0;
                const distance = 300; // একটু বড় স্টেপ
                const timer = setInterval(() => {
                    const scrollHeight = document.body.scrollHeight;
                    window.scrollBy(0, distance);
                    totalHeight += distance;
                    
                    // ১০,০০০ পিক্সেল বা পেজ শেষ হলে থামা
                    if (totalHeight >= scrollHeight || totalHeight > 10000) {
                        clearInterval(timer);
                        resolve();
                    }
                }, 150); // দ্রুত স্ক্রলিং
            });
        });

        // লোড হওয়ার পর সামান্য অপেক্ষা
        await new Promise(r => setTimeout(r, 2000));

        // ৮. নতুন কুকি সেভ (ভবিষ্যতের জন্য)
        try {
            const currentCookies = await page.cookies();
            fs.writeFileSync(cookiePath, JSON.stringify(currentCookies, null, 2));
        } catch (e) {}

        // ৯. ফাইনাল HTML এক্সট্রাকশন
        const html = await page.content();
        fs.writeFileSync(outputFile, html);

        await browser.close();
        process.exit(0); // Success

    } catch (error) {
        console.error("🔥 Critical Error:", error.message);
        if (browser) await browser.close();
        process.exit(1); // Error code specifically for PHP to catch
    }
})();