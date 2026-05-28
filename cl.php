<?php
// Tiyakin ang tamang Time Zone para sa petsa (hal. Philippines/Manila)
date_default_timezone_set('Asia/Manila');
$current_system_date = date('Y-m-d');

// Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    // Palitan ang "localhost", "root", "", "mindtrack" kung iba ang credentials
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    exit("Database connection failed: " . $e->getMessage());
}

// --- 1. TUKUYIN ANG BUWAN, TAON, AT PINILING PETSA ---
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Base date para sa buwan na ipapakita (first day of the displayed month)
$current_display_date_str = date('Y-m-01', strtotime("$year-$month-01"));

// Kumuha ng Selected Date mula sa URL, o gamitin ang Today's Date
$selected_date_str = isset($_GET['date']) ? $_GET['date'] : $current_system_date; 

// Tiyakin na ang selected date ay nasa loob ng kasalukuyang display month/year
// Kung wala sa buwan, i-set sa unang araw ng display month ang selected date
if (date('m', strtotime($selected_date_str)) != $month || date('Y', strtotime($selected_date_str)) != $year) {
    $selected_date_str = date('Y-m-d', strtotime("$year-$month-01"));
}

$current_display_date_obj = new DateTime($current_display_date_str);
$current_month_name = $current_display_date_obj->format('F');
$current_year = $current_display_date_obj->format('Y');

$days_in_month = (int)$current_display_date_obj->format('t');
$start_day_offset = (int)$current_display_date_obj->format('w'); // 0 (Sun) - 6 (Sat)

// Navigation logic para sa previous/next month
$prev_month = date('m', strtotime("$current_display_date_str -1 month"));
$prev_year = date('Y', strtotime("$current_display_date_str -1 month"));
$next_month = date('m', strtotime("$current_display_date_str +1 month"));
$next_year = date('Y', strtotime("$current_display_date_str +1 month"));

$prev_link = "cl.php?month=$prev_month&year=$prev_year";
$next_link = "cl.php?month=$next_month&year=$next_year";

// --- 2. FETCH APPOINTMENT DATA PARA SA BUONG DISPLAYED MONTH (Indicator) ---
$appointments_on_days = [];
$end_of_month = date('Y-m-t', strtotime($current_display_date_str));

$sql_month_appointments = "
    SELECT 
        DATE_FORMAT(appointment_date, '%e') as day_of_month 
    FROM 
        appointment 
    WHERE 
        status = 'Scheduled' 
        AND appointment_date BETWEEN ? AND ?
    GROUP BY 
        day_of_month";

$stmt_month = $conn->prepare($sql_month_appointments);
$stmt_month->bind_param("ss", $current_display_date_str, $end_of_month);
$stmt_month->execute();
$result_month = $stmt_month->get_result();

while ($row = $result_month->fetch_assoc()) {
    $appointments_on_days[(int)$row['day_of_month']] = true;
}
$stmt_month->close();

// --- 3. FETCH APPOINTMENT DETAILS PARA SA SELECTED DATE ---
$selected_appointments = [];
$header_date = date('l, F j, Y', strtotime($selected_date_str));

$sql_selected_day = "
    SELECT 
        a.appointment_time, 
        a.service_type, 
        b.birthdate AS patient_id_dob 
    FROM 
        appointment a
    JOIN
        booking_request b ON a.booking_id = b.booking_id
    WHERE 
        a.appointment_date = ? 
        AND a.status = 'Scheduled'
    ORDER BY 
        a.appointment_time ASC";

$stmt_day = $conn->prepare($sql_selected_day);
$stmt_day->bind_param("s", $selected_date_str);
$stmt_day->execute();
$result_day = $stmt_day->get_result();

while ($row = $result_day->fetch_assoc()) {
    $appointment_time_db = $row['appointment_time'];
    
    // PAG-AAYOS DITO: Pilitin ang AM na maging PM kung 1:00 AM o 5:00 AM
    // Ito ay temporary workaround dahil sa posibleng maling data entry
    if (in_array($appointment_time_db, ['01:00:00', '05:00:00'])) {
        $appointment_time_db = date('H:i:s', strtotime($appointment_time_db . ' + 12 hours')); // E.g., 05:00:00 -> 17:00:00 (5 PM)
    }

    $selected_appointments[] = [
        'time' => date('h:i A', strtotime($appointment_time_db)), // Gumamit ng $appointment_time_db
        'patient' => htmlspecialchars($row['patient_id_dob']) . ' - ' . htmlspecialchars($row['service_type'])
    ];
}
$stmt_day->close();

// --- 4. FETCH UPCOMING APPOINTMENTS (Next 7 days mula sa Selected Date) ---
$upcoming_appointments = [];
// Magsimula sa ARAW PAGKATAPOS ng selected date
$start_date_upcoming = date('Y-m-d', strtotime($selected_date_str . ' + 1 day'));
// Hanggang sa 7 days mula sa start date (e.g., kung Selected Date ay 10/21, hahanapin ang 10/22 hanggang 10/28)
$date_limit = date('Y-m-d', strtotime($start_date_upcoming . ' + 7 days'));

$sql_upcoming = "
    SELECT 
        a.appointment_date, 
        a.appointment_time, 
        b.birthdate AS patient_id_dob 
    FROM 
        appointment a
    JOIN
        booking_request b ON a.booking_id = b.booking_id
    WHERE 
        a.appointment_date >= ?
        AND a.appointment_date < ?
        AND a.status = 'Scheduled'
    ORDER BY 
        a.appointment_date ASC, a.appointment_time ASC
    LIMIT 10"; 

$stmt_upcoming = $conn->prepare($sql_upcoming);
$stmt_upcoming->bind_param("ss", $start_date_upcoming, $date_limit); 
$stmt_upcoming->execute();
$result_upcoming = $stmt_upcoming->get_result();

while ($row = $result_upcoming->fetch_assoc()) {
    $appointment_time_db = $row['appointment_time'];

    // PAG-AAYOS DITO: Pilitin ang AM na maging PM kung 1:00 AM o 5:00 AM
    if (in_array($appointment_time_db, ['01:00:00', '05:00:00'])) {
        $appointment_time_db = date('H:i:s', strtotime($appointment_time_db . ' + 12 hours')); 
    }

    $upcoming_appointments[] = [
        'date' => date('M j', strtotime($row['appointment_date'])),
        'time' => date('h:i A', strtotime($appointment_time_db)), // Gumamit ng $appointment_time_db
        'patient' => htmlspecialchars($row['patient_id_dob'])
    ];
}
$stmt_upcoming->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Calendar - MindTrack Health Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Variables */
        :root {
            --color-primary-dark: #0077b6;
            --color-deep-blue: #00A9FF;
            --color-medium-blue: #89CFF3; 
            --color-light-blue: #A0E9FF; 
            --color-very-light-blue: #E0F7FF;
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-light: #F0F8FF;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #d3e5ff, #e6f6ff); color: var(--color-text-dark); }

        .main-container { display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar Navigation --- */
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

        .user-info { padding: 20px 30px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background-color: var(--color-light-blue); color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-right: 10px; }
        .user-details strong { color: var(--color-text-dark); font-size: 14px; }
        .user-details span { font-size: 11px; color: var(--color-text-medium); }
        /* End Sidebar */

        /* --- Main Content Area --- */
        .main-content-area { flex-grow: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-title { padding: 0; }
        .header-title h1 { font-size: 24px; color: var(--color-text-dark); font-weight: 600; } 
        .header-title p { font-size: 14px; color: var(--color-text-medium); margin-top: 5px; } 

        .btn-primary { background-color: var(--color-deep-blue); color: white; padding: 10px 15px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(0, 169, 255, 0.3); }
        .btn-primary:hover { background-color: var(--color-primary-dark); }

        /* Calendar Layout */
        .calendar-layout { display: flex; gap: 20px; flex-grow: 1; }
        .calendar-card { flex: 2; background-color: white; border-radius: 10px; padding: 25px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); border: 1px solid var(--color-very-light-blue); display: flex; flex-direction: column; }
        
        /* Appointment Cards Container */
        .appointment-cards-container { flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px; }
        .appointment-card { background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); border: 1px solid var(--color-very-light-blue); }

        /* Calendar Grid Styling */
        .calendar-header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-weight: 600; }
        .calendar-month-year { font-size: 18px; color: var(--color-text-dark); }
        .nav-arrows { display: flex; gap: 5px; }
        .nav-arrows a i { font-size: 14px; color: var(--color-text-medium); padding: 8px; cursor: pointer; border: 1px solid var(--color-very-light-blue); border-radius: 5px; transition: background-color 0.2s; text-decoration: none; }
        .nav-arrows a:hover i { background-color: var(--color-bg-light); }

        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; flex-grow: 1; }
        .calendar-day-name { font-size: 13px; font-weight: 600; color: var(--color-text-dark); padding-bottom: 10px; text-transform: uppercase; }
        
        /* Day Number Styling */
        .calendar-day-number { font-size: 14px; padding: 8px 0; border-radius: 8px; cursor: pointer; font-weight: 500; color: var(--color-text-dark); height: 40px; width: 40px; display: flex; align-items: center; justify-content: center; margin: auto; transition: background-color 0.2s, color 0.2s; text-decoration: none; }
        .calendar-day-number.offset { color: var(--color-text-medium); opacity: 0.5; cursor: default; pointer-events: none; }
        .calendar-day-number:not(.offset):hover { background-color: var(--color-very-light-blue); }

        /* Calendar Status Colors (Dynamic) */
        .calendar-day-number.has-appointment { background-color: var(--color-light-blue); color: var(--color-primary-dark); font-weight: 600; }
        .calendar-day-number.today { background-color: var(--color-primary-dark); color: white; font-weight: 700; border: none; }
        .calendar-day-number.selected { background-color: var(--color-medium-blue); color: white; font-weight: 700; border: none; }
        
        .calendar-legend { display: flex; gap: 20px; padding: 15px 10px 0; font-size: 12px; color: var(--color-text-medium); }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-box { width: 10px; height: 10px; border-radius: 50%; }
        .legend-box.has-appointment { background-color: var(--color-light-blue); }
        .legend-box.today { background-color: var(--color-primary-dark); }
        .legend-box.selected { background-color: var(--color-medium-blue); }
        
        /* Appointment Cards Detail Styling */
        .appointment-card-title { 
            font-size: 16px; 
            font-weight: 600; 
            color: transparent; /* Changed to transparent as it uses anchor text for color */
            margin-bottom: 15px; 
            border-bottom: 1px solid var(--color-very-light-blue); 
            padding-bottom: 10px; 
        }

        /* Styling for the clickable link inside the card title */
        .appointment-card-title a {
            color: var(--color-primary-dark);
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .appointment-card-title a:hover {
            color: var(--color-deep-blue);
        }


        .appointment-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--color-very-light-blue); }
        .appointment-item:last-child { border-bottom: none; padding-bottom: 0; }
        .appointment-item strong { font-size: 14px; font-weight: 600; color: var(--color-text-dark); }
        .appointment-item span { font-size: 13px; color: var(--color-text-medium); font-weight: 500; }

        /* Upcoming Appointments Styling */
        .upcoming-list { display: flex; flex-direction: column; gap: 10px; }
        .upcoming-item { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px dotted var(--color-very-light-blue); }
        .upcoming-item:last-child { border-bottom: none; }
        .upcoming-details { display: flex; flex-direction: column; }
        .upcoming-date { font-size: 14px; font-weight: 600; color: var(--color-primary-dark); }
        .upcoming-patient { font-size: 13px; color: var(--color-text-dark); }
        .upcoming-time { font-size: 12px; font-weight: 500; color: var(--color-text-medium); background-color: var(--color-bg-light); padding: 5px 8px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="sidebar">
        <div class="logo-section">
            <i class="fas fa-heartbeat"></i>
            <div class="logo-text">
                <h2>MindTrack</h2>
                <p>Health Management</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a class="nav-item" href="mindtrack.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-item" href="appointment.php"><i class="far fa-calendar-alt"></i>Appointments</a>
            <a class="nav-item active" href="cl.php"><i class="far fa-calendar-check"></i>Calendar</a>
            <a class="nav-item" href="pt.php"><i class="fas fa-users"></i>Patients</a>
            <a class="nav-item" href="doctors.php"><i class="fas fa-user-md"></i>Doctors</a>
            <a class="nav-item" href="set.php"><i class="fas fa-cog"></i>Settings</a>
        </nav>

        <div class="user-info">
            <div class="user-avatar">A</div>
            <div class="user-details">
                <strong>Admin User</strong>
                <span>admin@mindtrack.com</span>
            </div>
        </div>
    </div>

    <div class="main-content-area">
        <div class="header">
            <div class="header-title">
                <h1>Calendar</h1>
                <p>View and manage appointments by date</p>
            </div>
            <a href="appointment.php" class="btn-primary">
                <i class="fas fa-tasks"></i> Manage All Appointments
            </a>
        </div>

        <div class="calendar-layout">
            
            <div class="calendar-card">
                <div class="calendar-header-nav">
                    <div class="nav-arrows">
                        <a href="<?= htmlspecialchars($prev_link) ?>" title="Previous Month"><i class="fas fa-chevron-left"></i></a>
                        <span class="calendar-month-year"><?= $current_month_name ?> <?= $current_year ?></span>
                        <a href="<?= htmlspecialchars($next_link) ?>" title="Next Month"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <div class="calendar-day-name">Su</div>
                    <div class="calendar-day-name">Mo</div>
                    <div class="calendar-day-name">Tu</div>
                    <div class="calendar-day-name">We</div>
                    <div class="calendar-day-name">Th</div>
                    <div class="calendar-day-name">Fr</div>
                    <div class="calendar-day-name">Sa</div>

                    <?php
                    // --- 1. Print offset days (previous month) ---
                    $prev_month_days_to_show = $start_day_offset;
                    if ($prev_month_days_to_show > 0) {
                        $prev_month_days_count = (int)date('t', strtotime("$current_display_date_str -1 month"));
                        for ($i = $prev_month_days_count - $prev_month_days_to_show + 1; $i <= $prev_month_days_count; $i++) {
                            echo '<div class="calendar-day-number offset">' . $i . '</div>';
                        }
                    }

                    // --- 2. Print actual days (Clickable) ---
                    $is_current_month = ($year == date('Y', strtotime($current_system_date)) && $month == date('m', strtotime($current_system_date)));
                    $current_system_day_num = (int)date('d', strtotime($current_system_date));

                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $classes = '';
                        $full_date = date('Y-m-d', strtotime("$year-$month-$day"));
                        // Link for the day: Updates the side cards on cl.php
                        $link_url = "cl.php?month=$month&year=$year&date=$full_date";

                        $is_today = $is_current_month && $day == $current_system_day_num;
                        $is_selected = date('Y-m-d', strtotime($selected_date_str)) == $full_date;
                        $has_appointment = isset($appointments_on_days[$day]);
                        
                        if ($is_selected) {
                            $classes = 'selected';
                        } elseif ($is_today) {
                            $classes = 'today';
                        } elseif ($has_appointment) {
                            $classes = 'has-appointment';
                        }
                        
                        echo '<a href="'. htmlspecialchars($link_url) . '" class="calendar-day-number '. $classes . '">' . $day . '</a>';
                    }

                    // --- 3. Print next month days (fill the grid) ---
                    $total_cells = $start_day_offset + $days_in_month;
                    $cells_to_fill = (7 - ($total_cells % 7)) % 7;
                    // Tiyakin na mayroong 6 rows kung kailangan
                    if ($total_cells <= 35 && $cells_to_fill == 0) $cells_to_fill = 7; 

                    for ($i = 1; $i <= $cells_to_fill; $i++) {
                        echo '<div class="calendar-day-number offset">' . $i . '</div>';
                    }
                    ?>
                </div>

                <div class="calendar-legend">
                    <div class="legend-item"><div class="legend-box today"></div>Today</div>
                    <div class="legend-item"><div class="legend-box has-appointment"></div>Has Appointments</div>
                    <div class="legend-item"><div class="legend-box selected"></div>Selected Date</div>
                </div>
            </div>

            <div class="appointment-cards-container">
                
                <div class="appointment-card">
                    
                    <div class="appointment-card-title">
                        <a href="appointment.php?selected_date=<?= htmlspecialchars($selected_date_str) ?>">
                            <span>Appointments on <?= $header_date ?></span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <?php if (empty($selected_appointments)): ?>
                        <div style="font-size: 14px; color: var(--color-text-medium); text-align: center; padding: 10px;">No scheduled appointments for this date.</div>
                    <?php else: ?>
                        <?php foreach ($selected_appointments as $appt): ?>
                        <div class="appointment-item">
                            <strong><?= htmlspecialchars($appt['patient']) ?></strong>
                            <span><?= htmlspecialchars($appt['time']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="appointment-card">
                    <div class="appointment-card-title">
                        <a href="appointment.php">
                            <span>Upcoming Appointments (Next 7 Days)</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <div class="upcoming-list">
                        <?php if (empty($upcoming_appointments)): ?>
                            <div style="font-size: 14px; color: var(--color-text-medium); text-align: center; padding: 5px 0;">No scheduled appointments in the next 7 days.</div>
                        <?php else: ?>
                            <?php foreach ($upcoming_appointments as $upcoming): ?>
                            <div class="upcoming-item">
                                <div class="upcoming-details">
                                    <span class="upcoming-date"><?= htmlspecialchars($upcoming['date']) ?></span>
                                    <span class="upcoming-patient">Patient: <?= htmlspecialchars($upcoming['patient']) ?></span>
                                </div>
                                <span class="upcoming-time"><?= htmlspecialchars($upcoming['time']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                    
        </div>
    </div>
</div>

</body>
</html>