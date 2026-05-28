<?php
// === DATABASE CONFIGURATION (PALITAN ITO) ===
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; // Palitan ng inyong username
$DB_PASSWORD = "";     // Palitan ng inyong password
$DB_NAME = "mindtrack"; // Palitan ng pangalan ng inyong database

date_default_timezone_set('Asia/Manila');

$error_message = '';

/**
 * Custom ID Generation Logic for Doctors
 * Format: DR + 5-digit unique number (e.g., DR00123)
 * NOTE: Since we are not querying the DB for existing IDs here, 
 * we rely on the DB's UNIQUE constraint to catch duplicates.
 */
function generateUniqueDoctorID() {
    // Generate a 5-digit random number as a suffix
    $unique_suffix = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return 'DR' . $unique_suffix; 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 1. CONNECT TO DATABASE ===
    $conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error) {
        $error_message = "Database connection failed. Please check your DB settings: " . $conn->connect_error;
    } else {
        // === 2. GATHER AND SANITIZE INPUTS ===
        $first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $last_name = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
        $specialization = $conn->real_escape_string(trim($_POST['specialization'] ?? ''));
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        
        // Default values for doctor record
        $status = "Active"; 

        // Basic Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($specialization) || empty($phone)) {
            $error_message = "All required fields (Name, Specialization, Email, and Phone Number) must be filled.";
        } else {
            // === 3. GENERATE CUSTOM ID ===
            // We loop until a unique ID is successfully inserted or we hit a connection error
            $max_attempts = 5;
            $attempt = 0;
            $success = false;
            
            while ($attempt < $max_attempts && !$success) {
                $doctor_custom_id = generateUniqueDoctorID();
                $attempt++;

                // === 4. PREPARE AND EXECUTE INSERT QUERY ===
                $sql = "INSERT INTO doctors (doctor_custom_id, first_name, last_name, specialization, email, phone, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $doctor_custom_id, $first_name, $last_name, $specialization, $email, $phone, $status);
                
                if ($stmt->execute()) {
                    $success = true;
                    // SUCCESS: Redirect back to the doctors list with a success message
                    header("Location: doctors.php?registration=success&id=" . urlencode($doctor_custom_id));
                    exit;
                } else if ($conn->errno != 1062) {
                    // Break the loop if it's a non-ID-related error (like unique email or table missing)
                    $error_message = "Registration failed: " . $stmt->error;
                    break; 
                }
                // If error is 1062 and ID is the conflict, the loop continues to generate a new ID.
                $stmt->close();
            }

            if (!$success && empty($error_message)) {
                 $error_message = "Registration failed. Could not generate a unique Doctor ID after {$max_attempts} attempts. Please try again.";
            }
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register New Doctor - MindTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary-dark: #0077b6;
            --color-deep-blue: #00A9FF;
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-very-light-blue: #E0F7FF;
            --color-danger-red: #dc3545;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #d3e5ff, #e6f6ff); color: var(--color-text-dark); display: flex; justify-content: center; align-items: center; }

        .form-container {
            width: 100%;
            max-width: 500px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-container h2 {
            font-size: 22px;
            color: var(--color-primary-dark);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-very-light-blue);
            padding-bottom: 10px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--color-text-medium); margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--color-primary-dark); }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .btn-submit {
            width: 100%;
            background-color: var(--color-deep-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover { background-color: var(--color-primary-dark); }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2><i class="fas fa-user-plus" style="margin-right: 10px; color: var(--color-primary-dark);"></i> Register New Doctor</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert-error"><?= $error_message ?></div>
    <?php endif; ?>

    <form method="POST" action="doctor_registration.php">
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="specialization">Specialization</label>
            <select id="specialization" name="specialization" required>
                <option value="">-- Select Specialization --</option>
                <option value="Clinical Psychology" <?= (($_POST['specialization'] ?? '') == 'Clinical Psychology') ? 'selected' : '' ?>>Clinical Psychology</option>
                <option value="Psychiatry" <?= (($_POST['specialization'] ?? '') == 'Psychiatry') ? 'selected' : '' ?>>Psychiatry</option>
                <option value="Child Psychology" <?= (($_POST['specialization'] ?? '') == 'Child Psychology') ? 'selected' : '' ?>>Child Psychology</option>
                <option value="Counseling" <?= (($_POST['specialization'] ?? '') == 'Counseling') ? 'selected' : '' ?>>Counseling</option>
                <option value="Neurology" <?= (($_POST['specialization'] ?? '') == 'Neurology') ? 'selected' : '' ?>>Neurology</option>
            </select>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Contact Number</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
        </div>
        
        <button type="submit" class="btn-submit">Register Doctor</button>
        <a href="doctors.php" style="display: block; text-align: center; margin-top: 15px; color: var(--color-primary-dark); text-decoration: none; font-size: 14px;">
             <i class="fas fa-arrow-left"></i> Back to Doctors List
        </a>
    </form>
</div>
</body>
</html>