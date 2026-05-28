<?php
$patientId = $_GET['id'] ?? '';
$conn = new mysqli("localhost", "root", "", "mindtrack_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if no patientId
if (!$patientId) {
    header("Location: pt.php");
    exit;
}

// Handle auto-save via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine = trim($_POST['medicine_name'] ?? '');
    $dosage   = trim($_POST['dosage'] ?? '');

    if ($medicine && $dosage) {
        $stmt = $conn->prepare("INSERT INTO prescriptions (patientId, medicine_name, dosage, date_prescribed) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $patientId, $medicine, $dosage);
        $stmt->execute();
        echo date("M d, Y h:i A"); // return server timestamp
        exit;
    }
    http_response_code(400);
    echo "Missing data";
    exit;
}

// Load existing prescriptions
$prescriptions = [];
$stmt = $conn->prepare("SELECT medicine_name, dosage, date_prescribed FROM prescriptions WHERE patientId = ? ORDER BY date_prescribed DESC");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $prescriptions[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindTrack - Prescription</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            background-color: #cdebf1; overflow: hidden;
        }
        body { display: flex; }

          /* Layout container */
        .main-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 320px;
            background-color: #99ccdd;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 40px 30px;
        }

        .sidebar h2 {
            font-size: 30px;
            font-weight: bold;
            margin: 0 0 60px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 20px;
            margin-bottom: 35px;
            padding: 10px 12px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
            width: 100%;
        }

        .nav-item:hover {
            background-color: #88bcd0;
        }

        .nav-item img {
            width: 40px;
            height: 40px;
            margin-right: 16px;
        }

        .nav-item.active {
            background-color: #77b4c6;
        }

        .main {
            flex-grow: 1; padding: 40px 60px;
            display: flex; flex-direction: column;
            height: 100vh; overflow: hidden; position: relative;
        }

        .back-btn {
            position: absolute; top: 20px; left: 20px;
            background: none; border: none;
            font-size: 24px; color: #007b9e; cursor: pointer;
        }
        .back-btn:hover { color: #005f7a; }

        .prescription-box {
            background-color: #b7dde5;
            flex-grow: 1; padding: 20px;
            border-radius: 12px;
            display: flex; flex-direction: column;
            margin-top: 40px;
        }

        .prescription-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
        .prescription-header h1 {
            margin: 0; font-size: 28px; font-weight: bold;
        }

        .add-btn {
            background-color: #6aacc9; color: white;
            padding: 10px 16px; text-decoration: none;
            border-radius: 6px; font-weight: bold;
            font-size: 16px; cursor: pointer;
        }
        .add-btn:hover { background-color: #4c8ba8; }

        .prescription-table {
            width: 100%; border-collapse: collapse;
            background: white; border-radius: 8px;
            overflow: hidden;
        }
        .prescription-table th, .prescription-table td {
            padding: 12px; font-size: 16px;
            border-bottom: 1px solid #ccc;
            vertical-align: middle; text-align: left;
        }
        .prescription-table th {
            background-color: #e6f3f7;
            font-size: 18px; border-bottom: 2px solid #003366;
        }
        .prescription-table th + th, .prescription-table td + td {
            border-left: 2px solid #003366;
        }
        .editable-input {
            width: 100%; padding: 8px;
            font-size: 16px; border-radius: 4px;
            border: 1px solid #ccc; box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>MindTrack</h2>
        <a class="nav-item" href="mindtrack.php"><img src="db.png" alt=""> Dashboard</a>
        <a class="nav-item" href="appointment.php"><img src="ap.png" alt=""> Appointment</a>
        <a class="nav-item" href="cl.php"><img src="cl.png" alt=""> Calendar</a>
        <a class="nav-item active" href="pt.php"><img src="pt.png" alt=""> Patients</a>
        <a class="nav-item" href="set.php"><img src="set.png" alt=""> Settings</a>
    </div>

    <div class="main">
        <button class="back-btn" onclick="window.location.href='sp.php?id=<?php echo urlencode($patientId); ?>'">&larr;</button>

        <div class="prescription-box">
            <div class="prescription-header">
                <h1>PRESCRIPTION</h1>
                <button id="addPresBtn" class="add-btn">+ Add</button>
            </div>

            <table class="prescription-table" id="prescriptionTable">
                <thead>
                    <tr>
                        <th style="width:40%;">Medicine Name</th>
                        <th style="width:40%;">Dosage</th>
                        <th style="width:20%;">Date</th>
                    </tr>
                </thead>
                <tbody id="prescriptionBody">
                    <?php foreach ($prescriptions as $pres): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pres['medicine_name']); ?></td>
                            <td><?php echo htmlspecialchars($pres['dosage']); ?></td>
                            <td><?php echo date("M d, Y h:i A", strtotime($pres['date_prescribed'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const addBtn = document.getElementById('addPresBtn');
        const prescriptionBody = document.getElementById('prescriptionBody');

        addBtn.addEventListener('click', () => {
            const newRow = document.createElement('tr');

            const placeholders = ['Medicine name', 'Dosage'];
            placeholders.forEach(text => {
                const cell = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.placeholder = text;
                input.className = 'editable-input';
                cell.appendChild(input);
                newRow.appendChild(cell);
            });

            const dateCell = document.createElement('td');
            dateCell.textContent = "Waiting...";
            newRow.appendChild(dateCell);

            prescriptionBody.prepend(newRow);

            newRow.querySelector('input').focus();

            newRow.querySelectorAll('input').forEach(input => {
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        const parentRow = input.closest('tr');
                        const inputs = parentRow.querySelectorAll('input');
                        const medicine = inputs[0].value.trim();
                        const dosage  = inputs[1].value.trim();

                        if (!medicine || !dosage) {
                            alert("Please fill in both fields.");
                            return;
                        }

                        const formData = new FormData();
                        formData.append('medicine_name', medicine);
                        formData.append('dosage', dosage);

                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => {
                            if (!res.ok) throw new Error("Save failed");
                            return res.text();
                        })
                        .then(date => {
                            inputs[0].parentElement.textContent = medicine;
                            inputs[1].parentElement.textContent = dosage;
                            dateCell.textContent = date;
                        })
                        .catch(err => {
                            console.error(err);
                            dateCell.textContent = "Error saving";
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
