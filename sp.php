<?php
// Save note if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_date'], $_POST['note_text'])) {
    $date = $_POST['note_date'];
    $text = trim($_POST['note_text']);
    if ($date && $text) {
        file_put_contents('notes.txt', "$date|$text\n", FILE_APPEND);
    }
}

// Load notes
$notes = [];
if (file_exists('notes.txt')) {
    $lines = file('notes.txt', FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        list($date, $text) = explode('|', $line, 2);
        $notes[$date] = $text;
    }
}

$patientId = $_GET['id'] ?? '';
$validId = '011204';
$currentDate = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindTrack - Patient Record</title>
  <style>
    * {
      box-sizing: border-box;
    }

    html, body {
      margin: 0;
      padding: 0;
      height: 100vh;
      font-family: 'Segoe UI', sans-serif;
      background-color: #cdebf1;
      overflow: hidden;
    }

    body {
      display: flex;
    }

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

    .main-content {
      flex-grow: 1;
      padding: 40px 60px;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }

    .main-content h3 {
      font-size: 22px;
      margin-bottom: 10px;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .search-box input {
      width: 300px;
      padding: 12px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #aaa;
    }

    .search-box button {
      padding: 12px 20px;
      font-size: 16px;
      background-color: #99ccdd;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .search-box button:hover {
      background-color: #77aac0;
    }

    .update-button {
      background-color: #77aac0;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .update-button:hover {
      background-color: #6699b0;
    }

    .patient-info {
      background-color: #f0fbff;
      padding: 16px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      max-width: 100%;
      flex-shrink: 0;
      font-size: 14px;
      margin-bottom: 12px;
    }

    .id-label {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 6px;
      line-height: 1.2;
      color: #007b9e;
    }

    .info-line {
      margin-bottom: 4px;
    }

    .tabs {
      display: flex;
      gap: 12px;
      margin-top: 10px;
    }

    .tab {
      padding: 6px 14px;
      background-color: #99ccdd;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
    }

    .tab.active {
      background-color: #77aac0;
      font-weight: bold;
    }

    .notes {
      flex-grow: 1;
      overflow-y: auto;
      margin-top: 12px;
      padding-right: 10px;
    }

    .notes table {
      width: 100%;
      border-collapse: collapse;
    }

    .notes th, .notes td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }

    .notes input[type="text"],
    .notes input[type="date"] {
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #aaa;
      font-size: 16px;
      width: 100%;
    }

    .note-view {
      margin-top: 30px;
      background: #e0f7ff;
      padding: 20px;
      border-radius: 8px;
    }

    .note-view h4 {
      margin-top: 0;
    }

    .notes a {
      text-decoration: none;
      color: black;
    }

    .notes a:hover {
      text-decoration: underline;
    }

    .notes::-webkit-scrollbar {
      width: 8px;
    }

    .notes::-webkit-scrollbar-thumb {
      background-color: #aaa;
      border-radius: 4px;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>MindTrack</h2>
  <a class="nav-item" href="mindtrack.php"><img src="db.png" alt=""> Dashboard</a>
  <a class="nav-item" href="appointment.php"><img src="ap.png" alt=""> Appointment</a>
  <a class="nav-item" href="cl.php"><img src="cl.png" alt=""> Calendar</a>
  <a class="nav-item active" href="pt.php"><img src="pt.png" alt=""> Patients</a>
  <a class="nav-item" href="set.php"><img src="set.png" alt=""> Settings</a>
</div>

<!-- Main Content -->
<div class="main-content">
  <h3>Search Patient</h3>

  <div class="top-bar">
    <form method="get" class="search-box">
      <input type="text" name="id" placeholder="Enter Patient ID">
      <button type="submit">Search Patient</button>
    </form>
    <form method="post">
      <button type="submit" class="update-button">Update</button>
    </form>
  </div>

  <?php if ($patientId && $patientId === $validId): ?>
    <div class="patient-info">
      <p class="id-label">#011204</p>
      <div class="info-line"><strong>Condition:</strong> Anxiety</div>
      <div class="info-line"><strong>Sex:</strong> Female</div>
      <div class="info-line"><strong>Last Visit:</strong> July 8, 2025</div>
      <div class="info-line"><strong>Last Update:</strong> July 8, 2025</div>

      <div class="tabs">
        <div class="tab">Prescription</div>
        <div class="tab active">History</div>
      </div>
    </div>

    <div class="notes">
      <form method="post">
        <table>
          <tr>
            <th>Date</th>
            <th>Progress Notes</th>
          </tr>
          <tr>
            <td>
              <input type="date" name="note_date" value="<?php echo $currentDate; ?>" required>
            </td>
            <td>
              <input type="text" name="note_text" placeholder="Add Notes" required>
            </td>
          </tr>
        </table>
      </form>

      <table style="margin-top: 30px;">
        <tr>
          <th>Date</th>
                   <th>Note</th>
        </tr>
        <?php foreach ($notes as $date => $text): ?>
        <tr>
          <td><a href="?id=011204&view=<?php echo $date; ?>"><?php echo date("F j, Y", strtotime($date)); ?></a></td>
          <td><?php echo htmlspecialchars(substr($text, 0, 60)) . '...'; ?></td>
        </tr>
        <?php endforeach; ?>
      </table>

      <?php if (isset($_GET['view']) && isset($notes[$_GET['view']])): ?>
      <div class="note-view">
        <h4>Note for <?php echo date("F j, Y", strtotime($_GET['view'])); ?></h4>
        <p><?php echo nl2br(htmlspecialchars($notes[$_GET['view']])); ?></p>
      </div>
      <?php endif; ?>
    </div>
  <?php elseif ($patientId): ?>
    <div class="patient-info">
      <p style="font-size: 16px; color: #555;">Patient ID not found.</p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
