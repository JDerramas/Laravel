// native WebSocket is available globally in Node 24
const crypto = require('crypto');

function base64UrlEncode(str) {
    return Buffer.from(str).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function makeToken(apiKey, secret, room, identity, name) {
    const header = base64UrlEncode(JSON.stringify({ alg: "HS256", typ: "JWT" }));
    const now = Math.floor(Date.now() / 1000);
    const payload = base64UrlEncode(JSON.stringify({
        exp: now + 86400,
        iss: apiKey,
        sub: identity,
        nbf: now - 5,
        video: {
            room: room,
            roomJoin: true,
            canPublish: true,
            canSubscribe: true,
            canPublishData: true
        },
        name: name
    }));
    const sig = crypto.createHmac('sha256', secret).update(header + '.' + payload).digest();
    const encodedSig = base64UrlEncode(sig);
    return header + '.' + payload + '.' + encodedSig;
}

const token = makeToken("npc_elms_key", "npc_elms_secret_2026", "NPC-AIS201", "stu_202400192", "Lovi Student (2024-00192)");
console.log("Token:", token);

const ws = new WebSocket(`ws://localhost:7880/rtc?access_token=${token}&protocol=7`);
ws.onopen = () => {
    console.log("SUCCESS: LiveKit WebSocket connected successfully on port 7880!");
    ws.close();
    process.exit(0);
};
ws.onerror = (err) => {
    console.error("LiveKit connection error:", err);
    process.exit(1);
};
setTimeout(() => {
    console.log("Timeout waiting for WebSocket");
    process.exit(1);
}, 4000);
