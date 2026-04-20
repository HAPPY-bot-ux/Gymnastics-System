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
$current_user = $_SESSION['username'];
$user_role = $_SESSION['role'];

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
                    // DO NOT redirect - stay on the same page to keep admin logged in
                    // Just set success message and refresh the page data
                    $_SESSION['registration_success'] = $successMessage;
                    // Refresh the page to show updated list without logging out
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

// Check for success message in session
if (isset($_SESSION['registration_success'])) {
    $successMessage = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Admin Dashboard' : 'My Dashboard'; ?> - Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
        }
        
        /* Premium Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .nav-links {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 6px 18px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 24px;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            padding: 40px 45px;
            margin-bottom: 35px;
            backdrop-filter: blur(10px);
        }
        
        .welcome-banner h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            color: #a0aec0;
            font-size: 15px;
        }
        
        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 28px;
            color: #34d399;
        }
        
        .success-message i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 35px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 24px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 18px;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.active { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.onhold { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.completed { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        
        .stat-info h3 {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: white;
        }
        
        /* Programs Section */
        .programs-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 35px;
        }
        
        .program-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 24px;
            transition: all 0.3s;
        }
        
        .program-card:hover {
            transform: translateY(-3px);
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .program-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        
        .program-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        
        .program-icon.beginner { background: linear-gradient(135deg, #10b981, #059669); }
        .program-icon.intermediate { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .program-icon.advanced { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .program-count {
            font-size: 32px;
            font-weight: 800;
            color: white;
        }
        
        .program-label {
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }
        
        .progress-fill.beginner { background: linear-gradient(90deg, #10b981, #34d399); }
        .progress-fill.intermediate { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .progress-fill.advanced { background: linear-gradient(90deg, #ef4444, #f87171); }
        
        /* Table Card */
        .table-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 28px;
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-header h2 {
            color: white;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 180px;
        }
        
        .filter-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary, .btn-export, .btn-reset {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-export {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-primary:hover, .btn-export:hover, .btn-reset:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 16px 12px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .data-table th:hover {
            color: #a78bfa;
        }
        
        .data-table td {
            padding: 16px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            font-size: 14px;
        }
        
        .data-table tr {
            transition: all 0.3s;
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        /* Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-onhold {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .status-completed {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .program-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .program-beginner {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        
        .program-intermediate {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }
        
        .program-advanced {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }
        
        .btn-view {
            background: rgba(102, 126, 234, 0.15);
            color: #a78bfa;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .btn-update {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        /* Premium Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1e1b4b, #1a1a3e);
            margin: 5% auto;
            padding: 0;
            border-radius: 28px;
            width: 90%;
            max-width: 600px;
            border: 1px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1));
            padding: 24px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header h3 {
            color: white;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-body {
            padding: 28px;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: 500;
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.3s;
        }
        
        .close:hover {
            color: white;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #94a3b8;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: #f87171;
            margin-left: 4px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 35px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 32px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        
        .profile-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .profile-avatar i {
            font-size: 45px;
            color: white;
        }
        
        .profile-info h2 {
            color: white;
            font-size: 26px;
            font-weight: 700;
        }
        
        .membership-id {
            color: #a78bfa;
            margin-top: 6px;
            font-weight: 500;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            padding: 16px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .detail-label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .detail-value {
            color: white;
            font-weight: 500;
            font-size: 15px;
        }
        
        .results-count {
            text-align: right;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            font-size: 14px;
        }
        
        .admin-only { display: <?php echo $isAdmin ? 'block' : 'none'; ?>; }
        .gymnast-only { display: <?php echo $isGymnast ? 'block' : 'none'; ?>; }
        
        /* Test Credentials Box */
        .test-credentials {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 28px;
        }
        
        .test-credentials h4 {
            color: #34d399;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        
        .credential-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .credential-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .credential-item strong {
            color: #a78bfa;
        }
        
        .info-text {
            margin-top: 15px;
            padding: 12px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Print styles for better PDF generation */
        @media print {
            .navbar, .filter-bar, .action-buttons, .btn-export, .btn-primary, .test-credentials, .close {
                display: none !important;
            }
            .profile-card, .program-card, .table-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            body {
                background: white;
            }
            .welcome-banner h1 {
                -webkit-text-fill-color: #667eea;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .filter-bar {
                flex-direction: column;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .container {
                padding: 0 16px;
            }
            .welcome-banner {
                padding: 25px;
            }
            .welcome-banner h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon"><i class="fas fa-gymnastics"></i></div>
                <span>Gymnastics Academy</span>
            </a>
            <div class="nav-links">
                <div class="user-info">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <span><?php echo htmlspecialchars($current_user); ?></span>
                    <span style="background: rgba(102,126,234,0.3); padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600;"><?php echo strtoupper($user_role); ?></span>
                </div>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($current_user); ?>! 👋</h1>
            <p><?php echo $isAdmin ? 'Manage gymnasts, track progress, and monitor academy performance.' : 'Track your training progress and view personal information.'; ?></p>
        </div>
        
        <?php if ($isAdmin): ?>
        <!-- Success Message -->
        <?php if ($successMessage): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> 
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <!-- Test Credentials Info Box -->
        <div class="test-credentials">
            <h4><i class="fas fa-flask"></i> Test Credentials</h4>
            <div class="credential-row">
                <div class="credential-item"><strong>👑 Admin:</strong> admin / admin123</div>
                <div class="credential-item"><strong>🤸 Gymnast:</strong> gymnast1 / gymnast123</div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon total"><i class="fas fa-users"></i></div><div class="stat-info"><h3>Total Gymnasts</h3><div class="stat-number"><?php echo $totalGymnasts; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon active"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3>Active Members</h3><div class="stat-number"><?php echo $activeCount; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon onhold"><i class="fas fa-pause-circle"></i></div><div class="stat-info"><h3>On Hold</h3><div class="stat-number"><?php echo $onHoldCount; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon completed"><i class="fas fa-trophy"></i></div><div class="stat-info"><h3>Completed</h3><div class="stat-number"><?php echo $completedCount; ?></div></div></div>
        </div>
        
        <!-- Programs Distribution -->
        <div class="programs-section">
            <div class="program-card"><div class="program-header"><div class="program-icon beginner"><i class="fas fa-seedling"></i></div><div class="program-count"><?php echo $beginnerCount; ?></div></div><div class="program-label">Beginner Program</div><div class="progress-bar"><div class="progress-fill beginner" style="width: <?php echo $totalGymnasts > 0 ? ($beginnerCount/$totalGymnasts)*100 : 0; ?>%"></div></div></div>
            <div class="program-card"><div class="program-header"><div class="program-icon intermediate"><i class="fas fa-chart-line"></i></div><div class="program-count"><?php echo $intermediateCount; ?></div></div><div class="program-label">Intermediate Program</div><div class="progress-bar"><div class="progress-fill intermediate" style="width: <?php echo $totalGymnasts > 0 ? ($intermediateCount/$totalGymnasts)*100 : 0; ?>%"></div></div></div>
            <div class="program-card"><div class="program-header"><div class="program-icon advanced"><i class="fas fa-trophy"></i></div><div class="program-count"><?php echo $advancedCount; ?></div></div><div class="program-label">Advanced Program</div><div class="progress-bar"><div class="progress-fill advanced" style="width: <?php echo $totalGymnasts > 0 ? ($advancedCount/$totalGymnasts)*100 : 0; ?>%"></div></div></div>
        </div>
        
        <!-- Register New Gymnast Form -->
      <!-- Register New Gymnast Section -->
<div class="table-card" style="margin-bottom: 35px;">
    <div class="table-header">
        <h2><i class="fas fa-user-plus"></i> Register New Gymnast</h2>
        <a href="register_gymnast.php" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus-circle"></i> Register New Gymnast
        </a>
    </div>
    <div class="info-text">
        <i class="fas fa-info-circle"></i> Click the button above to register a new gymnast. You will be redirected to the registration form with all required fields.
    </div>
</div>
        
        <!-- Gymnasts List -->
        <div class="table-card">
            <div class="table-header"><h2><i class="fas fa-users"></i> Gymnasts Management</h2><button onclick="exportToCSV()" class="btn-export"><i class="fas fa-file-csv"></i> Export CSV</button></div>
            <div class="filter-bar">
                <div class="filter-group"><label>Search</label><input type="text" id="searchInput" placeholder="Search by name, ID, email..."></div>
                <div class="filter-group"><label>Program</label><select id="programFilter"><option value="">All</option><option value="Beginner">Beginner</option><option value="Intermediate">Intermediate</option><option value="Advanced">Advanced</option></select></div>
                <div class="filter-group"><label>Status</label><select id="statusFilter"><option value="">All</option><option value="Active">Active</option><option value="On Hold">On Hold</option><option value="Completed">Completed</option></select></div>
                <button onclick="applyFilters()" class="btn-primary">Apply Filters</button>
                <button onclick="resetFilters()" class="btn-reset">Reset</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table" id="gymnastTable">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)">Name <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(1)">Membership ID <i class="fas fa-sort"></i></th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th onclick="sortTable(4)">Program <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(5)">Status <i class="fas fa-sort"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($gymnasts as $gymnast): ?>
                        <tr data-program="<?php echo $gymnast['training_program']; ?>" data-status="<?php echo $gymnast['progress_status']; ?>">
                            <td><strong><?php echo htmlspecialchars($gymnast['full_name']); ?></strong></td>
                            <td><code style="background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 8px;"><?php echo htmlspecialchars($gymnast['membership_id']); ?></code></td>
                            <td><i class="fas fa-envelope" style="color: #a78bfa; margin-right: 6px;"></i> <?php echo htmlspecialchars($gymnast['email']); ?></td>
                            <td><i class="fas fa-phone" style="color: #34d399; margin-right: 6px;"></i> <?php echo htmlspecialchars($gymnast['contact_no']); ?></td>
                            <td><span class="program-badge program-<?php echo strtolower($gymnast['training_program']); ?>"><?php echo $gymnast['training_program']; ?></span></td>
                            <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $gymnast['progress_status'])); ?>"><i class="fas <?php echo $gymnast['progress_status'] == 'Active' ? 'fa-check-circle' : ($gymnast['progress_status'] == 'On Hold' ? 'fa-pause-circle' : 'fa-check-double'); ?>"></i> <?php echo $gymnast['progress_status']; ?></span></td>
                            <td class="action-buttons">
                                <button onclick="viewGymnast(<?php echo $gymnast['id']; ?>)" class="action-btn btn-view"><i class="fas fa-eye"></i> View</button>
                                <a href="update.php?id=<?php echo $gymnast['id']; ?>" class="action-btn btn-update"><i class="fas fa-edit"></i> Edit</a>
                                <button onclick="confirmDelete(<?php echo $gymnast['id']; ?>, '<?php echo addslashes($gymnast['full_name']); ?>')" class="action-btn btn-delete"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="results-count" id="resultsCount"></div>
        </div>
        
        <?php else: ?>
        <!-- Gymnast Dashboard with Enhanced Reports -->
        <div class="programs-section">
            <!-- Report 1: Profile Summary Card -->
            <div class="program-card">
                <div class="program-header">
                    <div class="program-icon beginner"><i class="fas fa-id-card"></i></div>
                    <div>
                        <button onclick="viewProfileReport()" class="action-btn btn-view"><i class="fas fa-eye"></i> View</button>
                        <button onclick="downloadProfileReport()" class="btn-export" style="margin-left: 8px;"><i class="fas fa-download"></i> PDF</button>
                    </div>
                </div>
                <h3 style="color:white; margin-top:15px;">Profile Summary Report</h3>
                <p style="color:#94a3b8; font-size:13px; margin-top:8px;">Complete profile information including membership details, personal info, and training program.</p>
                <div class="info-text" style="margin-top: 15px;">
                    <i class="fas fa-info-circle"></i> Includes: Name, Membership ID, Email, DOB, Training Program, Enrollment Date
                </div>
            </div>
            
            <!-- Report 2: Enrollment Confirmation Slip -->
            <div class="program-card">
                <div class="program-header">
                    <div class="program-icon intermediate"><i class="fas fa-receipt"></i></div>
                    <div>
                        <button onclick="viewEnrollmentSlip()" class="action-btn btn-view"><i class="fas fa-eye"></i> View</button>
                        <button onclick="downloadEnrollmentSlip()" class="btn-export" style="margin-left: 8px;"><i class="fas fa-download"></i> PDF</button>
                    </div>
                </div>
                <h3 style="color:white; margin-top:15px;">Enrollment Confirmation</h3>
                <p style="color:#94a3b8; font-size:13px; margin-top:8px;">Official enrollment confirmation with registration timestamp and course summary.</p>
                <div class="info-text" style="margin-top: 15px;">
                    <i class="fas fa-info-circle"></i> Includes: Registration timestamp, Course summary, Status, Confirmation number
                </div>
            </div>
        </div>
        
        <!-- Profile Display Area -->
        <?php if ($myProfile): ?>
        <div class="profile-card" id="profileReportContent">
            <div class="profile-header">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($myProfile['full_name']); ?></h2>
                    <p class="membership-id">Member ID: <?php echo $myProfile['membership_id']; ?></p>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $myProfile['progress_status'])); ?>">
                        <i class="fas <?php echo $myProfile['progress_status'] == 'Active' ? 'fa-check-circle' : ($myProfile['progress_status'] == 'On Hold' ? 'fa-pause-circle' : 'fa-check-double'); ?>"></i> 
                        <?php echo $myProfile['progress_status']; ?>
                    </span>
                </div>
            </div>
            <div class="profile-details">
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($myProfile['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-phone"></i> Contact Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($myProfile['contact_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-calendar-alt"></i> Date of Birth</div>
                    <div class="detail-value"><?php echo date('F j, Y', strtotime($myProfile['date_of_birth'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-chalkboard-user"></i> Training Program</div>
                    <div class="detail-value"><span class="program-badge program-<?php echo strtolower($myProfile['training_program']); ?>"><?php echo $myProfile['training_program']; ?></span></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-calendar-check"></i> Enrollment Date</div>
                    <div class="detail-value"><?php echo date('F j, Y', strtotime($myProfile['enrollment_date'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-clock"></i> Member Since</div>
                    <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($myProfile['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Academy Program Analytics -->
        <div class="table-card">
            <h2 style="color:white; margin-bottom:24px;"><i class="fas fa-chart-line"></i> Academy Program Analytics</h2>
            <div class="stats-grid" style="margin-bottom:0;">
                <div class="stat-card">
                    <div class="stat-icon beginner"><i class="fas fa-seedling"></i></div>
                    <div class="stat-info">
                        <h3>Beginner Program</h3>
                        <div class="stat-number"><?php echo $beginnerCount; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon intermediate"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-info">
                        <h3>Intermediate Program</h3>
                        <div class="stat-number"><?php echo $intermediateCount; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon advanced"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info">
                        <h3>Advanced Program</h3>
                        <div class="stat-number"><?php echo $advancedCount; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Premium Modals -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeViewModal()">&times;</span>
                <h3><i class="fas fa-user-circle"></i> Gymnast Details</h3>
            </div>
            <div class="modal-body" id="viewModalContent"></div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeDeleteModal()">&times;</span>
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            </div>
            <div class="modal-body">
                <p id="deleteMessage" style="margin-bottom: 24px; color: #e2e8f0;">Are you sure?</p>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button onclick="closeDeleteModal()" class="btn-reset">Cancel</button>
                    <button id="confirmDeleteBtn" class="btn-delete" style="padding:10px 24px;">Delete Permanently</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let deleteId = null;
        
        let sortDirection = {};
        function sortTable(colIndex) {
            const table = document.getElementById('gymnastTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            sortDirection[colIndex] = sortDirection[colIndex] === 'asc' ? 'desc' : 'asc';
            rows.sort((a, b) => {
                let aVal = a.cells[colIndex].textContent.toLowerCase();
                let bVal = b.cells[colIndex].textContent.toLowerCase();
                if (sortDirection[colIndex] === 'asc') return aVal.localeCompare(bVal);
                else return bVal.localeCompare(aVal);
            });
            rows.forEach(row => tbody.appendChild(row));
        }
        
        function exportToCSV() {
            const rows = document.querySelectorAll('#tableBody tr');
            let csv = [["Name", "Membership ID", "Email", "Contact", "Program", "Status"]];
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    csv.push([
                        row.cells[0].textContent.trim(),
                        row.cells[1].textContent.trim(),
                        row.cells[2].textContent.trim(),
                        row.cells[3].textContent.trim(),
                        row.cells[4].textContent.trim(),
                        row.cells[5].textContent.trim()
                    ]);
                }
            });
            const csvContent = csv.map(row => row.join(",")).join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `gymnasts_export_${new Date().toISOString().slice(0,19)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }
        
        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const program = document.getElementById('programFilter').value;
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#tableBody tr');
            let visible = 0;
            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const id = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const rowProgram = row.getAttribute('data-program');
                const rowStatus = row.getAttribute('data-status');
                const matchSearch = name.includes(search) || id.includes(search) || email.includes(search);
                const matchProgram = !program || rowProgram === program;
                const matchStatus = !status || rowStatus === status;
                if (matchSearch && matchProgram && matchStatus) { row.style.display = ''; visible++; }
                else { row.style.display = 'none'; }
            });
            document.getElementById('resultsCount').innerHTML = `<i class="fas fa-chart-bar"></i> Showing ${visible} of ${rows.length} gymnasts`;
        }
        
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('statusFilter').value = '';
            applyFilters();
        }
        
        function viewGymnast(id) {
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('viewModalContent');
            content.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            modal.style.display = 'block';
            fetch(`get_gymnast.php?id=${id}`).then(r=>r.json()).then(data=>{
                if(data.success){
                    const g = data.gymnast;
                    content.innerHTML = `
                        <div style="display:grid; gap:14px;">
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Membership ID:</strong> <span style="color:white;">${g.membership_id}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Email:</strong> <span style="color:white;">${g.email}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Contact Number:</strong> <span style="color:white;">${g.contact_no}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Date of Birth:</strong> <span style="color:white;">${new Date(g.date_of_birth).toLocaleDateString()}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Training Program:</strong> <span style="color:white;">${g.training_program}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Enrollment Date:</strong> <span style="color:white;">${new Date(g.enrollment_date).toLocaleDateString()}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Status:</strong> <span style="color:white;">${g.progress_status}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                                <strong style="color:#a78bfa;">Member Since:</strong> <span style="color:white;">${new Date(g.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                        <div style="margin-top: 28px; display: flex; gap: 12px; justify-content: flex-end;">
                            <a href="update.php?id=${g.id}" class="btn-update" style="padding:10px 20px; text-decoration:none;">Edit Gymnast</a>
                            <button onclick="closeViewModal()" class="btn-reset">Close</button>
                        </div>
                    `;
                } else { content.innerHTML = '<p style="color:white;">Error loading data</p><button onclick="closeViewModal()" class="btn-reset" style="margin-top:20px;">Close</button>'; }
            }).catch(()=>{ content.innerHTML = '<p style="color:white;">Error loading data</p><button onclick="closeViewModal()" class="btn-reset" style="margin-top:20px;">Close</button>'; });
        }
        
        function confirmDelete(id, name) { deleteId = id; document.getElementById('deleteMessage').innerHTML = `Delete <strong>${name}</strong>? This action cannot be undone.`; document.getElementById('deleteModal').style.display = 'block'; }
        function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }
        function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; deleteId = null; }
        document.getElementById('confirmDeleteBtn').onclick = function() { if(deleteId) window.location.href = `delete.php?id=${deleteId}`; };
        window.onclick = function(e) { if(e.target == document.getElementById('viewModal')) closeViewModal(); if(e.target == document.getElementById('deleteModal')) closeDeleteModal(); };
        
        <?php if ($isGymnast && $myProfile): ?>
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
            content.innerHTML = `
                <div style="background:linear-gradient(135deg, #667eea, #764ba2); padding:30px; border-radius:20px; text-align:center; margin-bottom:25px;">
                    <i class="fas fa-user-circle" style="font-size:60px; color:white;"></i>
                    <h2 style="color:white; margin-top:15px;">${gymnastData.full_name}</h2>
                    <p style="color:#c4b5fd;">Member ID: ${gymnastData.membership_id}</p>
                </div>
                <div style="display:grid; gap:12px;">
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-envelope"></i> Email:</strong> 
                        <span style="color:white;">${gymnastData.email}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-phone"></i> Contact:</strong> 
                        <span style="color:white;">${gymnastData.contact_no}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-calendar-alt"></i> DOB:</strong> 
                        <span style="color:white;">${gymnastData.date_of_birth}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-chalkboard-user"></i> Program:</strong> 
                        <span style="color:white;">${gymnastData.training_program}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-calendar-check"></i> Enrolled:</strong> 
                        <span style="color:white;">${gymnastData.enrollment_date}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-chart-line"></i> Status:</strong> 
                        <span style="color:white;">${gymnastData.progress_status}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:14px; background:rgba(255,255,255,0.05); border-radius:14px;">
                        <strong style="color:#a78bfa;"><i class="fas fa-clock"></i> Member Since:</strong> 
                        <span style="color:white;">${gymnastData.created_at}</span>
                    </div>
                </div>
                <div style="margin-top:20px; text-align:center; font-size:12px; color:#94a3b8; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
                    <p>Generated: ${new Date().toLocaleString()}</p>
                </div>
            `;
            modal.style.display = 'block';
        }
        
        function downloadProfileReport() {
            const div = document.createElement('div');
            div.style.padding = '30px';
            div.style.fontFamily = 'Inter, sans-serif';
            div.style.background = 'white';
            div.innerHTML = `
                <div style="background:linear-gradient(135deg,#667eea,#764ba2); padding:40px; border-radius:20px; color:white; text-align:center;">
                    <h1 style="margin:0;">Gymnastics Academy</h1>
                    <p style="margin-top:10px;">Profile Summary Report</p>
                </div>
                <div style="text-align:center; margin:30px 0;">
                    <h2>${gymnastData.full_name}</h2>
                    <p><strong>Member ID:</strong> ${gymnastData.membership_id}</p>
                </div>
                <div style="margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Email:</strong> ${gymnastData.email}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Contact Number:</strong> ${gymnastData.contact_no}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Date of Birth:</strong> ${gymnastData.date_of_birth}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Training Program:</strong> ${gymnastData.training_program}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Enrollment Date:</strong> ${gymnastData.enrollment_date}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Status:</strong> ${gymnastData.progress_status}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px;">
                        <strong>Member Since:</strong> ${gymnastData.created_at}
                    </div>
                </div>
                <div style="margin-top:30px; text-align:center; font-size:12px; color:#666;">
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <p>© Gymnastics Academy - Official Document</p>
                </div>
            `;
            html2pdf().set({
                margin: 0.5,
                filename: `Profile_${gymnastData.membership_id}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            }).from(div).save();
        }
        
        function viewEnrollmentSlip() {
            const confNum = 'ENR-' + new Date().getFullYear() + '-' + Math.random().toString(36).substring(2,10).toUpperCase();
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('viewModalContent');
            content.innerHTML = `
                <div style="text-align:center; margin-bottom:20px;">
                    <h2 style="color:#a78bfa; margin-bottom:5px;">Gymnastics Academy</h2>
                    <p style="color:#94a3b8;">Official Enrollment Confirmation</p>
                </div>
                <div style="background:rgba(255,255,255,0.05); padding:20px; border-radius:16px;">
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Confirmation Number:</strong> 
                        <span style="color:white;">${confNum}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Registration Timestamp:</strong> 
                        <span style="color:white;">${gymnastData.created_at}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Student Name:</strong> 
                        <span style="color:white;">${gymnastData.full_name}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Member ID:</strong> 
                        <span style="color:white;">${gymnastData.membership_id}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Course/Program:</strong> 
                        <span style="color:white;">${gymnastData.training_program} Program</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Enrollment Date:</strong> 
                        <span style="color:white;">${gymnastData.enrollment_date}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1);">
                        <strong style="color:#a78bfa;">Status:</strong> 
                        <span style="color:white;">${gymnastData.progress_status}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0;">
                        <strong style="color:#a78bfa;">Contact:</strong> 
                        <span style="color:white;">${gymnastData.contact_no}</span>
                    </div>
                </div>
                <div style="margin-top:20px; text-align:center; font-size:12px; color:#94a3b8;">
                    <p><i class="fas fa-check-circle" style="color:#34d399;"></i> This is an electronically generated confirmation slip.</p>
                    <p style="margin-top:5px;">Valid for official purposes within Gymnastics Academy.</p>
                </div>
            `;
            modal.style.display = 'block';
        }
        
        function downloadEnrollmentSlip() {
            const confNum = 'ENR-' + new Date().getFullYear() + '-' + Math.random().toString(36).substring(2,10).toUpperCase();
            const div = document.createElement('div');
            div.style.padding = '30px';
            div.style.fontFamily = 'Inter, sans-serif';
            div.style.background = 'white';
            div.innerHTML = `
                <div style="text-align:center; border-bottom:2px solid #667eea; padding-bottom:20px; margin-bottom:20px;">
                    <h1 style="color:#667eea; margin:0;">Gymnastics Academy</h1>
                    <p style="margin:5px 0 0 0;">Official Enrollment Confirmation Slip</p>
                </div>
                <div style="margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Confirmation Number:</strong> ${confNum}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Registration Timestamp:</strong> ${gymnastData.created_at}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Student Name:</strong> ${gymnastData.full_name}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Member ID:</strong> ${gymnastData.membership_id}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Course Summary:</strong> ${gymnastData.training_program} Training Program
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Enrollment Date:</strong> ${gymnastData.enrollment_date}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #e2e8f0;">
                        <strong>Status:</strong> ${gymnastData.progress_status}
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px;">
                        <strong>Contact:</strong> ${gymnastData.contact_no}
                    </div>
                </div>
                <div style="margin-top:30px; text-align:center; font-size:12px; color:#666;">
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <p>© Gymnastics Academy - Official Document</p>
                    <p style="margin-top:20px;">This confirms that the above-named student is officially enrolled in the Gymnastics Academy program.</p>
                </div>
            `;
            html2pdf().set({
                margin: 0.5,
                filename: `Enrollment_${gymnastData.membership_id}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            }).from(div).save();
        }
        <?php endif; ?>
        
        window.addEventListener('load', function() { 
            applyFilters(); 
            const bars = document.querySelectorAll('.progress-fill'); 
            bars.forEach(bar => { 
                const w = bar.style.width; 
                bar.style.width = '0%'; 
                setTimeout(() => { bar.style.width = w; }, 100); 
            });
        });
    </script>
</body>
</html>