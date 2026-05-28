<?php
// Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    exit("Database connection failed.");
}

$id = $_GET['id'] ?? null; // booking_id or appointment_id
$action = $_GET['action'] ?? null;

// Default redirection: Tiyakin na babalik lang sa appointment.php (walang view parameter)
$redirect_to = 'appointment.php'; 
$today_date = date('Y-m-d');

if ($id && $action) {
    
    // --- HANDLE APPROVAL (MOVE TO APPOINTMENT TABLE) ---
    if ($action == 'approve') {
        
        // 1. Kuhanin ang data mula sa booking_request
        $sql_select = "SELECT * FROM booking_request WHERE booking_id = ? AND status = 'Pending'";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $booking_data = $result->fetch_assoc();
        $stmt_select->close();

        if ($booking_data) {
            
            $appointment_date = $booking_data['booking_date'];
            
            // **PAST DATE CONSTRAINT CHECK**
            if (strtotime($appointment_date) < strtotime($today_date)) {
                // Kung ang requested date ay nakalipas na, i-cancel nalang ang request
                $declined_status = 'Cancelled';
                $sql_update_booking = "UPDATE booking_request SET status = ? WHERE booking_id = ?";
                $stmt_update_booking = $conn->prepare($sql_update_booking);
                $stmt_update_booking->bind_param("si", $declined_status, $id);
                $stmt_update_booking->execute();
                $stmt_update_booking->close();
                
                // Dahil nag-approve/decline tayo, mas maganda na bumalik sa Requests view
                $redirect_to = 'appointment.php?view=requests'; 

            } else {
                // *** SAFE TO APPROVE (Future or Today's Appointment) ***
                $new_status = 'Scheduled'; 
                $remarks = 'Approved by admin.';
                $appointment_time = $booking_data['booking_time'];
                $service_type = $booking_data['service_type'];

                // 2. I-insert sa 'appointment' table
                // NOTE: Ang Patient ID ay hindi natin ginagamit sa INSERT dahil hindi siya galing sa booking_request
                // (Ginagamit lang natin ang birthdate sa UI. Kung may Patient ID number talaga, i-include mo dapat dito.)
                $sql_insert = "INSERT INTO appointment (booking_id, appointment_date, appointment_time, service_type, status, remarks) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("isssss", $id, $appointment_date, $appointment_time, $service_type, $new_status, $remarks);
                
                if ($stmt_insert->execute()) {
                    // 3. I-update ang status sa booking_request
                    $sql_update_booking = "UPDATE booking_request SET status = ? WHERE booking_id = ?";
                    $stmt_update_booking = $conn->prepare($sql_update_booking);
                    $stmt_update_booking->bind_param("si", $new_status, $id);
                    $stmt_update_booking->execute();
                    $stmt_update_booking->close();
                }
                $stmt_insert->close();
                
                // Pag na-approve, babalik sa Requests view para mag-approve pa ng iba
                $redirect_to = 'appointment.php?view=requests'; 
            }
        } 

    // --- HANDLE DECLINE (UPDATE STATUS IN booking_request) ---
    } else if ($action == 'decline') {
        $declined_status = 'Cancelled';
        
        $sql_update_booking = "UPDATE booking_request SET status = ? WHERE booking_id = ?";
        $stmt_update_booking = $conn->prepare($sql_update_booking);
        $stmt_update_booking->bind_param("si", $declined_status, $id);
        $stmt_update_booking->execute();
        $stmt_update_booking->close();
        
        // Pag na-decline, babalik sa Requests view
        $redirect_to = 'appointment.php?view=requests'; 
    
    // --- HANDLE APPOINTMENT UPDATE (Mark as Completed/Cancel sa Today's Appt) ---
    } else if ($action == 'complete') {
        $completed_status = 'Completed';
        
        $sql_update_appointment = "UPDATE appointment SET status = ? WHERE appointment_id = ?";
        $stmt_update_appointment = $conn->prepare($sql_update_appointment);
        $stmt_update_appointment->bind_param("si", $completed_status, $id);
        $stmt_update_appointment->execute();
        $stmt_update_appointment->close();
        
        // Pag na-complete, babalik sa Today's Appointments view
        $redirect_to = 'appointment.php'; 
    }
}

$conn->close();

header("Location: " . $redirect_to);
exit();
?>