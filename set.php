<?php
// PHP DUMMY DATA para sa Settings page
$user_data = [
    'name' => 'Admin User',
    'email' => 'admin@mindtrack.com',
    'phone' => '+1 (555) 123-4567',
    'role' => 'Administrator'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings - MindTrack Health Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* INAYOS ANG MGA KULAY GAMIT ANG PROVIDED HEX CODES */
        :root {
            --color-primary-dark: #0077b6; /* Mas madilim na primary blue */
            --color-deep-blue: #00A9FF; /* Accent Blue - Gagamitin para sa ON Toggle */
            --color-medium-blue: #89CFF3; 
            --color-light-blue: #A0E9FF; 
            --color-very-light-blue: #E0F7FF; /* Mas matingkad na hover/bg light blue */
            --color-text-dark: #333; 
            --color-text-medium: #666; 
            --color-bg-light: #F0F8FF;
            --color-bg-main: #E6F3FA; 
            --color-success-green: #4CAF50; /* Green (para sa Status/Connection) */
            --color-danger-red: #dc3545;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%; 
            font-family: 'Poppins', sans-serif;
            /* Background gradient na inayon sa Dashboard */
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
        /* Active state for Settings page */
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

        /* Header Style - Inayon sa Dashboard Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; 
            padding: 0;
        }
        .header-title h1 { font-size: 24px; color: var(--color-text-dark); font-weight: 600; } 
        .header-title p { font-size: 14px; color: var(--color-text-medium); margin-top: 5px; } 

        /* Settings Card Layout */
        .settings-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-bottom: 80px; /* Space for fixed Save Changes button */
        }

        .settings-section-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--color-very-light-blue);
        }

        .settings-section-title {
            display: flex;
            align-items: center;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-primary-dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--color-very-light-blue);
        }
        .settings-section-title i {
            margin-right: 10px;
            font-size: 20px;
        }

        /* Account Profile Section */
        .account-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .profile-avatar {
            width: 60px; height: 60px; border-radius: 50%;
            background-color: var(--color-light-blue);
            color: var(--color-primary-dark);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 24px;
            margin-right: 15px;
        }
        .profile-text h3 {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
        }
        .profile-text p {
            font-size: 13px;
            color: var(--color-text-medium);
        }

        .profile-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--color-text-medium);
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--color-very-light-blue);
            border-radius: 8px;
            font-size: 14px;
            color: var(--color-text-dark);
            background-color: var(--color-bg-light);
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--color-primary-dark);
            background-color: white;
        }

        .update-button {
            width: 100%;
            margin-top: 20px;
            background-color: var(--color-deep-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            box-shadow: 0 4px 8px rgba(0, 169, 255, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .update-button:hover {
            background-color: var(--color-primary-dark);
        }

        /* Preferences and Security Layout */
        .preferences-security-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Shared Settings Item Style */
        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
        }

        /* Added dashed border separator for notification items */
        .settings-item {
            border-bottom: 1px dashed var(--color-very-light-blue);
        }
        /* Tanggalin ang border sa dulo ng bawat group */
        .notification-item-group .settings-item:last-child,
        .system-prefs-group .settings-item:last-child {
            border-bottom: none;
        }

        /* Add bottom margin sa notification group para may space sa next section */
        .notification-item-group {
             margin-bottom: 25px;
        }
        
        /* --- Style para sa System Settings Group (Language/Theme Mode) --- */
        .system-prefs-group {
            /* Nilipat ang border-top pabalik sa System Settings card sa ilalim */
            border-top: none; 
            padding-top: 0;
        }
        .system-prefs-group .settings-item {
            border-bottom: none;
        }
        .language-mode-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0; /* Inayos padding */
        }
        
        .language-select, .theme-mode-select {
            padding: 8px 12px;
            border: 1px solid var(--color-very-light-blue);
            border-radius: 5px;
            font-size: 13px;
            background-color: white;
            cursor: pointer;
            appearance: none;
        }
        .theme-mode-select {
            background-color: var(--color-bg-light);
        }
        /* ----------------------------------------------------- */


        .item-details {
            display: flex;
            align-items: center;
        }
        .item-icon {
            width: 35px; height: 35px; border-radius: 50%;
            background-color: var(--color-bg-light);
            color: var(--color-deep-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .item-text strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
        }
        .item-text span {
            font-size: 12px;
            color: var(--color-text-medium);
        }

        /* Toggle Switch Styling (Inayos para mas kitang-kita at BLUE) */
        .toggle-switch {
            flex-shrink: 0;
            margin-left: 15px; 
        }
        .toggle-switch input {
            display: none;
        }
        .toggle-slider {
            width: 40px;
            height: 20px;
            /* OFF state: Mas matingkad na gray na may border para kitang-kita */
            background-color: #ddd; 
            border: 1px solid #ccc; /* Added border */
            border-radius: 10px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.4s;
        }
        .toggle-slider:before {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            left: 2px;
            bottom: 1px; /* Inayos para pumantay sa center */
            background-color: white; /* White slider button */
            border-radius: 50%;
            transition: transform 0.4s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25); 
        }
        /* ON state: Ginamit ang --color-deep-blue (#00A9FF) */
        .toggle-switch input:checked + .toggle-slider {
            background-color: var(--color-deep-blue); 
            border-color: var(--color-deep-blue); /* Added border-color change */
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }

        .action-link {
            font-size: 13px;
            font-weight: 500;
            color: var(--color-deep-blue);
            text-decoration: none;
            transition: color 0.2s;
            display: flex; /* Para magkatabi ang text at arrow */
            align-items: center;
        }
        .action-link i {
            margin-left: 5px; /* Space sa pagitan ng text at arrow */
            font-size: 12px;
        }
        .action-link:hover {
            text-decoration: underline;
        }

        /* Account Actions */
        .account-actions-box p {
            font-size: 13px;
            color: var(--color-text-medium);
            margin-bottom: 15px;
        }
        .log-out-button {
            width: 100%;
            background-color: var(--color-danger-red);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        .log-out-button:hover {
            background-color: #c82333; /* Darker red */
        }

        /* Fixed Footer Save Button */
        .footer-save-bar {
            position: fixed;
            bottom: 0;
            right: 0;
            width: calc(100% - 280px); /* Main content width */
            background-color: white;
            padding: 15px 30px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: flex-end;
            z-index: 1000;
        }
        .btn-save-changes {
            background-color: var(--color-deep-blue);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            box-shadow: 0 4px 8px rgba(0, 169, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-save-changes:hover {
            background-color: var(--color-primary-dark);
        }
        
        /* System Information Styles (Copied from image_3d5ac5.png) */
        .system-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .system-info-grid strong {
            display: block;
            font-weight: 600;
            color: var(--color-text-dark);
        }
        .system-info-grid span {
            color: var(--color-text-medium);
        }
        .system-info-grid .status-connected {
            color: var(--color-success-green);
            font-weight: 600;
        }

        .system-action-item {
            padding: 12px 0;
            border-top: 1px solid var(--color-very-light-blue);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .system-action-item:hover {
            background-color: var(--color-very-light-blue);
        }
        .system-action-item:last-child {
            border-bottom: 1px solid var(--color-very-light-blue);
        }
        .system-action-item a {
            display: flex;
            align-items: center;
            color: var(--color-deep-blue);
            font-weight: 500;
            text-decoration: none;
        }
        .system-action-item a.danger {
            color: var(--color-danger-red);
        }
        .system-action-item a i {
            margin-right: 15px;
            font-size: 16px;
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
            <a class="nav-item" href="doctors.php"><i class="fas fa-user-md"></i>Doctors</a>
            <a class="nav-item active" href="set.php"><i class="fas fa-cog"></i>Settings</a>
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
                <h1>Settings</h1>
                <p>Manage your account preferences, notifications, and security</p>
            </div>
        </div>

        <div class="settings-content">
            <div class="settings-section-card">
                <div class="settings-section-title">
                    <i class="fas fa-user-circle"></i> Account Profile
                </div>
                
                <div class="account-profile-header">
                    <div class="profile-avatar">A</div>
                    <div class="profile-text">
                        <h3><?= htmlspecialchars($user_data['name']) ?></h3>
                        <p><?= htmlspecialchars($user_data['role']) ?></p>
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" value="<?= htmlspecialchars($user_data['name']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?= htmlspecialchars($user_data['email']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" value="<?= htmlspecialchars($user_data['phone']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" value="<?= htmlspecialchars($user_data['role']) ?>" readonly>
                    </div>
                </div>

                <button class="update-button">
                    <i class="fas fa-pen"></i> Update Details
                </button>
            </div>

            <div class="preferences-security-grid">
                <div class="settings-section-card">
                    <div class="settings-section-title">
                        <i class="fas fa-bell"></i> Notification Preferences
                    </div>
                    
                    <div class="notification-item-group">
                        <div class="settings-item">
                            <div class="item-details">
                                <div class="item-icon"><i class="fas fa-envelope"></i></div>
                                <div class="item-text">
                                    <strong>Email Notifications</strong>
                                    <span>Get updates and alerts via email</span>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="settings-item">
                            <div class="item-details">
                                <div class="item-icon"><i class="fas fa-desktop"></i></div>
                                <div class="item-text">
                                    <strong>System Notifications</strong>
                                    <span>In-app reminders and news</span>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="settings-section-card">
                    <div class="settings-section-title">
                        <i class="fas fa-lock"></i> Security & Privacy
                    </div>

                    <div class="settings-item">
                        <div class="item-details">
                            <div class="item-icon"><i class="fas fa-key"></i></div>
                            <div class="item-text">
                                <strong>Change Password</strong>
                                <span>Update your security credentials</span>
                            </div>
                        </div>
                        <a href="#" class="action-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="settings-item">
                        <div class="item-details">
                            <div class="item-icon"><i class="fas fa-user-shield"></i></div>
                            <div class="item-text">
                                <strong>Two-Factor Authentication</strong>
                                <span>(2FA) is currently disabled</span>
                            </div>
                        </div>
                        <a href="#" class="action-link">Enable <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="settings-item">
                        <div class="item-details">
                            <div class="item-icon"><i class="fas fa-history"></i></div>
                            <div class="item-text">
                                <strong>Active Sessions</strong>
                                <span>Review devices logged into your account</span>
                            </div>
                        </div>
                        <a href="#" class="action-link">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="preferences-security-grid"> 
                    <div class="settings-section-card">
                        <div class="settings-section-title">
                            <i class="fas fa-wrench"></i> System Settings
                        </div>

                        <div class="system-prefs-group">
                            <div class="language-mode-item">
                                <div class="item-details">
                                    <div class="item-icon"><i class="fas fa-language"></i></div>
                                    <div class="item-text">
                                        <strong>App Language</strong>
                                        <span>Select your preferred language</span>
                                    </div>
                                </div>
                                <select class="language-select">
                                    <option>English (US)</option>
                                    <option>Filipino</option>
                                </select>
                            </div>

                            <div class="language-mode-item">
                                <div class="item-details">
                                    <div class="item-icon"><i class="fas fa-moon"></i></div>
                                    <div class="item-text">
                                        <strong>Theme Mode</strong>
                                        <span>Switch between light and dark mode</span>
                                    </div>
                                </div>
                                <select class="theme-mode-select">
                                    <option>Light Mode</option>
                                    <option>Dark Mode</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section-card account-actions-box">
                        <div class="settings-section-title">
                            <i class="fas fa-sign-out-alt"></i> Account Actions
                        </div>

                        <p>Log out of your current session or delete your account data.</p>
                        
                        <button class="log-out-button">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </button>
                    </div>
            </div>

            <div class="settings-section-card">
                <div class="settings-section-title">
                    <i class="fas fa-info-circle"></i> System Information
                </div>

                <div class="system-info-grid">
                    <div>
                        <strong>Version</strong>
                        <span>MindTrack v2.5.0</span>
                    </div>
                    <div>
                        <strong>Last Updated</strong>
                        <span>October 17, 2025</span>
                    </div>
                    <div>
                        <strong>Database Status</strong>
                        <span class="status-connected">Connected</span>
                    </div>
                    <div>
                        <strong>Storage Used</strong>
                        <span>2.4 GB / 10 GB</span>
                    </div>
                </div>

                <div class="system-action-item">
                    <a href="#"><i class="fas fa-database"></i> Backup Database</a>
                </div>
                <div class="system-action-item">
                    <a href="#"><i class="fas fa-file-export"></i> Export Data</a>
                </div>
                <div class="system-action-item">
                    <a href="#" class="danger"><i class="fas fa-eraser"></i> Clear Cache</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer-save-bar">
    <button class="btn-save-changes">
        <i class="fas fa-save"></i> Save Changes
    </button>
</div>

</body>
</html>