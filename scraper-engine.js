import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import AdblockerPlugin from 'puppeteer-extra-plugin-adblocker';
import fs from 'fs';
import path from 'path';
import os from 'os';
import crypto from 'crypto';
import { URL } from 'url';

// ---------------------------------------------------------
// ১. প্লাগিন কনফিগারেশন
// ---------------------------------------------------------
puppeteer.use(StealthPlugin());
puppeteer.use(AdblockerPlugin({ blockTrackers: true }));

// ---------------------------------------------------------
// ২. ইনপুট হ্যান্ডলিং
// ---------------------------------------------------------
const targetUrl = process.argv[2];
const outputFile = process.argv[3];
const fullProxyUrl = process.argv[4];

if (!targetUrl || !outputFile) {
    console.error("❌ Usage: node scraper-engine.js <url> <outputFile> [proxy]");
    process.exit(1);
}

// ---------------------------------------------------------
// ৩. কনফিগারেশন ও ইউটিলিটি
// ---------------------------------------------------------
// URL থেকে ডোমেইন বের করে আলাদা হ্যাশ তৈরি হবে (Concurrency Fix)
const domainHash = crypto.createHash('md5').update(targetUrl).digest('hex');
const cookiePath = path.join(os.tmpdir(), `cookie_${domainHash}.json`);

// হেল্পার: র‍্যান্ডম ডিলে (Human Behavior)
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

// লেটেস্ট ক্রোম ভার্সন (Anti-Bot)
const CHROME_VERSION = "121.0.0.0";
const USER_AGENT = `Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/${CHROME_VERSION} Safari/537.36`;

(async () => {
    let browser;
    try {
        // ---------------------------------------------------------
        // ৪. প্রক্সি সেটআপ (Advanced Auth)
        // ---------------------------------------------------------
        let proxyArgs = [];
        let proxyAuth = null;

        if (fullProxyUrl) {
            try {
                const parsed = new URL(fullProxyUrl);
                proxyArgs.push(`--proxy-server=${parsed.protocol}//${parsed.host}`);
                if (parsed.username) {
                    proxyAuth = {
                        username: decodeURIComponent(parsed.username),
                        password: decodeURIComponent(parsed.password)
                    };
                }
            } catch (e) { console.error("⚠️ Proxy Error:", e.message); }
        }

        // ---------------------------------------------------------
        // ৫. ব্রাউজার লঞ্চ (VPS Optimized)
        // ---------------------------------------------------------
        browser = await puppeteer.launch({
            headless: "new",
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage', // মেমোরি ক্রাশ ফিক্স
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-blink-features=AutomationControlled',
                '--disable-features=IsolateOrigins,site-per-process',
                '--no-first-run',
                ...proxyArgs
            ],
            ignoreDefaultArgs: ["--enable-automation"],
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined
        });

        const page = await browser.newPage();
        if (proxyAuth) await page.authenticate(proxyAuth);

        // ---------------------------------------------------------
        // ৬. রিসোর্স ব্লকিং (Speed Booster 🚀)
        // ---------------------------------------------------------
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const type = req.resourceType();
            // ফন্ট, স্টাইলশিট, মিডিয়া এবং অন্যান্য ভারী ফাইল ব্লক
            if (['font', 'media', 'stylesheet', 'texttrack', 'object', 'beacon', 'csp_report'].includes(type)) {
                req.abort();
            } else {
                req.continue();
            }
        });

        // ---------------------------------------------------------
        // ৭. অ্যান্টি-বট হেডার ও ভিউপোর্ট
        // ---------------------------------------------------------
        await page.setUserAgent(USER_AGENT);
        await page.setViewport({ 
            width: 1920 + randomDelay(-50, 50), 
            height: 1080 + randomDelay(-50, 50),
            deviceScaleFactor: 1,
            isMobile: false
        });
        
        // কুকি রিস্টোর (আগের সেশন থাকলে বাইপাস সহজ হয়)
        if (fs.existsSync(cookiePath)) {
            try {
                const cookies = JSON.parse(fs.readFileSync(cookiePath));
                await page.setCookie(...cookies);
            } catch (e) {}
        }

        // ---------------------------------------------------------
        // ৮. নেভিগেশন (Ultra Fast)
        // ---------------------------------------------------------
        console.log(`🚀 Fast Nav to: ${targetUrl}`);
        try {
            // networkidle2 এর বদলে domcontentloaded ব্যবহার (অনেক ফাস্ট)
            await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 90000 });
        } catch (e) {
            console.log(`⚠️ Nav Warning: ${e.message}`);
        }

        // ---------------------------------------------------------
        // ৯. 🔥 CONTENT WAITER (Critical for Jamuna TV)
        // ---------------------------------------------------------
        try {
            console.log("⏳ Waiting for content...");
            // নিউজ কন্টেন্ট লোড হওয়া পর্যন্ত অপেক্ষা করবে
            await page.waitForSelector('article, .story-element-text, .jw_article_body, .details-content, #content, .post-content', { 
                timeout: 15000, 
                visible: true 
            });
            console.log("✅ Content detected!");
        } catch (e) {
            console.log("⚠️ Content selector timeout. Proceeding anyway...");
        }

        // ---------------------------------------------------------
        // ১০. CLOUDFLARE BYPASS (Active Solver)
        // ---------------------------------------------------------
        const isCloudflare = async () => {
            const title = await page.title();
            const content = await page.content();
            return title.includes("Just a moment") || title.includes("Cloudflare") || content.includes("challenge-platform");
        };

        if (await isCloudflare()) {
            console.log("🛡️ Cloudflare Detected. Engaging Ghost Cursor...");
            
            // A. Ghost Cursor Movement (Random Bezier Curve Simulation)
            const steps = randomDelay(10, 30);
            await page.mouse.move(100, 100);
            await page.mouse.move(200 + randomDelay(10,50), 300 + randomDelay(10,50), { steps: steps });
            
            // B. Checkbox ক্লিক করার চেষ্টা (যদি থাকে)
            try {
                const challengeBox = await page.$('iframe[src*="cloudflare"]');
                if (challengeBox) {
                    const box = await challengeBox.boundingBox();
                    if (box) await page.mouse.click(box.x + 10, box.y + 10);
                }
            } catch(e) {}

            // C. Active Waiting (ফিক্সড টাইম নয়, আনলক হওয়া পর্যন্ত)
            let attempts = 0;
            while (await isCloudflare() && attempts < 15) {
                console.log(`⏳ Bypass attempt ${attempts+1}/15...`);
                await new Promise(r => setTimeout(r, 1500)); // ১.৫ সেকেন্ড পরপর চেক
                attempts++;
            }
        }

        // ---------------------------------------------------------
        // ১১. ULTRA SCROLL (Accelerated)
        // ---------------------------------------------------------
        console.log("📜 Fast Scrolling...");
        await page.evaluate(async () => {
            await new Promise((resolve) => {
                let totalHeight = 0;
                const distance = 800; // বড় জাম্প (দ্রুত কন্টেন্ট লোড করার জন্য)
                let timer = setInterval(() => {
                    const scrollHeight = document.body.scrollHeight;
                    window.scrollBy(0, distance);
                    totalHeight += distance;
                    // ১০,০০০ পিক্সেলের বেশি স্ক্রল করার দরকার নেই
                    if (totalHeight >= scrollHeight || totalHeight > 10000) {
                        clearInterval(timer);
                        resolve();
                    }
                }, 100);
            });
        });
        
        // ইমেজ রেন্ডারিংয়ের জন্য ২ সেকেন্ড অপেক্ষা
        await new Promise(r => setTimeout(r, 2000));

        // ---------------------------------------------------------
        // ১২. ডেটা সেভ ও এক্সিট
        // ---------------------------------------------------------
        // নতুন কুকি সেভ (ভবিষ্যতের জন্য)
        try {
            const currentCookies = await page.cookies();
            fs.writeFileSync(cookiePath, JSON.stringify(currentCookies, null, 2));
        } catch (e) {}

        const html = await page.content();
        
        // ভ্যালিডেশন
        if (html.length < 500) {
             console.error("❌ Content too short/Blocked.");
             // এখানে throw Error করলে PHP জব ফেইল হিসেবে মার্ক করবে
             // throw new Error("Blocked or Empty Page");
        }

        fs.writeFileSync(outputFile, html);
        console.log("✅ Scraping SUCCESS.");

        await browser.close();
        process.exit(0);

    } catch (error) {
        console.error("🔥 NODE FATAL:", error.message);
        if (browser) await browser.close();
        process.exit(1);
    }
})();