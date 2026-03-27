import puppeteer from 'puppeteer';

(async () => {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    await page.goto('https://www.prothomalo.com/collection/latest', { waitUntil: 'networkidle2', timeout: 60000 });
    
    const links = await page.$$eval('a', as => as.map(a => a.href).filter(h => h.includes('.com/') && !h.includes('/collection/')).slice(0, 10));
    console.log("Prothom Alo Links:", links);
    
    const parents = await page.$$eval('a', as => as.map(a => a.parentElement.className).filter(c => c && c.length > 0).slice(0, 10));
    console.log("Prothom Alo Parent Classes:", parents);
    
    await browser.close();
})();
