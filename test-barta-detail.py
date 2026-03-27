from curl_cffi import requests
from bs4 import BeautifulSoup

url = 'https://bartabazar.com/news/291638/'
print("Fetching:", url)
try:
    r = requests.get(url, impersonate="chrome120")
    print("Status code:", r.status_code)
    soup = BeautifulSoup(r.text, 'html.parser')
    
    # meta images
    for n in soup.select('meta[property="og:image"], meta[name="twitter:image"], meta[itemprop="image"]'):
        print("Meta Image:", n.get('content'))
        
    # article images
    for img in soup.select('article img, .post-content img, .details img, .news-details img, .hdl img, .barta-content img, img'):
        src = img.get('src') or img.get('data-src')
        if src and len(str(src)) > 20:
            print("Article Image:", src)
            
except Exception as e:
    print("Error:", e)
