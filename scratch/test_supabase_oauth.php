<?php
require_once __DIR__ . '/../includes/supabase_helper.php';
$env = loadEnv();
$url = 'https://woscjghjrleqxxyezlyu.supabase.co/auth/v1/authorize?provider=google&redirect_to=' . urlencode('http://localhost:8000/auth_callback.php');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $env['SUPABASE_KEY']]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $code\n";
echo "Response headers / body:\n" . substr($res, 0, 500) . "\n";
