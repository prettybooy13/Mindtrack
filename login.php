<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if ($username === 'admin' && $password === 'admin1234') {
    $_SESSION['logged_in'] = true;
    header('Location: mindtrack.php');
    exit;
  } else {
    $error = 'Invalid username or password. Please try again.';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindTrack Login</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #cdebf1;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-box {
      background-color: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 360px;
    }

    .login-header {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 30px;
    }

    .logo {
      width: 40px;
      height: 40px;
      margin-right: 15px;
    }

    .header-text h2 {
      margin: 0;
      font-size: 24px;
      color: #2f3542;
    }

    .subtext {
      margin: 4px 0 0;
      font-size: 14px;
      color: #57606f;
      text-align: left;
    }

    .login-box input[type="text"],
    .login-box input[type="password"],
    .login-box button {
      display: block;
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 6px;
      font-size: 16px;
      border: 1px solid #ccc;
    }

    .login-box button {
      background-color: #1e90ff;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .login-box button:hover {
      background-color: #0077cc;
    }

    .error {
      color: #ff4757;
      font-size: 14px;
      margin-bottom: 12px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-header">
      <img src="waysidelogo.jpg" alt="Wayside Logo" class="logo">
      <div class="header-text">
        <h2>Login to MindTrack</h2>
        <p class="subtext">Wayside Psyche Resources Center</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
