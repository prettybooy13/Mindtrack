<?php
$patientId = $_GET['id'] ?? '';
$conn = new mysqli("localhost", "root", "", "mindtrack_db");

// Auto-save via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = trim($_POST['session_note'] ?? '');
    $pid  = trim($_POST['patientId'] ?? '');

    if ($note !== '' && $pid !== '') {
        $stmt = $conn->prepare("INSERT INTO history (patientId, session_note, date_created) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $pid, $note);
        $stmt->execute();

        // Kunin yung latest entry para ma-display agad
        $newId = $stmt->insert_id;
        $stmt->close();

        $res = $conn->prepare("SELECT session_note, date_created FROM history WHERE id = ?");
        $res->bind_param("i", $newId);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        $res->close();

        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// Load history entries
$history = [];
$sql = "SELECT session_note, date_created FROM history WHERE patientId = ? ORDER BY date_created DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $patientId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindTrack - History</title>
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

        .arrow-btn {
            position: absolute; top: 20px; left: 20px;
            background: none; border: none;
            font-size: 24px; color: #007b9e; cursor: pointer;
        }
        .arrow-btn:hover { color: #005f7a; }

        .history-box {
            background-color: #b7dde5;
            flex-grow: 1; padding: 20px;
            border-radius: 12px;
            display: flex; flex-direction: column;
            margin-top: 40px;
        }

        .history-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
        .history-header h1 {
            margin: 0; font-size: 28px; font-weight: bold;
        }
        .history-header button {
            background-color: #77aac0; color: white;
            border: none; padding: 10px 18px;
            font-size: 16px; border-radius: 6px;
            cursor: pointer; transition: background-color 0.2s ease;
        }
        .history-header button:hover {
            background-color: #5f8ea3;
        }

        .history-table {
            width: 100%; border-collapse: collapse;
            background: white; border-radius: 8px;
            overflow: hidden;
        }
        .history-table th, .history-table td {
            padding: 12px; font-size: 16px;
            border-bottom: 1px solid #ccc;
        }
        .history-table th {
            background-color: #e6f3f7;
            text-align: left; font-size: 18px;
            border-bottom: 2px solid #003366;
        }
        .history-table th + th, .history-table td + td {
            border-left: 2px solid #003366;
        }

        .editable-input {
            width: 95%; padding: 6px;
            font-size: 16px; border-radius: 4px;
            border: 1px solid #ccc;
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
    <button class="arrow-btn" onclick="window.location.href='sp.php?id=<?php echo urlencode($patientId); ?>'">&larr;</button>

    <div class="history-box">
        <div class="history-header">
            <h1>HISTORY</h1>
            <button id="addHistoryBtn">Add History</button>
        </div>

        <table class="history-table" id="historyTable">
            <thead>
                <tr>
                    <th style="width:75%;">VISITS | SESSIONS</th>
                    <th style="width:25%;">DATE</th>
                </tr>
            </thead>
            <tbody id="historyBody">
                <?php foreach ($history as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['session_note']); ?></td>
                        <td><?php echo date("M d, Y h:i A", strtotime($entry['date_created'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const addBtn = document.getElementById('addHistoryBtn');
const historyBody = document.getElementById('historyBody');
const patientId = "<?php echo $patientId; ?>";

addBtn.addEventListener('click', () => {
    const newRow = document.createElement('tr');

    const visitCell = document.createElement('td');
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Type session here...';
    input.className = 'editable-input';
    visitCell.appendChild(input);

    const dateCell = document.createElement('td');
    dateCell.textContent = '...'; // waiting from server

    newRow.appendChild(visitCell);
    newRow.appendChild(dateCell);
    historyBody.prepend(newRow);

    input.focus();

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const note = input.value.trim();
            if (note === '') return;

            const formData = new FormData();
            formData.append('session_note', note);
            formData.append('patientId', patientId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(res => res.json())
            .then(data => {
                visitCell.textContent = data.session_note;
                dateCell.textContent = new Date(data.date_created).toLocaleString();
            })
            .catch(() => {
                dateCell.textContent = "Save failed";
            });
        }
    });
});
</script>

</body>
</html>
