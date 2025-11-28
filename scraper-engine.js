import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';
puppeteer.use(StealthPlugin());
const url = process.argv[2];
const outputFile = process.argv[3];
const selector = process.argv[4];
if(!url||!outputFile) process.exit(1);
(async()=>{
  const browser = await puppeteer.launch({
    headless: "new",
    args: ['--no-sandbox','--disable-setuid-sandbox','--disable-dev-shm-usage','--disable-accelerated-2d-canvas','--disable-gpu','--window-size=1920,1080']
  });
  try{
    const page = await browser.newPage();
    // Resource Blocking for Speed
    await page.setRequestInterception(true);
    page.on('request',(req)=>{
        if(['image','stylesheet','font','media'].includes(req.resourceType())) req.abort();
        else req.continue();
    });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    try{await page.goto(url,{waitUntil:'domcontentloaded',timeout:60000});}catch(e){}
    try{if(selector) await page.waitForSelector(selector,{timeout:8000});}catch(e){}
    
    // Fast Scroll
    await page.evaluate(async()=>{
        await new Promise((resolve)=>{
            let totalHeight=0;const distance=600;
            const timer=setInterval(()=>{
                const scrollHeight=document.body.scrollHeight;
                window.scrollBy(0,distance);totalHeight+=distance;
                if(totalHeight>=scrollHeight||totalHeight>4000){clearInterval(timer);resolve();}
            },100);
        });
    });
    // Image Fix
    await page.evaluate(()=>{
        document.querySelectorAll('img').forEach(img=>{
            const h=img.getAttribute('data-src')||img.getAttribute('data-original')||img.getAttribute('data-srcset');
            if(h)img.setAttribute('src',h);
        });
    });
    const html=await page.content();
    fs.writeFileSync(outputFile,html);
    await browser.close();process.exit(0);
  }catch(e){console.error(e);await browser.close();process.exit(1);}
})();