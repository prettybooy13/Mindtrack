<?php
/**
 * send_appointment_email.php
 * --------------------------
 * Utility file for sending email notifications to patients
 * (Approval and Rejection) using PHPMailer.
 */

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 🔧 CONFIGURATION for PHPMailer
 */
function setupMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';              // SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';    // 🔹 your Gmail address
    $mail->Password = 'your-app-password';       // 🔹 Gmail App Password (not your login password!)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->isHTML(true);
    $mail->setFrom('your-email@gmail.com', 'Clinic Admin'); // Sender info
    return $mail;
}

/**
 * ✅ Send Appointment Approval Email
 */
function sendApprovalEmail($patientEmail, $patientName, $appointmentDate, $appointmentTime, $doctorName): array {
    $mail = setupMailer();

    try {
        $mail->addAddress($patientEmail, $patientName);
        $mail->Subject = 'Your Appointment Has Been Approved ✅';

        $mail->Body = "
        <div style='font-family:Arial,sans-serif;padding:20px;background:#f7fff7;border-radius:10px;border:1px solid #cce5cc;'>
            <h2 style='color:#28a745;'>Appointment Approved</h2>
            <p>Dear <strong>{$patientName}</strong>,</p>
            <p>Your appointment has been <strong>approved</strong> by our administrator.</p>
            <table style='border-collapse:collapse;margin-top:10px;'>
                <tr><td><strong>Doctor:</strong></td><td>{$doctorName}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{$appointmentDate}</td></tr>
                <tr><td><strong>Time:</strong></td><td>{$appointmentTime}</td></tr>
            </table>
            <p style='margin-top:15px;'>Please arrive 10 minutes before your scheduled time.</p>
            <p>Thank you,<br><strong>Clinic Admin Team</strong></p>
        </div>";

        $mail->AltBody = "Dear {$patientName}, your appointment with Dr. {$doctorName} on {$appointmentDate} at {$appointmentTime} has been approved.";

        $mail->send();
        return ['success' => true, 'message' => 'Approval email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * ❌ Send Appointment Rejection Email
 */
function sendRejectionEmail($patientEmail, $patientName, $appointmentDate, $appointmentTime, $doctorName, $reason = ''): array {
    $mail = setupMailer();

    try {
        $mail->addAddress($patientEmail, $patientName);
        $mail->Subject = 'Your Appointment Has Been Rejected ❌';

        $mail->Body = "
        <div style='font-family:Arial,sans-serif;padding:20px;background:#fff8f8;border-radius:10px;border:1px solid #f5c6cb;'>
            <h2 style='color:#dc3545;'>Appointment Rejected</h2>
            <p>Dear <strong>{$patientName}</strong>,</p>
            <p>We regret to inform you that your appointment has been <strong>rejected</strong>.</p>
            <table style='border-collapse:collapse;margin-top:10px;'>
                <tr><td><strong>Doctor:</strong></td><td>{$doctorName}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{$appointmentDate}</td></tr>
                <tr><td><strong>Time:</strong></td><td>{$appointmentTime}</td></tr>
            </table>";

        if (!empty($reason)) {
            $mail->Body .= "<p style='margin-top:15px;'><strong>Reason:</strong> {$reason}</p>";
        }

        $mail->Body .= "
            <p style='margin-top:15px;'>You may contact the clinic for rescheduling or clarification.</p>
            <p>Thank you,<br><strong>Clinic Admin Team</strong></p>
        </div>";

        $mail->AltBody = "Dear {$patientName}, your appointment with Dr. {$doctorName} on {$appointmentDate} at {$appointmentTime} was rejected. Reason: {$reason}";

        $mail->send();
        return ['success' => true, 'message' => 'Rejection email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
