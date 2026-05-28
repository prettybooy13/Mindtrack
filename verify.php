<?php
session_start();
require 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $code = trim($_POST["code"]);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE users SET verified = 1, verification_code = NULL WHERE id = ?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();

        $_SESSION["user_id"] = $row['id'];
        header("Location: book.php");
        exit;
    } else {
        $message = "❌ Incorrect code or email. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mindtrack Verification</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      height: 100vh; overflow: hidden;
      display: flex; flex-direction: column;
      position: relative;
    }
    .bg-blur {
      position: absolute; top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('bg.jpg') no-repeat center center fixed;
      background-size: cover;
      filter: blur(6px); z-index: -1;
    }
    header {
      background-color: #336b91; padding: 15px;
      color: white; font-weight: bold;
      font-size: 18px; position: relative; z-index: 1;
    }
    .verify-container {
      flex: 1; display: flex;
      justify-content: center; align-items: center;
      position: relative; z-index: 1;
    }
    .verify-box {
      background: rgba(51, 107, 145, 0.9);
      padding: 30px; border-radius: 8px;
      width: 320px; text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .verify-box h2 { color: #fff; margin-bottom: 20px; }
    .verify-box input {
      width: 100%; padding: 12px; margin-bottom: 15px;
      border: none; border-radius: 5px; font-size: 14px;
    }
    .verify-box button {
      width: 100%; padding: 12px; border: none;
      border-radius: 5px; background: white; color: #336b91;
      font-size: 15px; font-weight: bold; cursor: pointer;
      transition: 0.3s;
    }
    .verify-box button:hover { background: #e1e1e1; }
    .error {
      color: #ff6b6b;
      margin-bottom: 15px;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="bg-blur"></div>
  <header>MINDTRACK</header>
  <div class="verify-container">
    <div class="verify-box">
      <h2>Verify Your Account</h2>

      <?php if (!empty($message)) { ?>
        <p class="error"><?php echo $message; ?></p>
      <?php } ?>

      <form method="POST">
        <input type="email" name="email" placeholder="Enter your Gmail" required>
        <input type="text" name="code" placeholder="Enter Verification Code" required>
        <button type="submit">Verify Me</button>
      </form>
    </div>
  </div>
</body>
</html>
