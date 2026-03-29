import puppeteer from 'puppeteer';
import fs from 'fs';

(async () => {
    console.log("Launching puppeteer...");
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36');
    console.log("Going to URL...");
    await page.goto('https://www.prothomalo.com/latest', {waitUntil: 'networkidle2'});
    console.log("Getting content...");
    const html = await page.content();
    fs.writeFileSync('out.html', html);
    await browser.close();
    console.log('Saved to out.html');
})();
