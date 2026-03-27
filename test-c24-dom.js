import puppeteer from 'puppeteer';

(async () => {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    
    console.log("=== Channel24BD ===");
    try {
        await page.goto('https://www.channel24bd.tv/archives', { waitUntil: 'networkidle2', timeout: 30000 });
        
        let allLinks = await page.$$eval('a', anchors => {
            return anchors.filter(a => a.href && a.innerText.length > 20)
                          .map(a => ({href: a.href, text: a.innerText, parent: a.parentElement ? a.parentElement.className : 'NONE'}))
                          .slice(0, 10);
        });
        
        console.log("Channel24BD News Links:");
        allLinks.forEach(l => console.log(`[${l.parent}] ${l.text.substring(0,30)} - ${l.href}`));
        
    } catch (e) { console.log("Channel24BD Error:", e.message); }
    
    await browser.close();
})();
