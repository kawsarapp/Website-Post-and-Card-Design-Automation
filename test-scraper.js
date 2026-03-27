import puppeteer from 'puppeteer';

(async () => {
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    await page.goto('https://ekhon.tv/recent', { waitUntil: 'networkidle2' });
    
    const links = await page.$$eval('a', as => as.map(a => ({href: a.href, text: a.innerText.replace(/\s+/g,' ').trim()})).filter(a => a.href && a.text.length > 20));
    console.log("Ekhon TV Articles:");
    links.slice(0, 10).forEach(l => console.log(l.href, "-", l.text));
    
    await browser.close();
  } catch (error) {
    console.error(error);
  }
})();