import puppeteer from 'puppeteer';

(async () => {
  const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox']});
  const page = await browser.newPage();
  await page.setUserAgent('Mozilla/5.0');
  
  // Test Asia Post
  console.log("Fetching Asia Post...");
  await page.goto('https://www.asia-post.com/all-news', { waitUntil: 'domcontentloaded', timeout: 30000 });
  
  let tags = await page.$$eval('.col-md-12', els => els.length);
  console.log("Asia Post .col-md-12 count:", tags);
  
  let validLinks = await page.$$eval('a', as => as.filter(a => a.href.includes('/news/') || a.href.includes('/country-news/')).map(a => a.className || a.parentElement.className).slice(0, 5));
  console.log("Asia Post typical link parent classes:", validLinks);

  // Test Prothom Alo
  console.log("\nFetching Prothom Alo...");
  await page.goto('https://www.prothomalo.com/collection/latest', { waitUntil: 'domcontentloaded', timeout: 30000 });
  
  let paTags = await page.$$eval('div.news', els => els.length);
  console.log("Prothom Alo div.news count:", paTags);
  
  let paLinks = await page.$$eval('a', as => as.filter(a => a.href && a.innerText.length > 20 && !a.href.includes('/collection/')).map(a => ({href: a.href, parentClass: a.parentElement.className})).slice(0, 5));
  console.log("Prothom Alo typical news links:", paLinks);
  
  await browser.close();
})();
