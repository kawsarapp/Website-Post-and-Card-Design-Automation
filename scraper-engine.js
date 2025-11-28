import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];
const selector = process.argv[4]; 

if (!url || !outputFile) process.exit(1);

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
    
    // রিসোর্স ব্লক (ইমেজ/ফন্ট লোড হবে না - স্পিড বাড়বে)
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
            req.abort();
        } else {
            req.continue();
        }
    });

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    // ফাস্ট লোড
    try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {}

    // সিলেক্টরের জন্য অপেক্ষা
    try {
        if(selector) await page.waitForSelector(selector, { timeout: 8000 });
    } catch(e) {}

    // ফাস্ট স্ক্রলিং
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 500; 
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;
                if (totalHeight >= scrollHeight || totalHeight > 3000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 100);
        });
    });

    // ইমেজ সোর্স ফিক্স
    await page.evaluate(() => {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const hiddenSrc = img.getAttribute('data-src') || img.getAttribute('data-original');
            if (hiddenSrc) img.setAttribute('src', hiddenSrc);
        });
    });

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