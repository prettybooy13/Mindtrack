<?php
session_start();

// === SET YOUR ACCESS KEY HERE (Palitan ang "secretkey123") ===
$ACCESS_KEY = "mindtrack"; 

$error = '';

// Kukunin ang patient ID, galing man sa URL (GET) o sa form (POST)
$patient_id = isset($_POST['patient_id']) ? htmlspecialchars($_POST['patient_id']) : 
              (isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_password = trim($_POST['password']);

    // Verification ng Access Key
    if ($input_password === $ACCESS_KEY) { 
        $_SESSION['timeline_authenticated'] = true;
        
        // I-redirect pabalik sa patient timeline, kasama ang Patient ID
        $redirect_id_param = !empty($patient_id) ? '?id=' . urlencode($patient_id) : '';
        header("Location: patient_timeline.php" . $redirect_id_param);
        exit;
    } else {
        $error = "Invalid Access Key.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Timeline Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #e6f6ff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { 
            background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center; max-width: 350px; width: 100%;
        }
        .login-box h2 { color: #0077b6; margin-bottom: 20px; font-weight: 600; }
        .error-message { color: #dc3545; margin-bottom: 15px; font-size: 14px; }
        input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%; padding: 10px; background-color: #00A9FF; color: white; border: none; 
            border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;
        }
        button:hover { background-color: #0077b6; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Enter Timeline Access Key</h2>
        <?php if (!empty($error)): ?>
            <p class="error-message">❌ <?= $error ?></p>
        <?php endif; ?>
        <form method="POST" action="timeline_auth.php">
            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
            
            <input type="password" name="password" placeholder="Access Key" required>
            <button type="submit">Unlock Timeline</button>
        </form>
        <?php if (!empty($patient_id)): ?>
            <p style="margin-top: 15px; font-size: 12px; color: #666;">Patient ID: <?= $patient_id ?></p>
        <?php endif; ?>
    </div>
</body>
</html>