<?php
$ch = curl_init("http://127.0.0.1:8000/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'identifier' => 'jderramas251505@navotaspolytechniccollege.edu.ph',
    'password' => 'faculty'
]));
curl_setopt($ch, CURLOPT_HEADER, true);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Faculty Form Login HTTP Code: $code\n";
if (strpos($resp, 'Location: /teacher/index.php') !== false) {
    echo "SUCCESS: Faculty redirected to /teacher/index.php!\n";
} else {
    echo "Output: " . substr($resp, 0, 500) . "\n";
}
