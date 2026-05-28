<?php
// Tiyakin ang tamang Time Zone
date_default_timezone_set('Asia/Manila');

// Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Redirect pabalik sa appointment page na may error message
    header("Location: appointment.php?error=db_connect_failed");
    exit();
}

// 1. Kuhanin ang ID at Action mula sa URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Gumamit ng trim() para alisin ang anumang extra whitespace, na nagdudulot ng 'unexpected identifier' error
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($booking_id <= 0 || !in_array($action, ['approve', 'decline'])) {
    // Invalid request, i-redirect pabalik
    header("Location: appointment.php?view=requests");
    exit();
}

// Magsimula ng Transaction 
$conn->begin_transaction();
$success = false;
$redirect_message = 'failed'; // Default message

if ($action == 'approve') {
    // --- APPROVE LOGIC ---

    // 2. I-fetch ang data ng booking request. TANGGALIN ang booking_code.
    $sql_fetch = "
        SELECT 
            birthdate, 
            service_type, 
            booking_date, 
            booking_time
        FROM 
            booking_request 
        WHERE 
            booking_id = ? AND status = 'Pending'
    ";
    
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $booking_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $booking_data = $result->fetch_assoc();
    $stmt_fetch->close();

    if ($booking_data) {
        // I-format ang birthdate sa MMDDYY format para maging Patient ID
        $patient_id_mmddyy = date('mdy', strtotime($booking_data['birthdate']));
        
        // 3. I-insert ang bagong Appointment. TANGGALIN ang booking_code.
        $sql_insert = "
            INSERT INTO appointment 
            (booking_id, patient_id, appointment_date, appointment_time, service_type, status) 
            VALUES (?, ?, ?, ?, ?, 'Scheduled') 
        ";
        
        $stmt_insert = $conn->prepare($sql_insert);
        
        $stmt_insert->bind_param(
            "issss", // i=integer (booking_id), s=string (patient_id (mmddyy), date, time, type)
            $booking_id, 
            $patient_id_mmddyy, // Gamitin ang MMDDYY format bilang Patient ID
            $booking_data['booking_date'], 
            $booking_data['booking_time'], 
            $booking_data['service_type']
        );
        $insert_ok = $stmt_insert->execute();
        $stmt_insert->close();

        if ($insert_ok) {
            // 4. I-update ang status sa booking_request table
            $sql_update_booking = "UPDATE booking_request SET status = 'Approved' WHERE booking_id = ?";
            $stmt_update = $conn->prepare($sql_update_booking);
            $stmt_update->bind_param("i", $booking_id);
            $update_ok = $stmt_update->execute();
            $stmt_update->close();

            if ($update_ok) {
                $conn->commit();
                $success = true;
            } else {
                $conn->rollback();
            }
        } else {
            $conn->rollback();
        }
    }

    $redirect_message = $success ? 'approved' : 'approve_failed';

} elseif ($action == 'decline') {
    // --- DECLINE LOGIC ---
    // I-update lang ang status sa booking_request table
    $sql_update_booking = "UPDATE booking_request SET status = 'Declined' WHERE booking_id = ? AND status = 'Pending'";
    $stmt_update = $conn->prepare($sql_update_booking);
    $stmt_update->bind_param("i", $booking_id);
    $update_ok = $stmt_update->execute();
    $stmt_update->close();

    if ($update_ok) {
        $conn->commit();
        $success = true;
    } else {
        $conn->rollback();
    }
    
    $redirect_message = $success ? 'declined' : 'decline_failed';
}

$conn->close();

// I-redirect pabalik sa requests view at magbigay ng message
header("Location: appointment.php?view=requests&message=" . $redirect_message);
exit();
?>