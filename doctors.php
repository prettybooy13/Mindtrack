<?php
// doctors.php

// --- 1. Database Connection Configuration ---
// !!! REPLACE WITH YOUR ACTUAL CREDENTIALS !!!
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mindtrack";

// Static date for the header (as per the original code)
$header_date = date('l, F j, Y', strtotime('October 20, 2025'));

// --- 2. Connect to the Database ---
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Should be handled more gracefully in a real application
    die("Database Connection failed: " . $conn->connect_error);
}

// --- 3. Execute the Query and Fetch Data ---
$sql = "SELECT `id`, `doctor_custom_id`, `first_name`, `last_name`, `specialization`, `email`, `phone`, `status`, `date_added` FROM `doctors` WHERE 1";
$result = $conn->query($sql);

$doctors = []; // Initialize the array to hold fetched doctor data

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Combine first_name and last_name for the 'name' key
        $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        
        $doctors[] = [
            'id'             => htmlspecialchars($row['id']),
            'name'           => $full_name,
            'specialization' => htmlspecialchars($row['specialization']),
            'status'         => htmlspecialchars($row['status']),
            'email'          => htmlspecialchars($row['email']),
            'phone'          => htmlspecialchars($row['phone']),
            'patients'       => 0, // Static value
        ];
    }
}

// Close connection
$conn->close();

// THE FOLLOWING IS YOUR HTML CODE...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctors - MindTrack Health Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary-dark: #0077b6; /* Darker primary blue */
            --color-deep-blue: #00A9FF; /* Accent Blue */
            --color-medium-blue: #89CFF3; 
            --color-light-blue: #A0E9FF; 
            --color-very-light-blue: #E0F7FF; 
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-light: #F0F8FF;
            --color-bg-main: #E6F3FA; 
            --color-success-green: #4CAF50;
            --color-danger-red: #dc3545;
            --color-status-active: var(--color-success-green);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%; 
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #d3e5ff, #e6f6ff); 
            color: var(--color-text-dark);
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
        /* End Sidebar */

        /* --- Main Content Area --- */
        .main-content-area {
            flex-grow: 1;
            padding: 30px; 
            overflow-y: auto;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; 
        }
        .header-title { padding: 0; }
        .header-title h1 { font-size: 24px; font-weight: 600; color: var(--color-text-dark); } 
        .header-title p { font-size: 14px; color: var(--color-text-medium); margin-top: 5px; } 

        .btn-add-doctor {
            background-color: var(--color-deep-blue);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 8px rgba(0, 169, 255, 0.3);
        }
        .btn-add-doctor:hover { background-color: var(--color-primary-dark); }

        /* Doctor Controls (Search, Grid/List) */
        .doctor-controls {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
        }

        .search-box { position: relative; width: 500px; }
        .search-box input {
            width: 100%; padding: 10px 10px 10px 40px; border: 1px solid var(--color-very-light-blue);
            border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;
        }
        .search-box input:focus { border-color: var(--color-primary-dark); }
        .search-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--color-text-medium); font-size: 14px;
        }

        .view-toggle { display: flex; border: 1px solid var(--color-primary-dark); border-radius: 8px; overflow: hidden; }
        .view-toggle button {
            padding: 8px 15px; border: none; background-color: white; color: var(--color-primary-dark);
            font-weight: 600; cursor: pointer; transition: background-color 0.2s; font-size: 14px;
        }
        .view-toggle button:first-child { border-right: 1px solid var(--color-primary-dark); }
        .view-toggle button.active { background-color: var(--color-primary-dark); color: white; }

        /* Doctor Grid */
        .doctor-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;
        }

        .doctor-card {
            background-color: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05); border: 1px solid var(--color-very-light-blue);
            display: flex; flex-direction: column; justify-content: space-between;
        }
        /* Ensure form takes full space inside card */
        .doctor-card form {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
        }

        .doctor-header {
            display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 15px;
        }

        .doctor-icon-box {
            width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-light-blue);
            color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 16px; flex-shrink: 0; margin-right: 15px;
        }

        .doctor-info { flex-grow: 1; }
        .doctor-info h3 { font-size: 16px; font-weight: 600; color: var(--color-text-dark); line-height: 1.2; }
        .doctor-info p { font-size: 13px; color: var(--color-text-medium); margin-top: 2px; }

        .status-badge {
            display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 10px;
            font-weight: 600; text-transform: uppercase;
        }
        .status-active {
            background-color: rgba(76, 175, 80, 0.1); /* Light green tint */
            color: var(--color-status-active);
        }

        /* --- Doctor Details/Editing Styles --- */
        .doctor-details-row {
            display: flex; align-items: center; font-size: 13px; color: var(--color-text-dark); margin-bottom: 8px;
        }
        .doctor-details-row i {
            width: 20px; text-align: center; color: var(--color-medium-blue); margin-right: 10px; flex-shrink: 0;
        }
        
        /* Containers for read-only vs. editable fields */
        .display-mode, .edit-mode {
            width: 100%;
            display: flex;
            align-items: center;
        }

        .edit-mode {
            display: none; /* Default is hidden */
        }
        
        .doctor-card.editing .display-mode {
            display: none; /* Hide display mode when editing */
        }

        .doctor-card.editing .edit-mode {
            display: flex; /* Show edit mode when editing */
        }
        
        /* Style for the new input fields */
        .edit-mode input[type="email"],
        .edit-mode input[type="text"] {
            flex-grow: 1;
            padding: 5px 8px;
            border: 1px solid var(--color-medium-blue);
            border-radius: 4px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .edit-mode input[type="email"]:focus,
        .edit-mode input[type="text"]:focus {
            border-color: var(--color-primary-dark);
            box-shadow: 0 0 0 1px var(--color-light-blue);
        }

        /* --- Action Buttons --- */
        .card-actions {
            display: flex; justify-content: flex-end; margin-top: 15px;
            padding-top: 15px; border-top: 1px solid var(--color-very-light-blue); gap: 10px;
        }

        .btn-edit, .btn-delete, .btn-save, .btn-cancel {
            padding: 8px 15px; border-radius: 5px; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background-color 0.2s;
            display: flex; align-items: center; gap: 5px; border: 1px solid transparent; 
        }

        .btn-edit {
            background-color: var(--color-bg-light); color: var(--color-primary-dark);
            border: 1px solid var(--color-very-light-blue);
        }
        .btn-edit:hover { background-color: var(--color-very-light-blue); }
        
        .btn-delete {
            background-color: white; color: var(--color-danger-red);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .btn-delete:hover { background-color: var(--color-danger-red); color: white; }

        /* Save/Cancel button colors */
        .btn-save {
            background-color: var(--color-success-green); color: white;
            border: 1px solid var(--color-success-green);
        }
        .btn-save:hover { background-color: #388E3C; }

        .btn-cancel {
            background-color: #f8f9fa; color: var(--color-text-medium);
            border: 1px solid #ced4da;
        }
        .btn-cancel:hover { background-color: #e2e6ea; }

        /* Hide the Display mode buttons when editing, and vice versa */
        .doctor-card.editing .card-actions .display-mode-btn {
            display: none;
        }
        .doctor-card:not(.editing) .card-actions .edit-mode-btn {
            display: none;
        }
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
            <a class="nav-item" href="cl.php"><i class="far fa-calendar-check"></i>Calendar</a>
            <a class="nav-item" href="pt.php"><i class="fas fa-users"></i>Patients</a>
            <a class="nav-item active" href="doctors.php"><i class="fas fa-user-md"></i>Doctors</a>
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
                <h1>Doctors</h1>
                <p>Manage healthcare providers and specialists</p>
            </div>
            <a href="doctor_registration.php" class="btn-add-doctor">
                <i class="fas fa-plus"></i> Add Doctor
            </a>
        </div>

        <div class="doctor-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search doctors by name, specialization, or email...">
            </div>
            <div class="view-toggle">
                <button class="active">Grid</button>
                <button>List</button>
            </div>
        </div>

        <div class="doctor-grid">
            <?php if (empty($doctors)): ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--color-text-medium);">No doctors found in the database. Please add a new doctor.</p>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card" id="doctor-card-<?= $doctor['id'] ?>">
                    <form onsubmit="handleEdit(event, <?= $doctor['id'] ?>)">
                        <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                        
                        <div class="card-content-wrapper"> 
                            <div class="doctor-header">
                                <div style="display: flex;">
                                    <div class="doctor-icon-box"><i class="fas fa-stethoscope"></i></div>
                                    <div class="doctor-info">
                                        <h3><?= $doctor['name'] ?></h3>
                                        <p><?= $doctor['specialization'] ?></p>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= strtolower($doctor['status']) ?>"><?= strtoupper($doctor['status']) ?></span>
                            </div>

                            <div class="doctor-details">
                                <div class="doctor-details-row">
                                    <i class="fas fa-envelope"></i>
                                    <span class="display-mode" id="email-display-<?= $doctor['id'] ?>"><?= $doctor['email'] ?></span>
                                    <span class="edit-mode">
                                        <input type="email" name="email" value="<?= $doctor['email'] ?>" placeholder="Email Address">
                                    </span>
                                </div>
                                
                                <div class="doctor-details-row">
                                    <i class="fas fa-phone"></i>
                                    <span class="display-mode" id="phone-display-<?= $doctor['id'] ?>"><?= $doctor['phone'] ?></span>
                                    <span class="edit-mode">
                                        <input type="text" name="phone" value="<?= $doctor['phone'] ?>" placeholder="Phone Number">
                                    </span>
                                </div>
                                
                                <div class="doctor-details-row">
                                    <i class="fas fa-users"></i>
                                    <span><?= $doctor['patients'] ?> Patients</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button type="button" class="btn-edit display-mode-btn" onclick="toggleEditMode(<?= $doctor['id'] ?>)">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                            
                            <button type="button" class="btn-delete display-mode-btn" onclick="deleteDoctor(<?= $doctor['id'] ?>)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>

                            <button type="submit" class="btn-edit btn-save edit-mode-btn">
                                <i class="fas fa-save"></i> Save
                            </button>
                            
                            <button type="button" class="btn-delete btn-cancel edit-mode-btn" onclick="toggleEditMode(<?= $doctor['id'] ?>)">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    /**
     * Toggles the display mode (Read-only vs. Edit mode) for a doctor card.
     * @param {number} doctorId - The ID of the doctor to edit.
     */
    function toggleEditMode(doctorId) {
        const card = document.getElementById(`doctor-card-${doctorId}`);
        card.classList.toggle('editing');
        
        // Reset input values back to original display text when cancelling.
        if (!card.classList.contains('editing')) {
            const emailDisplay = document.getElementById(`email-display-${doctorId}`).textContent.trim();
            const phoneDisplay = document.getElementById(`phone-display-${doctorId}`).textContent.trim();
            
            card.querySelector('input[name="email"]').value = emailDisplay;
            card.querySelector('input[name="phone"]').value = phoneDisplay;
        }
    }

    /**
     * Handles the form submission (Save button) to update data via AJAX.
     * Uses Fetch API to send data to update_doctor.php.
     * @param {Event} event - The form's submit event.
     * @param {number} doctorId - The ID of the doctor to update.
     */
    function handleEdit(event, doctorId) {
        event.preventDefault(); // Prevent default page reload

        const form = event.target;
        const emailInput = form.querySelector('input[name="email"]').value.trim();
        const phoneInput = form.querySelector('input[name="phone"]').value.trim();

        if (emailInput === "" || phoneInput === "") {
            alert("Email and Phone fields cannot be empty.");
            return;
        }

        // Prepare data (FormData object for POST request)
        const formData = new FormData();
        formData.append('doctor_id', doctorId);
        formData.append('email', emailInput);
        formData.append('phone', phoneInput);

        const saveButton = form.querySelector('.btn-save');
        const originalText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving'; // Show a spinner
        saveButton.disabled = true;

        // Ensure the path to update_doctor.php is correct
        fetch('update_doctor.php', { 
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Catch HTTP errors (404, 500)
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json(); // Parse JSON response from PHP
        })
        .then(data => {
            if (data.success) {
                // SUCCESS: Update display values and exit edit mode
                document.getElementById(`email-display-${doctorId}`).textContent = emailInput;
                document.getElementById(`phone-display-${doctorId}`).textContent = phoneInput;
                
                toggleEditMode(doctorId);
                alert(`✅ Update Successful! ${data.message}`);
            } else {
                // SERVER ERROR: Show message from PHP
                alert(`❌ Failed to update data: ${data.message}`);
            }
        })
        .catch(error => {
            // NETWORK/FETCH ERROR (or JSON parsing error)
            console.error('Fetch/Network Error:', error);
            alert(`A critical network error occurred. Check if update_doctor.php is accessible and database is running. Details: ${error.message}`);
        })
        .finally(() => {
            // Restore button to original state
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        });
    }

    /**
     * Handles the deletion of a doctor record via AJAX.
     * Uses Fetch API to send the doctor ID to delete_doctor.php.
     * @param {number} doctorId - The ID of the doctor to delete.
     */
    function deleteDoctor(doctorId) {
        // CONFIRMATION: Ask the user before deleting
        if (!confirm("Are you sure you want to delete this doctor? This action CANNOT be undone in the database.")) {
            return; // Stop if the user cancels
        }

        const card = document.getElementById(`doctor-card-${doctorId}`);
        const originalContent = card.innerHTML; // For possible reset
        const cardActions = card.querySelector('.card-actions');

        // Show loading state
        cardActions.innerHTML = '<div style="flex-grow: 1; text-align: center; color: var(--color-danger-red); font-weight: 600;"><i class="fas fa-spinner fa-spin"></i> Deleting...</div>';

        // Prepare data
        const formData = new FormData();
        formData.append('doctor_id', doctorId);

        // Ensure the path to delete_doctor.php is correct
        fetch('delete_doctor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Catch HTTP errors (404, 500)
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json(); // Parse JSON response from PHP
        })
        .then(data => {
            if (data.success) {
                // SUCCESS: Remove the card from the DOM with animation
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    alert(`✅ Delete Successful! ${data.message}`); 
                }, 300);
            } else {
                // SERVER ERROR
                card.innerHTML = originalContent; // Restore buttons
                alert(`❌ Failed to delete doctor: ${data.message}`);
            }
        })
        .catch(error => {
            // NETWORK/FETCH ERROR
            console.error('Fetch/Network Error:', error);
            card.innerHTML = originalContent; // Restore buttons
            alert(`A critical network error occurred during deletion. Details: ${error.message}`);
        });
    }
</script>

</body>
</html>