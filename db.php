<?php
// Enable detailed error reporting during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create connection
    $conn = new mysqli("localhost", "root", "", "mindtrack");

    // Set charset for proper encoding support
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Handle connection error gracefully
    error_log($e->getMessage());
    exit("Database connection failed. Please try again later.");
}
?>
