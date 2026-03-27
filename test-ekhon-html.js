import puppeteer from 'puppeteer';
const url = 'https://ekhon.tv/economy/export-import/69c6a25ea98154ff24f41aea'; 

(async () => {
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
    
    const html = await page.$eval('article', el => el.innerHTML).catch(()=>"No article");
    console.log("Article HTML length:", html.length);
    console.log(html.substring(0, 1000));
    
    await browser.close();
  } catch (error) {
    console.error(error);
  }
})();
