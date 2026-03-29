import * as cheerio from 'cheerio';
import fs from 'fs';

const html = fs.readFileSync('out.html', 'utf8');
const $ = cheerio.load(html);

console.log('H2 links:', $('h2 a').length);
console.log('H3 links:', $('h3 a').length);
console.log('Story-card links:', $('.story-card a').length);
console.log('Data cards:', $('[data-testid="story-card"] a').length);
console.log('Data cards 2:', $('[data-testid="article-card"] a').length);
console.log('News links:', $('a[href*="/news/"]').length);

const a = $('a[href*="/news/"]').get().slice(0, 10);
console.log('Sample links class:');
a.forEach((el, i) => {
    console.log(i, $(el).attr('href'), $(el).attr('class') || 'NO-CLASS', $(el).parent().prop('tagName'), $(el).parent().attr('class') || 'NO-CLASS');
});
