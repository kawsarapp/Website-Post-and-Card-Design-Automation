import puppeteer from 'puppeteer';

(async () => {
  console.log("🚀 Starting browser for Bartabazar test...");
  try {
    const browser = await puppeteer.launch({
        headless: "new",
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0');

    await page.goto('https://bartabazar.com/news/291638/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    
    // get meta tags
    const metaImages = await page.$$eval('meta[property="og:image"], meta[name="twitter:image"], meta[itemprop="image"], link[rel="image_src"]', els => els.map(e => e.content || e.href));
    console.log("Meta Images:", metaImages);
    
    // get article images
    const articleImages = await page.$$eval('article img, .post-content img, .details img, .news-details img, img', els => els.map(e => e.src || e.getAttribute('data-src')).slice(0, 5));
    console.log("Article Images:", articleImages);
    
    await browser.close();
  } catch (error) {
    console.error("❌ ERROR:", error);
  }
})();
