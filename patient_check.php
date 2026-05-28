<?php
include 'db.php';

$patientId = $_GET['id'] ?? '';

if (!empty($patientId)) {
    // Check if patient exists in patients table
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patientId = ?");
    $stmt->bind_param("s", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Patient registered → go to timeline
        header("Location: sp.php?id=" . urlencode($patientId));
        exit;
    } else {
        // Not registered → fetch info from booking_requests
        $stmt2 = $conn->prepare("SELECT name, contact, patient_email, birthday FROM booking_requests WHERE DATE_FORMAT(birthday, '%m%d%Y') = ?");
        $stmt2->bind_param("s", $patientId);
        $stmt2->execute();
        $res = $stmt2->get_result();
        $info = $res->fetch_assoc();

        if ($info) {
            // Redirect with prefill data
            header("Location: np.php?id=" . urlencode($patientId) .
                   "&name=" . urlencode($info['name']) .
                   "&contact=" . urlencode($info['contact']) .
                   "&email=" . urlencode($info['email']) .
                   "&birthday=" . urlencode($info['birthday']));
            exit;
        } else {
            // No data found
            header("Location: np.php?id=" . urlencode($patientId));
            exit;
        }
    }
} else {
    echo "Invalid Patient ID.";
}
