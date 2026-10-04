import re

text = open('plugnmeet/assets/js/main-module.Cg1FQR-Q.js', encoding='utf-8').read()
matches = [m.start() for m in re.finditer(r'transform', text, re.IGNORECASE)]
print('Total matches in main-module:', len(matches))
for idx in matches:
    snippet = text[max(0, idx-60):idx+100]
    if 'video' in snippet or 'cam' in snippet or 'scale' in snippet or 'rotate' in snippet:
        print(snippet.encode('ascii', 'ignore').decode('ascii'))
