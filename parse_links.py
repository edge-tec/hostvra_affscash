import re
with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

start = content.find('<span>Tracking</span>')
end = content.find('<span>Finance</span>')
mid = content[start:end]

links = re.findall(r'<a href="([^"]+)".*?>(.*?)</a>', mid, re.DOTALL)
for link, text in links:
    text = re.sub(r'<[^>]+>', '', text).strip()
    print(f"{link} : {text}")
