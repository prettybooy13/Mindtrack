<?php
date_default_timezone_set('Asia/Manila');

// 🚨 TROUBLESHOOTING: GINAMIT NATING 'On' PARA LUMABAS ANG PHP ERRORS
error_reporting(E_ALL);
ini_set('display_errors', 'On'); 
session_start();
include 'db.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');

// ✅ CONFIG: replace with your own Gmail + App Password
$mailConfig = [
    'host' => 'smtp.gmail.com',
    'username' => 'vicijanphyllisnavalta12@gmail.com', // ← change this
    'password' => 'ajcuhitefejmssll',    // ← change this (App Password, not Gmail password)
    'port' => 587,
    'encryption' => 'tls'
];

// ✅ FETCH DATA FROM FORM OR DATABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service_type = $_POST['service_type'];

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // ✅ SMTP Settings
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
        $mail->Port       = $mailConfig['port'];

        // ✅ Enable debugging (comment out later if working)
        $mail->SMTPDebug = 2; 
        $mail->Debugoutput = 'html';

        // ✅ Sender and recipient
        $mail->setFrom($mailConfig['username'], 'Wayside Psyche Clinic');
        $mail->addAddress($email, $fullname);

        // ✅ Email content
        $mail->isHTML(true);
        $mail->Subject = 'Appointment Confirmation - Wayside Psyche Clinic';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 10px;'>
                <h2>Appointment Confirmation</h2>
                <p>Hi <strong>{$fullname}</strong>,</p>
                <p>Your appointment has been confirmed with the following details:</p>
                <ul>
                    <li><b>Date:</b> {$date}</li>
                    <li><b>Time:</b> {$time}</li>
                    <li><b>Service:</b> {$service_type}</li>
                </ul>
                <p>Thank you for choosing <b>Wayside Psyche Clinic</b>.</p>
                <p>See you soon!</p>
            </div>
        ";

        $mail->AltBody = "Appointment Confirmation\n\n
        Name: {$fullname}\n
        Date: {$date}\n
        Time: {$time}\n
        Service: {$service_type}";

        // ✅ Send the email
        $mail->send();

        echo "<script>alert('Confirmation email sent successfully to {$email}!');</script>";

    } catch (Exception $e) {
        echo "<h4 style='color:red;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</h4>";
    }
} else {
    echo "<h4 style='color:red;'>Invalid request. No email data found.</h4>";
}
?>

// 1. I-include ang PHPMailer at Exceptions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// **IMPORTANT:** Siguraduhin na tama ang path na ito
// Assuming you are using Composer or have PHPMailer files in 'vendor/'
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

// HELPER FUNCTION (para sa time formatting)
function format_time_display(?string $time_str): string {
    if (empty($time_str)) return '';
    $tz = new DateTimeZone('Asia/Manila');
    $formats = ['H:i:s','H:i','g:i A','h:i A','H:i:s.u'];

    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $time_str, $tz);
        if ($dt instanceof DateTime) {
            $errors = DateTime::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt->setTimezone($tz)->format('g:i A');
            }
        }
    }
    $ts = strtotime($time_str);
    if ($ts !== false) {
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone($tz);
        return $dt->format('g:i A');
    }
    return (string)$time_str;
}

// Kailangan ito para hindi magulo ang JSON output kapag may error sa code
ob_start();

header('Content-Type: application/json');

// Connect sa database (I-assume na naka-define na ang connection details)
try {
    // *** PALITAN ANG DATABASE CONNECTION DETAILS KUNG HINDI ITO ANG GAMIT MO ***
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Kapag may error sa database, ito ang lalabas
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing ID.']);
    $conn->close();
    exit();
}

$booking_id = filter_var($_POST['booking_id'] ?? 0, FILTER_VALIDATE_INT);

$response_data = ['success' => false, 'message' => 'Initialization error.'];

if (!$booking_id) {
    $response_data = ['success' => false, 'message' => 'Invalid booking id.'];
}
else {
    try {
        $conn->begin_transaction();

        // 1. Kumuha ng Request Details
        $sql_fetch = "
            SELECT 
                first_name, last_name, birthdate, booking_date, booking_time, service_type, email
            FROM booking_request
            WHERE booking_id = ? AND status = 'Pending'
        ";

        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $booking_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        $request_data = $result_fetch->fetch_assoc();
        $stmt_fetch->close();

        if (!$request_data) {
            throw new Exception("Pending booking request not found or already processed.");
        }
        
        // 2. I-update ang Booking Request status (Pending -> Approved)
        $sql_update_booking = "UPDATE `booking_request` SET `status` = 'Approved' WHERE booking_id = ?";
        $stmt_update = $conn->prepare($sql_update_booking);
        $stmt_update->bind_param("i", $booking_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Commit Transaction
        $conn->commit();
        
        // 4. Magpadala ng Email Confirmation
        $email_success = false;
        $final_email = $request_data['email']; // <--- ETO ANG PATIENT EMAIL NA NAKALAGAY SA DATABASE
        
        // --- EMAIL CREDENTIALS (Palitan mo ang mga ito) ---
        $MY_GMAIL = 'vicijanphyllisnavalta12@gmail.com'; 
        // Siguraduhin na 'ziuz ijyg gyqh rjmh' ay Google App Password, HINDI ang regular password
        $MY_APP_PASSWORD = 'ziuz ijyg gyqh rjmh'; 
        // ----------------------------------------------------

        if (!empty($final_email)) {
            try {
                $mail = new PHPMailer(true);
                
                // 🚨 NEW DEBUGGING CODE: I-set ang debug level para makita ang usapan sa server
                $mail->SMTPDebug = 2; // I-set sa 2 para makita ang communication logs
                $mail->Debugoutput = function($str, $level) {
                    // I-log sa PHP error log
                    error_log("SMTP Debug: " . $str);
                    // Para makita agad sa browser console/network response:
                    echo "<!-- SMTP DEBUG: " . htmlspecialchars($str) . " -->\n";
                };
                
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                
                // 1. IYONG BUONG GMAIL ADDRESS (ang sender/admin account mo):
                $mail->Username = $MY_GMAIL; 
                // 2. ANG 16-CHARACTER GOOGLE APP PASSWORD:
                $mail->Password = $MY_APP_PASSWORD; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Mas common na ginagamit
                $mail->Port = 587; 

                $mail->setFrom($MY_GMAIL, 'MindTrack Booking'); 
                // ETO PO ANG PATIENT EMAIL
                $mail->addAddress($final_email, $request_data['first_name']);

                $mail->isHTML(false); // Plain text email
                $mail->Subject = "MindTrack: Your Booking Request is Approved!";
                
                $patient_full_name = trim($request_data['first_name'] . ' ' . $request_data['last_name']);
                $formatted_date = date('F j, Y', strtotime($request_data['booking_date']));
                $formatted_time = format_time_display($request_data['booking_time']);

                // Email Body
                $body = "Hi {$patient_full_name},\n\n";
                $body .= "We are pleased to inform you that your booking request has been **APPROVED**.\n\n";
                $body .= "**Your appointment is pending final scheduling and doctor assignment.**\n\n";
                $body .= "--- Request Details ---\n";
                $body .= "Patient Name: {$patient_full_name}\n";
                $body .= "Service Type: {$request_data['service_type']}\n";
                $body .= "Requested Date: {$formatted_date}\n";
                $body .= "Requested Time: {$formatted_time}\n";
                $body .= "---------------------------\n\n";
                $body .= "We will notify you again once your doctor has been assigned and the final appointment is confirmed.\n\n";
                $body .= "MindTrack Health Management Team";
                
                $mail->Body = $body;

                $mail->send();
                $email_success = true;

            } catch (Exception $e) {
                // Kung mag-fail ang email, i-log natin ang error at isasama sa response
                error_log("Email sending failed for {$final_email}. Mailer Error: {$mail->ErrorInfo}");
                $response_data['email_error'] = "Email failed to send. Mailer Info: " . $mail->ErrorInfo;
            }
        }

        $response_data = [
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Booking request approved successfully. Status updated.',
            'email_status' => $email_success ? 'Sent' : 'Not sent (Failed to send: ' . ($response_data['email_error'] ?? 'Unknown reason') . ')'
        ];

    } catch (Exception $e) {
        if ($conn->in_transaction) $conn->rollback();
        error_log("Approval Error: " . $e->getMessage()); 
        
        $response_data = [
            'success' => false, 
            'message' => 'Database error during approval. Details: ' . $e->getMessage()
        ];
    }
}

// Kunin ang anumang output na nangyari bago ang JSON
$debug_output = ob_get_clean(); 
if (!empty($debug_output)) {
    // Kung may output bago ang JSON, ibig sabihin may PHP error/warning na nakasira sa AJAX
    error_log("AJAX JSON Corruption Detected (might be SMTP logs): " . trim($debug_output));
    // DITO NA PO LALABAS ANG SMTP DEBUG LOGS SA HTML COMMENTS
}

// I-output ang final JSON response
echo json_encode($response_data);

$conn->close();
exit();
?>
