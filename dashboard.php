<?php
session_start();
require_once 'auth.php';
require_once 'includes/GymnastManager.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$gymnastManager = new GymnastManager();
$gymnasts = $gymnastManager->getAllGymnasts();
$isAdmin = $auth->isAdmin();
$isGymnast = $auth->isGymnast();

// Initialize variables with defaults
$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'gymnast';

$myProfile = null;
if ($isGymnast && isset($_SESSION['gymnast_id'])) {
    $myProfile = $gymnastManager->getGymnastById($_SESSION['gymnast_id']);
}

// Admin statistics
$totalGymnasts = count($gymnasts);
$activeCount = count(array_filter($gymnasts, function($g) { return $g['progress_status'] == 'Active'; }));
$beginnerCount = count(array_filter($gymnasts, function($g) { return $g['training_program'] == 'Beginner'; }));
$intermediateCount = count(array_filter($gymnasts, function($g) { return $g['training_program'] == 'Intermediate'; }));
$advancedCount = count(array_filter($gymnasts, function($g) { return $g['training_program'] == 'Advanced'; }));
$onHoldCount = count(array_filter($gymnasts, function($g) { return $g['progress_status'] == 'On Hold'; }));
$completedCount = count(array_filter($gymnasts, function($g) { return $g['progress_status'] == 'Completed'; }));

// Function to generate random password
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

// Handle new gymnast registration (Admin only)
$registerErrors = [];
$registerSuccess = false;
$generatedPassword = '';
$successMessage = '';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $training_program = $_POST['training_program'] ?? '';
    $enrollment_date = $_POST['enrollment_date'] ?? '';
    
    if (empty($username) && !empty($full_name)) {
        $username = strtolower(str_replace(' ', '.', $full_name));
        $username = preg_replace('/\.+/', '.', $username);
    }
    
    $generatedPassword = generateRandomPassword(10);
    
    if (empty($full_name)) $registerErrors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $registerErrors[] = "Valid email is required";
    if (empty($contact_no)) $registerErrors[] = "Contact number is required";
    if (empty($username) || strlen($username) < 3) $registerErrors[] = "Username must be at least 3 characters";
    if (empty($date_of_birth)) $registerErrors[] = "Date of birth is required";
    if (empty($training_program)) $registerErrors[] = "Training program is required";
    if (empty($enrollment_date)) $registerErrors[] = "Enrollment date is required";
    
    if (empty($registerErrors)) {
        $db = Database::getInstance();
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $db->executeQuery($check_sql, "s", [$username]);
        if ($check_stmt && $check_stmt->get_result()->num_rows > 0) {
            $registerErrors[] = "Username already exists. Please choose a different username.";
        }
    }
    
    if (empty($registerErrors)) {
        $db = Database::getInstance();
        $db->getConnection()->begin_transaction();
        
        try {
            $year = date('Y');
            $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $membership_id = "GYM{$year}{$random}";
            
            $sql = "INSERT INTO gymnasts (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->executeQuery($sql, "ssssssss", [
                $membership_id, $full_name, $email, $contact_no,
                $date_of_birth, $training_program, $enrollment_date, 'Active'
            ]);
            
            if ($stmt && $stmt->affected_rows > 0) {
                $gymnast_id = $stmt->insert_id;
                $hashed_password = password_hash($generatedPassword, PASSWORD_DEFAULT);
                
                $user_sql = "INSERT INTO users (username, password, role, email, full_name, contact_no, gymnast_id) 
                            VALUES (?, ?, 'gymnast', ?, ?, ?, ?)";
                $user_stmt = $db->executeQuery($user_sql, "ssssss", [
                    $username, $hashed_password, $email, $full_name, $contact_no, $gymnast_id
                ]);
                
                if ($user_stmt && $user_stmt->affected_rows > 0) {
                    $db->getConnection()->commit();
                    $registerSuccess = true;
                    $successMessage = "Gymnast registered successfully!<br><strong>Username:</strong> " . htmlspecialchars($username) . "<br><strong>Password:</strong> " . $generatedPassword;
                    $_SESSION['registration_success'] = $successMessage;
                    echo '<script>window.location.href = "dashboard.php";</script>';
                    exit();
                } else {
                    throw new Exception("Failed to create user account");
                }
            } else {
                throw new Exception("Failed to register gymnast");
            }
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $registerErrors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

if (isset($_SESSION['registration_success'])) {
    $successMessage = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $isAdmin ? 'Admin Dashboard' : 'My Dashboard'; ?> - Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            color: #1e293b;
        }

        /* Modern glassmorphism nav */
        .app-nav {
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.02);
        }

        .nav-inner {
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.9rem 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 1.4rem;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .brand-icon {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 6px 12px -6px rgba(79,70,229,0.3);
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #f8fafc;
            padding: 0.4rem 1.2rem 0.4rem 0.8rem;
            border-radius: 60px;
            border: 1px solid #e2e8f0;
        }

        .avatar {
            background: linear-gradient(145deg, #4f46e5, #7c3aed);
            width: 38px;
            height: 38px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .role-tag {
            background: #eef2ff;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #4f46e5;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 40px;
            font-weight: 500;
            transition: all 0.2s;
            color: #1e293b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: #fee2e2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .container {
            max-width: 1440px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Welcome header */
        .welcome-card {
            background: white;
            border-radius: 32px;
            padding: 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            border: 1px solid #eef2ff;
        }

        /* Stats grid professional */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-tile {
            background: white;
            border-radius: 28px;
            padding: 1.4rem 1.5rem;
            transition: all 0.2s;
            border: 1px solid #eef2ff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .stat-tile:hover {
            transform: translateY(-3px);
            border-color: #cbd5e1;
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.08);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .bg-primary-light { background: #eef2ff; color: #4f46e5; }
        .bg-success-light { background: #e6f7ec; color: #10b981; }
        .bg-warning-light { background: #fffbeb; color: #f59e0b; }
        .bg-info-light { background: #e0f2fe; color: #0ea5e9; }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-top: 0.8rem;
            color: #0f172a;
        }

        /* Program distribution cards */
        .program-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .program-card {
            background: white;
            border-radius: 28px;
            padding: 1.5rem;
            border-left: 5px solid;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            cursor: pointer;
        }

        .beginner-border { border-left-color: #10b981; }
        .intermediate-border { border-left-color: #f59e0b; }
        .advanced-border { border-left-color: #ef4444; }

        /* table card */
        .data-card {
            background: white;
            border-radius: 28px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 14px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 1rem 1.2rem;
            border-radius: 60px;
            margin-bottom: 1.8rem;
        }

        .filter-input, .filter-select {
            padding: 0.6rem 1rem;
            border-radius: 40px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 0.85rem;
        }

        .btn-sm {
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
            box-shadow: 0 2px 6px rgba(79,70,229,0.2);
        }

        .btn-outline {
            background: white;
            border: 1px solid #cbd5e1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem 0.8rem;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
        }

        td {
            padding: 1rem 0.8rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-active { background: #e6f7ec; color: #0e6b3e; }
        .badge-onhold { background: #fffbeb; color: #b45309; }
        .badge-completed { background: #eef2ff; color: #3730a3; }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-link {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-view { background: #eef2ff; color: #4f46e5; }
        .btn-edit { background: #e6f7ec; color: #0e6b3e; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }

        /* profile card for gymnast */
        .profile-modern {
            background: white;
            border-radius: 32px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }

        .profile-avatar-lg {
            width: 100px;
            height: 100px;
            background: linear-gradient(145deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .info-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
            gap: 1rem;
        }

        .info-item {
            background: #f8fafc;
            padding: 0.8rem 1rem;
            border-radius: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-container {
            background: white;
            max-width: 550px;
            width: 90%;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            padding: 1.2rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #eef2ff;
            font-weight: 700;
        }

        .modal-body {
            padding: 1.8rem;
        }

        .text-center { text-align: center; }

        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .filter-bar { border-radius: 20px; }
        }
    </style>
</head>
<body>

<nav class="app-nav">
    <div class="nav-inner">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-handstand"></i></div>
            <span>GYMNASTICS PRO</span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div class="user-badge">
                <div class="avatar"><i class="fas fa-user"></i></div>
                <span><strong><?php echo htmlspecialchars($current_user); ?></strong></span>
                <span class="role-tag"><?php echo strtoupper(htmlspecialchars($user_role)); ?></span>
            </div>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Exit</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Welcome banner -->
    <div class="welcome-card">
        <h1 style="font-size: 1.8rem; font-weight: 700;">👋 Welcome back, <?php echo htmlspecialchars($current_user); ?></h1>
        <p style="color: #475569; margin-top: 8px;"><?php echo $isAdmin ? 'Centralized athlete management & performance insights' : 'Your personal training dashboard & documents'; ?></p>
    </div>

    <?php if ($isAdmin): ?>
        <!-- ================= ADMIN DASHBOARD ================= -->
        <?php if ($successMessage): ?>
            <div style="background: #e6f7ec; border-radius: 20px; padding: 1rem; margin-bottom: 1.5rem; color: #0e6b3e; border-left: 5px solid #10b981;">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div class="stats-row">
            <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-primary-light"><i class="fas fa-users"></i></div></div><div class="stat-value"><?php echo $totalGymnasts; ?></div><div>Total athletes</div></div>
            <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-success-light"><i class="fas fa-check-circle"></i></div></div><div class="stat-value"><?php echo $activeCount; ?></div><div>Active members</div></div>
            <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-warning-light"><i class="fas fa-pause"></i></div></div><div class="stat-value"><?php echo $onHoldCount; ?></div><div>On hold</div></div>
            <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-info-light"><i class="fas fa-trophy"></i></div></div><div class="stat-value"><?php echo $completedCount; ?></div><div>Completed</div></div>
        </div>

        <!-- Program distribution -->
        <div class="program-grid">
            <div class="program-card beginner-border"><div style="display: flex; justify-content: space-between;"><span><i class="fas fa-seedling" style="color:#10b981;"></i> Beginner</span><span class="stat-value" style="font-size: 1.8rem;"><?php echo $beginnerCount; ?></span></div><div class="progress-bar" style="height:6px; background:#e2e8f0; border-radius:10px; margin-top:12px;"><div style="width:<?php echo $totalGymnasts>0 ? ($beginnerCount/$totalGymnasts)*100 : 0; ?>%; height:6px; background:#10b981; border-radius:10px;"></div></div></div>
            <div class="program-card intermediate-border"><div style="display: flex; justify-content: space-between;"><span><i class="fas fa-chart-line" style="color:#f59e0b;"></i> Intermediate</span><span class="stat-value" style="font-size: 1.8rem;"><?php echo $intermediateCount; ?></span></div><div class="progress-bar" style="height:6px; background:#e2e8f0; border-radius:10px; margin-top:12px;"><div style="width:<?php echo $totalGymnasts>0 ? ($intermediateCount/$totalGymnasts)*100 : 0; ?>%; height:6px; background:#f59e0b; border-radius:10px;"></div></div></div>
            <div class="program-card advanced-border"><div style="display: flex; justify-content: space-between;"><span><i class="fas fa-crown" style="color:#ef4444;"></i> Advanced</span><span class="stat-value" style="font-size: 1.8rem;"><?php echo $advancedCount; ?></span></div><div class="progress-bar" style="height:6px; background:#e2e8f0; border-radius:10px; margin-top:12px;"><div style="width:<?php echo $totalGymnasts>0 ? ($advancedCount/$totalGymnasts)*100 : 0; ?>%; height:6px; background:#ef4444; border-radius:10px;"></div></div></div>
        </div>

        <!-- Registration CTA + Table -->
        <div class="data-card">
            <div class="flex-between">
                <h3 style="font-weight: 700;"><i class="fas fa-user-plus"></i> Athlete management</h3>
                <a href="register_gymnast.php" style="background:#4f46e5; color:white; padding: 8px 24px; border-radius: 40px; text-decoration: none; font-weight: 500;"><i class="fas fa-plus"></i> Register gymnast</a>
            </div>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Search name, ID, email" class="filter-input">
                <select id="programFilter" class="filter-select"><option value="">All programs</option><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                <select id="statusFilter" class="filter-select"><option value="">All status</option><option>Active</option><option>On Hold</option><option>Completed</option></select>
                <button onclick="applyFilters()" class="btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <button onclick="resetFilters()" class="btn-sm btn-outline">Reset</button>
                <button onclick="exportToCSV()" class="btn-sm btn-outline"><i class="fas fa-file-csv"></i> Export</button>
            </div>
            <div style="overflow-x: auto;">
                <table id="gymnastTable">
                    <thead><tr><th onclick="sortTable(0)">Name <i class="fas fa-sort"></i></th><th onclick="sortTable(1)">Member ID</th><th>Email</th><th>Contact</th><th onclick="sortTable(4)">Program</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="tableBody">
                        <?php foreach ($gymnasts as $g): ?>
                        <tr data-program="<?php echo htmlspecialchars($g['training_program']); ?>" data-status="<?php echo htmlspecialchars($g['progress_status']); ?>">
                            <td><strong><?php echo htmlspecialchars($g['full_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($g['membership_id']); ?></code></td>
                            <td><?php echo htmlspecialchars($g['email']); ?></td>
                            <td><?php echo htmlspecialchars($g['contact_no']); ?></td>
                            <td><span class="badge" style="background:#f1f5f9;"><?php echo htmlspecialchars($g['training_program']); ?></span></td>
                            <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '', $g['progress_status'])); ?>"><?php echo htmlspecialchars($g['progress_status']); ?></span></td>
                            <td class="action-group">
                                <button onclick="viewGymnast(<?php echo $g['id']; ?>)" class="action-link btn-view"><i class="fas fa-eye"></i> View</button>
                                <a href="update.php?id=<?php echo $g['id']; ?>" class="action-link btn-edit"><i class="fas fa-pen"></i> Edit</a>
                                <button onclick="confirmDelete(<?php echo $g['id']; ?>, '<?php echo addslashes($g['full_name']); ?>')" class="action-link btn-delete"><i class="fas fa-trash"></i> Del</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="resultsCount" style="margin-top: 1rem; font-size:0.85rem; color:#475569;"></div>
        </div>

    <?php else: ?>
        <!-- ================= GYMNAST DASHBOARD ================= -->
        <?php if ($myProfile): ?>
        <div class="profile-modern">
            <div class="profile-avatar-lg"><i class="fas fa-user-astronaut"></i></div>
            <div style="flex:1">
                <h2 style="font-size: 1.8rem;"><?php echo htmlspecialchars($myProfile['full_name']); ?></h2>
                <p style="color:#4f46e5; font-weight: 500;"><?php echo htmlspecialchars($myProfile['membership_id']); ?></p>
                <div style="display: flex; gap: 12px; margin-top: 12px;">
                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $myProfile['progress_status'])); ?>"><i class="fas fa-circle"></i> <?php echo htmlspecialchars($myProfile['progress_status']); ?></span>
                </div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($myProfile['email']); ?></div>
            <div class="info-item"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($myProfile['contact_no']); ?></div>
            <div class="info-item"><i class="fas fa-calendar"></i> DOB: <?php echo date('M d, Y', strtotime($myProfile['date_of_birth'])); ?></div>
            <div class="info-item"><i class="fas fa-chalkboard"></i> Program: <?php echo htmlspecialchars($myProfile['training_program']); ?></div>
            <div class="info-item"><i class="fas fa-calendar-check"></i> Enrolled: <?php echo date('M d, Y', strtotime($myProfile['enrollment_date'])); ?></div>
            <div class="info-item"><i class="fas fa-clock"></i> Member since: <?php echo date('M d, Y', strtotime($myProfile['created_at'])); ?></div>
        </div>

        <!-- Two report cards -->
        <div class="program-grid" style="margin-top: 2rem;">
            <div class="program-card beginner-border" onclick="viewProfileReport()">
                <div><i class="fas fa-id-card" style="font-size: 1.8rem; color:#4f46e5;"></i></div>
                <h3 style="margin-top: 12px;">Profile Summary</h3>
                <p style="color:#475569;">Complete membership details & personal info</p>
                <button class="btn-sm btn-primary" style="margin-top: 12px;"><i class="fas fa-eye"></i> View report</button>
                <button class="btn-sm btn-outline" style="margin-left: 8px;" onclick="event.stopPropagation();downloadProfileReport()"><i class="fas fa-download"></i> PDF</button>
            </div>
            <div class="program-card intermediate-border" onclick="viewEnrollmentSlip()">
                <div><i class="fas fa-receipt" style="font-size: 1.8rem; color:#f59e0b;"></i></div>
                <h3 style="margin-top: 12px;">Enrollment Confirmation</h3>
                <p style="color:#475569;">Official enrollment slip & registration timestamp</p>
                <button class="btn-sm btn-primary" style="margin-top: 12px;"><i class="fas fa-eye"></i> View slip</button>
                <button class="btn-sm btn-outline" style="margin-left: 8px;" onclick="event.stopPropagation();downloadEnrollmentSlip()"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <!-- Academy analytics (global) -->
        <div class="data-card">
            <h3><i class="fas fa-chart-simple"></i> Academy program distribution</h3>
            <div class="stats-row" style="margin-top: 1rem;">
                <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-success-light"><i class="fas fa-seedling"></i></div></div><div class="stat-value"><?php echo $beginnerCount; ?></div><div>Beginner athletes</div></div>
                <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-warning-light"><i class="fas fa-chart-line"></i></div></div><div class="stat-value"><?php echo $intermediateCount; ?></div><div>Intermediate</div></div>
                <div class="stat-tile"><div class="stat-header"><div class="stat-icon bg-primary-light"><i class="fas fa-trophy"></i></div></div><div class="stat-value"><?php echo $advancedCount; ?></div><div>Advanced</div></div>
            </div>
        </div>
        <?php else: ?>
            <div class="data-card">
                <p style="color: #475569;">Loading your profile information...</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modals -->
<div id="viewModal" class="modal"><div class="modal-container"><div class="modal-header"><span style="float:right; cursor:pointer;" onclick="closeModal('viewModal')">&times;</span><i class="fas fa-user-circle"></i> Athlete details</div><div class="modal-body" id="viewModalContent"></div></div></div>
<div id="deleteModal" class="modal"><div class="modal-container"><div class="modal-header">Confirm removal</div><div class="modal-body"><p id="deleteMessage"></p><div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px;"><button onclick="closeModal('deleteModal')" class="btn-sm btn-outline">Cancel</button><button id="confirmDeleteBtn" class="btn-sm" style="background:#dc2626; color:white;">Delete permanently</button></div></div></div></div>

<script>
    let deleteId = null;
    let sortDir = {};

    function sortTable(col) {
        const tbody = document.getElementById('tableBody');
        if(!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        sortDir[col] = sortDir[col] === 'asc' ? 'desc' : 'asc';
        rows.sort((a,b) => {
            let aText = a.cells[col].innerText.trim().toLowerCase();
            let bText = b.cells[col].innerText.trim().toLowerCase();
            if(sortDir[col] === 'asc') return aText.localeCompare(bText);
            else return bText.localeCompare(aText);
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function applyFilters() {
        const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const program = document.getElementById('programFilter')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';
        const rows = document.querySelectorAll('#tableBody tr');
        let visible = 0;
        rows.forEach(row => {
            const name = row.cells[0].innerText.toLowerCase();
            const id = row.cells[1].innerText.toLowerCase();
            const email = row.cells[2].innerText.toLowerCase();
            const rowProg = row.getAttribute('data-program');
            const rowStat = row.getAttribute('data-status');
            const matchSearch = name.includes(search) || id.includes(search) || email.includes(search);
            const matchProg = !program || rowProg === program;
            const matchStat = !status || rowStat === status;
            if(matchSearch && matchProg && matchStat) { row.style.display = ''; visible++; }
            else row.style.display = 'none';
        });
        const countSpan = document.getElementById('resultsCount');
        if(countSpan) countSpan.innerText = `Showing ${visible} of ${rows.length} athletes`;
    }

    function resetFilters() {
        if(document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
        if(document.getElementById('programFilter')) document.getElementById('programFilter').value = '';
        if(document.getElementById('statusFilter')) document.getElementById('statusFilter').value = '';
        applyFilters();
    }

    function exportToCSV() {
        const rows = document.querySelectorAll('#tableBody tr');
        let csv = [["Name","Member ID","Email","Contact","Program","Status"]];
        rows.forEach(row => {
            if(row.style.display !== 'none'){
                csv.push([
                    row.cells[0].innerText.trim(), row.cells[1].innerText.trim(),
                    row.cells[2].innerText.trim(), row.cells[3].innerText.trim(),
                    row.cells[4].innerText.trim(), row.cells[5].innerText.trim()
                ]);
            }
        });
        const blob = new Blob([csv.map(r=>r.join(",")).join("\n")], {type:'text/csv'});
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `gymnasts_${new Date().toISOString().slice(0,19)}.csv`;
        a.click(); URL.revokeObjectURL(a.href);
    }

    function viewGymnast(id) {
        const modal = document.getElementById('viewModal');
        const content = document.getElementById('viewModalContent');
        content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Loading profile...</div>';
        modal.style.display = 'flex';
        fetch(`get_gymnast.php?id=${id}`).then(r=>r.json()).then(data=>{
            if(data.success){
                const g = data.gymnast;
                content.innerHTML = `<div style="display:grid; gap:12px;"><div><strong>Membership ID:</strong> ${g.membership_id}</div><div><strong>Email:</strong> ${g.email}</div><div><strong>Contact:</strong> ${g.contact_no}</div><div><strong>DOB:</strong> ${new Date(g.date_of_birth).toLocaleDateString()}</div><div><strong>Program:</strong> ${g.training_program}</div><div><strong>Enrolled:</strong> ${new Date(g.enrollment_date).toLocaleDateString()}</div><div><strong>Status:</strong> ${g.progress_status}</div><div><strong>Member since:</strong> ${new Date(g.created_at).toLocaleString()}</div></div><div style="margin-top:24px; text-align:right;"><a href="update.php?id=${g.id}" class="action-link btn-edit">Edit athlete</a><button onclick="closeModal('viewModal')" class="btn-sm btn-outline" style="margin-left:8px;">Close</button></div>`;
            } else content.innerHTML = '<p>Unable to load details</p><button onclick="closeModal(\'viewModal\')" class="btn-sm">Close</button>';
        }).catch(()=>{ content.innerHTML = '<p>Error loading</p><button onclick="closeModal(\'viewModal\')" class="btn-sm">Close</button>';});
    }

    function confirmDelete(id, name) { deleteId = id; document.getElementById('deleteMessage').innerHTML = `Delete <strong>${name}</strong>? This action is irreversible.`; document.getElementById('deleteModal').style.display = 'flex'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if(confirmBtn) {
        confirmBtn.addEventListener('click', ()=>{ if(deleteId) window.location.href = `delete.php?id=${deleteId}`; });
    }
    
    window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    <?php if (!$isAdmin && isset($myProfile) && $myProfile): ?>
    const gymnastData = {
        full_name: '<?php echo addslashes($myProfile['full_name']); ?>',
        membership_id: '<?php echo $myProfile['membership_id']; ?>',
        email: '<?php echo $myProfile['email']; ?>',
        contact_no: '<?php echo $myProfile['contact_no']; ?>',
        date_of_birth: '<?php echo date('F j, Y', strtotime($myProfile['date_of_birth'])); ?>',
        training_program: '<?php echo $myProfile['training_program']; ?>',
        enrollment_date: '<?php echo date('F j, Y', strtotime($myProfile['enrollment_date'])); ?>',
        progress_status: '<?php echo $myProfile['progress_status']; ?>',
        created_at: '<?php echo date('F j, Y g:i A', strtotime($myProfile['created_at'])); ?>'
    };
    function viewProfileReport() {
        const modal = document.getElementById('viewModal');
        const content = document.getElementById('viewModalContent');
        content.innerHTML = `<div style="background:#f0f4ff; padding:1.5rem; border-radius:24px; text-align:center;"><h2>${gymnastData.full_name}</h2><p>${gymnastData.membership_id}</p></div>
        <div style="margin-top:1rem;">${Object.entries(gymnastData).map(([k,v])=>`<div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eef2ff;"><strong>${k.replace(/_/g,' ')}</strong><span>${v}</span></div>`).join('')}</div>
        <div style="margin-top:20px; font-size:11px; text-align:center;">Generated ${new Date().toLocaleString()}</div>`;
        modal.style.display = 'flex';
    }
    function downloadProfileReport() {
        const div = document.createElement('div'); div.style.padding='2rem'; div.style.fontFamily='Inter'; div.innerHTML = `<h2>Gymnastics Academy · Profile</h2><hr/><p><strong>${gymnastData.full_name}</strong> (${gymnastData.membership_id})</p>${Object.entries(gymnastData).map(([k,v])=>`<p><strong>${k}:</strong> ${v}</p>`).join('')}<p>Report date: ${new Date().toLocaleString()}</p>`;
        html2pdf().set({filename:`Profile_${gymnastData.membership_id}.pdf`}).from(div).save();
    }
    function viewEnrollmentSlip() {
        const conf = 'ENR-'+new Date().getFullYear()+'-'+Math.random().toString(36).substring(2,8).toUpperCase();
        const modal = document.getElementById('viewModal');
        const content = document.getElementById('viewModalContent');
        content.innerHTML = `<div style="text-align:center;"><h3>Official Enrollment Confirmation</h3><p style="color:#4f46e5;">${conf}</p></div>
        <div style="margin-top:1rem;">${[['Student',gymnastData.full_name],['Member ID',gymnastData.membership_id],['Program',gymnastData.training_program],['Enrollment date',gymnastData.enrollment_date],['Status',gymnastData.progress_status],['Registration timestamp',gymnastData.created_at]].map(([l,v])=>`<div style="display:flex; justify-content:space-between; padding:6px 0;"><strong>${l}:</strong> ${v}</div>`).join('')}</div>
        <div class="text-center" style="margin-top:16px;"><i class="fas fa-check-circle" style="color:#10b981;"></i> Electronically generated slip</div>`;
        modal.style.display = 'flex';
    }
    function downloadEnrollmentSlip() {
        const conf = 'ENR-'+new Date().getFullYear()+'-'+Math.random().toString(36).substring(2,8).toUpperCase();
        const div = document.createElement('div'); div.style.padding='2rem'; div.innerHTML = `<h1>Gymnastics Academy</h1><h3>Enrollment Confirmation</h3><p><strong>Confirmation #:</strong> ${conf}</p><hr/>${[['Student',gymnastData.full_name],['Member ID',gymnastData.membership_id],['Program',gymnastData.training_program],['Enrollment date',gymnastData.enrollment_date],['Status',gymnastData.progress_status],['Timestamp',gymnastData.created_at]].map(([l,v])=>`<p><strong>${l}:</strong> ${v}</p>`).join('')}<p>Valid official document</p>`;
        html2pdf().set({filename:`Enrollment_${gymnastData.membership_id}.pdf`}).from(div).save();
    }
    <?php endif; ?>
    window.addEventListener('load', applyFilters);
</script>
</body>
</html>