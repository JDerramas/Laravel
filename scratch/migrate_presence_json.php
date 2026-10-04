<?php
$f = __DIR__ . '/../backend/elms_presence.json';
$content = file_get_contents($f);
$content = str_replace('"google_meet_verified"', '"plugnmeet_verified"', $content);
file_put_contents($f, $content);
echo "Migrated backend/elms_presence.json successfully.\n";
