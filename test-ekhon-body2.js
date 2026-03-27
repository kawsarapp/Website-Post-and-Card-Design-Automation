import puppeteer from 'puppeteer';
const url = 'https://ekhon.tv/economy/export-import/69c6a25ea98154ff24f41aea'; 

(async () => {
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
    const page = await browser.newPage();
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
    
    // Find the p tag with mb-3 text-justify and get its parent's classes
    const parentClass = await page.$eval('p.mb-3.text-justify', el => el.parentElement.className).catch(()=>"Not found");
    console.log("Parent Class:", parentClass);
    
    // Check main container
    const mainHtml = await page.$eval('.content-area', el => el.innerHTML).catch(()=>"No .content-area");
    console.log(".content-area length:", mainHtml.length);
    
    // Print body container classes
    const classes = await page.$$eval('div', divs => divs.filter(d => d.innerText.length > 500).map(d => d.className));
    console.log("Divs with >500 text chars:", [...new Set(classes)]);
    
    await browser.close();
  } catch (error) {
    console.error(error);
  }
})();
