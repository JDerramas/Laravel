const ngrok = require('@ngrok/ngrok');
const fs = require('fs');
const http = require('http');
const path = require('path');

const envFile = path.join(__dirname, '.env');
if (fs.existsSync(envFile)) {
    process.loadEnvFile(envFile);
}

const appDomain = 'twice-careless-occultist.ngrok-free.dev';
const proxy = http.createServer((req, res) => {
    const requestPath = req.url || '/';
    const pathOnly = requestPath.split('?')[0];
    const isPlugNmeet = pathOnly === '/plugnmeet' || pathOnly.startsWith('/plugnmeet/');
    const upstreamPort = isPlugNmeet ? 8085 : 8000;
    let upstreamPath = requestPath;

    if (isPlugNmeet) {
        upstreamPath = requestPath.replace(/^\/plugnmeet(?=\/|\?|$)/, '');
        if (!upstreamPath || upstreamPath.startsWith('?')) {
            upstreamPath = '/' + upstreamPath;
        }
    }

    const headers = {
        ...req.headers,
        'x-forwarded-host': req.headers['x-forwarded-host'] || req.headers.host || '',
        'x-forwarded-proto': req.headers['x-forwarded-proto'] || 'http'
    };
    const upstream = http.request({
        hostname: '127.0.0.1',
        port: upstreamPort,
        method: req.method,
        path: upstreamPath,
        headers
    }, (upstreamRes) => {
        res.writeHead(upstreamRes.statusCode || 502, upstreamRes.headers);
        upstreamRes.pipe(res);
    });

    upstream.on('error', (error) => {
        console.error(`Proxy error for ${requestPath}:`, error.message);
        if (!res.headersSent) {
            res.writeHead(502, { 'Content-Type': 'text/plain' });
            res.end('Local service is unavailable.');
        } else {
            res.destroy();
        }
    });
    req.pipe(upstream);
});

proxy.on('upgrade', (req, socket, head) => {
    if (!(req.url || '').startsWith('/nats')) {
        socket.end('HTTP/1.1 404 Not Found\r\n\r\n');
        return;
    }

    let upstreamPath = (req.url || '/nats').replace(/^\/nats(?=\/|\?|$)/, '');
    if (!upstreamPath || upstreamPath.startsWith('?')) {
        upstreamPath = '/' + upstreamPath;
    }

    const upstream = http.request({
        hostname: '127.0.0.1',
        port: 8222,
        method: req.method,
        path: upstreamPath,
        headers: { ...req.headers, host: '127.0.0.1:8222' }
    });

    upstream.on('upgrade', (response, upstreamSocket, upstreamHead) => {
        let responseHeaders = `HTTP/${response.httpVersion} ${response.statusCode} ${response.statusMessage}\r\n`;
        for (let index = 0; index < response.rawHeaders.length; index += 2) {
            responseHeaders += `${response.rawHeaders[index]}: ${response.rawHeaders[index + 1]}\r\n`;
        }
        socket.write(responseHeaders + '\r\n');
        if (upstreamHead.length) socket.write(upstreamHead);
        if (head.length) upstreamSocket.write(head);
        upstreamSocket.pipe(socket);
        socket.pipe(upstreamSocket);
    });

    upstream.on('response', (response) => {
        socket.end(`HTTP/1.1 ${response.statusCode} ${response.statusMessage}\r\n\r\n`);
        response.resume();
    });
    upstream.on('error', (error) => {
        console.error('NATS WebSocket proxy error:', error.message);
        socket.destroy();
    });
    socket.on('error', () => upstream.destroy());
    upstream.end();
});

(async function () {
    try {
        await new Promise((resolve, reject) => {
            proxy.once('error', reject);
            proxy.listen(8001, '127.0.0.1', resolve);
        });
        console.log('Local ngrok router listening on 127.0.0.1:8001');

        const authtoken = process.env.NGROK_AUTHTOKEN;
        if (!authtoken) {
            throw new Error('NGROK_AUTHTOKEN is missing from app/.env or the process environment.');
        }
        console.log('Connecting to ngrok session...');

        // Use ngrok.forward() — confirmed working with @ngrok/ngrok v1.x
        const listener = await ngrok.forward({
            addr: 'http://127.0.0.1:8001',
            authtoken,
            domain: appDomain,
        });

        const publicUrl = listener.url();
        fs.writeFileSync(path.join(__dirname, 'public_url.txt'), publicUrl, 'utf-8');
        console.log(`Ngrok is live: ${publicUrl}`);
        console.log('Routes: / -> PHP, /plugnmeet -> PlugNmeet, /nats -> NATS WebSocket');
    } catch (err) {
        console.error('Ngrok connection failed:', err.message);
        fs.writeFileSync(path.join(__dirname, 'public_url.txt'), 'ERROR: ' + err.message, 'utf-8');
    }
})();
