import urllib.request, urllib.parse, re, json, http.cookiejar

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. GET login page
resp = opener.open('http://localhost:8000/login')
html = resp.read().decode('utf-8')
m = re.search(r'csrf-token" content="([^"]+)"', html)
token = m.group(1) if m else None
print('CSRF Token found:', token[:15] if token else 'None')

# 2. POST login as JSON
post_data = json.dumps({'identifier': 'admin@citycollegeoftagaytay.edu.ph', 'password': 'password', 'remember': False}).encode('utf-8')
req = urllib.request.Request('http://localhost:8000/login', data=post_data, headers={
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': token,
    'X-Requested-With': 'XMLHttpRequest'
})
try:
    res = opener.open(req)
    print('Status:', res.status)
    print('Body:', res.read().decode('utf-8'))
except urllib.error.HTTPError as e:
    print('HTTPError:', e.code)
    print('Error Body:', e.read().decode('utf-8'))
