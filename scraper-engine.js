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
puppeteer.use(AdblockerPlugin({ blockTrackers: true, blockTrackersAndAnnoyances: true }));

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
const domainHash = crypto.createHash('md5').update(targetUrl).digest('hex');
const cookiePath = path.join(os.tmpdir(), `cookie_${domainHash}.json`);
const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

// Rotating User Agents (Latest)
const USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
];

(async () => {
    let browser;
    try {
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
        // ৪. ব্রাউজার লঞ্চ (Production Military Grade)
        // ---------------------------------------------------------
        browser = await puppeteer.launch({
            headless: "new",
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage', // RAM Fix
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-blink-features=AutomationControlled',
                '--disable-features=IsolateOrigins,site-per-process', // Iframe CORS bypass
                '--disable-site-isolation-trials',
                '--no-first-run',
                '--ignore-certificate-errors',
                ...proxyArgs
            ],
            ignoreDefaultArgs: ["--enable-automation"],
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined
        });

        const page = await browser.newPage();
        if (proxyAuth) await page.authenticate(proxyAuth);

        // 🔥 5. DEEP STEALTH INJECTION (Anti-Bot Bypass)
        await page.evaluateOnNewDocument(() => {
            // Remove Webdriver
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            // Mock Plugins
            Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
            // Mock Languages
            Object.defineProperty(navigator, 'languages', { get: () => ['bn-BD', 'bn', 'en-US', 'en'] });
            // Chrome Runtime
            window.chrome = { runtime: {} };
        });

        // ---------------------------------------------------------
        // ৬. স্মার্ট রিসোর্স ব্লকিং (Speed Booster 🚀)
        // ---------------------------------------------------------
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const type = req.resourceType();
            // আমরা ইমেজ অ্যালাও করব যাতে lazy-loading স্ক্রিপ্টগুলো ঠিকমতো ছবি বসাতে পারে
            if (['font', 'media', 'stylesheet', 'texttrack', 'object', 'beacon', 'csp_report'].includes(type) && !req.url().includes('cloudflare')) {
                req.abort();
            } else {
                req.continue();
            }
        });

        await page.setUserAgent(USER_AGENTS[Math.floor(Math.random() * USER_AGENTS.length)]);
        await page.setViewport({ 
            width: 1920 + randomDelay(-50, 50), 
            height: 1080 + randomDelay(-50, 50),
            deviceScaleFactor: 1,
            isMobile: false
        });
        
        if (fs.existsSync(cookiePath)) {
            try {
                const cookies = JSON.parse(fs.readFileSync(cookiePath));
                await page.setCookie(...cookies);
            } catch (e) {}
        }

        // ---------------------------------------------------------
        // ৭. নেভিগেশন (Ultra Fast)
        // ---------------------------------------------------------
        console.log(`🚀 Navigating to: ${targetUrl}`);
        try {
            await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 90000 });
        } catch (e) {
            console.log(`⚠️ Nav Warning: ${e.message}`);
        }

        // ---------------------------------------------------------
        // ৮. CLOUDFLARE/DATADOME BYPASS (Active Solver)
        // ---------------------------------------------------------
        const checkProtection = async () => {
            const title = await page.title();
            const content = await page.content();
            return title.includes("Just a moment") || 
                   title.includes("Cloudflare") || 
                   content.includes("challenge-platform") ||
                   content.includes("datadome");
        };

        if (await checkProtection()) {
            console.log("🛡️ Protection Detected. Engaging Human Simulator...");
            
            // Human Mouse Movement Simulation
            await page.mouse.move(randomDelay(100, 300), randomDelay(100, 300));
            await new Promise(r => setTimeout(r, randomDelay(500, 1000)));
            await page.mouse.move(randomDelay(400, 600), randomDelay(400, 600), { steps: randomDelay(15, 30) });
            await page.mouse.click(randomDelay(400, 600), randomDelay(400, 600)); // Random click
            
            // Checkbox clicking logic
            try {
                const frames = page.frames();
                for (let frame of frames) {
                    const cfBox = await frame.$('.ctp-checkbox-label, input[type="checkbox"]');
                    if (cfBox) {
                        await cfBox.click();
                        console.log("🖱️ Clicked Cloudflare Checkbox!");
                    }
                }
            } catch(e) {}

            let attempts = 0;
            while (await checkProtection() && attempts < 20) {
                console.log(`⏳ Bypassing... attempt ${attempts+1}/20`);
                await new Promise(r => setTimeout(r, 2000)); 
                attempts++;
            }
        }

        // ---------------------------------------------------------
        // ৯. CONTENT WAITER & SCROLLER
        // ---------------------------------------------------------
        try {
            await page.waitForSelector('article, .story-element-text, .jw_article_body, .details-content, #content, .post-content, h1', { 
                timeout: 10000, visible: true 
            });
        } catch (e) {}

        console.log("📜 Executing Smart Scroll...");
        await page.evaluate(async () => {
            await new Promise((resolve) => {
                let totalHeight = 0;
                const distance = 600; 
                let timer = setInterval(() => {
                    window.scrollBy(0, distance);
                    totalHeight += distance;
                    if (totalHeight >= document.body.scrollHeight || totalHeight > 12000) {
                        clearInterval(timer);
                        resolve();
                    }
                }, 150); // একটু ধীরে স্ক্রল, যাতে lazy image লোড হওয়ার সময় পায়
            });
        });
        
        await new Promise(r => setTimeout(r, 2000));

        // ---------------------------------------------------------
        // ১০. 🔥 MAGIC LAZY-LOAD FIXER (Game Changer)
        // ---------------------------------------------------------
        console.log("🪄 Forcing Lazy-loaded images to visible...");
        await page.evaluate(() => {
            document.querySelectorAll('img').forEach(img => {
                // যত রকমের লেজি-লোড অ্যাট্রিবিউট আছে, সব খুঁজে বের করবে
                const realSrc = img.getAttribute('data-src') || 
                                img.getAttribute('data-original') || 
                                img.getAttribute('data-lazy-src') ||
                                img.getAttribute('lazy-src');
                
                if (realSrc && realSrc.length > 10) {
                    img.setAttribute('src', realSrc); // আসল সোর্স বসিয়ে দেওয়া হলো
                }
            });

            // RAM বাঁচানোর জন্য পেজের ফালতু ভিডিও/SVG JS লেভেলেই ডিলিট
            document.querySelectorAll('video, svg, iframe.ads, .advertisement').forEach(el => el.remove());
        });

        // ---------------------------------------------------------
        // ১১. ডেটা সেভ ও এক্সিট
        // ---------------------------------------------------------
        try {
            const currentCookies = await page.cookies();
            fs.writeFileSync(cookiePath, JSON.stringify(currentCookies, null, 2));
        } catch (e) {}

        const html = await page.content();
        
        if (html.length < 500) {
             console.error("❌ Content too short/Blocked.");
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