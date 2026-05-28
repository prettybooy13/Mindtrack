<?php
// update_doctor.php
header('Content-Type: application/json');

// 1. Database Connection Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mindtrack"; 

// 2. I-check kung POST request ito
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// 3. I-konekta sa Database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Database Connection failed: ' . $conn->connect_error]);
    exit;
}

// 4. Kolektahin ang data mula sa POST request
$doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if ($doctorId === 0 || empty($email) || empty($phone)) {
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required data (ID, email, or phone).']);
    $conn->close();
    exit;
}

// 5. Ihanda at I-execute ang UPDATE Statement
$sql = "UPDATE doctors SET email = ?, phone = ? WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ssi", $email, $phone, $doctorId); 

if ($stmt->execute()) {
    // 6. I-check ang affected rows
    if ($stmt->affected_rows > 0) {
        // SUCCESS: May nagbago at na-update
        echo json_encode(['success' => true, 'message' => 'Doctor details updated successfully.']);
    } else {
        // WALANG NABAGO: Matagumpay ang query, pero same data
        echo json_encode(['success' => true, 'message' => 'Update successful, but no changes were made (data was identical).']);
    }
} else {
    // 6. Magbigay ng error response
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database execution failed: ' . $stmt->error]);
}

// 7. Isara ang koneksyon
$stmt->close();
$conn->close();
?>