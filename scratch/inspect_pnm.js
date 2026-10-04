const fs = require('fs');
const s = fs.readFileSync('c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/plugnmeet/assets/chunks/pnm.CwbQqutO.js', 'utf8');

// Find occurrences of WebSocket creation
let pos = 0;
while ((pos = s.indexOf('new WebSocket', pos)) !== -1) {
    console.log('--- new WebSocket at', pos, '---');
    console.log(s.slice(Math.max(0, pos - 150), Math.min(s.length, pos + 150)));
    pos += 13;
}

// Find occurrences of RTCPeerConnection
pos = 0;
while ((pos = s.indexOf('RTCPeerConnection', pos)) !== -1) {
    console.log('--- RTCPeerConnection at', pos, '---');
    console.log(s.slice(Math.max(0, pos - 150), Math.min(s.length, pos + 150)));
    pos += 17;
}
