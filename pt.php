<?php
$DB_SERVER = "localhost";
$DB_USERNAME = "root"; 
$DB_PASSWORD = ""; 
$DB_NAME = "mindtrack"; 

$conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$patients = [];
$sql = "SELECT patient_custom_id, birthdate, doctor, diagnosis, status FROM patients";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($p = $result->fetch_assoc()) {
        $p['patient_number'] = $p['patient_custom_id'];
        
        $dob = new DateTime($p['birthdate']);
        $today = new DateTime('today');
        $p['age'] = $dob->diff($today)->y . ' years old';
        
        $p['status'] = strtolower($p['status']);
        
        $patients[] = $p;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patients - MindTrack Health Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary-dark: #0077b6;
            --color-deep-blue: #00A9FF;
            --color-medium-blue: #89CFF3; 
            --color-light-blue: #A0E9FF; 
            --color-very-light-blue: #E0F7FF;
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-light: #F0F8FF;
            --color-status-scheduled: #4CAF50; 
            --color-status-new: #FFA500; 
            --color-status-active: #00A9FF; 
            --color-status-discharged: #d9534f; 
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
        .nav-item.active { background-color: white; color: var(--color-primary-dark); font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: none; }
        .nav-item.active i { color: var(--color-primary-dark); }
        .nav-item i { margin-right: 15px; font-size: 18px; color: var(--color-medium-blue); }
        .user-info { padding: 20px 30px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background-color: var(--color-light-blue); color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-right: 10px; }
        .main-content-area { flex-grow: 1; padding: 30px; overflow-y: auto; background-color: #ffffff; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-title h1 { font-size: 24px; color: var(--color-text-dark); font-weight: 600; } 
        .btn-add-patient { background-color: var(--color-deep-blue); color: white; padding: 10px 15px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(0, 169, 255, 0.3); }
        .btn-add-patient:hover { background-color: var(--color-primary-dark); }

        .patient-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { position: relative; width: 400px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--color-medium-blue); }
        .search-box input { width: 100%; padding: 10px 10px 10px 40px; border: 1px solid var(--color-light-blue); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .view-toggle { display: flex; border: 1px solid var(--color-primary-dark); border-radius: 8px; overflow: hidden; }
        .view-toggle button { padding: 8px 15px; border: none; background-color: white; color: var(--color-primary-dark); font-weight: 600; cursor: pointer; transition: background-color 0.2s; font-size: 14px; }
        .view-toggle button.active { background-color: var(--color-primary-dark); color: white; }

        .patient-list-view { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 20px; 
        }
        .patient-card {
            background-color: var(--color-bg-light); 
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--color-very-light-blue);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .patient-info h3 { margin: 0; font-size: 18px; color: var(--color-deep-blue); line-height: 1.2; font-weight: 600; } 
        .patient-info p { display: none; margin: 0; font-size: 13px; color: var(--color-text-medium); } 

        .patient-details { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            margin-bottom: 15px;
            font-size: 14px;
        }
        .patient-details > div {
            display: flex;
            align-items: center;
            color: var(--color-text-medium);
        }
        .patient-details i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            color: var(--color-medium-blue);
        }
        /* Tinanggal ang icon at number */
        .patient-details .contact-number { 
            display: none; 
        } 
        /* Para sa List View */
        .doctor-name-list-only { 
            display: none; 
        } 

        .patient-avatar-box {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            background-color: var(--color-light-blue);
            color: var(--color-primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 11px !important; 
        }

        .status-badge {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: capitalize;
            line-height: 1;
        }
        .status-new { background-color: #ffeccf; color: var(--color-status-new); }
        .status-active { background-color: #e0f7ff; color: var(--color-status-active); }
        .status-scheduled { background-color: #d4edda; color: var(--color-status-scheduled); }
        .status-discharged { background-color: #f2dede; color: var(--color-status-discharged); }


        .patient-list-view.list-active {
            display: block; 
        }
        .patient-list-view.list-active .patient-card {
            display: grid;
            grid-template-columns: 120px 1fr 100px 100px; 
            align-items: center;
            margin-bottom: 10px;
            padding: 15px 20px;
            gap: 10px;
            background-color: white; 
        }
        
        .patient-list-view.list-active .diagnosis-container, 
        .patient-list-view.list-active .patient-details, 
        .patient-list-view.list-active .patient-avatar-box,
        .patient-list-view.list-active .patient-info p { 
            display: none;
        }
        .patient-list-view.list-active .patient-header {
            display: contents; 
        }
        .patient-list-view.list-active .patient-info {
            text-align: left;
        }
        .patient-list-view.list-active .patient-info h3 {
            font-size: 15px; 
        }
        .patient-list-view.list-active .status-badge {
            justify-self: center;
        }
        .patient-list-view.list-active .doctor-name-list-only {
            display: block;
        }

        .patient-list-header { 
            display: none; 
            grid-template-columns: 120px 1fr 100px 100px;
            gap: 10px;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--color-primary-dark);
            border-bottom: 2px solid var(--color-light-blue);
            margin-bottom: 10px;
            background-color: var(--color-bg-light); 
            border-radius: 8px 8px 0 0;
        }
        .patient-list-view.list-active ~ .patient-list-header {
            display: grid;
        }

        .diagnosis-container { 
            padding-top: 15px; 
            margin-top: 15px; 
            border-top: 1px solid var(--color-very-light-blue); 
            font-size: 13px; 
            color: var(--color-text-medium); 
            display: flex; 
            align-items: center;
        }
        .diagnosis-input {
            width: 100%;
            border: none;
            padding: 0;
            font-size: 13px;
            color: var(--color-text-dark);
            font-style: italic;
            background-color: transparent;
            outline: none;
            cursor: pointer;
        }
        .diagnosis-input:read-only {
            cursor: pointer; 
        }
        .diagnosis-input:focus {
             outline: 1px dashed var(--color-primary-dark);
             background-color: #fff;
        }
        .btn-timeline { 
            background-color: var(--color-bg-light); 
            color: var(--color-primary-dark); 
            border: 1px solid var(--color-medium-blue); 
            padding: 8px 15px; 
            border-radius: 5px; 
            font-size: 13px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background-color 0.2s; 
            text-decoration: none; 
            display: inline-block;
        }
        .btn-timeline:hover { background-color: var(--color-light-blue); }
        .card-actions { margin-top: 15px; text-align: right; }
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
            <a class="nav-item active" href="pt.php"><i class="fas fa-users"></i>Patients</a>
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
                <h1>Patients (<?= count($patients) ?>)</h1>
                <p>Manage patient records and information</p>
            </div>
            <a href="patient_registration.php" class="btn-add-patient">
                <i class="fas fa-plus"></i> Add Patient
            </a>
        </div>

        <div class="patient-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search patients by ID or diagnosis..." id="searchInput">
            </div>
            <div class="view-toggle">
                <button id="gridViewBtn" class="active">Grid</button>
                <button id="listViewBtn">List</button>
            </div>
        </div>
        
        <div class="patient-list-header" id="listHeader">
            <div>Patient ID</div>
            <div>Assigned Doctor</div>
            <div>Status</div>
            <div>Action</div>
        </div>

        <div class="patient-list-view" id="patientListView">
            <?php foreach ($patients as $patient): ?>
            <div class="patient-card">
                <div>
                    <div class="patient-header">
                        <div style="display: flex;">
                            <div class="patient-avatar-box" title="Patient ID: <?= htmlspecialchars($patient['patient_number']) ?>">
                                <?= htmlspecialchars($patient['patient_number']) ?>
                            </div>
                            <div class="patient-info">
                                <h3>Patient No. <?= htmlspecialchars($patient['patient_number']) ?></h3> 
                                <p style="display: none;"></p>
                            </div>
                        </div>
                        <span class="status-badge status-<?= htmlspecialchars($patient['status']) ?>"><?= ucfirst(htmlspecialchars($patient['status'])) ?></span>
                    </div>

                    <div class="patient-details">
                        <div><i class="fas fa-user"></i>Age: <?= htmlspecialchars($patient['age']) ?></div>
                        <div class="contact-number"></div>
                        <div><i class="fas fa-user-md"></i><?= htmlspecialchars($patient['doctor'] ?? 'Unassigned') ?></div>
                    </div>

                    <div class="doctor-name-list-only" style="font-size: 14px; font-weight: 500; color: var(--color-text-dark); justify-self: start;"><?= htmlspecialchars($patient['doctor'] ?? 'Unassigned') ?></div>
                </div>

                <div class="diagnosis-container">
                    <label for="diagnosis-<?= htmlspecialchars($patient['patient_custom_id']) ?>" style="margin-right: 5px;">DIAGNOSIS:</label>
                    <input 
                        type="text" 
                        class="diagnosis-input" 
                        id="diagnosis-<?= htmlspecialchars($patient['patient_custom_id']) ?>"
                        name="diagnosis-<?= htmlspecialchars($patient['patient_custom_id']) ?>"
                        value="<?= htmlspecialchars($patient['diagnosis'] ?? 'N/A - Initial Registration') ?>"
                        readonly 
                        title="Double-click to edit diagnosis"
                        ondblclick="this.readOnly=false; this.style.color='var(--color-primary-dark)';" 
                        onblur="this.readOnly=true; this.style.color='var(--color-text-dark)'; saveDiagnosis(this);"
                        onkeydown="if(event.keyCode===13){this.readOnly=true; this.style.color='var(--color-text-dark)'; saveDiagnosis(this); event.preventDefault();}"
                    >
                </div>
                
                <div class="card-actions">
                    <a href="patient_timeline.php?id=<?= urlencode($patient['patient_custom_id']) ?>" class="btn-timeline">
                        <i class="fas fa-history" style="margin-right: 5px;"></i> Timeline
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function saveDiagnosis(inputElement) {
        if (inputElement.readOnly === false) {
             inputElement.readOnly = true;
        }
        
        const patientId = inputElement.id.replace('diagnosis-', '');
        const newDiagnosis = inputElement.value;
        
        fetch('update_diagnosis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `patient_id=${encodeURIComponent(patientId)}&diagnosis=${encodeURIComponent(newDiagnosis)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Success:', data.message);
                inputElement.title = 'Diagnosis updated successfully!';
            } else {
                console.error('Error saving diagnosis:', data.message);
                inputElement.title = 'Error saving: ' + data.message;
            }
        })
        .catch((error) => {
            console.error('Fetch Error:', error);
            inputElement.title = 'Failed to connect to server.';
        });
    }


    document.addEventListener('DOMContentLoaded', function() {
        const gridViewBtn = document.getElementById('gridViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        const patientListView = document.getElementById('patientListView');

        const savedView = localStorage.getItem('patientView') || 'grid';

        function setView(view) {
            if (view === 'list') {
                patientListView.classList.add('list-active');
                gridViewBtn.classList.remove('active');
                listViewBtn.classList.add('active');
            } else {
                patientListView.classList.remove('list-active');
                listViewBtn.classList.remove('active');
                gridViewBtn.classList.add('active');
            }
            localStorage.setItem('patientView', view);
        }

        setView(savedView);

        gridViewBtn.addEventListener('click', () => setView('grid'));
        listViewBtn.addEventListener('click', () => setView('list'));
        
        const searchInput = document.getElementById('searchInput');
        const patientCards = document.querySelectorAll('.patient-card');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();

            patientCards.forEach(card => {
                const textContent = card.textContent.toLowerCase();
                if (textContent.includes(searchTerm)) {
                    card.style.display = ''; 
                } else {
                    card.style.display = 'none'; 
                }
            });
        });
    });
</script>

</body>
</html>