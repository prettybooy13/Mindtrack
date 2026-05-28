<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindTrack - New Patient</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            background-color: #cdebf1;
        }

        body {
            display: flex;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 340px;
            background-color: #99ccdd;
            padding: 40px 30px;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            font-size: 34px;
            margin-bottom: 50px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            text-decoration: none;
            color: white;
            font-size: 22px;
            padding: 14px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .nav-item:hover {
            background-color: #88bcd0;
        }

        .nav-item img {
            width: 48px;
            height: 48px;
            margin-right: 18px;
        }

        .nav-item.active {
            background-color: #77aac0;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 60px;
            overflow-y: auto;
        }

        .main-content h3 {
            margin-top: 0;
            font-size: 28px;
            border-bottom: 2px solid #999;
            padding-bottom: 12px;
        }

        .form-section {
            max-width: 900px;
            margin: 0 auto;
            background-color: #f0fbff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .form-section h4 {
            font-size: 22px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        .form-section label {
            font-size: 16px;
            display: block;
            margin-bottom: 6px;
        }

        .form-section input {
            width: 100%;
            padding: 10px;
            font-size: 15px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-section button {
            background-color: #99ccdd;
            color: white;
            padding: 14px 28px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
            display: block;
            width: 100%;
        }

        .form-section button:hover {
            background-color: #88bcd0;
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>MindTrack</h2>
    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'mindtrack.php' ? 'active' : ''; ?>" href="mindtrack.php">
        <img src="db.png" alt="Dashboard Icon"> Dashboard
    </a>
    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : ''; ?>" href="appointment.php">
        <img src="ap.png" alt="Appointment Icon"> Appointment
    </a>
    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'cl.php' ? 'active' : ''; ?>" href="cl.php">
        <img src="cl.png" alt="Calendar Icon"> Calendar
    </a>
    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pt.php' ? 'active' : ''; ?>" href="pt.php">
        <img src="pt.png" alt="Patients Icon"> Patients
    </a>
    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'set.php' ? 'active' : ''; ?>" href="set.php">
        <img src="set.png" alt="Settings Icon"> Settings
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h3>Register New Patient</h3>

    <div class="form-section">
        <form>
            <h4>PERSONAL INFORMATION</h4>
            <label for="patientId">Patient ID:</label>
            <input type="text" id="patientId" name="patientId" required>

            <label for="surname">Surname:</label>
            <input type="text" id="surname" name="surname" required>

            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname" required>

            <label for="middlename">Middle Name:</label>
            <input type="text" id="middlename" name="middlename">

            <label for="address">Address:</label>
            <input type="text" id="address" name="address">

            <label for="email">Email:</label>
            <input type="email" id="email" name="email">

            <label for="contact">Contact No:</label>
            <input type="text" id="contact" name="contact">

            <label for="sex">Sex:</label>
            <input type="text" id="sex" name="sex">

            <label for="birthdate">Birthdate:</label>
            <input type="date" id="birthdate" name="birthdate">

            <h4>EMERGENCY CONTACT</h4>
            <label for="ec_surname">Surname:</label>
            <input type="text" id="ec_surname" name="ec_surname">

            <label for="ec_firstname">First Name:</label>
            <input type="text" id="ec_firstname" name="ec_firstname">

            <label for="ec_relationship">Relationship:</label>
            <input type="text" id="ec_relationship" name="ec_relationship">

            <label for="ec_contact">Contact No:</label>
            <input type="text" id="ec_contact" name="ec_contact">

            <button type="submit">REGISTER</button>
        </form>
    </div>
</div>

</body>
</html>
