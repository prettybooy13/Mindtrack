<?php
include 'db.php';

$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['id'])) {
    $successMessage = "Doctor registered successfully! ID: " . htmlspecialchars($_GET['id']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contact'];

    // Auto-generate doctor_id
    $doctorId = uniqid("DOC");

    $stmt = $conn->prepare("INSERT INTO doctors (doctor_id, name, email, specialization, contact, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $doctorId, $name, $email, $specialization, $contact);

    if ($stmt->execute()) {
        header("Location: nd.php?success=1&id=$doctorId");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindTrack - New Doctor</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            background-color: #cdebf1;
        }
        body { display: flex; }
        .  /* Layout container */
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

        .main-content { flex-grow: 1; padding: 60px; overflow-y: auto; }
        .main-content h3 {
            margin-top: 0; font-size: 28px;
            border-bottom: 2px solid #999; padding-bottom: 12px;
        }
        .form-section {
            max-width: 800px; margin: 0 auto;
            background-color: #f0fbff; padding: 40px;
            border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .form-section h4 {
            font-size: 22px; margin: 20px 0;
            border-bottom: 2px solid #ccc; padding-bottom: 8px;
            grid-column: 1 / -1;
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 25px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 14px; margin-bottom: 6px; font-weight: bold; }
        .form-group input {
            padding: 10px; font-size: 15px;
            border: 1px solid #ccc; border-radius: 6px; width: 100%;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-section button {
            background-color: #99ccdd; color: white;
            padding: 14px 28px; font-size: 18px;
            border: none; border-radius: 8px; cursor: pointer;
            margin-top: 30px; display: block; width: 100%; grid-column: 1 / -1;
        }
        .form-section button:hover { background-color: #88bcd0; }
        .success-alert {
            background-color: #d4edda; color: #155724;
            padding: 15px; border-radius: 8px;
            margin-bottom: 20px; font-size: 16px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>MindTrack</h2>
    <a class="nav-item" href="mindtrack.php"><img src="db.png"> Dashboard</a>
    <a class="nav-item" href="appointment.php"><img src="ap.png"> Appointment</a>
    <a class="nav-item" href="cl.php"><img src="cl.png"> Calendar</a>
    <a class="nav-item" href="pt.php"><img src="pt.png"> Patients</a>
    <a class="nav-item" href="set.php"><img src="set.png"> Settings</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h3>Register New Doctor</h3>

    <?php if (!empty($successMessage)): ?>
        <div id="success-alert" class="success-alert"><?php echo $successMessage; ?></div>
        <script>
            setTimeout(() => {
                const alertBox = document.getElementById('success-alert');
                if (alertBox) alertBox.style.display = 'none';
            }, 10000);
        </script>
    <?php endif; ?>

    <div class="form-section">
        <form method="POST" action="nd.php" class="form-grid">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact No:</label>
                <input type="number" id="contact" name="contact" min="0" required>
            </div>
            <button type="submit">REGISTER</button>
        </form>
    </div>
</div>

</body>
</html>
