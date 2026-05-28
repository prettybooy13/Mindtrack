<?php
// delete_doctor.php

header('Content-Type: application/json');

// --- 1. Database Connection Configuration ---
// !!! PALITAN ANG MGA ITO NG IYONG AKTUAL NA CREDENTIALS !!!
$servername = "localhost";
$username = "root";       // <- Halimbawa: "root"
$password = "";           // <- Halimbawa: ""
$dbname = "mindtrack";    // Ang pangalan ng iyong database

// Default response
$response = ['success' => false, 'message' => 'Invalid request or data not provided.'];

// Tiyakin na ang request ay POST at mayroong doctor_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'])) {
    
    // Kunin at linisin ang input
    $doctor_id = filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT);

    if ($doctor_id === false || $doctor_id <= 0) {
        $response['message'] = "Invalid Doctor ID.";
    } else {
        // --- 2. Connect to the Database ---
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            $response['message'] = "Database Connection failed: " . $conn->connect_error;
            echo json_encode($response);
            exit();
        }

        // --- 3. Execute the DELETE Query (Using Prepared Statements for security) ---
        $sql = "DELETE FROM doctors WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $doctor_id); // "i" para sa integer
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Doctor with ID #{$doctor_id} has been successfully deleted.";
                } else {
                    $response['message'] = "Doctor not found or already deleted.";
                }
            } else {
                $response['message'] = "Error executing statement: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $response['message'] = "Error preparing statement: " . $conn->error;
        }

        // Close connection
        $conn->close();
    }
}

// Ibalik ang JSON response sa client (JavaScript)
echo json_encode($response);
?>