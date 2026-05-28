<?php
// patient_registration.php

// === DATABASE CONFIGURATION (PALITAN ITO) ===
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; // Palitan ng inyong username
$DB_PASSWORD = "";     // Palitan ng inyong password
$DB_NAME = "mindtrack"; // Palitan ng pangalan ng inyong database

date_default_timezone_set('Asia/Manila');

$success_message = '';
$error_message = '';

/**
 * Custom ID Generation Logic (UPDATED: MMDDYY format)
 * Generates an ID based on birthdate (MMDDYY) and appends a sequential suffix (P1, P2...) if duplicates exist.
 * E.g., 011204 or 011204P1
 */
function generateUniquePatientID($conn, $birthdate) {
    // UPDATED: Convert YYYY-MM-DD to MMDDYY format
    $base_id = date('mdy', strtotime($birthdate)); // e.g., 011204 for 2004-01-12

    // 1. Query existing IDs that match the base date
    $sql = "SELECT patient_custom_id FROM patients WHERE patient_custom_id LIKE '{$base_id}%' ORDER BY LENGTH(patient_custom_id) DESC, patient_custom_id DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows == 0) {
        return $base_id; // First patient with this birthday
    }

    // 2. Find the highest sequential suffix
    $highest_sequence = 0;
    
    while ($row = $result->fetch_assoc()) {
        $custom_id = $row['patient_custom_id'];
        
        // If the ID is just MMDDYY (no suffix), sequence is implicitly 0.
        if ($custom_id === $base_id) {
            // Do nothing, but we know one base ID exists.
        } 
        
        // Matches MMDDYYPX format (e.g., 011204P5)
        else if (preg_match('/P(\d+)$/', $custom_id, $matches)) { 
            $highest_sequence = max($highest_sequence, (int)$matches[1]);
        }
    }
    
    $next_sequence = $highest_sequence + 1;
    return $base_id . 'P' . $next_sequence; 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 1. CONNECT TO DATABASE ===
    $conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error) {
        $error_message = "Database connection failed: " . $conn->connect_error;
    } else {
        // === 2. GATHER AND SANITIZE INPUTS ===
        $first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $last_name = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
        $birthdate = $conn->real_escape_string($_POST['birthdate'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $contact = $conn->real_escape_string($_POST['contact'] ?? '');
        
        // --- EMERGENCY CONTACT FIELDS ---
        $emergency_contact_fname = $conn->real_escape_string(trim($_POST['emergency_contact_fname'] ?? ''));
        $emergency_contact_lname = $conn->real_escape_string(trim($_POST['emergency_contact_lname'] ?? ''));
        $emergency_contact_phone = $conn->real_escape_string($_POST['emergency_contact_phone'] ?? '');

        // Default values for patient record 
        $service_type = "Pending Evaluation";
        $doctor = "Unassigned";
        $diagnosis = "N/A - Initial Registration";
        $status = "New"; 

        // Concatenate Emergency Contact details
        if (!empty($emergency_contact_fname) || !empty($emergency_contact_lname) || !empty($emergency_contact_phone)) {
            $emergency_contact_name = trim($emergency_contact_fname . " " . $emergency_contact_lname);
            $emergency_contact = "Name: " . ($emergency_contact_name ?: 'N/A') . ", Contact: " . ($emergency_contact_phone ?: 'N/A');
        } else {
            $emergency_contact = NULL; // Save as NULL if optional fields are empty
        }

        // Basic Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($birthdate) || empty($contact)) {
            $error_message = "All required fields (Name, Birthdate, Email, Contact Number) must be filled.";
        } else {
            // === 3. GENERATE CUSTOM ID gamit ang MMDDYY format ===
            $patient_custom_id = generateUniquePatientID($conn, $birthdate);

            // === 4. PREPARE AND EXECUTE INSERT QUERY ===
            $sql = "INSERT INTO patients (patient_custom_id, first_name, last_name, birthdate, email, contact, emergency_contact, service_type, doctor, diagnosis, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssss", $patient_custom_id, $first_name, $last_name, $birthdate, $email, $contact, $emergency_contact, $service_type, $doctor, $diagnosis, $status);
            
            if ($stmt->execute()) {
                // === REDIRECT SA TIMELINE ===
                $redirect_url = "patient_timeline.php?id=" . urlencode($patient_custom_id);
                header("Location: " . $redirect_url);
                exit; // Mahalaga: Itigil ang script execution
                // ====================================
            } else {
                if ($conn->errno == 1062) { // 1062 is duplicate entry error (for unique column like email or patient_custom_id)
                    $error_message = "Registration failed. The email address '{$email}' or the generated Patient ID '{$patient_custom_id}' may already be in use. Error: " . $stmt->error;
                } else {
                    $error_message = "Registration failed: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register New Patient - MindTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary-dark: #0077b6;
            --color-deep-blue: #00A9FF;
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-very-light-blue: #E0F7FF;
            --color-success-green: #4CAF50;
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
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--color-primary-dark); }
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

        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2><i class="fas fa-plus-circle" style="margin-right: 10px; color: var(--color-primary-dark);"></i> Register New Patient</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert-error"><?= $error_message ?></div>
    <?php endif; ?>

    <form method="POST" action="patient_registration.php">
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
            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>" required>
            <small style="display: block; font-size: 11px; color: var(--color-text-medium); margin-top: 3px;">Patient ID format: MMDDYY (e.g., 011204)</small>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="tel" id="contact" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
        </div>
        
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
            <label style="display: block; font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 10px;">Emergency Contact Person (Optional)</label>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emergency_contact_fname">First Name</label>
                    <input type="text" id="emergency_contact_fname" name="emergency_contact_fname" value="<?= htmlspecialchars($_POST['emergency_contact_fname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="emergency_contact_lname">Last Name</label>
                    <input type="text" id="emergency_contact_lname" name="emergency_contact_lname" value="<?= htmlspecialchars($_POST['emergency_contact_lname'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="emergency_contact_phone">Contact Number</label>
                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?= htmlspecialchars($_POST['emergency_contact_phone'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn-submit">Register Patient</button>
        <a href="pt.php" style="display: block; text-align: center; margin-top: 15px; color: var(--color-primary-dark); text-decoration: none; font-size: 14px;">
             <i class="fas fa-arrow-left"></i> Back to Patient List
        </a>
    </form>
</div>
</body>
</html><s></s>