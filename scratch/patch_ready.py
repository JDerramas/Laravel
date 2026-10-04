p = 'plugnmeet/assets/js/main-module.Cg1FQR-Q.js'
with open(p, 'r', encoding='utf-8') as f:
    text = f.read()

target = 'case`ready`:{i(!1);'
assert target in text, 'target not found'
replacement = 'case`ready`:{try{window.parent&&window.parent!==window&&window.parent.postMessage({type:`PNM_ROOM_READY`},`*`)}catch(e){}i(!1);'
text = text.replace(target, replacement, 1)

with open(p, 'w', encoding='utf-8') as f:
    f.write(text)

print('Successfully added PNM_ROOM_READY message to main-module!')
