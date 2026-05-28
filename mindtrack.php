<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    // header('Location: login.php');
    // exit;
}

// --- DUMMY DATA ---
$conn = true;

if ($conn) {
    $patientCount = 0;
    $doctorCount = 4;
    $appointmentCount = 0;
} else {
    $patientCount = 0;
    $doctorCount = 4;
    $appointmentCount = 0;
}

$monthlyAppointments = [0, 0, 0, 3, 1, 4, 3, 5, 8, 4, 0, 0];
$monthlyNewPatients = [0, 0, 0, 2, 2, 5, 4, 8, 10, 6, 0, 0];

$actualDay = 20; 
$selectedDay = 20;

$appointments_on_days = [
    '15' => 'with_doctor',
    '18' => 'no_doctor',
    '20' => 'today_selected',
    '25' => 'no_doctor'
];

$activeDoctorsValue = 4;
$patientGrowthPercent = "0";

$days_in_month = 31;
$start_day_offset = 3; 
$current_month_name = 'October';
$current_year = date('Y', strtotime('October 2025'));
$header_date = date('l, F j, Y'); 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Wayside Psyche Resources Center Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* INAYOS ANG MGA KULAY */
        :root {
            --color-primary-dark: #0077b6; /* Mas madilim na primary blue */
            --color-deep-blue: #00A9FF; /* Accent Blue */
            --color-medium-blue: #89CFF3; 
            --color-light-blue: #A0E9FF; 
            --color-very-light-blue: #E0F7FF; /* Mas matingkad na hover/bg light blue */
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-light: #F0F8FF;
            --color-bg-main: #E6F3FA; 
            --color-success-green: #4CAF50;
            --color-danger-red: #dc3545;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%; 
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #d3e5ff, #e6f6ff); 
        }

        .main-container { display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar Navigation --- */
        .sidebar {
            width: 280px;
            background: linear-gradient(to top, #d3e5ff, #e6f6ff); 
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            padding: 30px 0;
            flex-shrink: 0;
        }

        .logo-section { display: flex; align-items: center; padding: 0 30px 40px; gap: 10px; }
        .logo-section i { font-size: 28px; color: var(--color-primary-dark); }
        .logo-text h2 { font-size: 18px; font-weight: 600; color: var(--color-primary-dark); line-height: 1; }
        .logo-text p { font-size: 10px; color: var(--color-medium-blue); font-weight: 300; }

        .nav-menu { flex-grow: 1; padding: 0 20px; } 
        .nav-item {
            display: flex; align-items: center; text-decoration: none; color: var(--color-text-medium); font-size: 15px; margin-bottom: 8px;
            padding: 12px 15px; border-radius: 8px; transition: background-color 0.2s ease, color 0.2s ease; font-weight: 500;
        }
        .nav-item:hover:not(.active) { background-color: var(--color-very-light-blue); color: var(--color-primary-dark); }
        .nav-item.active { 
            background-color: white; 
            color: var(--color-primary-dark); 
            font-weight: 600; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            border-left: none; 
        }
        .nav-item i { margin-right: 15px; font-size: 18px; color: var(--color-medium-blue); }
        .nav-item.active i { color: var(--color-primary-dark); }

        .user-info { padding: 20px 30px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%; background-color: var(--color-light-blue); color: var(--color-primary-dark);
            display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-right: 10px;
        }
        .user-details strong { color: var(--color-text-dark); font-size: 14px; }
        .user-details span { font-size: 11px; color: var(--color-text-medium); }

        /* --- Main Content Area --- */
        .main-content-area {
            flex-grow: 1;
            padding: 30px; 
            overflow-y: auto;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; 
            background: white; border-radius: 12px;
            padding: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .header h1 { font-size: 20px; color: var(--color-primary-dark); font-weight: 600; } 
        .header p { font-size: 14px; color: var(--color-text-medium); margin-top: 5px; } 
        .header-date { font-size: 14px; color: var(--color-text-medium); display: flex; align-items: center; gap: 8px; }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px; 
            margin-bottom: 25px;
        }

        .stat-box {
            background-color: white; border-radius: 10px; 
            padding: 20px; 
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--color-very-light-blue);
            text-align: center;
        }

        .stat-header { display: flex; justify-content: center; align-items: center; margin-bottom: 10px; gap: 10px; } 
        .stat-header .icon {
            width: 34px; height: 34px; 
            background-color: var(--color-light-blue); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: var(--color-primary-dark); 
        }
        .stat-indicator { font-size: 11px; font-weight: 600; padding: 3px 6px; border-radius: 10px; }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--color-primary-dark); } 
        .stat-label { font-size: 13px; color: var(--color-text-medium); margin-top: 3px; } 


        /* --- Content Rows (Chart ONLY) --- */
        .content-row {
            display: flex;
            gap: 20px; 
            margin-bottom: 25px;
        }

        .card-base {
            background-color: white; border-radius: 10px;
            padding: 15px; 
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--color-very-light-blue);
        }

        .card-title { font-size: 16px; font-weight: 600; color: var(--color-text-dark); } 
        .card-subtitle { font-size: 12px; color: var(--color-text-medium); margin-bottom: 10px; } 

        /* CHART SECTION IS NOW FULL WIDTH */
        .chart-section {
            flex: 1; /* Puno na ang width */
            height: 300px; 
            max-height: 300px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* --- Calendar --- */
        .calendar-row {
            display: flex;
            margin-bottom: 20px;
        }

        .calendar-card {
            flex-grow: 1;
            min-height: 250px;
            display: flex;
            flex-direction: column;
        }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 0 10px; }
        .calendar-header h3 { font-size: 16px; font-weight: 600; color: var(--color-primary-dark); }
        .calendar-header .fa-chevron-left, .calendar-header .fa-chevron-right { font-size: 14px; cursor: pointer; color: var(--color-primary-dark); padding: 5px; }

        .calendar-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            gap: 5px; 
            text-align: center;
            flex-grow: 1; 
            padding: 0 10px;
        }
        .calendar-day-name { font-size: 12px; font-weight: 600; color: var(--color-text-dark); padding-bottom: 8px; }
        
        .calendar-day-number { 
            font-size: 12px;
            padding: 8px 0; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 500;
            color: var(--color-text-dark);
            height: 35px;
            width: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
        }
        .calendar-day-number.offset { color: var(--color-text-medium); opacity: 0.5; }

        /* Calendar Status Colors */
        .calendar-day-number.with-doctor { background-color: var(--color-light-blue); color: var(--color-primary-dark); font-weight: 600; }
        .calendar-day-number.no-doctor { background-color: var(--color-danger-red); color: white; font-weight: 600; }
        .calendar-day-number.today { border: 2px solid var(--color-primary-dark); background-color: transparent; color: var(--color-primary-dark); }
        .calendar-day-number.today-selected { background-color: var(--color-primary-dark); color: white; font-weight: 700; border: none; }
        
        .calendar-legend {
            display: flex;
            gap: 20px;
            padding: 15px 10px 0;
            font-size: 12px;
            color: var(--color-text-medium);
        }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-box { width: 10px; height: 10px; border-radius: 3px; }
        .legend-box.with-doctor { background-color: var(--color-light-blue); }
        .legend-box.no-doctor { background-color: var(--color-danger-red); }
        .legend-box.today { border: 2px solid var(--color-primary-dark); background-color: white; }
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
            <a class="nav-item active" href="mindtrack.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-item" href="appointment.php"><i class="far fa-calendar-alt"></i>Appointments</a>
            <a class="nav-item" href="cl.php"><i class="far fa-calendar-check"></i>Calendar</a>
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
            <div>
                <h1>WAYSIDE PSYCHE RESOURCES CENTER</h1>
                <p>Welcome back to your dashboard</p>
            </div>
            <div class="header-date">
                <i class="far fa-calendar-alt"></i>
                <span><?= $header_date ?></span>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <span class="stat-indicator" style="color:#4CAF50;background:#E6F5E9;">+0%</span>
                </div>
                <div class="stat-value"><?= $patientCount ?></div>
                <div class="stat-label">Total Patients</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                    <span class="stat-indicator" style="color:#4CAF50;background:#E6F5E9;">+2 this month</span>
                </div>
                <div class="stat-value"><?= $activeDoctorsValue ?></div>
                <div class="stat-label">Active Doctors</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="icon"><i class="far fa-calendar-alt"></i></div>
                    <span class="stat-indicator" style="color:#dc3545;background:#fce0e3;">No appointments</span>
                </div>
                <div class="stat-value"><?= $appointmentCount ?></div>
                <div class="stat-label">Appointments Today</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <span class="stat-indicator" style="color:#666;background:#f0f0f0;">vs last month</span>
                </div>
                <div class="stat-value"><?= $patientGrowthPercent ?>%</div>
                <div class="stat-label">Patient Growth</div>
            </div>
        </div>

        <div class="content-row">
            <div class="chart-section card-base">
                <h2 class="card-title">Patient Activity Overview (12 Months)</h2>
                <p class="card-subtitle">Monthly patients and appointments tracking</p>
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <div class="calendar-row">
            <div class="calendar-card card-base">
                <div class="calendar-header">
                    <i class="fas fa-chevron-left"></i>
                    <h3><?= $current_month_name ?> <?= $current_year ?></h3>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-day-name">Sun</div>
                    <div class="calendar-day-name">Mon</div>
                    <div class="calendar-day-name">Tue</div>
                    <div class="calendar-day-name">Wed</div>
                    <div class="calendar-day-name">Thu</div>
                    <div class="calendar-day-name">Fri</div>
                    <div class="calendar-day-name">Sat</div>

                    <?php
                    // Print offset days (blanks)
                    for ($i = 0; $i < $start_day_offset; $i++) {
                        echo '<div class="calendar-day-number offset">' . (30 + $i - $start_day_offset + 1) . '</div>';
                    }

                    // Print actual days
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $classes = 'calendar-day-number';
                        $is_today = ($day == $actualDay);

                        if (isset($appointments_on_days[$day])) {
                            if ($appointments_on_days[$day] === 'with_doctor') {
                                $classes .= ' with-doctor';
                            } elseif ($appointments_on_days[$day] === 'no_doctor') {
                                $classes .= ' no-doctor';
                            } elseif ($appointments_on_days[$day] === 'today_selected') {
                                $classes .= ' today-selected';
                            }
                        } else if ($is_today) {
                            $classes .= ' today';
                        }
                        
                        echo '<div class="'. $classes . '">' . $day . '</div>';
                    }
                    // Print next month days (fill the grid)
                    $remaining_cells = (7 - (($days_in_month + $start_day_offset) % 7)) % 7;
                    for ($i = 1; $i <= $remaining_cells; $i++) {
                        echo '<div class="calendar-day-number offset">' . $i . '</div>';
                    }
                    ?>
                </div>

                <div class="calendar-legend">
                    <div class="legend-item"><div class="legend-box with-doctor"></div>With Doctor</div>
                    <div class="legend-item"><div class="legend-box no-doctor"></div>No Doctor Assigned</div>
                    <div class="legend-item"><div class="legend-box today"></div>Today</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const ctx = document.getElementById('activityChart').getContext('2d');

const appointmentsData = [<?= implode(',', $monthlyAppointments); ?>]; 
const newPatientsData = [<?= implode(',', $monthlyNewPatients); ?>];

// Chart Colors
const chartColorAppointment = '#0077b6'; 
const chartColorNewPatient = '#89CFF3'; 

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [
            {
                label: 'Appointments',
                data: appointmentsData,
                backgroundColor: chartColorAppointment, 
                borderRadius: 3, 
                barPercentage: 0.7, 
                categoryPercentage: 0.8
            },
            {
                label: 'New Patients',
                data: newPatientsData,
                backgroundColor: chartColorNewPatient, 
                borderRadius: 3, 
                barPercentage: 0.7, 
                categoryPercentage: 0.8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, 
        scales: {
            y: {
                beginAtZero: true,
                max: 12, 
                ticks: { 
                    stepSize: 3, 
                    font: { size: 10 }, 
                    padding: 5 
                }, 
                grid: {
                    lineWidth: 0.5,
                    color: '#f0f0f0'
                }
            },
            x: { 
                ticks: { 
                    font: { size: 10 } 
                },
                grid: {
                    display: false
                }
            } 
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { 
                    usePointStyle: true, 
                    boxWidth: 8, 
                    font: { size: 10 }, 
                    padding: 15 
                } 
            },
            tooltip: {
                bodyFont: { size: 10 },
                titleFont: { size: 10 }
            }
        },
        layout: {
            padding: {
                top: 5, 
                left: 0,
                right: 5, 
                bottom: 0
            }
        }
    }
});
</script>
</body>
</html>
