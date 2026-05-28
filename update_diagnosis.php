<?php
// Tiyaking POST request lang ang tatanggapin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// === 1. DATABASE CONFIGURATION (PALITAN ITO) ===
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; // Palitan ng inyong username
$DB_PASSWORD = "";     // Palitan ng inyong password
$DB_NAME = "mindtrack"; // Palitan ng pangalan ng inyong database

// === 2. CONNECT TO DATABASE ===
$conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// === 3. GET DATA from AJAX POST ===
// Gamitin ang isset() at i-sanitize ang input
$patient_id = $_POST['patient_id'] ?? null;
$diagnosis = $_POST['diagnosis'] ?? null;

if (empty($patient_id) || $diagnosis === null) {
    echo json_encode(['success' => false, 'message' => 'Missing patient ID or diagnosis data.']);
    $conn->close();
    exit;
}

// === 4. UPDATE DATABASE gamit ang Prepared Statement (Para sa security) ===
// Tiyaking ang 'patient_custom_id' ay ang tamang column name para sa ID, at 'diagnosis' ang tamang column name.
$sql = "UPDATE patients SET diagnosis = ? WHERE patient_custom_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
}

// Bind parameters: 's' for string (diagnosis) and 's' for string (patient_custom_id)
$stmt->bind_param("ss", $diagnosis, $patient_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Success
        echo json_encode(['success' => true, 'message' => 'Diagnosis successfully updated.']);
    } else {
        // No row affected (Baka pareho lang ang value o mali ang ID)
        echo json_encode(['success' => false, 'message' => 'Update executed, but no changes were made (ID not found or diagnosis is the same).']);
    }
} else {
    // Execution error
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>