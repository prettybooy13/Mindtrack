<?php
date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'Off');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    exit("Database connection failed. Please check your configuration.");
}

// ======================================================================
// === HELPER FUNCTIONS =================================================
// ======================================================================

function generate_unique_patient_identifier(string $birthdate, string $firstName = '', string $lastName = ''): string {
    $ts = strtotime($birthdate);
    $base_id = date('mdy', $ts);
    
    $first_initial = strtoupper(substr($firstName, 0, 1));
    $birth_month = date('n', $ts);
    $last_initial = strtoupper(substr($lastName, 0, 1));
    
    return $base_id . '-' . $first_initial . $birth_month . $last_initial;
}

function formatPatientName(string $first, string $last, string $patient_id_or_birthdate): string {
    $identifier = $patient_id_or_birthdate;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $patient_id_or_birthdate)) {
        $identifier = generate_unique_patient_identifier($patient_id_or_birthdate, $first, $last);
    }

    $last_upper = strtoupper(trim((string)$last));
    $first_initial = $first !== '' ? strtoupper(substr(trim($first), 0, 1)) : '';

    // Ensure identifier is uppercase for consistency
    $identifier = strtoupper($identifier);

    if ($first_initial !== '') {
        return "{$last_upper}, {$first_initial} ({$identifier})";
    } else {
        return "{$last_upper} ({$identifier})";
    }
}

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

if (!function_exists('findColumn')) {
function findColumn($conn, $table, $candidates = []) {
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) return null;
    $exists = [];
    while ($c = $res->fetch_assoc()) {
        $exists[] = $c['Field'];
    }
    foreach ($candidates as $cand) {
        if (in_array($cand, $exists)) return $cand;
    }
    return null;
}
}

$appointment_doctor_col = findColumn($conn, 'appointment', ['doctor_id', 'doctor', 'assigned_doctor_id', 'doc_id', 'doctorId']);
$doctors_pk_col = findColumn($conn, 'doctors', ['id', 'doctor_id', 'doc_id']);
$patients_pk_col = findColumn($conn, 'patients', ['patient_custom_id', 'patient_id', 'id']);
$appointment_patient_fk = findColumn($conn, 'appointment', ['patient_id', 'patient', 'patient_custom_id']);

$appointment_patient_fname_col = findColumn($conn, 'appointment', ['patient_first_name', 'first_name']); 
$appointment_patient_lname_col = findColumn($conn, 'appointment', ['patient_last_name', 'last_name']);
$patient_names_in_appt = ($appointment_patient_fname_col !== null && $appointment_patient_lname_col !== null);

$doctors_join_possible = ($appointment_doctor_col !== null && $doctors_pk_col !== null);
$patients_join_possible = ($appointment_patient_fk !== null && $patients_pk_col !== null);


// ----------------------------------------------------------------------
// --- AJAX HANDLER: fetch_unassigned_appointments -----------------------
// ----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'fetch_unassigned_appointments') {
    $output = '';

    $appt_doctor_select = $appointment_doctor_col ? "a.`{$appointment_doctor_col}` AS doctor_id," : "NULL AS doctor_id,";
    
    if ($patient_names_in_appt) {
        // Use the name columns directly from the appointment table (a), aliased to match the expected $row['first_name']
        $patient_join_clause = "";
        $patient_select = "a.{$appointment_patient_fname_col} AS first_name, a.{$appointment_patient_lname_col} AS last_name, a.`{$appointment_patient_fk}` AS patient_id";
    } else {
        // Fallback to the original join logic if names aren't in the appointment table
        $patient_join_clause = $patients_join_possible ? "LEFT JOIN patients p ON a.`{$appointment_patient_fk}` = p.`{$patients_pk_col}`" : "";
        $patient_select = $patients_join_possible ? "p.first_name, p.last_name, a.`{$appointment_patient_fk}` AS patient_id" : "NULL AS first_name, NULL AS last_name, a.`{$appointment_patient_fk}` AS patient_id";
    }

    if ($appointment_doctor_col) {
        $where_doctor_clause = "(a.`{$appointment_doctor_col}` IS NULL OR a.`{$appointment_doctor_col}` = 0)";
    } else {
        $where_doctor_clause = "1";
    }

    $sql_unassigned = "
        SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.service_type,
            {$appt_doctor_select}
            {$patient_select}
        FROM
            appointment a
        {$patient_join_clause}
        WHERE
            a.status = 'Scheduled'
            AND {$where_doctor_clause}
        ORDER BY
            a.appointment_date ASC, a.appointment_time ASC
    ";

    $result_unassigned = $conn->query($sql_unassigned);

    if ($result_unassigned && $result_unassigned->num_rows > 0) {
        while ($row = $result_unassigned->fetch_assoc()) {
            $patient_name_display = formatPatientName($row['first_name'] ?? '', $row['last_name'] ?? '', $row['patient_id'] ?? '');

            $output .= '<tr data-appointment-id="' . htmlspecialchars($row['appointment_id']) . '" data-service-type="' . htmlspecialchars($row['service_type']) . '">';
            $output .= '<td><a href="view_patient.php?patient_id=' . htmlspecialchars($row['patient_id']) . '&source=appt" class="patient-id-link">' . $patient_name_display . '</a></td>';
            $output .= '<td class="datetime-cell">';
            $output .= '<span><i class="far fa-calendar-alt"></i> ' . date('M d, Y', strtotime($row['appointment_date'])) . '</span>';
            $output .= '<span><i class="far fa-clock"></i> ' . htmlspecialchars(format_time_display($row['appointment_time'])) . '</span>';
            $output .= '</td>';
            $output .= '<td>' . htmlspecialchars($row['service_type']) . '</td>';
            $output .= '<td class="actions-cell"><button type="button" class="assign-btn" data-id="' . htmlspecialchars($row['appointment_id']) . '" data-service="' . htmlspecialchars($row['service_type']) . '">Assign Doctor</button></td>';
            $output .= '</tr>';
        }
    } else {
        $output = '<tr><td colspan="4" style="text-align:center; color: var(--color-text-medium); background-color: white;">All scheduled appointments have an assigned doctor.</td></tr>';
    }

    $conn->close();
    echo $output;
    exit();
}

// ----------------------------------------------------------------------
// --- AJAX HANDLER: fetch_doctors --------------------------------------
// ----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'fetch_doctors') {
    $doctors = [];
    $doctors_pk = $doctors_pk_col ?? 'id';
    $sql_doctors = "SELECT `{$doctors_pk}` AS doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC";
    $result_doctors = $conn->query($sql_doctors);

    if ($result_doctors && $result_doctors->num_rows > 0) {
        while ($row = $result_doctors->fetch_assoc()) {
            $doctors[] = [
                'id' => $row['doctor_id'],
                'name' => htmlspecialchars($row['last_name']) . ', ' . htmlspecialchars($row['first_name'])
            ];
        }
    }

    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($doctors);
    exit();
}

// ----------------------------------------------------------------------
// --- AJAX POST HANDLER: assign_doctor_bulk -----------------------------
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_doctor_bulk') {
    header('Content-Type: application/json');

    if (!isset($_POST['appointment_id']) || !isset($_POST['doctor_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        $conn->close();
        exit();
    }

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $doctor_id = filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT);

    if (!$appointment_id || !$doctor_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID format.']);
        $conn->close();
        exit();
    }

    try {
        $stmt_fetch = $conn->prepare("SELECT service_type FROM appointment WHERE appointment_id = ?");
        $stmt_fetch->bind_param("i", $appointment_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 0) {
            $stmt_fetch->close();
            echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
            $conn->close();
            exit();
        }

        $row = $result_fetch->fetch_assoc();
        $service_type = $row['service_type'];
        $stmt_fetch->close();

        if (!$appointment_doctor_col) {
            echo json_encode(['success' => false, 'message' => 'Appointment table does not support doctor assignment (missing column).']);
            $conn->close();
            exit();
        }

        $conn->begin_transaction();

        $sql_update = "
            UPDATE
                appointment
            SET
                `{$appointment_doctor_col}` = ?
            WHERE
                service_type = ?
                AND status = 'Scheduled'
                AND (`{$appointment_doctor_col}` IS NULL OR `{$appointment_doctor_col}` = 0)
        ";

        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("is", $doctor_id, $service_type);
        $stmt_update->execute();

        $rows_affected = $stmt_update->affected_rows;
        $stmt_update->close();

        $conn->commit();
        echo json_encode([
            'success' => true,
            'rows_affected' => $rows_affected,
            'message' => "Doctor assigned to $rows_affected appointment(s) with service type: " . $service_type
        ]);
    } catch (Exception $e) {
        if ($conn->in_transaction) $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    $conn->close();
    exit();
}

// ----------------------------------------------------------------------
// --- AJAX POST HANDLER: assign_doctor (single appointment) ------------
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_doctor') {
    header('Content-Type: application/json');

    $appointment_id = filter_var($_POST['appointment_id'] ?? 0, FILTER_VALIDATE_INT);
    $doctor_id = filter_var($_POST['doctor_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$appointment_id || !$doctor_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment or doctor id.']);
        $conn->close();
        exit();
    }

    if (!$appointment_doctor_col) {
        echo json_encode(['success' => false, 'message' => 'Appointment table does not support doctor assignment (missing column).']);
        $conn->close();
        exit();
    }

    try {
        $conn->begin_transaction();

        $sql_update = "UPDATE `appointment` SET `{$appointment_doctor_col}` = ? WHERE appointment_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql_update);
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        $stmt->bind_param("ii", $doctor_id, $appointment_id);
        $stmt->execute();
        $rows_affected = $stmt->affected_rows;
        $stmt->close();

        $conn->commit();

        echo json_encode(['success' => true, 'rows_affected' => $rows_affected, 'message' => 'Doctor assigned to appointment.']);
    } catch (Exception $e) {
        if ($conn->in_transaction) $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    $conn->close();
    exit();
}


// ======================================================================
// === AJAX POST HANDLER: approve_request_with_doctor (FIXED) ===========
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_request_with_doctor') {
    
    ob_start();
    
    header('Content-Type: application/json');

    $booking_id = filter_var($_POST['booking_id'] ?? 0, FILTER_VALIDATE_INT);
    $doctor_id = filter_var($_POST['doctor_id'] ?? 0, FILTER_VALIDATE_INT);
    $patient_email = filter_var($_POST['patient_email'] ?? '', FILTER_SANITIZE_EMAIL);

    $response_data = ['success' => false, 'message' => 'Initialization error.'];

    if (!$booking_id || !$doctor_id) {
        $response_data = ['success' => false, 'message' => 'Invalid booking or doctor id.'];
    } 
    else if (!$appointment_doctor_col || !$appointment_patient_fk) {
        $response_data = ['success' => false, 'message' => 'Appointment table structure incomplete (missing doctor/patient ID column).'];
    }
    else {
        try {
            $conn->begin_transaction();

            // 1. Kumuha ng Request Details
            $stmt_fetch = $conn->prepare("SELECT first_name, last_name, birthdate, booking_date, booking_time, service_type, email FROM booking_request WHERE booking_id = ? AND status = 'Pending'");
            $stmt_fetch->bind_param("i", $booking_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $request_data = $result_fetch->fetch_assoc();
            $stmt_fetch->close();

            if (!$request_data) {
                throw new Exception("Pending booking request not found or already processed.");
            }

            // 2. Kumuha ng Patient ID
            $patient_id_placeholder = generate_unique_patient_identifier(
                $request_data['birthdate'], 
                $request_data['first_name'], 
                $request_data['last_name']
            );
            
            // 3. I-insert ang Appointment
            $name_cols = $patient_names_in_appt ? ", {$appointment_patient_fname_col}, {$appointment_patient_lname_col}" : "";
            $name_qmarks = $patient_names_in_appt ? ", ?, ?" : "";
            
            $sql_insert_appt = "
                INSERT INTO `appointment` 
                ({$appointment_patient_fk}, appointment_date, appointment_time, service_type, {$appointment_doctor_col}, status {$name_cols}) 
                VALUES (?, ?, ?, ?, ?, 'Scheduled' {$name_qmarks})";
            
            $stmt_insert = $conn->prepare($sql_insert_appt);
            
            // Prepare parameter types and values
            $bind_types = "ssssi";
            $bind_values = [
                $patient_id_placeholder, 
                $request_data['booking_date'], 
                $request_data['booking_time'], 
                $request_data['service_type'], 
                $doctor_id
            ];
            
            if ($patient_names_in_appt) {
                $bind_types .= "ss";
                $bind_values[] = $request_data['first_name'];
                $bind_values[] = $request_data['last_name'];
            }
            
            $stmt_insert->bind_param($bind_types, ...$bind_values);

            $stmt_insert->execute();
            $new_appointment_id = $conn->insert_id;
            $stmt_insert->close();

            // 4. I-update ang Booking Request status
            // FIX: Inalis ang assignment ng 'appointment_id' column
            $sql_update_booking = "UPDATE `booking_request` SET `status` = 'Approved' WHERE booking_id = ?";
            $stmt_update = $conn->prepare($sql_update_booking);
            $stmt_update->bind_param("i", $booking_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 5. Commit Transaction
            $conn->commit();

            $response_data = [
                'success' => true,
                'appointment_id' => $new_appointment_id,
                'patient_email' => $patient_email,
                'message' => 'Booking request approved and appointment created.'
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

    $debug_output = ob_get_clean(); 
    if (!empty($debug_output)) {
        error_log("AJAX JSON Corruption Detected: " . trim($debug_output));
        $response_data = ['success' => false, 'message' => 'Critical PHP Error: Server output detected before JSON. Check PHP error logs.'];
    }
    
    echo json_encode($response_data);

    $conn->close();
    exit();
}
// ======================================================================


// ----------------------------------------------------------------------
// --- AJAX HANDLER: fetch_requests_table --------------------------------
// ----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'fetch_requests_table') {
    $requests = [];
    $today_date = date('Y-m-d');
    $today_date_ts = strtotime($today_date);

    $sql_requests = "SELECT booking_id, first_name, last_name, birthdate, service_type, booking_date, booking_time, status, email
                     FROM booking_request
                     WHERE status = 'Pending'
                     ORDER BY booking_date ASC, created_at DESC";

    $result_requests = $conn->query($sql_requests);

    if ($result_requests && $result_requests->num_rows > 0) {
        while ($row = $result_requests->fetch_assoc()) {
            $requests[] = $row;
        }
    }

    $output = '';
    if (empty($requests)) {
        $output = '<tr><td colspan="4" style="text-align:center; color: var(--color-text-medium); background-color: white;">No new booking requests.</td></tr>';
    } else {
        foreach ($requests as $req) {
            $is_past_due = (strtotime($req['booking_date']) < $today_date_ts);
            $row_class = $is_past_due ? 'past-due' : '';
            $approve_class = $is_past_due ? 'disabled' : '';
            $approve_title = $is_past_due ? 'Cannot approve: date has passed' : 'Approve Request (Appoint)';

            $patient_id_display = $req['birthdate'];
            $patient_name_display = formatPatientName($req['first_name'], $req['last_name'], $patient_id_display);

            $output .= '<tr class="' . $row_class . '">';
            $output .= '<td><a href="view_patient.php?birthdate=' . htmlspecialchars($req['birthdate']) . '&source=request" class="patient-id-link">' . $patient_name_display . '</a></td>';
            $output .= '<td class="datetime-cell">';
            $output .= '<span><i class="far fa-calendar-alt"></i> ' . date('M d, Y', strtotime($req['booking_date'])) . '</span>';
            $output .= '<span><i class="far fa-clock"></i> ' . htmlspecialchars(format_time_display($req['booking_time'])) . '</span>';
            if ($is_past_due) {
                $output .= '<span style="color:var(--color-past-due); font-weight:600; font-size:12px;">(Past Due)</span>';
            }
            $output .= '</td>';
            $output .= '<td>' . htmlspecialchars($req['service_type']) . '</td>';
            $output .= '<td class="actions-cell">';
            $jsReq = htmlspecialchars(json_encode([
                'booking_id' => $req['booking_id'],
                'first_name' => $req['first_name'],
                'last_name'  => $req['last_name'],
                'birthdate'  => $req['birthdate'],
                'service_type'=> $req['service_type'],
                'booking_date'=> $req['booking_date'],
                'booking_time'=> $req['booking_time'],
                'email' => $req['email'] ?? ''
            ]), ENT_QUOTES);
            $output .= '<button type="button" onclick="openApproveModalFromRow(' . $jsReq . ')" class="action-icon approve ' . $approve_class . '" title="' . $approve_title . '"><i class="fas fa-user-md"></i></button>';
            $output .= '<a href="process_request.php?id=' . $req['booking_id'] . '&action=decline" onclick="return confirm(\'Are you sure you want to DECLINE this request? \')" class="action-icon decline" title="Decline Request (Cancel)"><i class="fas fa-times"></i></a>';
            $output .= '</td>';
            $output .= '</tr>';
        }
    }

    $conn->close();
    echo $output;
    exit();
}

// ----------------------------------------------------------------------
// --- AJAX HANDLER: fetch_pending_count --------------------------------
// ----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'fetch_pending_count') {
    $sql_count = "SELECT COUNT(booking_id) AS total_pending FROM booking_request WHERE status = 'Pending'";
    $result_count = $conn->query($sql_count);
    $row_count = $result_count ? $result_count->fetch_assoc() : ['total_pending' => 0];
    $conn->close();
    echo $row_count['total_pending'];
    exit();
}

if (isset($_GET['error']) || isset($_GET['message'])) {
    //
}

$today_date = date('Y-m-d');
$filter_date = isset($_GET['selected_date']) && !empty($_GET['selected_date'])
    ? date('Y-m-d', strtotime($_GET['selected_date']))
    : $today_date;

if ($filter_date == $today_date) {
    $main_title = "Today's Appointments";
    $main_subtitle = "Scheduled patient visits for " . date('F j, Y', strtotime($filter_date));
} else {
    $main_title = "Appointments on " . date('F j, Y', strtotime($filter_date));
    $main_subtitle = "Viewing scheduled patient visits for a specific date.";
}

// ----------------------------------------------------------------------
// --- 2. FETCH APPOINTMENTS (ADAPTIVE TO SCHEMA) ------------------------
// ----------------------------------------------------------------------
$appointments = [];

$appt_doctor_select = $appointment_doctor_col ? "a.`{$appointment_doctor_col}` AS doctor_id," : "NULL AS doctor_id,";

if ($patient_names_in_appt) {
    // Use the name columns directly from the appointment table (a)
    $patient_select = "a.{$appointment_patient_fname_col} AS patient_first_name, a.{$appointment_patient_lname_col} AS patient_last_name, a.`{$appointment_patient_fk}` AS patient_id";
    $patient_join_clause = ""; // No join needed
} else {
    // Fallback to the original join logic if names aren't in the appointment table
    $patient_select = $patients_join_possible ? "p.first_name AS patient_first_name, p.last_name AS patient_last_name, a.`{$appointment_patient_fk}` AS patient_id" : "NULL AS patient_first_name, NULL AS patient_last_name, a.`{$appointment_patient_fk}` AS patient_id";
    $patient_join_clause = $patients_join_possible ? "LEFT JOIN patients p ON a.`{$appointment_patient_fk}` = p.`{$patients_pk_col}`" : "";
}


$doctor_name_select = ($doctors_join_possible) ? "d.first_name AS doctor_first_name, d.last_name AS doctor_last_name" : "NULL AS doctor_first_name, NULL AS doctor_last_name";

$doctor_join_clause = $doctors_join_possible ? "LEFT JOIN doctors d ON a.`{$appointment_doctor_col}` = d.`{$doctors_pk_col}`" : "";

$sql_appointments = "
    SELECT
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.service_type,
        a.status,
        {$appt_doctor_select}
        {$patient_select},
        {$doctor_name_select}
    FROM
        appointment a
    {$patient_join_clause}
    {$doctor_join_clause}
    WHERE
        a.appointment_date = ?
    ORDER BY
        a.appointment_time ASC
";

$stmt_appt = $conn->prepare($sql_appointments);
if (!$stmt_appt) {
    $err = $conn->error;
    $conn->close();
    exit("SQL prepare failed while fetching appointments: " . htmlspecialchars($err));
}
$stmt_appt->bind_param("s", $filter_date);
$stmt_appt->execute();
$result_appointments = $stmt_appt->get_result();
if ($result_appointments && $result_appointments->num_rows > 0) {
    while ($row = $result_appointments->fetch_assoc()) {
        if (!empty($row['doctor_first_name']) || !empty($row['doctor_last_name'])) {
            $row['doctor_name'] = htmlspecialchars($row['doctor_last_name'] . ', ' . $row['doctor_first_name']);
        } else if (!empty($row['doctor_id']) && $row['doctor_id'] != '0') {
            $row['doctor_name'] = '<span style="color:#ffc107; font-style:italic;">Doctor ID: ' . htmlspecialchars($row['doctor_id']) . ' (Name Missing)</span>';
        } else {
            $row['doctor_name'] = '<span style="color:var(--color-pending); font-style: italic;">Unassigned / N/A</span>';
        }
        $appointments[] = $row;
    }
}
$stmt_appt->close();

// ----------------------------------------------------------------------
// --- 3. FETCH PENDING REQUESTS (FOR INITIAL LOAD) ---------------------
// ----------------------------------------------------------------------
$requests = [];
$sql_requests = "SELECT booking_id, first_name, last_name, birthdate, service_type, booking_date, booking_time, status, email
                 FROM booking_request
                 WHERE status = 'Pending'
                 ORDER BY booking_date ASC, created_at DESC";

$result_requests = $conn->query($sql_requests);
if ($result_requests && $result_requests->num_rows > 0) {
    while ($row = $result_requests->fetch_assoc()) {
        $requests[] = $row;
    }
}
$pending_count = count($requests);

$conn->close();

if (isset($_GET['selected_date']) && !empty($_GET['selected_date'])) {
    $initial_view = 'scheduled';
} else {
    $initial_view = (isset($_GET['view']) && in_array($_GET['view'], ['requests', 'assigned_doctor'])) ? $_GET['view'] : 'scheduled';
}

$today_display_date = date('l, F j, Y');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Appointments - MindTrack Health Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary-dark: #0077b6; --color-deep-blue: #00A9FF; --color-medium-blue: #89CFF3; --color-light-blue: #A0E9FF;
            --color-very-light-blue: #E0F7FF; --color-text-dark: #333; --color-text-medium: #666; --color-bg-light: #F0F8FF;
            --color-pending: #ffc107; --color-pending-bg: #fff8e1; --color-scheduled: #0077b6; --color-scheduled-bg: #E0F7FF;
            --color-completed: #28a745; --color-completed-bg: #e9f5ea; --color-cancelled: #dc3545; --color-cancelled-bg: #fbebed;
            --color-past-due: #dc3545;
            --color-past-due-bg: #fbebed;
            --color-assign-doctor: #6f42c1;
            --color-assign-doctor-bg: #f2e9ff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #d3e5ff, #e6f6ff); color: var(--color-text-dark); }
        .main-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: linear-gradient(to top, #d3e5ff, #e6f6ff); box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; padding: 30px 0; flex-shrink: 0; }
        .logo-section { display: flex; align-items: center; padding: 0 30px 40px; gap: 10px; }
        .logo-section i { font-size: 28px; color: var(--color-primary-dark); }
        .logo-text h2 { font-size: 18px; font-weight: 600; color: var(--color-primary-dark); line-height: 1; }
        .logo-text p { font-size: 10px; color: var(--color-medium-blue); font-weight: 300; }
        .nav-menu { flex-grow: 1; padding: 0 20px; }
        .nav-item { display: flex; align-items: center; text-decoration: none; color: var(--color-text-medium); font-size: 15px; margin-bottom: 8px; padding: 12px 15px; border-radius: 8px; transition: all 0.2s ease; font-weight: 500; }
        .nav-item:hover:not(.active) { background-color: var(--color-very-light-blue); color: var(--color-primary-dark); }
        .nav-item.active { background-color: white; color: var(--color-primary-dark); font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .nav-item i { margin-right: 15px; font-size: 18px; color: var(--color-medium-blue); }
        .nav-item.active i { color: var(--color-primary-dark); }
        .main-content-area { flex-grow: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header h1 { font-size: 20px; color: var(--color-primary-dark); font-weight: 600; }
        .header p { font-size: 14px; color: var(--color-text-medium); margin-top: 5px; }
        .header-date { font-size: 14px; color: var(--color-text-medium); display: flex; align-items: center; gap: 8px; }
        .content-section { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); border: 1px solid var(--color-very-light-blue); }
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; }

        .view-switcher { display: flex; border-radius: 8px; background-color: var(--color-bg-light); padding: 5px; gap: 5px; }
        .view-switcher a { text-decoration: none; color: var(--color-text-medium); padding: 8px 15px; border-radius: 6px; font-size: 14px; font-weight: 500; transition: all 0.2s; cursor: pointer; }
        .view-switcher a.active-view { background-color: white; color: var(--color-primary-dark); font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .view-switcher a:hover:not(.active-view) { color: var(--color-primary-dark); }
        .view-switcher a i { margin-right: 5px; }

        .appointments-table, .requests-table, .unassigned-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .appointments-table thead th, .requests-table thead th, .unassigned-table thead th { text-align: left; padding: 10px 15px; color: var(--color-text-medium); font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .appointments-table tbody td, .requests-table tbody td, .unassigned-table tbody td { background-color: var(--color-bg-light); padding: 15px; font-size: 14px; font-weight: 500; color: var(--color-text-dark); vertical-align: middle; border: 1px solid var(--color-very-light-blue); border-width: 1px 0; }
        .requests-table tbody tr.past-due td { background-color: var(--color-past-due-bg); color: var(--color-past-due); font-style: italic; opacity: 0.7; }

        .appointments-table tbody tr td:first-child, .requests-table tbody tr td:first-child, .unassigned-table tbody tr td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; border-left-width: 1px; }
        .appointments-table tbody tr td:last-child, .requests-table tbody tr td:last-child, .unassigned-table tbody tr:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; border-right-width: 1px; }
        .appointments-table tbody tr:hover td, .requests-table tbody tr:not(.past-due):hover td, .unassigned-table tbody tr:hover td { background-color: var(--color-very-light-blue); }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-Scheduled { background-color: var(--color-scheduled-bg); color: var(--color-scheduled); }
        .status-Completed { background-color: var(--color-completed-bg); color: var(--color-completed); }
        .status-Cancelled { background-color: var(--color-cancelled-bg); color: var(--color-cancelled); }
        .status-Pending { background-color: var(--color-pending-bg); color: var(--color-pending); }
        .status-Declined { background-color: var(--color-cancelled-bg); color: var(--color-cancelled); }

        .actions-cell { display: flex; gap: 5px; }
        .action-icon { background: white; border: 1px solid var(--color-very-light-blue); color: var(--color-text-medium); width: 30px; height: 30px; border-radius: 5px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .action-icon.approve:hover { background-color: #e9f5ea; color: #28a745; }
        .action-icon.decline:hover { background-color: #fbebed; color: #dc3545; }
        .action-icon.complete:hover { background-color: var(--color-completed-bg); color: var(--color-completed); }
        .action-icon.disabled { opacity: 0.5; pointer-events: none; }
        .assign-btn {
            background-color: var(--color-assign-doctor);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.2s;
            font-weight: 500;
        }
        .assign-btn:hover { background-color: #5b36a1; }

        .patient-id-link { text-decoration: none; color: var(--color-primary-dark); font-weight: 600; cursor: pointer; }
        .patient-id-link:hover { text-decoration: underline; color: var(--color-deep-blue); }
        .datetime-cell span { display: block; line-height: 1.4;}

        .date-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-filter-group label {
            font-size: 14px;
            color: var(--color-text-medium);
            font-weight: 500;
        }
        .date-filter-group input[type="date"] {
            padding: 8px 10px;
            border: 1px solid var(--color-light-blue);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            color: var(--color-text-dark);
            background-color: white;
            cursor: pointer;
        }

        /* MODAL Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--color-very-light-blue);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            font-size: 18px;
            color: var(--color-assign-doctor);
        }
        .close-btn {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: var(--color-cancelled);
            text-decoration: none;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--color-text-dark);
        }
        .form-group select, .form-group input[readonly] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-light-blue);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background-color: var(--color-bg-light);
        }
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }
        .modal-footer button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .modal-footer .cancel-btn {
            background-color: #e9ecef;
            color: var(--color-text-medium);
            margin-right: 10px;
        }
        .modal-footer .save-btn {
            background-color: var(--color-assign-doctor);
            color: white;
        }
        .modal-footer .save-btn:hover { background-color: #5b36a1; }
        
        /* NEW: Toast Notification Styles */
        #toast-notification {
            visibility: hidden;
            min-width: 250px;
            background-color: var(--color-completed);
            color: white;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 1100;
            left: 50%;
            transform: translateX(-50%);
            top: 30px;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.5s, top 0.5s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #toast-notification.show {
            visibility: visible;
            opacity: 1;
            top: 40px; /* Slight drop-down effect */
        }
    </style>
</head>
<body>
<div id="toast-notification">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message"></span>
</div>

<div class="main-container">
    <div class="sidebar">
        <div class="logo-section"><i class="fas fa-heartbeat"></i><div class="logo-text"><h2>MindTrack</h2><p>Health Management</p></div></div>
        <nav class="nav-menu">
            <a class="nav-item" href="mindtrack.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-item active" href="appointment.php"><i class="far fa-calendar-alt"></i>Appointments</a>
            <a class="nav-item" href="cl.php"><i class="far fa-calendar-check"></i>Calendar</a>
            <a class="nav-item" href="pt.php"><i class="fas fa-users"></i>Patients</a>
            <a class="nav-item" href="doctors.php"><i class="fas fa-user-md"></i>Doctors</a>
            <a class="nav-item" href="set.php"><i class="fas fa-cog"></i>Settings</a>
        </nav>
    </div>
    <div class="main-content-area">
        <div class="header">
            <div>
                <h1 id="main-header-title"><?= $main_title ?></h1>
                <p id="main-header-subtitle"><?= $main_subtitle ?></p>
            </div>
            <div class="header-date"><span><?= $today_display_date ?></span></div>
        </div>

        <div class="content-section">
            <div class="table-controls">
                <div class="view-switcher">
                    <a id="show-scheduled-btn" class="view-btn" data-view="scheduled" href="?selected_date=<?= htmlspecialchars($filter_date) ?>&view=scheduled">
                        <i class="far fa-calendar-check"></i> View Appointments
                    </a>

                    <a id="show-requests-btn" class="view-btn" data-view="requests" href="?view=requests">
                        <i class="fas fa-inbox"></i> Booking Requests
                        <?php if ($pending_count > 0): ?>
                            <span id="pending-count-badge" style="background:var(--color-pending); color:white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <form method="GET" class="date-filter-group" id="date-filter-form" style="display: <?= ($initial_view == 'scheduled' ? 'flex' : 'none') ?>;">
                    <label for="date-filter">Filter by Date:</label>
                    <input type="date" id="date-filter" name="selected_date" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">
                    <input type="hidden" name="view" value="scheduled">
                </form>
            </div>

            <div id="scheduled-section">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Patient Name (ID)</th>
                            <th>Time</th>
                            <th>Service Type</th>
                            <th>Assigned Doctor</th> <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="6" style="text-align:center; color: var(--color-text-medium); background-color: white;">No appointments scheduled for <?= date('F j, Y', strtotime($filter_date)) ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <a href="view_patient.php?patient_id=<?= htmlspecialchars($appt['patient_id']) ?>&source=appt" class="patient-id-link">
                                        <?= formatPatientName($appt['patient_first_name'] ?? '', $appt['patient_last_name'] ?? '', $appt['patient_id'] ?? '') ?>
                                    </a>
                                </td>
                                <td><i class="far fa-clock"></i> <?= htmlspecialchars(format_time_display($appt['appointment_time'])) ?></td>
                                <td><?= htmlspecialchars($appt['service_type']) ?></td>
                                <td>
                                    <?= $appt['doctor_name'] ?>
                                </td>
                                <td><span class="status-badge status-<?= htmlspecialchars($appt['status']) ?>"><?= htmlspecialchars($appt['status']) ?></span></td>
                                <td class="actions-cell">
                                    <a href="update_status.php?id=<?= $appt['appointment_id'] ?>&action=complete" class="action-icon complete" title="Mark as Completed"><i class="fas fa-check-circle"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="requests-section" style="display: none;">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Patient Name (ID)</th>
                            <th>Requested Date & Time</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requests-table-body">
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="4" style="text-align:center; color: var(--color-text-medium); background-color: white;">No new booking requests.</td></tr>
                        <?php else: ?>
                            <?php
                                $today_date_ts = strtotime($today_date);
                                foreach ($requests as $req):
                                $is_past_due = (strtotime($req['booking_date']) < $today_date_ts);
                                $row_class = $is_past_due ? 'past-due' : '';
                                $approve_class = $is_past_due ? 'disabled' : '';
                                $approve_title = $is_past_due ? 'Cannot approve: date has passed' : 'Approve Request (Appoint)';

                                $patient_id_display = $req['birthdate'];
                                $patient_name_display = formatPatientName($req['first_name'], $req['last_name'], $patient_id_display);
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td>
                                    <a href="view_patient.php?birthdate=<?= htmlspecialchars($req['birthdate']) ?>&source=request" class="patient-id-link">
                                        <?= $patient_name_display ?>
                                    </a>
                                </td>
                                <td class="datetime-cell">
                                    <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($req['booking_date'])) ?></span>
                                    <span><i class="far fa-clock"></i> <?= htmlspecialchars(format_time_display($req['booking_time'])) ?></span>
                                    <?php if($is_past_due): ?>
                                        <span style="color:var(--color-past-due); font-weight:600; font-size:12px;">(Past Due)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($req['service_type']) ?></td>
                                <td class="actions-cell">
                                    <?php
                                        $jsReq = htmlspecialchars(json_encode([
                                            'booking_id' => $req['booking_id'],
                                            'first_name' => $req['first_name'],
                                            'last_name'  => $req['last_name'],
                                            'birthdate'  => $req['birthdate'],
                                            'service_type'=> $req['service_type'],
                                            'booking_date'=> $req['booking_date'],
                                            'booking_time'=> $req['booking_time'],
                                            'email' => $req['email'] ?? ''
                                        ]), ENT_QUOTES);
                                    ?>
                                    <button type="button" onclick="openApproveModalFromRow(<?= $jsReq ?>)" class="action-icon approve <?= $approve_class ?>" title="<?= $approve_title ?>"><i class="fas fa-user-md"></i></button>
                                     <a href="process_request.php?id=<?= $req['booking_id'] ?>&action=decline" onclick="return confirm('Are you sure you want to DECLINE this request?')" class="action-icon decline" title="Decline Request (Cancel)"><i class="fas fa-times"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="assign-doctor-section" style="display: none;">
                <p style="margin-bottom: 15px; font-size: 14px; color: var(--color-text-medium);">Appointments that are scheduled but do not yet have an assigned doctor. Click 'Assign Doctor' to perform bulk assignment by service type.</p>
                <table class="unassigned-table">
                    <thead>
                        <tr>
                            <th>Patient Name (ID)</th>
                            <th>Date & Time</th>
                            <th>Service Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="unassigned-appointments-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="assignDoctorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Assign Doctor</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form id="doctorAssignmentForm">
            <input type="hidden" id="apptId" name="appointment_id">

            <div class="form-group">
                <label for="modalServiceType">Service Type</label>
                <input type="text" id="modalServiceType" name="service_type" readonly>
                <small style="color: var(--color-assign-doctor); font-weight: 500;">Note: This will assign the selected doctor only to the specific appointment chosen.</small>
            </div>

            <div class="form-group">
                <label for="doctorSelect">Select Doctor</label>
                <select id="doctorSelect" name="doctor_id" required>
                    <option value="">-- Choose a Doctor --</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="submit" class="save-btn">Assign</button>
            </div>
        </form>
    </div>
</div>

<div id="approveRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Assign Doctor & Approve</h2>
            <span class="close-btn" onclick="closeApproveModal()">&times;</span>
        </div>
        <form id="approveRequestForm">
            <input type="hidden" id="approveBookingId" name="booking_id">
            <div class="form-group">
                <label>Patient</label>
                <input type="text" id="approvePatientName" readonly>
            </div>
            <div class="form-group">
                <label>Requested Date & Time</label>
                <input type="text" id="approveDatetime" readonly>
            </div>

            <div class="form-group">
                <label>Patient Email</label>
                <input type="email" id="approveEmail" name="patient_email" readonly placeholder="patient@example.com">
            </div>

            <div class="form-group">
                <label for="approveDoctorSelect">Assign Doctor</label>
                <select id="approveDoctorSelect" name="doctor_id" required>
                    <option value="">Loading doctors...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="save-btn">Assign & Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
    // NEW: Toast Notification Function
    function showToast(message, duration = 10000) {
        const toast = document.getElementById("toast-notification");
        const toastMessage = document.getElementById("toast-message");
        
        // Hide any existing toast first
        toast.classList.remove("show");
        clearTimeout(toast.timer);
        
        toastMessage.textContent = message;
        toast.classList.add("show");
        
        // Auto-hide the toast after the specified duration
        toast.timer = setTimeout(function(){ 
            toast.classList.remove("show"); 
        }, duration);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const scheduledSection = document.getElementById('scheduled-section');
        const requestsSection = document.getElementById('requests-section');
        const assignDoctorSection = document.getElementById('assign-doctor-section');

        const showScheduledBtn = document.getElementById('show-scheduled-btn');
        const showRequestsBtn = document.getElementById('show-requests-btn');

        const headerTitle = document.getElementById('main-header-title');
        const headerSubtitle = document.getElementById('main-header-subtitle');
        const dateFilterForm = document.getElementById('date-filter-form');

        const requestsTableBody = document.getElementById('requests-table-body');
        const unassignedTableBody = document.getElementById('unassigned-appointments-body');
        let pendingCountBadge = document.getElementById('pending-count-badge');

        const initialView = '<?= $initial_view ?>';
        const defaultTitleText = "<?= $main_title ?>";
        const defaultSubtitleText = "<?= $main_subtitle ?>";
        const requestsTitleText = "New Booking Requests";
        const requestsSubtitleText = "Review and approve new patient appointment requests.";
        const assignDoctorTitleText = "Doctor Assignment - Unassigned Appointments";
        const assignDoctorSubtitleText = "Assign doctors to scheduled appointments for today and upcoming days.";

        // MODAL ELEMENTS
        const modal = document.getElementById('assignDoctorModal');
        const apptIdField = document.getElementById('apptId');
        const modalServiceTypeField = document.getElementById('modalServiceType');
        const doctorSelect = document.getElementById('doctorSelect');
        const assignmentForm = document.getElementById('doctorAssignmentForm');

        window.openModal = function(appointmentId, serviceType) {
            apptIdField.value = appointmentId;
            modalServiceTypeField.value = serviceType;
            fetchDoctors();
            modal.style.display = 'block';
        }

        window.closeModal = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // APPROVE REQUEST (Assign doctor then create appointment)
        const approveModal = document.getElementById('approveRequestModal');
        const approveForm = document.getElementById('approveRequestForm');
        const approveBookingId = document.getElementById('approveBookingId');
        const approvePatientName = document.getElementById('approvePatientName');
        const approveDatetime = document.getElementById('approveDatetime');
        const approveDoctorSelect = document.getElementById('approveDoctorSelect');
        const approveEmail = document.getElementById('approveEmail');

        window.openApproveModalFromRow = function(req) {
            approveBookingId.value = req.booking_id || '';
            
            approvePatientName.value = (req.last_name || '') + ', ' + (req.first_name || '') + ' (' + (req.birthdate || '') + ')';
            
            const datePart = req.booking_date || '';
            const timePart = (req.booking_time || '').toString().trim();
            approveDatetime.value = datePart + ' ' + timePart + ' — ' + (req.service_type || '');
            approveEmail.value = req.email || '';
            approveDoctorSelect.innerHTML = '<option>Loading doctors...</option>';
            approveModal.style.display = 'block';

            fetch('appointment.php?action=fetch_doctors')
                .then(r => r.json())
                .then(list => {
                    approveDoctorSelect.innerHTML = '<option value=""> Choose a Doctor </option>';
                    list.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        approveDoctorSelect.appendChild(opt);
                    });
                })
                .catch(() => {
                    approveDoctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
                });
        }

        window.closeApproveModal = function() {
            approveModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == approveModal) closeApproveModal();
        }

        approveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const bookingId = approveBookingId.value;
            const doctorId = approveDoctorSelect.value;
            const patientEmail = approveEmail.value;
            if (!doctorId) { alert('Please select a doctor.'); return; }

            const fd = new FormData();
            fd.append('action', 'approve_request_with_doctor');
            fd.append('booking_id', bookingId);
            fd.append('doctor_id', doctorId);
            if (patientEmail) fd.append('patient_email', patientEmail);

            fetch('appointment.php', { method: 'POST', body: fd })
                .then(r => {
                    if (!r.ok) {
                        return r.text().then(text => { throw new Error('HTTP Error ' + r.status + ': ' + text) });
                    }
                    return r.json();
                })
                .then(resp => {
                    if (resp.success) {
                        showToast('Request Approved! Appointment ID: ' + (resp.appointment_id || '—'), 60000);
                        closeApproveModal();
                        
                        // I-update ang table at count, tapos i-reload ang Scheduled section
                        if (typeof updateRequestsTable === 'function') updateRequestsTable();
                        if (typeof updatePendingCount === 'function') updatePendingCount();
                        
                        // Delay the reload para makita ang toast, tapos i-redirect sa scheduled view
                        setTimeout(() => { 
                             window.location.href = 'appointment.php?selected_date=<?= $filter_date ?>&view=scheduled';
                        }, 500); 
                    } else {
                        // Gumamit pa rin ng alert() para sa error
                        alert('Error: ' + (resp.message || 'Unable to approve request.'));
                    }
                })
                .catch(err => {
                    console.error("AJAX Error:", err.message);
                    alert('An error occurred while approving the request: ' + (err.message.includes("Unexpected token") ? "Server did not return a valid JSON response. Please check PHP error logs on the server." : err.message));
                });
        });

        // --- DATA FETCHING FUNCTIONS ---
        function fetchDoctors() {
            doctorSelect.innerHTML = '<option value="">Loading Doctors...</option>';
            fetch('appointment.php?action=fetch_doctors')
                .then(response => response.json())
                .then(doctors => {
                    doctorSelect.innerHTML = '<option value="">-- Choose a Doctor --</option>';
                    doctors.forEach(doctor => {
                        const option = document.createElement('option');
                        option.value = doctor.id;
                        option.textContent = doctor.name;
                        doctorSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching doctors:', error);
                    doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
                });
        }

        function updateUnassignedAppointmentsTable() {
            fetch('appointment.php?action=fetch_unassigned_appointments')
                .then(response => response.text())
                .then(html => {
                    if (unassignedTableBody) {
                        unassignedTableBody.innerHTML = html;
                        attachAssignButtonListeners();
                    }
                })
                .catch(error => {
                    console.error('Error fetching unassigned appointments:', error);
                    unassignedTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--color-cancelled);">Error loading unassigned appointments.</td></tr>';
                });
        }

        function attachAssignButtonListeners() {
            document.querySelectorAll('.assign-btn').forEach(button => {
                button.removeEventListener('click', handleAssignButtonClick);
                button.addEventListener('click', handleAssignButtonClick);
            });
        }

        function handleAssignButtonClick(event) {
            event.preventDefault();
            const button = event.target;
            const apptId = button.getAttribute('data-id');
            const serviceType = button.getAttribute('data-service');
            openModal(apptId, serviceType);
        }

        // --- FORM SUBMISSION HANDLER ---
        assignmentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const appointmentId = apptIdField.value;
            const doctorId = doctorSelect.value;
            const serviceType = modalServiceTypeField.value;

            if (!doctorId) {
                alert("Please select a doctor.");
                return;
            }

            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('doctor_id', doctorId);
            formData.append('action', 'assign_doctor_bulk');

            fetch('appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Doctor successfully assigned! Total ' + data.rows_affected + ' appointment(s) with the same service type were updated.', 5000);
                    closeModal();
                    updateUnassignedAppointmentsTable();
                    window.location.reload();
                } else {
                    alert('Assignment failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error during assignment:', error);
                alert('An error occurred during doctor assignment.');
            });
        });

        // --- VIEW SWITCHING LOGIC ---
        function switchView(view) {
            document.querySelectorAll('.view-btn').forEach(button => {
                 button.classList.remove('active-view');
            });

            scheduledSection.style.display = 'none';
            requestsSection.style.display = 'none';
            assignDoctorSection.style.display = 'none';
            dateFilterForm.style.display = 'none';

            if (view === 'requests') {
                showRequestsBtn.classList.add('active-view');
                requestsSection.style.display = 'block';
                headerTitle.textContent = requestsTitleText;
                headerSubtitle.textContent = requestsSubtitleText;
                updateRequestsTable();
            } else if (view === 'assigned_doctor') {
                assignDoctorSection.style.display = 'block';
                headerTitle.textContent = assignDoctorTitleText;
                headerSubtitle.textContent = assignDoctorSubtitleText;
                updateUnassignedAppointmentsTable();
            } else {
                showScheduledBtn.classList.add('active-view');
                scheduledSection.style.display = 'block';
                dateFilterForm.style.display = 'flex';
                headerTitle.textContent = defaultTitleText;
                headerSubtitle.textContent = defaultSubtitleText;
            }
        }

        function updatePendingCount() {
            fetch('appointment.php?action=fetch_pending_count')
                .then(response => response.text())
                .then(count => {
                    const countInt = parseInt(count.trim());

                    pendingCountBadge = document.getElementById('pending-count-badge');

                    if (countInt > 0) {
                        if (!pendingCountBadge) {
                            const newBadge = document.createElement('span');
                            newBadge.id = 'pending-count-badge';
                            newBadge.style.cssText = "background:var(--color-pending); color:white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;";
                            newBadge.textContent = countInt;
                            showRequestsBtn.appendChild(newBadge);
                            pendingCountBadge = newBadge;
                        } else {
                            pendingCountBadge.textContent = countInt;
                        }
                    } else if (pendingCountBadge) {
                        pendingCountBadge.remove();
                    }
                })
                .catch(error => {
                    console.error('Error fetching pending count:', error);
                });
        }

        function updateRequestsTable() {
            fetch('appointment.php?action=fetch_requests_table')
                .then(response => response.text())
                .then(html => {
                    if (requestsTableBody) {
                        requestsTableBody.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error fetching requests table:', error);
                });
        }

        switchView(initialView);

        setInterval(function() {
            updatePendingCount();
            if (requestsSection.style.display === 'block') {
                updateRequestsTable();
            }
            if (assignDoctorSection.style.display === 'block') {
                updateUnassignedAppointmentsTable();
            }
        }, 5000);

        updatePendingCount();

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.getAttribute('data-view') === 'assigned_doctor') {
                    e.preventDefault();
                }
                const view = this.getAttribute('data-view');
                if(view !== 'scheduled' && view !== 'requests' && view !== 'assigned_doctor') {
                    return;
                }
                if(view !== initialView) {
                    const newUrl = new URL(window.location.href);
                    if (view === 'scheduled') {
                        newUrl.searchParams.set('view', 'scheduled');
                    } else if (view === 'requests') {
                        newUrl.searchParams.set('view', 'requests');
                        newUrl.searchParams.delete('selected_date');
                    } else if (view === 'assigned_doctor') {
                        newUrl.searchParams.set('view', 'assigned_doctor');
                        newUrl.searchParams.delete('selected_date');
                    }
                    history.pushState(null, '', newUrl.href);
                }
                switchView(view);
            });
        });
    });
</script>
</body>
</html>