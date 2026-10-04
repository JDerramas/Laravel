import re

text = open('plugnmeet/assets/js/main-module.Cg1FQR-Q.js', encoding='utf-8').read()
matches = set(re.findall(r'G\([`\'"]([a-zA-Z0-9_\/]+)[`\'"]', text))
print('Endpoints called via G():', sorted(list(matches)))
