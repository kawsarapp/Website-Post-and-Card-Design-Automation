import puppeteer from 'puppeteer';

(async () => {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    
    // ITVBD Test
    console.log("=== ITVBD ===");
    try {
        await page.goto('https://www.itvbd.com', { waitUntil: 'domcontentloaded', timeout: 30000 });
        let itvLinks = await page.$$eval('a', anchors => anchors.map(a => a.href).filter(h => h.includes('.com/') && h.length > 30).slice(0, 5));
        console.log("ITVBD Sample Links:", itvLinks);
        
        let itvParents = await page.$$eval('a', anchors => anchors.map(a => a.parentElement.className).filter(c => c && c.length > 0).slice(0, 5));
        console.log("ITVBD Link Parent Classes:", itvParents);
    } catch (e) { console.log("ITVBD Error:", e.message); }

    // Channel24BD Test
    console.log("\n=== Channel24BD ===");
    try {
        await page.goto('https://www.channel24bd.tv/archives', { waitUntil: 'domcontentloaded', timeout: 30000 });
        let c24Links = await page.$$eval('a', anchors => anchors.map(a => a.href).filter(h => h.includes('.tv/')).slice(0, 5));
        console.log("Channel24BD Sample Links:", c24Links);
        
        let c24Parents = await page.$$eval('a', anchors => anchors.map(a => a.parentElement.className).filter(c => c && c.length > 0).slice(0, 5));
        console.log("Channel24BD Parent Classes:", c24Parents);
    } catch (e) { console.log("Channel24BD Error:", e.message); }
    
    await browser.close();
})();
