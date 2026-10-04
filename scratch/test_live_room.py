import urllib.request
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Dev login
login_url = 'http://127.0.0.1:8001/dev_login.php?role=student'
resp = opener.open(login_url)
print('Login status:', resp.status)

# 2. Get live_room.php
room_url = 'http://127.0.0.1:8001/live_room.php?course_code=IT101'
resp2 = opener.open(room_url)
html = resp2.read().decode('utf-8')

print('Live room status:', resp2.status)
print('Timer element present?:', 'id="classroom-duration-timer"' in html)
print('Unli badge present?:', 'Walang Time Limit' in html)

m = re.search(r'id="plugnmeet-frame"[^>]*src="([^"]+)"', html)
if m:
    iframe_src = m.group(1)
    print('Iframe SRC found:', iframe_src[:100] + '...')
    
    try:
        iframe_resp = opener.open(iframe_src)
        print('Iframe target status:', iframe_resp.status)
        iframe_body = iframe_resp.read().decode('utf-8')
        print('Iframe body contains plugNmeet-app?:', 'plugNmeet-app' in iframe_body)
    except Exception as e:
        print('Iframe fetch error:', e)
else:
    print('No iframe src found!')
