<?php
// view_patient.php - FIXED REDIRECT LOGIC for registration

// Tiyakin ang tamang Time Zone
date_default_timezone_set('Asia/Manila');

// Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    // Palitan ang "root" at "" kung mayroon kayong ibang credentials
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    header("Location: appointment.php?error=db_connection_failed");
    exit;
}

// ----------------------------------------------------
// 1. DETERMINE PATIENT IDENTIFIER
// ----------------------------------------------------

$birthdate = null;
$identifier_value = null;
$identifier_type = null;

if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
    // Scenario 1: LINK from Scheduled Appointments (patient_custom_id value)
    $identifier_type = 'patient_id';
    $identifier_value = $_GET['patient_id'];
    $sql_patient = "SELECT patient_custom_id FROM patients WHERE patient_custom_id = ?";
    $stmt = $conn->prepare($sql_patient);
    $stmt->bind_param("s", $identifier_value);

} elseif (isset($_GET['birthdate']) && !empty($_GET['birthdate'])) {
    // Scenario 2: LINK from Pending Requests
    $identifier_type = 'birthdate';
    $identifier_value = $_GET['birthdate'];
    $birthdate = $identifier_value; // Store birthdate for potential registration redirect
    
    // Hanapin muna ang pasyente gamit ang birthdate
    $sql_patient = "SELECT patient_custom_id FROM patients WHERE DATE(birthdate) = ?"; 
    $stmt = $conn->prepare($sql_patient);
    $stmt->bind_param("s", $identifier_value);

} else {
    // Walang sapat na parameter
    $conn->close();
    header("Location: appointment.php?error=missing_params");
    exit;
}

// ----------------------------------------------------
// 2. EXECUTE QUERY AND REDIRECT
// ----------------------------------------------------

$found_id = '';

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Kaso 1: Patient Found: Redirect to Timeline (SUCCESS)
        $patient_data = $result->fetch_assoc();
        $found_id = $patient_data['patient_custom_id'];
        $conn->close();
        
        $redirect_url = "patient_timeline.php?id=" . urlencode($found_id);
        header("Location: " . $redirect_url);
        exit;
        
    } else {
        // Kaso 2: Patient Not Found. **FORCE REDIRECT TO REGISTRATION.**
        $conn->close();
        
        $redirect_params = [];
        
        // Kung galing sa 'birthdate', gamitin ito.
        if ($identifier_type == 'birthdate' && $birthdate) {
            $redirect_params['birthdate'] = urlencode($birthdate);
            $redirect_params['source'] = 'request_redirect';
        } 
        // Kung galing sa 'patient_id', ipadala ang ID (MMDDYY) bilang prefill_id.
        elseif ($identifier_type == 'patient_id') {
            $redirect_params['prefill_id'] = urlencode($identifier_value);
            $redirect_params['source'] = 'id_not_found_register';
        }
        
        // I-construct ang redirect URL
        if (!empty($redirect_params)) {
            $redirect_url = "patient_registration.php?" . http_build_query($redirect_params);
            header("Location: " . $redirect_url);
            exit;
        } else {
             // Fallback kung walang identifier
             header("Location: appointment.php?error=patient_not_found_no_info");
             exit;
        }
    }
    $stmt->close();
}
// Kung umabot man dito (na hindi dapat), redirect pabalik.
header("Location: appointment.php?error=unhandled_case_end");
exit;
?>