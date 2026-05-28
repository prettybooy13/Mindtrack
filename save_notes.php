<?php
// Set headers para sa JSON response
header('Content-Type: application/json');

// =================================================================
// 🚨 DATABASE CONFIGURATION (Ibinabalik sa "mindtrack") 🚨
// =================================================================
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; 
$DB_PASSWORD = ""; // Kung walang password, iwanan lang na blanko ("")
$DB_NAME = "mindtrack"; 
// =================================================================

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if data was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id']) && isset($_POST['note_content'])) {
    
    $patient_id = trim($_POST['patient_id']);
    $note_content = trim($_POST['note_content']);
    
    if (empty($patient_id)) {
        $response['message'] = "Patient ID is missing.";
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

    // Tiyakin na ang table name ay "patient_notes"
    $sql = "INSERT INTO patient_notes (patient_custom_id, note_content, created_at) VALUES (?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $response['message'] = "SQL Prepare failed (Check 'patient_notes' table/column names): " . $conn->error;
        $conn->close();
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("ss", $patient_id, $note_content);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Note saved successfully.";
    } else {
        $response['message'] = "Database Execute failed: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();

} else {
    $response['message'] = "Invalid request method or missing required data (patient_id/note_content).";
}

// Ibalik ang sagot sa JavaScript sa format na JSON
echo json_encode($response);
?>