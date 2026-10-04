<?php
$ch = curl_init('https://api.github.com/repos/mynaparrot/plugNmeet-server/releases');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
$releases = json_decode(curl_exec($ch), true) ?: [];

echo "=== PLUGNMEET SERVER RELEASES ===\n";
foreach (array_slice($releases, 0, 5) as $rel) {
    echo "Tag: " . $rel['tag_name'] . " - Assets count: " . count($rel['assets']) . "\n";
    foreach ($rel['assets'] as $asset) {
        echo "   -> " . $asset['name'] . "\n";
    }
}

$ch2 = curl_init('https://api.github.com/repos/mynaparrot/plugNmeet-client/releases');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
$cReleases = json_decode(curl_exec($ch2), true) ?: [];

echo "\n=== PLUGNMEET CLIENT RELEASES ===\n";
foreach (array_slice($cReleases, 0, 5) as $rel) {
    echo "Tag: " . $rel['tag_name'] . " - Assets count: " . count($rel['assets']) . "\n";
    foreach ($rel['assets'] as $asset) {
        echo "   -> " . $asset['name'] . " (" . $asset['browser_download_url'] . ")\n";
    }
}
