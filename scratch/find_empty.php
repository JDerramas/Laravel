<?php
$empty = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..')) as $f) {
    if ($f->isFile() && $f->getSize() === 0) {
        $p = str_replace('\\', '/', $f->getPathname());
        if (!str_contains($p, '/.git/') && !str_contains($p, '/node_modules/')) {
            $empty[] = $p;
        }
    }
}
echo "Empty files found:\n" . implode("\n", $empty) . "\n";
