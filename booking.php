<?php
$color_primary_dark = '#0077b6';
$color_deep_blue = '#00A9FF';
$color_medium_blue = '#89CFF3';
$color_light_blue = '#A0E9FF';
$color_very_light_blue = '#E0F7FF';
$color_text_dark = '#333';
$color_text_medium = '#666';
$color_bg_light = '#F0F8FF';
$color_card_bg = '#FFFFFF';
$color_warning_bg = '#FFF3CD';
$color_warning_text = '#856404';

$map_lat = '14.818966525374533';
$map_lng = '120.96169943389609';
$map_zoom = '15';
$map_embed_url = "https://maps.google.com/maps?q={$map_lat},{$map_lng}&z={$map_zoom}&output=embed";

// Maximum date for DOB (at least 5 years old from today's actual date)
$max_dob = date('Y-m-d', strtotime('-5 years'));
$max_dob_display = date('F d, Y', strtotime($max_dob));

// Placeholders for form processing messages
$success_message = '';
$error_message = '';
$connection_error = '';

/**
 * AJAX endpoint: return booked times for a given date
 * GET params: action=booked_times, date=YYYY-MM-DD
 */
if (isset($_GET['action']) && $_GET['action'] === 'booked_times' && !empty($_GET['date'])) {
    $req_date = $_GET['date'];

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = new mysqli("localhost", "root", "", "mindtrack");
        $db->set_charset("utf8mb4");
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'db_connection']);
        exit;
    }

    $booked = [];
    $sql = "SELECT booking_time FROM booking_request WHERE booking_date = ? AND status != 'Cancelled'";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('s', $req_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $booked[] = $row['booking_time'];
        }
        $stmt->close();
    }
    $db->close();

    header('Content-Type: application/json');
    echo json_encode(array_values($booked));
    exit;
}
?>

<?php
// ==== BOOKING SAVE FUNCTION ====
if (isset($_POST['submit_booking'])) {
    // --- collect incoming fields (keep existing validations afterwards) ---
    // Prefer display-preserved time if provided by client
    $display_time = trim($_POST['booking_time_display'] ?? '');
    $raw_time = trim($_POST['booking_time'] ?? '');

    if ($display_time !== '') {
        // store the AM/PM string as submitted (e.g. "04:00 PM")
        $to_store = $display_time;
    } else if ($raw_time !== '') {
        // fallback: normalize 24h value to "HH:MM:SS" (existing behavior)
        $ts = strtotime($raw_time);
        $to_store = ($ts !== false) ? date('H:i:s', $ts) : $raw_time;
    } else {
        $to_store = '';
    }

    // Now use $to_store when preparing INSERT into booking_request for the booking_time column.
    // e.g. $stmt->bind_param(..., $to_store, ...);
    // If your DB column is TIME type and cannot accept "04:00 PM", convert here to H:i:s as a fallback.
    // To ensure compatibility, detect DB column type if needed — simplest: if DB expects TIME convert $display_time:
    if (!empty($display_time)) {
        // basic check: if display_time contains AM/PM and DB expects TIME, convert to H:i:s too
        $m = preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $display_time, $mm);
        if ($m) {
            $hh = intval($mm[1]);
            $mn = str_pad($mm[2],2,'0',STR_PAD_LEFT);
            $amp = strtoupper($mm[3]);
            if ($amp === 'AM' && $hh === 12) $hh = 0;
            if ($amp === 'PM' && $hh !== 12) $hh += 12;
            $to_store_time = sprintf('%02d:%02d:00', $hh, $mn);
            // choose whether to save $display_time or $to_store_time depending on your DB schema:
            // - if booking_request.booking_time is VARCHAR: save $display_time
            // - if TIME: save $to_store_time
            // Replace below with the correct variable for your INSERT:
            // $stmt->bind_param(..., $to_store_time, ...);
        }
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli("localhost", "root", "", "mindtrack");
        $conn->set_charset("utf8mb4");
    } catch (Exception $e) {
        $connection_error = "Database connection failed. Please try again later.";
        goto display_html;
    }

    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $service_type = $_POST['service_type'] ?? '';
    $birthdate = $_POST['dob'] ?? '';
    $booking_date = $_POST['appointment_date'] ?? '';
    $booking_time = $_POST['appointment_time'] ?? '';
    $status = 'Pending'; // default

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($service_type) || empty($birthdate) || empty($booking_date) || empty($booking_time)) {
        $error_message = "Please fill in all required fields, including date and time.";
    } else {
        // Insert booking (ensure your DB table has these columns)
        $sql = "INSERT INTO booking_request (first_name, last_name, email, service_type, birthdate, booking_date, booking_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssssssss", $first_name, $last_name, $email, $service_type, $birthdate, $booking_date, $to_store, $status);

            if ($stmt->execute()) {
                $success_message = "✔ Your appointment request has been sent! We will contact you via email for confirmation.";
            } else {
                $error_message = "Error: Could not save your booking. Please try again. " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error: Could not prepare the SQL statement. " . $conn->error;
        }
    }

    $conn->close();
}
display_html:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Wayside Psyche Resources Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
   <style>
        /* === CUSTOM CSS STYLES === */
        :root {
            --bs-border-radius: 1rem;
        }

        body {
            background-color: <?php echo $color_bg_light; ?>;
            color: <?php echo $color_text_dark; ?>;
            font-family: 'Poppins', sans-serif;
            padding-top: 20px;
        }
        .header-bar {
            background-color: <?php echo $color_card_bg; ?>;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: center;
        }
        .header-content-wrapper {
            max-width: 960px;
            width: 90%;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }
        .logo-text {
            color: <?php echo $color_primary_dark; ?>;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .primary-icon {
            color: <?php echo $color_primary_dark; ?>;
            margin-right: 10px;
        }
        .card-custom {
            background-color: <?php echo $color_card_bg; ?>;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .card-light-blue {
            background-color: <?php echo $color_very_light_blue; ?>;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: 600;
            color: <?php echo $color_primary_dark; ?>;
            padding: 15px;
            border-radius: 12px;
            background-color: #f7ffff;
            border: 1px solid <?php echo $color_light_blue; ?>;
            margin-bottom: 15px;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s;
        }
        .section-title:hover {
            background-color: <?php echo $color_very_light_blue; ?>;
        }
        .service-item {
            background-color: <?php echo $color_card_bg; ?>;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid <?php echo $color_light_blue; ?>;
            margin-bottom: 15px;
            height: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .professional-badge {
            background-color: <?php echo $color_very_light_blue; ?>;
            color: <?php echo $color_primary_dark; ?>;
            border: 1px solid <?php echo $color_medium_blue; ?>;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 400;
            display: inline-block;
        }
        .professional-card {
            border: 1px solid <?php echo $color_light_blue; ?>;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f7ffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .cancellation-policy {
            background-color: <?php echo $color_warning_bg; ?>;
            color: <?php echo $color_warning_text; ?>;
            border: 1px solid #FFC107;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .btn-primary-custom {
            background-color: <?php echo $color_deep_blue; ?>;
            border-color: <?php echo $color_deep_blue; ?>;
            font-size: 1.2rem;
            padding: 10px 30px;
            margin-top: 20px;
            font-weight: 600;
            border-radius: 10px;
        }
        .btn-primary-custom:hover {
             background-color: <?php echo $color_primary_dark; ?>;
             border-color: <?php echo $color_primary_dark; ?>;
        }
        .form-control-custom {
            background-color: <?php echo $color_very_light_blue; ?>;
            border: 1px solid <?php echo $color_light_blue; ?>;
            padding: 10px 15px;
            height: auto;
            border-radius: 10px;
        }

        /* CALENDAR/TIME STYLES */
        .date-picker-grid {
            display: grid; grid-template-columns: repeat(7, 1fr);
            gap: 5px; text-align: center; margin-top: 10px;
        }
        .day-header {
            font-weight: 600;
            color: <?php echo $color_primary_dark; ?>;
        }
        .date-cell {
            padding: 5px;
            cursor: pointer;
            border-radius: 6px;
            transition: background-color 0.2s, color 0.2s;
        }
        .date-cell:not(.text-muted):not(.past-date):hover {
            background-color: <?php echo $color_medium_blue; ?>;
        }
        .date-cell.selected {
            background-color: <?php echo $color_primary_dark; ?>;
            color: white;
            font-weight: 600;
        }
        .date-cell.today {
            border: 2px solid <?php echo $color_primary_dark; ?>;
            font-weight: 600;
            background-color: transparent !important;
            color: <?php echo $color_text_dark; ?>;
        }
        .date-cell.today.selected {
            background-color: <?php echo $color_primary_dark; ?> !important;
            color: white;
            border-color: <?php echo $color_primary_dark; ?>;
        }

        .time-slot {
            padding: 8px; margin: 5px;
            border: 1px solid <?php echo $color_medium_blue; ?>;
            border-radius: 8px;
            cursor: pointer; text-align: center;
            color: <?php echo $color_primary_dark; ?>;
            background-color: <?php echo $color_very_light_blue; ?>;
            transition: all 0.2s;
        }
        .time-slot:hover {
            background-color: <?php echo $color_light_blue; ?>;
            color: <?php echo $color_text_dark; ?>;
        }
        .time-slot.selected {
            background-color: <?php echo $color_deep_blue; ?>;
            color: white;
            border-color: <?php echo $color_deep_blue; ?>;
            font-weight: 600;
        }

        /* Disabled time-slot style */
        .time-slot.disabled {
            opacity: 0.45;
            pointer-events: none;
            background-color: #f8f9fa;
            border-color: #ddd;
            color: #999;
        }

        .contact-details-box {
            background-color: <?php echo $color_very_light_blue; ?>;
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }
        .privacy-box {
            background-color: <?php echo $color_very_light_blue; ?>;
            color: <?php echo $color_text_dark; ?>;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* === GRAY OUT PAST DATES IN BOOKING CALENDAR === */
        .date-cell.past-date {
            color: #aaa !important; /* gray text */
            cursor: not-allowed !important;
            pointer-events: none !important;
            text-decoration: line-through;
        }
</style>
</head>
<body>

<div class="header-bar sticky-top">
    <div class="header-content-wrapper">
        <div>
            <span class="logo-text">
                <i class="fas fa-heart primary-icon"></i> Wayside Psyche Resources Center
            </span>
            <small class="d-block text-muted">Care, Core, Cure</small>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mt-3" role="alert"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message) || !empty($connection_error)): ?>
                <div class="alert alert-danger mt-3" role="alert"><?php echo $error_message . $connection_error; ?></div>
            <?php endif; ?>

            <div class="card-light-blue text-center">
                <i class="fas fa-heart fa-2x primary-icon mb-2" style="color: <?php echo $color_primary_dark; ?>;"></i>
                <h3>Welcome to Wayside Psyche Resources Center</h3>
                <h4 style="color: <?php echo $color_primary_dark; ?>;">Care, Core, Cure</h4>
                <p class="mt-3">"Our focus is <strong>Quality Care</strong> for our Client's <strong>Psychological Core</strong> and <strong>Cure</strong>".</p>
                <p>At Wayside Psyche Resources Center, we are dedicated to promoting mental health and emotional wellness through compassionate, professional, and evidence-based care. Our clinic provides <strong>face-to-face consultations</strong>, ensuring accessible support wherever you are.</p>
            </div>

            <div class="card-custom mt-4">
                <div class="section-title" data-bs-toggle="collapse" data-bs-target="#marketingWhyChoose" aria-expanded="true" aria-controls="marketingWhyChoose">
                    <i class="fas fa-hand-holding-heart primary-icon"></i> Why Choose Wayside Psyche? <i class="fas fa-chevron-down float-end"></i>
                </div>

                <div class="collapse show pt-3" id="marketingWhyChoose">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <h5 style="color: <?php echo $color_deep_blue; ?>;"><i class="fas fa-check-circle me-1"></i> Compassionate Care</h5>
                            <p class="text-muted"><small>We offer a supportive, non-judgemental environment focused on your holistic well-being.</small></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h5 style="color: <?php echo $color_deep_blue; ?>;"><i class="fas fa-lightbulb me-1"></i> Evidence-Based Practice</h5>
                            <p class="text-muted"><small>Our therapies use proven scientific methods (e.g., CBT, ABA) for effective outcomes.</small></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h5 style="color: <?php echo $color_deep_blue; ?>;"><i class="fas fa-users me-1"></i> Diverse Services</h5>
                            <p class="text-muted"><small>From individual counseling to specialized therapies, we cover all facets of mental health.</small></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="section-title" data-bs-toggle="collapse" data-bs-target="#marketingProfessionals" aria-expanded="true" aria-controls="marketingProfessionals">
                    <i class="fas fa-user-tie primary-icon"></i> Meet Our Professionals <i class="fas fa-chevron-down float-end"></i>
                </div>

                <div class="collapse show pt-3" id="marketingProfessionals">
                    <p class="text-center" style="color: <?php echo $color_text_medium; ?>;">Our team is composed of licensed and highly experienced mental health practitioners dedicated to your care.</p>

                    <h6 class="mt-4" style="color: <?php echo $color_deep_blue; ?>;"><i class="fas fa-user-tag me-1"></i> We have practitioners for the following fields:</h6>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <?php
                        $professionals = [
                            "Licensed Clinical Psychologist", "Licensed Occupational Therapist",
                            "Psychiatrist", "School Guidance Counselor", "Child Psychiatrist",
                            "Neuro-Developmental Pediatrician"
                        ];
                        foreach ($professionals as $prof) {
                            echo "<span class='professional-badge'><small>{$prof}</small></span>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="section-title" data-bs-toggle="collapse" data-bs-target="#servicesCollapse" aria-expanded="true" aria-controls="servicesCollapse">
                    <i class="fas fa-calendar-check primary-icon"></i> Services Offered <i class="fas fa-chevron-down float-end"></i>
                </div>

                <div class="collapse show pt-3" id="servicesCollapse">
                    <div class="row">
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-user-friends primary-icon"></i> Consultation</strong></p><small>Initial assessments and professional guidance tailored to your individual mental health needs.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-brain primary-icon"></i> Psychotherapy</strong></p><small>A collaborative process designed to help you understand and manage emotions, behaviors, and thought patterns.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-redo primary-icon"></i> Cognitive / Behavior Therapy (CBT)</strong></p><small>Evidence-based therapy that helps reshape unhelpful thinking and behavior patterns to promote better mental well-being.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-users primary-icon"></i> Family / Couple Therapy</strong></p><small>Strengthen relationships, improve communication, and resolve conflicts within families and couples.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-user primary-icon"></i> Individual Therapy</strong></p><small>Personalized sessions focused on self-understanding, healing, and emotional growth.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-heartbeat primary-icon"></i> Addiction Counseling</strong></p><small>Support and guidance for individuals overcoming addiction and substance use challenges.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-vials primary-icon"></i> Psychological Testing</strong></p><small>Comprehensive assessments for academic, behavioral, clinical, and occupational purposes.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-briefcase primary-icon"></i> Occupational Therapy</strong></p><small>Helps clients regain daily functioning, productivity, and independence in personal or work life.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-child primary-icon"></i> Applied Behavioral Analysis (ABA)</strong></p><small>A specialized program to support individuals, especially children, with behavioral or developmental challenges.</small></div></div>
                        <div class="col-md-6 mb-3"><div class="service-item"><p class="service-title mb-1"><strong><i class="fas fa-chalkboard-teacher primary-icon"></i> Training & Development</strong></p><small>Workshops and seminars for mental health awareness, emotional intelligence, and personal growth.</small></div></div>
                    </div>
                </div>
            </div>

            <div class="card-light-blue text-center">
                <h3>Take the First Step Toward Wellness</h3>
                <p>Whether you're seeking help for yourself, your child, or your family — we're here to guide you toward healing and growth.</p>
            </div>

            <h2 class="text-center mt-5 mb-3">Book Your Appointment</h2>
            <p class="text-center" style="color: <?php echo $color_text_medium; ?>;">Ready to schedule? Fill out the form below and we'll contact you shortly to confirm your appointment.</p>

            <form action="booking.php" method="POST">
                <input type="hidden" id="selected_time" name="booking_time" value="">
                <input type="hidden" id="selected_time_display" name="booking_time_display" value="">

                <div class="card-custom">
                    <h4>Select Service</h4>
                    <p style="color: <?php echo $color_text_medium; ?>;">Choose the type of therapy session you'd like to book</p>

                    <select class="form-select form-control-custom" name="service_type" required>
                        <option value="" disabled selected> Select a service </option>
                        <option value="Consultation">Consultation</option>
                        <option value="Psychotherapy">Psychotherapy</option>
                        <option value="CBT">Cognitive / Behavior Therapy (CBT)</option>
                        <option value="Family_Couple_Therapy">Family / Couple Therapy</option>
                        <option value="Individual_Therapy">Individual Therapy</option>
                        <option value="Addiction_Counseling">Addiction Counseling</option>
                        <option value="Psychological_Testing">Psychological Testing</option>
                        <option value="Occupational_Therapy">Occupational Therapy</option>
                        <option value="ABA">Applied Behavioral Analysis (ABA)</option>
                        <option value="Training">Training & Development Programs</option>
                    </select>
                </div>

                <div class="card-custom">
                    <h4>Choose Date & Time</h4>
                    <p style="color: <?php echo $color_text_medium; ?>;">Select your preferred appointment date and time. <strong style="color: <?php echo $color_primary_dark; ?>">Sundays are not available.</strong></p>
                    <div class="row">
                        <div class="col-lg-7">
                            <h5><i class="fas fa-calendar-alt primary-icon"></i> Date</h5>
                            <div class="border p-3 rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <i class="fas fa-chevron-left text-muted" id="prevMonth" style="cursor: pointer;"></i>
                                    <strong id="monthDisplay" style="color: <?php echo $color_primary_dark; ?>;"></strong>
                                    <i class="fas fa-chevron-right text-muted" id="nextMonth" style="cursor: pointer;"></i>
                                </div>
                                <div class="date-picker-grid" id="dateGrid">
                                    <div class="day-header">Su</div><div class="day-header">Mo</div><div class="day-header">Tu</div><div class="day-header">We</div><div class="day-header">Th</div><div class="day-header">Fr</div><div class="day-header">Sa</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 mt-3 mt-lg-0">
                            <h5><i class="fas fa-clock primary-icon"></i>Arrival Time</h5>
                            <div class="d-flex flex-wrap" id="timeSlotsContainer">
                                <div class="time-slot" data-time="08:00 AM">08:00 AM</div>
                                <div class="time-slot" data-time="09:00 AM">09:00 AM</div>
                                <div class="time-slot" data-time="10:00 AM">10:00 AM</div>
                                <div class="time-slot" data-time="11:00 AM">11:00 AM</div>
                                <div class="time-slot" data-time="12:00 PM">12:00 PM</div>
                                <div class="time-slot" data-time="01:00 PM">01:00 PM</div>
                                <div class="time-slot" data-time="02:00 PM">02:00 PM</div>
                                <div class="time-slot" data-time="03:00 PM">03:00 PM</div>
                                <div class="time-slot" data-time="04:00 PM">04:00 PM</div>
                                <div class="time-slot" data-time="05:00 PM">05:00 PM</div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="selectedDate" name="appointment_date" value="">
                    <input type="hidden" id="selectedTime" name="appointment_time" value="">
                </div>

                <div class="card-custom">
                    <h4>Your Information</h4>
                    <p style="color: <?php echo $color_text_medium; ?>;">Please provide your contact details</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label"><i class="fas fa-user primary-icon"></i> First Name</label>
                            <input type="text" class="form-control form-control-custom" id="firstName" name="first_name" placeholder="e.g., Juan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control form-control-custom" id="lastName" name="last_name" placeholder="e.g., Dela Cruz" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label"><i class="fas fa-birthday-cake primary-icon"></i> Date of Birth</label>
                        <input
                            type="date"
                            class="form-control form-control-custom"
                            id="dob"
                            name="dob"
                            required
                            max="<?php echo $max_dob; ?>"
                        >
                        <div class="form-text">Client must be at least 5 years old. (Born on or before <strong><?php echo $max_dob_display; ?></strong>)</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope primary-icon"></i> Email</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" placeholder="e.g., juandelacruz@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label"><i class="fas fa-phone primary-icon"></i> Phone Number</label>
                        <input type="tel" class="form-control form-control-custom" id="phone" name="phone" placeholder="e.g., 09171234567" required>
                    </div>

                    <div class="cancellation-policy mt-4">
                        <p class="mb-1"><strong><i class="fas fa-exclamation-triangle me-2"></i> Cancellation Policy</strong></p>
                        <small>Please note: Appointments must be cancelled at least <strong>48-72 hours (2-3 days)</strong> before your scheduled appointment time. Late cancellations may result in a fee. Please notify us as early as possible if you need to reschedule.</small>
                    </div>

                    <button type="submit" name="submit_booking" class="btn btn-primary-custom w-100"><i class="fas fa-calendar-plus me-2"></i> Request Appointment</button>
                </div>
            </form>

            <div class="contact-details-box text-center mb-5">
                <h4>Need Help Booking?</h4>
                <div class="row justify-content-center pt-3">
                    <div class="col-md-3 mb-3 footer-link-group">
                        <i class="fas fa-phone-alt primary-icon"></i>
                        <p class="mb-0"><strong>Phone</strong></p>
                        <small>0933 586 5859</small><br>
                        <small>0915 411 3022</small>
                    </div>
                    <div class="col-md-3 mb-3 footer-link-group">
                        <i class="fas fa-envelope primary-icon"></i>
                        <p class="mb-0"><strong>Email</strong></p>
                        <small>wayside.inquiries@gmail.com</small>
                    </div>
                    <div class="col-md-3 mb-3 footer-link-group">
                        <i class="fas fa-map-marker-alt primary-icon"></i>
                        <p class="mb-0"><strong>Location</strong></p>
                        <small>2nd Floor, AFG Bldg, C. De Jesus St, Poblacion, Sta. Maria, Bulacan</small>
                    </div>
                    <div class="col-md-3 mb-3 footer-link-group">
                        <i class="fab fa-facebook primary-icon"></i>
                        <p class="mb-0"><strong>Facebook</strong></p>
                        <small>Wayside Psyche Resource Center</small>
                    </div>
                </div>

                <hr style="color: <?php echo $color_primary_dark; ?>;">

                <div class="border rounded-3 mt-3" style="height: 300px; position: relative; overflow: hidden;">
                    <iframe
                        width="100%"
                        height="100%"
                        frameborder="0"
                        style="border:0;"
                        src="<?php echo $map_embed_url; ?>"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>

            <div class="privacy-box mb-5">
                <p class="mb-0"><i class="fas fa-lock me-2 primary-icon"></i> Your privacy is important to us. All information is kept confidential and secure in accordance with professional mental health regulations.</p>
            </div>

            <div class="text-center pb-4" style="color: <?php echo $color_text_medium; ?>; border-top: 1px solid #ddd; padding-top: 1rem;">
                <small>© <?php echo date("Y"); ?> Wayside Psyche Resources Center. All rights reserved.</small>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === CACHE DOM ELEMENTS ===
    const dateGrid = document.getElementById('dateGrid');
    const monthDisplay = document.getElementById('monthDisplay');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const selectedDateInput = document.getElementById('selectedDate');
    const selectedTimeInput = document.getElementById('selectedTime');

    // === STATE VARIABLES ===
    let nav = 0; // Month navigation offset
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Normalize today's date to midnight

    // === DATE & TIME SELECTION LOGIC ===
    function updateSelectedDate(dateString, targetCell) {
        document.querySelectorAll('.date-cell.selected').forEach(cell => cell.classList.remove('selected'));
        if (targetCell) {
            targetCell.classList.add('selected');
        }
        selectedDateInput.value = dateString;

        // Fetch booked times for selected date and disable corresponding slots
        fetch(`booking.php?action=booked_times&date=${encodeURIComponent(dateString)}`)
            .then(resp => resp.json())
            .then(bookedTimes => {
                // bookedTimes is expected to be an array of strings like "08:00 AM"
                document.querySelectorAll('.time-slot').forEach(slot => {
                    // clean previous state & listeners
                    slot.classList.remove('disabled');
                    slot.removeEventListener('click', handleTimeSlotClick);

                    const time = slot.dataset.time;
                    if (Array.isArray(bookedTimes) && bookedTimes.includes(time)) {
                        slot.classList.add('disabled');
                        // If disabled slot was selected, clear selection
                        if (slot.classList.contains('selected')) {
                            slot.classList.remove('selected');
                            selectedTimeInput.value = '';
                        }
                    } else {
                        // reattach click listener for available slots
                        slot.addEventListener('click', handleTimeSlotClick);
                    }
                });
            })
            .catch(() => {
                // On error: re-enable all slots and attach listeners
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.classList.remove('disabled');
                    slot.removeEventListener('click', handleTimeSlotClick);
                    slot.addEventListener('click', handleTimeSlotClick);
                });
            });
    }

    function handleTimeSlotClick(event) {
        // Prevent selecting disabled slots (defensive)
        if (event.currentTarget.classList.contains('disabled')) return;
        document.querySelectorAll('.time-slot.selected').forEach(slot => slot.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        selectedTimeInput.value = event.currentTarget.dataset.time;
    }

    // Initialize time slots (attach listeners)
    function initTimeSlots() {
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('disabled');
            slot.removeEventListener('click', handleTimeSlotClick);
            slot.addEventListener('click', handleTimeSlotClick);
        });
    }

    // === CALENDAR RENDERING LOGIC ===
    function renderCalendar() {
        const dt = new Date();
        if (nav !== 0) {
            dt.setMonth(new Date().getMonth() + nav);
        }

        const month = dt.getMonth();
        const year = dt.getFullYear();
        const firstDayOfMonth = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const paddingDays = firstDayOfMonth.getDay();

        monthDisplay.innerText = `${dt.toLocaleString('en-US', { month: 'long' })} ${year}`;

        // Clear previous calendar days but keep headers
        dateGrid.innerHTML = `
            <div class="day-header">Su</div><div class="day-header">Mo</div><div class="day-header">Tu</div><div class="day-header">We</div><div class="day-header">Th</div><div class="day-header">Fr</div><div class="day-header">Sa</div>
        `;

        const fragment = document.createDocumentFragment();

        for (let i = 1; i <= paddingDays + daysInMonth; i++) {
            const daySquare = document.createElement('div');
            daySquare.classList.add('date-cell');

            if (i > paddingDays) {
                const dayOfMonth = i - paddingDays;
                const currentDate = new Date(year, month, dayOfMonth);
                const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayOfMonth).padStart(2, '0')}`;

                daySquare.innerText = dayOfMonth;

                const isPast = currentDate < today;
                const isSunday = currentDate.getDay() === 0;

                if (isPast || isSunday) {
                    daySquare.classList.add('past-date');
                } else {
                    daySquare.dataset.date = fullDate;
                    daySquare.addEventListener('click', () => updateSelectedDate(fullDate, daySquare));
                    if (currentDate.getTime() === today.getTime()) {
                        daySquare.classList.add('today');
                    }
                    if (selectedDateInput.value === fullDate) {
                        daySquare.classList.add('selected');
                    }
                }
            }
            fragment.appendChild(daySquare);
        }
        dateGrid.appendChild(fragment);
    }

    // === EVENT LISTENERS FOR NAVIGATION ===
    prevMonthBtn.addEventListener('click', () => {
        if (nav > 0) { // Prevent going to past months
            nav--;
            selectedDateInput.value = ""; // Clear selection when changing month
            initTimeSlots(); // reset time slots
            renderCalendar();
        }
    });

    nextMonthBtn.addEventListener('click', () => {
        nav++;
        selectedDateInput.value = ""; // Clear selection when changing month
        initTimeSlots(); // reset time slots
        renderCalendar();
    });

    // --- INITIALIZATION ---
    initTimeSlots();
    renderCalendar();
});
</script>

</body>
</html>