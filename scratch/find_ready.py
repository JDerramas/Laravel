text = open('plugnmeet/assets/js/main-module.Cg1FQR-Q.js', encoding='utf-8').read()
idx = text.find('case`ready`')
print('idx:', idx)
print(text[max(0, idx-50):idx+300])
