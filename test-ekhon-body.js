import puppeteer from 'puppeteer';
const url = 'https://ekhon.tv/economy/export-import/69c6a25ea98154ff24f41aea'; // from previous run

(async () => {
  console.log("🚀 Starting browser for Ekhon TV Detail...");
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0');
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
    
    // Check possible article selectors
    const selectors = ['article', '.news-details', '.content-area', '.post-content', 'main p', '.text-gray-800'];
    for(const sel of selectors) {
        const text = await page.$eval(sel, el => el.innerText).catch(()=>null);
        if(text) console.log(`Selector [${sel}] found ${text.length} chars. Preview: ${text.substring(0, 100)}`);
    }

    // Print all <p> innerText
    const pTexts = await page.$$eval('p', ps => ps.map(p => p.innerText).filter(t => t.length > 50));
    console.log(`\nFound ${pTexts.length} <p> tags with text > 50 chars.`);
    if (pTexts.length > 0) {
        console.log("First <p>:", pTexts[0].substring(0, 100));
    }

    await browser.close();
  } catch (error) {
    console.error(error);
  }
})();
