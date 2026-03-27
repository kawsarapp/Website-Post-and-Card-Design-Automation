import puppeteer from 'puppeteer';

(async () => {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    try {
        await page.goto('https://www.prothomalo.com/collection/latest', { waitUntil: 'networkidle2', timeout: 60000 });
        
        // Let's get all 'a' tags with href
        const links = await page.$$eval('a', anchors => {
            return anchors.map(a => {
                return {
                    href: a.href,
                    text: a.innerText.trim(),
                    parentClass: a.parentElement ? a.parentElement.className : '',
                    class: a.className
                };
            }).filter(item => item.href && item.text.length > 10);
        });
        
        console.log(`Found ${links.length} total links with text > 10 chars`);
        
        // Print distinct patterns
        const sampleLinks = links.filter(l => !l.href.includes('/collection/') && !l.href.includes('/topic/') && l.href.includes('prothomalo.com')).slice(0, 10);
        
        sampleLinks.forEach((l, i) => {
            console.log(`[${i}] ${l.text.substring(0,30)}... | ${l.href} | Parent: ${l.parentClass}`);
        });

    } catch (e) {
        console.error("Error:", e);
    } finally {
        await browser.close();
    }
})();
