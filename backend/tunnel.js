const ngrok = require('@ngrok/ngrok');

(async function () {
    try {
        const authtoken = '3INVCvR6jKA0iOtue641VBeD5yj_4dw5127TbUF776esvustG';
        const session = await new ngrok.SessionBuilder()
            .authtoken(authtoken)
            .connect();

        const listener = await session.httpEndpoint()
            .domain('twice-careless-occultist.ngrok-free.dev')
            .listen();

        await listener.forward('http://127.0.0.1:8000');

        console.log('\n=========================================');
        console.log('NGROK TUNNEL IS LIVE & RUNNING!');
        console.log('PUBLIC URL: ' + listener.url());
        console.log('FORWARDING TO: http://127.0.0.1:8000');
        console.log('=========================================\n');

        // Keep process running
        setInterval(() => {}, 1000 * 60 * 60);
    } catch (err) {
        console.error('Ngrok connection failed:', err);
    }
})();
