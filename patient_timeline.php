<?php
session_start();

// -----------------------------------------------------------
// A. SECURITY CHECK
// -----------------------------------------------------------
if (!isset($_SESSION['timeline_authenticated']) || $_SESSION['timeline_authenticated'] !== true) {
    $redirect_url = "timeline_auth.php";
    if (isset($_GET['id'])) {
        $redirect_url .= "?id=" . urlencode($_GET['id']);
    }
    header("Location: " . $redirect_url);
    exit;
}
unset($_SESSION['timeline_authenticated']); 

// -----------------------------------------------------------
// B. CONFIGURATION
// -----------------------------------------------------------
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; 
$DB_PASSWORD = ""; 
$DB_NAME = "mindtrack"; 

date_default_timezone_set('Asia/Manila');

$patient_data = null;
$session_notes = [];
$error_message = '';

$patient_custom_id = isset($_GET['id']) ? trim($_GET['id']) : '';

// -----------------------------------------------------------
// C. DATABASE OPERATIONS
// -----------------------------------------------------------
if (!empty($patient_custom_id)) {
    
    $conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error) {
        $error_message = "Database connection failed: " . $conn->connect_error . ". Check your XAMPP MySQL status and credentials.";
    } else {
        // Step 1: Kumuha ng Patient Details
        $sql = "SELECT * FROM patients WHERE patient_custom_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $patient_custom_id); 
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $patient_data = $result->fetch_assoc();
            
            $patient_data['patient_number'] = $patient_data['patient_custom_id']; 
            
            // Calculate Age
            $dob = new DateTime($patient_data['birthdate']);
            $today = new DateTime('today');
            $patient_data['age'] = $dob->diff($today)->y;
            
            // Step 2: Kumuha ng Session Notes History (Kasama ang 'id' at 'prescription')
            $notes_sql = "SELECT id, note_content, prescription, created_at FROM patient_notes WHERE patient_custom_id = ? ORDER BY created_at DESC";
            $notes_stmt = $conn->prepare($notes_sql);
            $notes_stmt->bind_param("s", $patient_custom_id);
            $notes_stmt->execute();
            $notes_result = $notes_stmt->get_result();
            
            if ($notes_result) {
                while ($note = $notes_result->fetch_assoc()) {
                    $session_notes[] = $note;
                }
            }
            $notes_stmt->close();

        } else {
            $error_message = "Patient record with ID **{$patient_custom_id}** not found. (Database: {$DB_NAME})";
        }
        
        $stmt->close();
    }
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
} else {
    $error_message = "Invalid Patient ID specified. Please go back to the patient list.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Timeline - <?= $patient_data ? $patient_data['patient_number'] : 'N/A' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary-dark: #0077b6;
            --color-deep-blue: #00A9FF;
            --color-medium-blue: #89CFF3;
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-very-light-blue: #E0F7FF;
            --color-bg-light: #F0F8FF;
            --color-danger-red: #dc3545;
            --color-success-green: #28a745;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #d3e5ff, #e6f6ff); 
            color: var(--color-text-dark); 
            min-height: 100vh; 
            padding: 20px; 
            overflow-x: hidden; 
        }

        .timeline-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-width: 300px; 
        }

        @media (max-width: 600px) {
             .timeline-container {
                 padding: 15px; 
             }
             .info-card-grid {
                 grid-template-columns: 1fr; 
             }
        }


        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--color-very-light-blue);
            padding-bottom: 15px;
        }

        .patient-identifier h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primary-dark);
            line-height: 1.2;
        }
        .patient-identifier p {
            font-size: 14px;
            color: var(--color-text-medium);
            margin-top: 5px;
        }
        
        .patient-avatar-large {
            width: 60px; height: 60px; border-radius: 50%;
            background-color: var(--color-medium-blue);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 20px;
            margin-right: 20px;
        }

        .info-card-grid {
            grid-template-columns: repeat(3, 1fr); 
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background-color: var(--color-bg-light);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--color-deep-blue);
        }

        .info-card p {
            font-size: 12px;
            color: var(--color-text-medium);
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-card strong {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: var(--color-text-dark);
        }

        .details-section h3 {
            font-size: 18px;
            color: var(--color-primary-dark);
            border-bottom: 1px solid var(--color-very-light-blue);
            padding-bottom: 10px;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #eee;
            font-size: 14px;
        }

        .detail-label {
            font-weight: 500;
            color: var(--color-text-medium);
        }

        .detail-value {
            font-weight: 600;
            color: var(--color-text-dark);
        }

        .btn-back {
            text-decoration: none;
            color: var(--color-primary-dark);
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .error-container {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: var(--color-danger-red);
        }
        
        .current-session-note {
            margin-bottom: 10px;
        }

        .current-session-note textarea {
            width: 100%;
            min-height: 150px;
            padding: 10px;
            border: 1px solid var(--color-medium-blue);
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color 0.3s;
        }
        .current-session-note textarea:focus {
            border-color: var(--color-primary-dark);
        }
        
        /* CSS para sa Save Button */
        .save-button {
            width: 100%;
            padding: 10px 15px;
            background-color: var(--color-deep-blue);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 10px;
        }
        .save-button:hover {
            background-color: var(--color-primary-dark);
        }
        .save-button:active {
            transform: scale(0.99);
        }
        /* End CSS para sa Save Button */


        .autosave-status {
            text-align: right;
            font-size: 12px;
            margin-top: 5px;
            color: var(--color-text-medium);
            font-style: italic;
        }
        .autosave-status.saving {
            color: var(--color-deep-blue);
            font-weight: 500;
        }
        .autosave-status.saved {
            color: var(--color-success-green);
            font-weight: 500;
        }
        .autosave-status.error {
            color: var(--color-danger-red);
            font-weight: 500;
        }

        .session-history {
            margin-top: 25px;
        }
        
        /* BAGONG CSS PARA SA TABLE */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            overflow: hidden;
            border-radius: 8px; 
        }
        .history-table th, .history-table td {
            padding: 12px;
            border: 1px solid #e0f0f8; 
            text-align: left;
            vertical-align: top;
        }
        .history-table thead th {
            background-color: var(--color-deep-blue);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            border: none; 
        }
        .history-table tbody tr:nth-child(odd) {
            background-color: var(--color-bg-light); 
        }
        .history-table tbody tr:hover {
            background-color: #d3e5ff; 
        }
        .history-table td {
            color: var(--color-text-dark);
        }
        .history-table td:first-child {
            font-weight: 600;
            color: var(--color-primary-dark);
        }
        .time-stamp {
            font-size: 10px;
            color: var(--color-text-medium);
            display: block;
        }
        .placeholder-text {
            color: #aaa;
            font-style: italic;
        }
        .session-result {
            white-space: pre-wrap; 
        }
        
        /* 🚨 REVISED CSS PARA SA PRESCRIPTION TEXTAREA 🚨 */
        .prescription-textarea {
            width: 100%;
            min-height: 80px; 
            padding: 5px;
            
            /* Default state: No border, transparent background */
            border: 1px solid transparent; 
            background-color: transparent; 
            box-shadow: none;
            color: var(--color-text-dark); /* Default text color */

            border-radius: 4px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            resize: vertical; 
            transition: border-color 0.3s, background-color 0.3s, box-shadow 0.3s;
            outline: none;
        }
        
        /* Focus state: Ibalik ang border at background kapag ini-edit */
        .prescription-textarea:focus {
            border-color: #ccc; 
            background-color: white; 
            box-shadow: 0 0 5px rgba(0, 119, 182, 0.5); 
            color: var(--color-text-dark); /* Ensure text is dark when editing */
        }
        
        /* Special class for empty prescription */
        .prescription-textarea.empty::placeholder {
            color: #ccc; /* Mas light gray for N/A look */
            font-style: italic;
        }
        .prescription-textarea.empty {
             color: #ccc; /* Para sa actual value kung 'N/A' */
        }

        
        .status-badge {
            display: block;
            margin-top: 5px;
            font-size: 10px;
            text-align: right;
            color: var(--color-text-medium);
        }
        .status-badge.saved { color: var(--color-success-green); font-weight: 500; }
        .status-badge.saving { color: var(--color-deep-blue); font-weight: 500; }
        .status-badge.error { color: var(--color-danger-red); font-weight: 500; }
        /* End REVISED CSS */


        .no-history {
            text-align: center;
            padding: 20px;
            color: var(--color-text-medium);
            font-style: italic;
            border: 1px dashed var(--color-very-light-blue);
            border-radius: 8px;
        }
    </style>
</head>
<body>
<a href="pt.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Patient List</a>

<div class="timeline-container">
    <?php if ($patient_data): ?>
    
    <div class="header-section">
        <div style="display: flex; align-items: center;">
            <div class="patient-avatar-large" title="<?= htmlspecialchars($patient_data['first_name'] . ' ' . $patient_data['last_name']) ?>">
                <i class="fas fa-user"></i>
            </div>
            <div class="patient-identifier">
                <h1><?= htmlspecialchars($patient_data['first_name'] . ' ' . $patient_data['last_name']) ?></h1>
                <p>Patient Number: **<?= htmlspecialchars($patient_data['patient_number']) ?>**</p>
            </div>
        </div>
    </div>

    <div class="info-card-grid">
        <div class="info-card">
            <p>Patient Number</p>
            <strong><?= htmlspecialchars($patient_data['patient_number']) ?></strong>
        </div>
        <div class="info-card">
            <p>Assigned Doctor</p>
            <strong><?= htmlspecialchars($patient_data['doctor']) ?></strong>
        </div>
        <div class="info-card">
            <p>PRIMARY DIAGNOSIS</p>
            <strong><?= htmlspecialchars($patient_data['diagnosis']) ?></strong>
        </div>
    </div>

    <div class="details-section">
        <h3>NOTES</h3>
        <div class="current-session-note">
            <textarea 
                id="sessionNotes" 
                placeholder="Start typing your session notes here. It will automatically save every few seconds or when you press Enter (without Shift)..."
                data-patient-id="<?= htmlspecialchars($patient_data['patient_number']) ?>"
            ></textarea>
            <button id="saveButton" class="save-button"><i class="fas fa-save"></i> Save Note Now</button>
            <div class="autosave-status" id="autosaveStatus">Awaiting input...</div>
        </div>
    </div>

    <div class="details-section session-history">
        <h3>Patient History and Prescriptions</h3> 
        
        <?php if (!empty($session_notes)): ?>
            
            <div style="overflow-x: auto;">
            <table class="history-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">DATE</th>
                        <th style="width: 50%;">SESSION RESULT</th>
                        <th style="width: 25%;">PRESCRIPTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($session_notes as $note): ?>
                    <tr>
                        <td>
                            <?= date('M j, Y', strtotime($note['created_at'])) ?><br>
                            <small class="time-stamp"><?= date('g:i A', strtotime($note['created_at'])) ?></small>
                        </td>
                        
                        <td class="session-result">
                            <?= nl2br(htmlspecialchars($note['note_content'])) ?>
                        </td>
                        
                        <td>
                            <textarea 
                                class="prescription-textarea"
                                data-note-id="<?= htmlspecialchars($note['id']) ?>" 
                                placeholder="N/A"
                                oninput="checkPrescriptionContent(this)"
                                onblur="checkPrescriptionContent(this)"
                            ><?= htmlspecialchars($note['prescription']) ?></textarea>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            
        <?php else: ?>
            <div class="no-history">
                <i class="fas fa-info-circle"></i> Walang history ng session notes para sa pasyenteng ito.
            </div>
        <?php endif; ?>
    </div>

    <div class="details-section">
        <h3>Registration Details</h3>
        <div class="detail-row">
            <span class="detail-label">Full Name</span>
            <span class="detail-value"><?= htmlspecialchars($patient_data['first_name'] . ' ' . $patient_data['last_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Age</span>
            <span class="detail-value"><?= $patient_data['age'] ?> years old</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Birthdate</span>
            <span class="detail-value"><?= date('F j, Y', strtotime($patient_data['birthdate'])) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email Address</span>
            <span class="detail-value"><?= htmlspecialchars($patient_data['email']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Contact Number</span>
            <span class="detail-value"><?= htmlspecialchars($patient_data['contact']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Emergency Contact</span>
            <span class="detail-value"><?= htmlspecialchars($patient_data['emergency_contact']) ?: 'N/A' ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date Registered</span>
            <span class="detail-value"><?= date('M j, Y g:i A', strtotime($patient_data['date_submitted'])) ?></span>
        </div>
    </div>

    <?php else: ?>
        <div class="error-container">
            <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i> <?= $error_message ?>
        </div>
    <?php endif; ?>
</div>

<script>
    
    // Function to handle the styling of the prescription textarea
    function checkPrescriptionContent(textarea) {
        if (textarea.value.trim().length === 0) {
            // Walang laman: Gawing 'N/A' ang kulay ng text at i-apply ang 'empty' class
            textarea.classList.add('empty');
            // I-set ang value sa placeholder para lumabas ang 'N/A'
            textarea.value = ''; 
            textarea.placeholder = 'N/A';
        } else {
            // May laman: Ibalik sa normal (dark text) at tanggalin ang 'empty' class
            textarea.classList.remove('empty');
            textarea.style.color = 'var(--color-text-dark)';
            textarea.placeholder = 'Add prescription here...'; // Optional: Ibalik ang normal placeholder
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
        
        // Initial check for all prescription textareas
        const prescriptionTextareas = document.querySelectorAll('.prescription-textarea');
        prescriptionTextareas.forEach(textarea => {
            checkPrescriptionContent(textarea);
        });

        // =================================================================
        // 1. CURRENT SESSION NOTES AUTOSAVE LOGIC (for save_notes.php)
        // =================================================================
        const notesTextarea = document.getElementById('sessionNotes');
        const autosaveStatus = document.getElementById('autosaveStatus');
        const saveButton = document.getElementById('saveButton'); 
        const patientId = notesTextarea ? notesTextarea.getAttribute('data-patient-id') : null;
        let saveTimeout;
        let hasUnsavedChanges = false; 

        if (notesTextarea && patientId) {
            function saveSessionNote() {
                const noteContent = notesTextarea.value.trim();
                
                if (noteContent.length === 0) {
                    autosaveStatus.textContent = 'Awaiting input...';
                    autosaveStatus.className = 'autosave-status';
                    hasUnsavedChanges = false;
                    return;
                }

                autosaveStatus.textContent = 'Saving...';
                autosaveStatus.className = 'autosave-status saving';

                // Paggamit ng 'save_notes.php'
                fetch('save_notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `patient_id=${encodeURIComponent(patientId)}&note_content=${encodeURIComponent(noteContent)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP Error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        autosaveStatus.textContent = `Saved at ${new Date().toLocaleTimeString()}`;
                        autosaveStatus.className = 'autosave-status saved';
                        hasUnsavedChanges = false;
                        // Auto-reload para makita agad ang bagong entry sa table
                        setTimeout(() => {
                            window.location.reload(); 
                        }, 500); 
                    } else {
                        const errorMessage = data.message || 'Unknown server error occurred.';
                        autosaveStatus.textContent = `Error saving: ${errorMessage}`;
                        autosaveStatus.className = 'autosave-status error';
                        console.error('Server Data Error:', errorMessage);
                    }
                })
                .catch(error => {
                    autosaveStatus.textContent = `Server Error: Failed to connect (${error.message}). Check XAMPP/File Path.`;
                    autosaveStatus.className = 'autosave-status error';
                    console.error('Fetch Failed:', error);
                });
            }

            notesTextarea.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                
                if (notesTextarea.value.trim().length > 0) {
                    hasUnsavedChanges = true; 
                }

                autosaveStatus.textContent = 'Unsaved changes...';
                autosaveStatus.className = 'autosave-status';

                saveTimeout = setTimeout(saveSessionNote, 5000); 
            });
            
            notesTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) { 
                    e.preventDefault(); 
                    if (notesTextarea.value.trim().length > 0) {
                        clearTimeout(saveTimeout); 
                        saveSessionNote(); 
                    }
                }
            });

            saveButton.addEventListener('click', function() {
                clearTimeout(saveTimeout); 
                saveSessionNote();
            });

            window.addEventListener('beforeunload', function(e) {
                clearTimeout(saveTimeout); 
                
                if (notesTextarea.value.trim().length > 0 && hasUnsavedChanges) {
                    e.preventDefault(); 
                    e.returnValue = 'Mayroon kang hindi pa na-sa-save na session notes. Sigurado ka bang iiwanan ang page?';
                    return 'Mayroon kang hindi pa na-sa-save na session notes. Sigurado ka bang iiwanan ang page?';
                }
            });
        }
        
        
        // =================================================================
        // 2. 🚨 PRESCRIPTION AUTOSAVE LOGIC (for save_prescription.php) 🚨
        // =================================================================
        const saveTimeouts = new Map();

        function savePrescription(textarea, noteId) {
            const prescriptionContent = textarea.value;
            const statusBadge = textarea.nextElementSibling || createStatusBadge(textarea);

            // Huwag mag-save kung walang laman at walang nagbago
            if (textarea.originalContent === prescriptionContent && prescriptionContent.trim().length > 0) {
                 statusBadge.textContent = `Saved: ${new Date().toLocaleTimeString()}`;
                 statusBadge.className = 'status-badge saved';
                 return;
            }
             if (prescriptionContent.trim().length === 0 && !textarea.originalContent) {
                 statusBadge.textContent = '';
                 statusBadge.className = 'status-badge';
                 return;
             }


            statusBadge.textContent = 'Saving...';
            statusBadge.className = 'status-badge saving';

            // Paggamit ng 'save_prescription.php'
            fetch('save_prescription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                // Iba ang parameter names nito: note_id at prescription_content
                body: `note_id=${encodeURIComponent(noteId)}&prescription_content=${encodeURIComponent(prescriptionContent)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusBadge.textContent = `Saved: ${new Date().toLocaleTimeString()}`;
                    statusBadge.className = 'status-badge saved';
                    // I-update ang original content para malaman kung may nagbago ulit
                    textarea.originalContent = prescriptionContent;
                } else {
                    statusBadge.textContent = `Error: ${data.message || 'Server error'}`;
                    statusBadge.className = 'status-badge error';
                    console.error('Prescription Save Error:', data.message);
                }
            })
            .catch(error => {
                statusBadge.textContent = 'Server Error!';
                statusBadge.className = 'status-badge error';
                console.error('Fetch Failed:', error);
            });
        }
        
        // Function para gumawa ng status badge
        function createStatusBadge(textarea) {
            const badge = document.createElement('span');
            badge.className = 'status-badge';
            textarea.parentNode.insertBefore(badge, textarea.nextSibling);
            return badge;
        }


        prescriptionTextareas.forEach(textarea => {
            const noteId = textarea.getAttribute('data-note-id');
            // Store the initial content
            textarea.originalContent = textarea.value; 
            
            // Magdagdag ng status badge sa bawat textarea
            const badge = createStatusBadge(textarea); 
            
            // Initial status for pre-filled prescription
            if (textarea.originalContent.trim().length > 0) {
                 badge.textContent = `Saved: ${new Date().toLocaleTimeString()}`;
                 badge.className = 'status-badge saved';
            }


            // 1. Auto-save after typing (3 seconds)
            textarea.addEventListener('input', function() {
                checkPrescriptionContent(this); // Check content for styling
                
                // I-clear ang previous timeout
                if (saveTimeouts.has(noteId)) {
                    clearTimeout(saveTimeouts.get(noteId));
                }
                
                const statusBadge = textarea.nextElementSibling;
                statusBadge.textContent = 'Unsaved changes...';
                statusBadge.className = 'status-badge';

                // Mag-set ng bagong timeout (3 seconds)
                const timeout = setTimeout(() => {
                    savePrescription(textarea, noteId);
                }, 3000); 
                
                saveTimeouts.set(noteId, timeout);
            });

            // 2. Save immediately on blur (kapag umalis sa field)
            textarea.addEventListener('blur', function() {
                checkPrescriptionContent(this); // Check content for styling
                
                if (saveTimeouts.has(noteId)) {
                    clearTimeout(saveTimeouts.get(noteId)); // I-cancel ang auto-save timer
                }
                savePrescription(textarea, noteId);
            });

            // 🚨 BAGONG FUNCTIONALITY: SAVE ON ENTER KEYPRESS 🚨
            textarea.addEventListener('keydown', function(e) {
                // I-check kung Enter key ang pinindot at hindi kasama ang Shift key
                if (e.key === 'Enter' && !e.shiftKey) { 
                    e.preventDefault(); // I-prevent ang default action (new line)
                    
                    // Siguraduhin na may laman bago mag-save
                    if (textarea.value.trim().length > 0 || textarea.originalContent.trim().length > 0) {
                        if (saveTimeouts.has(noteId)) {
                            clearTimeout(saveTimeouts.get(noteId)); // I-cancel ang auto-save timer
                        }
                        savePrescription(textarea, noteId); // Agad i-save
                    }
                }
            });
            
        });
        // =================================================================
    });
</script>

</body>
</html>