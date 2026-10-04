import re

text = open('plugnmeet/assets/js/main-module.Cg1FQR-Q.js', encoding='utf-8').read()
for m in re.finditer(r'<video|\bvideo\b', text):
    snippet = text[max(0, m.start()-50):m.end()+150]
    if 'track' in snippet.lower() or 'cam' in snippet.lower() or 'webcam' in snippet.lower():
        print(snippet.encode('ascii', 'ignore').decode('ascii'))
        print('-'*40)
