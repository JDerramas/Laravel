// NPC ELMS Gateway Router
// Routes Cloudflare Tunnel (Port 8001) seamlessly to:
//   - PHP Server (Port 8000): Portal, LMS, Attendance, Database
//   - PlugNmeet Docker (Port 8085): Virtual Classroom API & UI
//   - NATS WebSocket (Port 8222): Real-Time Whiteboard & Chat Signaling

const http = require('http');

const PHP_PORT = 8000;
const PLUGNMEET_PORT = 8085;
const NATS_PORT = 8222;

const PNM_DIRECT_ENDPOINTS = new Set([
    'verifyToken',
    'uploadedFileMerge',
    'changeVisibility',
    'enableSipDialIn',
    'endRoom',
    'externalDisplayLink',
    'externalMediaPlayer',
    'getRoomFilesByType',
    'muteUnmuteTrack',
    'recording',
    'removeParticipant',
    'rtmp',
    'switchPresenter',
    'updateLockSettings'
]);

const server = http.createServer((req, res) => {
    const rawUrl = req.url || '/';
    const [pathOnly, queryString] = rawUrl.split('?');
    const query = queryString ? `?${queryString}` : '';

    let upstreamPort = PHP_PORT;
    let upstreamPath = rawUrl;

    const isPnmPrefix = pathOnly === '/plugnmeet' || pathOnly.startsWith('/plugnmeet/');
    const isAuthRoute = pathOnly.startsWith('/auth/');
    const isLocalesRoute = pathOnly.startsWith('/assets/locales/');
    
    // Check if it's a direct PNM API call (e.g. /api/verifyToken)
    let isPnmApi = false;
    if (pathOnly.startsWith('/api/')) {
        const sub = pathOnly.slice(5).split('/')[0];
        if (PNM_DIRECT_ENDPOINTS.has(sub) || sub.startsWith('insights') || sub.startsWith('polls') || sub.startsWith('waitingRoom') || sub.startsWith('whiteboard') || sub.startsWith('sharedNotepad') || sub.startsWith('ingress')) {
            isPnmApi = true;
        }
    }

    if (isPnmPrefix) {
        upstreamPort = PLUGNMEET_PORT;
        let stripped = pathOnly.replace(/^\/plugnmeet(?=\/|\?|$)/, '');
        if (!stripped) stripped = '/';
        upstreamPath = stripped + query;
    } else if (isAuthRoute || isPnmApi || isLocalesRoute) {
        upstreamPort = PLUGNMEET_PORT;
        upstreamPath = rawUrl;
    } else {
        upstreamPort = PHP_PORT;
        upstreamPath = rawUrl;
    }

    const headers = {
        ...req.headers,
        'x-forwarded-host': req.headers['x-forwarded-host'] || req.headers.host || '',
        'x-forwarded-proto': req.headers['x-forwarded-proto'] || 'http'
    };

    const upstreamReq = http.request({
        hostname: '127.0.0.1',
        port: upstreamPort,
        method: req.method,
        path: upstreamPath,
        headers
    }, (upstreamRes) => {
        res.writeHead(upstreamRes.statusCode || 502, upstreamRes.headers);
        upstreamRes.pipe(res);
    });

    upstreamReq.on('error', (err) => {
        console.error(`[Gateway Error] ${req.method} ${rawUrl} -> :${upstreamPort}: ${err.message}`);
        if (!res.headersSent) {
            res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
            res.end('Gateway Error: Upstream service not responding.');
        } else {
            res.destroy();
        }
    });

    req.pipe(upstreamReq);
});

// WebSocket Upgrade Handling for NATS Signaling
server.on('upgrade', (req, socket, head) => {
    const rawUrl = req.url || '';
    const isNats = rawUrl.startsWith('/nats') || rawUrl.startsWith('/plugnmeet/nats');

    if (!isNats) {
        socket.end('HTTP/1.1 404 Not Found\r\n\r\n');
        return;
    }

    let upstreamPath = rawUrl.replace(/^(\/plugnmeet)?\/nats(?=\/|\?|$)/, '');
    if (!upstreamPath || upstreamPath.startsWith('?')) {
        upstreamPath = '/' + upstreamPath;
    }

    const upstreamReq = http.request({
        hostname: '127.0.0.1',
        port: NATS_PORT,
        method: req.method,
        path: upstreamPath,
        headers: {
            ...req.headers,
            host: `127.0.0.1:${NATS_PORT}`
        }
    });

    upstreamReq.on('upgrade', (upstreamRes, upstreamSocket, upstreamHead) => {
        let responseHeaders = `HTTP/${upstreamRes.httpVersion} ${upstreamRes.statusCode} ${upstreamRes.statusMessage}\r\n`;
        for (let i = 0; i < upstreamRes.rawHeaders.length; i += 2) {
            responseHeaders += `${upstreamRes.rawHeaders[i]}: ${upstreamRes.rawHeaders[i + 1]}\r\n`;
        }
        socket.write(responseHeaders + '\r\n');
        if (upstreamHead.length) socket.write(upstreamHead);
        if (head.length) upstreamSocket.write(head);
        upstreamSocket.pipe(socket);
        socket.pipe(upstreamSocket);
    });

    upstreamReq.on('response', (upstreamRes) => {
        socket.end(`HTTP/1.1 ${upstreamRes.statusCode} ${upstreamRes.statusMessage}\r\n\r\n`);
        upstreamRes.resume();
    });

    upstreamReq.on('error', (err) => {
        console.error(`[Gateway WS Error] NATS proxy: ${err.message}`);
        socket.destroy();
    });

    socket.on('error', () => upstreamReq.destroy());
    upstreamReq.end();
});

const PORT = 8001;
server.listen(PORT, '127.0.0.1', () => {
    console.log(`[Gateway] NPC Unified Gateway Router running on http://127.0.0.1:${PORT}`);
    console.log(`  -> Port 8000: PHP Web Server (LMS & Portal)`);
    console.log(`  -> Port 8085: PlugNmeet (Classroom UI & API)`);
    console.log(`  -> Port 8222: NATS WebSocket (/nats)`);
});
