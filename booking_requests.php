<?php
date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("localhost", "root", "", "mindtrack");
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    exit("DB connection failed.");
}

$sql = "SELECT booking_id, first_name, last_name, birthdate, service_type, booking_date, booking_time, status
        FROM booking_request
        WHERE status = 'Pending'
        ORDER BY booking_date ASC, created_at DESC";
$res = $conn->query($sql);
$requests = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Booking Requests - MindTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body{font-family:Inter, system-ui, Arial; background:#f4f7fb; padding:20px;}
.card{border-radius:12px}
.table td, .table th{vertical-align:middle}
.action-icon{width:36px;height:36px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e9eef8;background:white}
</style>
</head>
<body>
<div class="container">
  <div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Booking Requests</h4>
        <small class="text-muted">Assign doctor then approve request (approval will create appointment)</small>
      </div>
      <div>
        <a href="appointment.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Appointments</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-borderless bg-white rounded shadow-sm">
        <thead class="text-uppercase small text-muted">
          <tr>
            <th>Patient</th>
            <th>Date & Time</th>
            <th>Service</th>
            <th style="width:160px">Actions</th>
          </tr>
        </thead>
        <tbody id="requestsBody">
          <?php if (empty($requests)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No new booking requests.</td></tr>
          <?php else: ?>
            <?php foreach ($requests as $r): ?>
              <tr data-id="<?= htmlspecialchars($r['booking_id']) ?>" data-service="<?= htmlspecialchars($r['service_type']) ?>">
                <td>
                  <strong><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></strong><br>
                  <small class="text-muted">ID/BD: <?= htmlspecialchars($r['birthdate']) ?></small>
                </td>
                <td>
                  <div><i class="far fa-calendar-alt me-1"></i><?= date('M d, Y', strtotime($r['booking_date'])) ?></div>
                  <div><i class="far fa-clock me-1"></i><?= date('h:i A', strtotime($r['booking_time'])) ?></div>
                </td>
                <td><?= htmlspecialchars($r['service_type']) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2 btn-assign" data-id="<?= htmlspecialchars($r['booking_id']) ?>" data-first="<?= htmlspecialchars($r['first_name']) ?>" data-last="<?= htmlspecialchars($r['last_name']) ?>" data-bd="<?= htmlspecialchars($r['birthdate']) ?>" data-date="<?= htmlspecialchars($r['booking_date']) ?>" data-time="<?= htmlspecialchars($r['booking_time']) ?>" data-service="<?= htmlspecialchars($r['service_type']) ?>">
                    <i class="fas fa-user-md me-1"></i> Assign & Approve
                  </button>

                  <a href="process_request.php?id=<?= htmlspecialchars($r['booking_id']) ?>&action=decline" class="btn btn-sm btn-outline-danger" onclick="return confirm('Decline this request?')">
                    <i class="fas fa-times me-1"></i> Decline
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal" id="assignModal" tabindex="-1" style="display:none;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="assignForm">
        <div class="modal-header">
          <h5 class="modal-title">Assign Doctor & Approve</h5>
          <button type="button" class="btn-close" onclick="closeModal()"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="modalBookingId" name="booking_id">
          <div class="mb-2"><strong id="modalPatient"></strong></div>
          <div class="mb-3"><small id="modalDatetime" class="text-muted"></small></div>
          <div class="mb-3">
            <label class="form-label">Select Doctor</label>
            <select id="modalDoctor" name="doctor_id" class="form-select" required>
              <option value="">Loading doctors...</option>
            </select>
          </div>
          <div class="form-text text-muted">Assign a doctor before approving so the confirmation email includes the doctor.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign & Approve</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function qs(s){return document.querySelector(s)}
function qsa(s){return Array.from(document.querySelectorAll(s))}

function openModal(data){
  qs('#modalBookingId').value = data.id;
  qs('#modalPatient').innerText = data.last + ', ' + data.first + ' ('+data.bd+')';
  qs('#modalDatetime').innerText = data.date + ' ' + data.time + ' — ' + data.service;
  qs('#assignModal').style.display = 'block';
  loadDoctors();
}

function closeModal(){
  qs('#assignModal').style.display = 'none';
}

function loadDoctors(){
  const sel = qs('#modalDoctor');
  sel.innerHTML = '<option>Loading...</option>';
  fetch('appointment.php?action=fetch_doctors').then(r => r.json()).then(list=>{
    sel.innerHTML = '<option value="">-- Choose doctor --</option>';
    list.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.id; opt.text = d.name;
      sel.appendChild(opt);
    });
  }).catch(()=> sel.innerHTML = '<option value="">Error loading doctors</option>');
}

qsa('.btn-assign').forEach(btn=>{
  btn.addEventListener('click', function(){
    const data = {
      id: this.dataset.id,
      first: this.dataset.first,
      last: this.dataset.last,
      bd: this.dataset.bd,
      date: this.dataset.date,
      time: this.dataset.time,
      service: this.dataset.service
    };
    openModal(data);
  });
});

qs('#assignForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fd.append('action', 'approve_request_with_doctor');

  fetch('appointment.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(resp=>{
      if (resp.success) {
        alert('Approved. Appointment ID: ' + (resp.appointment_id || '—'));
        closeModal();
        location.reload();
      } else {
        alert('Error: ' + (resp.message || 'Unable to approve request.'));
      }
    })
    .catch(err=>{
      console.error(err);
      alert('Network or server error.');
    });
});
</script>
</body>
</html>