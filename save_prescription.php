<?php
// Set headers para sa JSON response
header('Content-Type: application/json');

// =================================================================
// 🚨 DATABASE CONFIGURATION 🚨
// =================================================================
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; 
$DB_PASSWORD = ""; 
$DB_NAME = "mindtrack"; 
// =================================================================

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if data was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id']) && isset($_POST['prescription_content'])) {
    
    $note_id = trim($_POST['note_id']);
    $prescription_content = trim($_POST['prescription_content']); // Ito ang i-u-update natin
    
    // Simple validation
    if (!is_numeric($note_id)) {
        $response['message'] = "Invalid Note ID format.";
        echo json_encode($response);
        exit;
    }
    
    // Connect to the database
    $conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error) {
        $response['message'] = "Database Connection Failed: " . $conn->connect_error;
        echo json_encode($response);
        exit;
    }

    // UPDATE QUERY: Ina-update ang 'prescription' field sa 'patient_notes' table 
    // batay sa 'id' (note_id)
    $sql = "UPDATE patient_notes SET prescription = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $response['message'] = "SQL Prepare failed: " . $conn->error;
        $conn->close();
        echo json_encode($response);
        exit;
    }

    // 's' para sa string (prescription), 'i' para sa integer (note ID)
    $stmt->bind_param("si", $prescription_content, $note_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Prescription updated successfully.";
    } else {
        $response['message'] = "Database Execute failed: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();

} else {
    $response['message'] = "Invalid request method or missing required data (note_id/prescription_content).";
}

// Ibalik ang sagot sa JavaScript sa format na JSON
echo json_encode($response);
?>