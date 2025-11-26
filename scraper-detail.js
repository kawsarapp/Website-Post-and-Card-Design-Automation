import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';
puppeteer.use(StealthPlugin());
const url = process.argv[2];
const outputFile = process.argv[3];
(async () => {
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-blink-features=AutomationControlled']});
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders({'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'});
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await new Promise(r => setTimeout(r, 5000)); 
    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    await browser.close();
    process.exit(0);
  } catch (e) { process.exit(1); }
})();