import puppeteer from 'puppeteer';

(async () => {
  console.log("🚀 Starting browser...");
  try {
    const browser = await puppeteer.launch({
        headless: "new",
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    // ব্রাউজারকে আসল মানুষ সাজানো
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    console.log("🌍 Going to Dhaka Post...");
    
    await page.goto('https://www.dhakapost.com/latest-news', {
        waitUntil: 'domcontentloaded', // শুধু কন্টেন্ট লোড হলেই হবে
        timeout: 60000 // ৬০ সেকেন্ড সময়
    });

    console.log("✅ Page loaded. Extracting title...");
    const title = await page.title();
    console.log("🎉 SUCCESS! Page Title: " + title);

    await browser.close();
  } catch (error) {
    console.error("❌ ERROR:", error);
  }
})();