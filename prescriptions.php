<?php
/**
 * MindTrack Health Management System - Prescriptions (prescriptions.php)
 *
 * This file displays a list of active and past patient prescriptions in a 
 * THREE-COLUMN card grid format, matching the requested layout.
 *
 * NOTE: Dummy data is used for demonstration. Replace with actual database 
 * queries when deploying.
 */

// ----------------------------------------------------
// 1. SESSION AND DATABASE INITIALIZATION
// ----------------------------------------------------

session_start();

// include 'db.php'; 
// if ($conn === false) { 
//     die("ERROR: Could not connect to database."); 
// }

if (!isset($_SESSION['logged_in'])) {
    // header('Location: login.php');
    // exit;
}

// ----------------------------------------------------
// 2. DUMMY DATA SETUP (FOR DEMONSTRATION ONLY)
// ----------------------------------------------------

$conn = true; // Placeholder for the database connection object.

// Dummy data for prescriptions, matching the content and structure of the image
$prescriptions = [
    [
        'id' => 1001,
        'medication' => 'Sertraline',
        'dosage' => '50mg',
        'date' => '10/15/2025',
        'patient_name' => 'Emily Rodriguez',
        'prescribed_by' => 'Dr. Amanda Foster',
        'frequency' => 'Once daily',
        'duration' => '30 days',
        'instructions' => 'Take with food in the morning',
    ],
    [
        'id' => 1002,
        'medication' => 'Zolpidem',
        'dosage' => '10mg',
        'date' => '10/10/2025',
        'patient_name' => 'Michael Chen',
        'prescribed_by' => 'Dr. James Martinez',
        'frequency' => 'Once daily at bedtime',
        'duration' => '14 days',
        'instructions' => 'Take 30 minutes before sleep. Do not drive after taking.',
    ],
    [
        'id' => 1003,
        'medication' => 'Lexapro',
        'dosage' => '20mg',
        'date' => '10/01/2025',
        'patient_name' => 'Sarah Johnson',
        'prescribed_by' => 'Dr. Lisa Kim',
        'frequency' => 'Once daily',
        'duration' => '60 days',
        'instructions' => 'Take in the morning with or without food.',
    ],
    // Add more to fill three columns, if desired:
    [
        'id' => 1004,
        'medication' => 'Bupropion',
        'dosage' => '150mg',
        'date' => '09/25/2025',
        'patient_name' => 'Tom Anderson',
        'prescribed_by' => 'Dr. A. Foster',
        'frequency' => 'Once daily',
        'duration' => '90 days',
        'instructions' => 'Do not crush or chew the tablet.',
    ],
];

// ----------------------------------------------------
// 3. HTML START AND CSS SECTION
// ----------------------------------------------------
?>

<!DOCTYPE html>
<html>
<head>
    <title>MindTrack - Prescriptions</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /**
         * ----------------------------------------------------
         * COLOR PALETTE AND BASE STYLES 
         * ----------------------------------------------------
         */
        :root {
            --color-deep-blue: #00A9FF;         /* Sidebar Start, Main Primary Color */
            --color-medium-blue: #89CFF3;       /* Sidebar End */
            --color-light-blue: #A0E9FF;        /* Icon Backgrounds */
            --color-very-light-blue: #CDF5FD;   /* Lightest BG */
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-main: #E6F3FA;           /* Overall Main Content Background */
            --color-card-bg: #F0F8FF;           /* Card internal background (very light blue) */
            --color-border-light: #e0e0e0;      
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { 
            height: 100%; 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--color-bg-main);
            line-height: 1.6;
        }
        .main-container { 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* ----------------------------------------------------
           SIDEBAR STYLES (Standardized)
        ---------------------------------------------------- */
        .sidebar { 
            width: 250px; 
            background: linear-gradient(to bottom, var(--color-deep-blue), var(--color-medium-blue)); 
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1); 
            display: flex; 
            flex-direction: column; 
            padding: 30px 0; 
            color: white; 
            flex-shrink: 0;
        }
        .logo-section { 
            display: flex; align-items: center; padding: 0 25px 30px; gap: 10px; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 10px;
        }
        .logo-section .fa-chart-line { font-size: 24px; color: white; }
        .logo-text h2 { font-size: 18px; font-weight: 600; line-height: 1; }
        .logo-text p { font-size: 10px; color: var(--color-very-light-blue); font-weight: 300; }
        
        .nav-menu { flex-grow: 1; padding: 10px 0; }
        .nav-item { 
            display: flex; align-items: center; text-decoration: none; color: white; 
            font-size: 15px; margin-bottom: 5px; padding: 12px 25px; margin-right: 15px; 
            border-radius: 0 50px 50px 0; transition: background-color 0.2s ease, color 0.2s ease; 
            font-weight: 400;
        }
        .nav-item:hover:not(.active) { background-color: rgba(255, 255, 255, 0.1); }
        .nav-item.active { 
            background-color: white; color: var(--color-deep-blue); font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .nav-item i { margin-right: 15px; font-size: 18px; color: white; }
        .nav-item.active i { color: var(--color-deep-blue); }

        .user-info { 
            padding: 20px 25px; border-top: 1px solid rgba(255, 255, 255, 0.2); 
            display: flex; align-items: center; 
        }
        .user-avatar { 
            width: 36px; height: 36px; border-radius: 50%; background-color: var(--color-light-blue); 
            color: var(--color-deep-blue); display: flex; align-items: center; justify-content: center; 
            font-weight: 600; font-size: 14px; margin-right: 10px; 
        }
        .user-details strong { display: block; color: white; font-size: 14px; font-weight: 500; }
        .user-details span { font-size: 11px; color: var(--color-very-light-blue); }

        /* ----------------------------------------------------
           MAIN CONTENT AREA & HEADER
        ---------------------------------------------------- */
        .main-content-area { 
            flex-grow: 1; 
            background: linear-gradient(to bottom right, #f0f8ff, #e6f6ff); 
            padding: 40px; 
            overflow-y: auto; 
        }

        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; background: white; border-radius: 12px; 
            padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .header h1 { 
            font-size: 28px; color: var(--color-deep-blue); font-weight: 600; margin: 0; 
        }
        .header p { 
            font-size: 15px; color: var(--color-text-medium); margin-top: 5px; 
        }
        .header .action-btn {
            background-color: var(--color-deep-blue);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }
        .header .action-btn:hover { background-color: #0088cc; }

        /* ----------------------------------------------------
           SEARCH BAR
        ---------------------------------------------------- */
        .search-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px 25px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--color-very-light-blue);
        }

        .search-bar {
            flex-grow: 1;
            display: flex;
            align-items: center;
            position: relative;
        }
        .search-bar i {
            position: absolute;
            left: 15px;
            color: var(--color-text-medium);
        }
        .search-bar input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: none;
            background: none;
            font-size: 15px;
            color: var(--color-text-dark);
        }
        .search-bar input:focus { outline: none; }

        /* ----------------------------------------------------
           PRESCRIPTION CARD GRID LAYOUT (UPDATED TO 3 COLUMNS)
        ---------------------------------------------------- */
        .prescriptions-grid {
            display: grid;
            /* Changed to 3 columns, allowing cards to shrink slightly for responsiveness */
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px;
        }

        .prescription-card {
            background-color: var(--color-card-bg); /* Very light blue background */
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--color-light-blue);
            display: flex;
            flex-direction: column;
            min-height: 400px; 
        }

        /* --- Header Section (Medication Name, Dosage, Date) --- */
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .med-info {
            display: flex;
            align-items: center;
        }
        .med-info .icon {
            width: 45px; height: 45px;
            background-color: var(--color-light-blue); 
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--color-deep-blue);
            margin-right: 15px;
        }
        .med-details h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-deep-blue);
            line-height: 1.2;
        }
        .med-details p {
            font-size: 14px;
            color: var(--color-text-medium);
            margin: 0;
        }
        .issue-date {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-medium);
            margin-top: 5px;
        }

        /* --- Patient/Doctor Details Section --- */
        .patient-details-group {
            margin-bottom: 20px;
            padding: 10px 0;
            font-size: 14px;
        }
        .detail-row {
            margin-bottom: 8px;
        }
        .detail-row span {
            display: block;
            font-size: 13px;
            color: var(--color-text-medium);
        }
        .detail-row strong {
            display: block;
            font-size: 15px;
            color: var(--color-text-dark);
            font-weight: 500;
        }
        .detail-row.prescribed-by strong {
            color: var(--color-deep-blue);
        }

        /* --- Frequency and Duration Grid --- */
        .freq-duration {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 15px 0;
            border-top: 1px dashed var(--color-border-light);
            border-bottom: 1px dashed var(--color-border-light);
            margin-bottom: 20px;
        }

        /* --- Instructions Section --- */
        .instructions-section {
            flex-grow: 1; 
            padding-bottom: 20px;
        }
        .instructions-section strong {
            display: block;
            font-size: 14px;
            color: var(--color-text-dark);
            margin-bottom: 5px;
        }
        .instructions-section p {
            font-size: 14px;
            color: var(--color-text-medium);
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--color-very-light-blue);
        }

        /* --- Action Buttons --- */
        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--color-border-light);
            padding-top: 20px;
            margin-top: auto;
        }
        .edit-button {
            flex-grow: 1;
            background-color: white;
            color: var(--color-text-dark);
            border: 1px solid var(--color-border-light);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .edit-button i { margin-right: 8px; color: var(--color-deep-blue); }
        .edit-button:hover { background-color: var(--color-very-light-blue); }

        .action-icons-group button {
            background: none;
            border: none;
            color: var(--color-text-medium);
            font-size: 18px;
            padding: 10px;
            margin-left: 10px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .action-icons-group button:hover {
            color: var(--color-deep-blue);
        }
        .action-icons-group button.delete-btn {
            color: #dc3545;
        }
        .action-icons-group button.delete-btn:hover {
            color: #b02a37;
        }
        /* Mobile adjustment for smaller screens to ensure readability */
        @media (max-width: 1200px) {
            .prescriptions-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            }
        }
        @media (max-width: 768px) {
            .prescriptions-grid {
                grid-template-columns: 1fr; /* Single column on very small screens */
            }
        }

    </style>
</head>
<body>

<div class="main-container">
    
    <div class="sidebar">
        <div class="logo-section">
            <i class="fas fa-chart-line"></i> 
            <div class="logo-text">
                <h2>MindTrack</h2>
                <p>Health Management</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a class="nav-item" href="mindtrack.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-item" href="appointment.php"><i class="far fa-calendar-alt"></i>Appointments</a>
            <a class="nav-item" href="cl.php"><i class="fas fa-calendar-alt"></i>Calendar</a>
            <a class="nav-item" href="pt.php"><i class="fas fa-users"></i>Patients</a>
            <a class="nav-item" href="doctors.php"><i class="fas fa-user-md"></i>Doctors</a> 
            <a class="nav-item active" href="prescriptions.php"><i class="fas fa-prescription-bottle-alt"></i>Prescriptions</a>
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
                <h1>Prescriptions</h1>
                <p>Manage patient prescriptions and medications</p>
            </div>
            <a href="new_prescription.php" class="action-btn">
                <i class="fas fa-file-medical"></i> New Prescription
            </a>
        </div>
        <div class="search-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search by patient, doctor, or medication...">
            </div>
        </div>
        <div class="prescriptions-grid">
            <?php foreach ($prescriptions as $p): ?>
                <div class="prescription-card">
                    
                    <div class="card-top">
                        <div class="med-info">
                            <div class="icon"><i class="fas fa-file-alt"></i></div>
                            <div class="med-details">
                                <h3><?php echo htmlspecialchars($p['medication']); ?></h3>
                                <p><?php echo htmlspecialchars($p['dosage']); ?></p>
                            </div>
                        </div>
                        <div class="issue-date"><?php echo htmlspecialchars($p['date']); ?></div>
                    </div>

                    <div class="patient-details-group">
                        <div class="detail-row">
                            <span>Patient</span>
                            <strong><?php echo htmlspecialchars($p['patient_name']); ?></strong>
                        </div>
                        <div class="detail-row prescribed-by">
                            <span>Prescribed by</span>
                            <strong><?php echo htmlspecialchars($p['prescribed_by']); ?></strong>
                        </div>
                    </div>

                    <div class="freq-duration">
                        <div class="detail-row">
                            <span>Frequency</span>
                            <strong><?php echo htmlspecialchars($p['frequency']); ?></strong>
                        </div>
                        <div class="detail-row">
                            <span>Duration</span>
                            <strong><?php echo htmlspecialchars($p['duration']); ?></strong>
                        </div>
                    </div>

                    <div class="instructions-section">
                        <strong>Instructions:</strong>
                        <p><?php echo htmlspecialchars($p['instructions']); ?></p>
                    </div>

                    <div class="card-actions">
                        <button class="edit-button" onclick="location.href='edit_prescription.php?id=<?php echo $p['id']; ?>'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <div class="action-icons-group">
                            <button title="Print"><i class="fas fa-print"></i></button>
                            <button title="Download"><i class="fas fa-download"></i></button>
                            <button class="delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        </div>
</div>

<script>
    // ----------------------------------------------------
    // JAVASCRIPT FOR DYNAMIC FUNCTIONALITY (Dummy functions)
    // ----------------------------------------------------
    document.addEventListener('DOMContentLoaded', function() {
        // Placeholder for any script needed for the action icons
        const actionButtons = document.querySelectorAll('.action-icons-group button');
        actionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const action = this.title;
                const card = this.closest('.prescription-card');
                const medName = card.querySelector('.med-details h3').textContent;
                
                if (action === 'Delete') {
                    if (confirm(`Are you sure you want to delete the prescription for ${medName}?`)) {
                        console.log(`Simulating deletion of ${medName}`);
                        // Example: Visually remove the card (in a real app, you'd make an AJAX call)
                        card.style.opacity = '0.5';
                    }
                } else {
                    console.log(`Action '${action}' triggered for ${medName}`);
                }
            });
        });
    });

</script>

<?php 
// ----------------------------------------------------
// 6. CLOSING DATABASE CONNECTION (If applicable)
// ----------------------------------------------------
if (isset($conn) && $conn !== true && method_exists($conn, 'close')) {
    $conn->close();
}
?>
</body>
</html>