<?php
ob_start();
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];

    if ($action == "login") {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT id, password, verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row["verified"] == 0) {
                    $message = "Please verify your email first.";
                }
                 elseif (password_verify($password, $row["password"])) {
                    $_SESSION["user_id"] = $row["id"];
                    header("Location: book.php");
                    exit;
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "No account found. Please sign up.";
            }
        } else {
            $message = "Please enter a valid email address.";
        }

    } elseif ($action == "signup") {
        $name = $_POST["name"];
        $surname = $_POST["surname"];
        $email = trim($_POST["email"]);
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $code = strtoupper(bin2hex(random_bytes(3))); // e.g. A1B2C3

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Email already registered. Please login.";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, surname, email, password, verification_code) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $surname, $email, $password, $code);
                $stmt->execute();

                // Send verification code via email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'vicijanphyllisnavalta12@gmail.com';
                    $mail->Password = 'rqnb lxwp llyr rbhr';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('vicijanphyllisnavalta12@gmail.com', 'Mindtrack');
                    $mail->addAddress($email);
                    $mail->Subject = 'Your Mindtrack Verification Code';
                    $mail->Body = "Heyy $name!\n\nYour Mindtrack verification code is: $code\n\nType this code on the verification page to activate your account.";

                    $mail->send();
                    header("Location: verify.php?email=$email");
                    exit;
                } catch (Exception $e) {
                    $message = "Signup successful, but email could not be sent. Error: {$mail->ErrorInfo}";
                }
            }
        } else {
            $message = "Please enter a valid email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mindtrack Login</title>
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
    .login-container {
      flex: 1; display: flex;
      justify-content: center; align-items: center;
      position: relative; z-index: 1;
    }
    .login-box {
      background: rgba(51, 107, 145, 0.9);
      padding: 30px; border-radius: 8px;
      width: 320px; text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .login-box h2 { color: #fff; margin-bottom: 20px; }
    .login-box input {
      width: 100%; padding: 12px; margin-bottom: 15px;
      border: none; border-radius: 5px; font-size: 14px;
    }
    .login-box button {
      width: 100%; padding: 12px; border: none;
      border-radius: 5px; background: white; color: #336b91;
      font-size: 15px; font-weight: bold; cursor: pointer;
      transition: 0.3s;
    }
    .login-box button:hover { background: #e1e1e1; }
    .error {
      color: #ff6b6b;
      margin-bottom: 15px;
      font-size: 14px;
    }
    .toggle-buttons {
      display: flex; justify-content: space-between;
      margin-bottom: 15px;
    }
    .toggle-buttons button {
      width: 48%; background: white; color: #336b91;
      font-weight: bold; border: none; padding: 10px;
      border-radius: 5px; cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="bg-blur"></div>
  <header>MINDTRACK</header>
  <div class="login-container">
    <div class="login-box">
      <h2 id="form-title">Login to Continue</h2>

      <?php if (!empty($message)) { ?>
        <p class="error"><?php echo $message; ?></p>
      <?php } ?>

      <div class="toggle-buttons">
        <button onclick="showForm('login')">Login</button>
        <button onclick="showForm('signup')">Sign Up</button>
      </div>

      <!-- Login Form -->
      <form method="POST" action="" id="login-form">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Enter your Gmail" required>
        <input type="password" name="password" placeholder="Enter your Password" required>
        <button type="submit">Continue</button>
      </form>

      <!-- Signup Form -->
      <form method="POST" action="" id="signup-form" style="display:none;">
        <input type="hidden" name="action" value="signup">
        <input type="text" name="name" placeholder="First Name" required>
        <input type="text" name="surname" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Enter your Gmail" required>
        <input type="password" name="password" placeholder="Create Password" required>
        <button type="submit">Sign Up</button>
      </form>
    </div>
  </div>

  <script>
    function showForm(form) {
      document.getElementById('login-form').style.display = form === 'login' ? 'block' : 'none';
      document.getElementById('signup-form').style.display = form === 'signup' ? 'block' : 'none';
      document.getElementById('form-title').innerText = form === 'login' ? 'Login to Continue' : 'Create an Account';
    }
  </script>
</body>
</html>
