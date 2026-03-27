import sys
import io
import time
from curl_cffi import requests

# ==========================================
# 🔥 UNIVERSAL ENCODING FIX (Windows/Linux)
# ==========================================
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')
else:
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

try:
    target_url = sys.argv[1]
except IndexError:
    print("Error: No URL provided")
    sys.exit(1)

proxy_url = sys.argv[2] if len(sys.argv) > 2 else None

# ==========================================
# 🚀 ADVANCED REQUEST WITH RETRY (Production Grade)
# ==========================================
def get_html(url, proxy=None, retries=2):
    proxies = {"http": proxy, "https": proxy} if proxy else None
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language': 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    }

    for attempt in range(retries):
        try:
            response = requests.get(
                url, impersonate="chrome120", timeout=30, proxies=proxies, headers=headers, verify=False
            )
            if response.status_code == 200:
                if response.encoding is None or response.encoding == 'ISO-8859-1':
                    response.encoding = response.apparent_encoding
                return response.text
            elif response.status_code in [403, 429, 503]:
                time.sleep(2) # Blocked, wait and retry
        except Exception:
            time.sleep(1)
    return ""

html = get_html(target_url, proxy_url)
print(html)
