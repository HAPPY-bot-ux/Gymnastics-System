<?php 
session_start();
require_once 'auth.php';
require_once 'includes/GymnastManager.php';

$auth = new Auth();

// Only admins can access this page
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';
$generatedPassword = '';
$selected_role = $_GET['role'] ?? 'gymnast';

// Function to generate random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'gymnast';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $training_program = $_POST['training_program'] ?? '';
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
    
    // Admin specific fields
    $admin_position = $_POST['admin_position'] ?? '';
    $department = $_POST['department'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    
    // Validation
    $errors = [];
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($contact_no)) $errors[] = "Contact number is required";
    if (empty($username) || strlen($username) < 3) $errors[] = "Username must be at least 3 characters";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    // Role-specific validation
    if ($role === 'gymnast') {
        if (empty($date_of_birth)) $errors[] = "Date of birth is required";
        if (empty($training_program)) $errors[] = "Training program is required";
    } elseif ($role === 'admin') {
        if (empty($admin_position)) $errors[] = "Admin position is required";
        if (empty($department)) $errors[] = "Department is required";
        if (empty($employee_id)) $errors[] = "Employee ID is required";
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        
        // Check if username exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $db->executeQuery($check_sql, "s", [$username]);
        if ($check_stmt && $check_stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists. Please try a different username.";
        }
        
        // Check if email exists
        $check_email_sql = "SELECT id FROM users WHERE email = ?";
        $check_email_stmt = $db->executeQuery($check_email_sql, "s", [$email]);
        if ($check_email_stmt && $check_email_stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        $db->getConnection()->begin_transaction();
        
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === 'gymnast') {
                // Generate membership ID
                $year = date('Y');
                $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $membership_id = "GYM{$year}{$random}";
                
                // Insert gymnast
                $sql = "INSERT INTO gymnasts (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')";
                $stmt = $db->executeQuery($sql, "sssssss", [
                    $membership_id, $full_name, $email, $contact_no,
                    $date_of_birth, $training_program, $enrollment_date
                ]);
                
                if ($stmt && $stmt->affected_rows > 0) {
                    $gymnast_id = $stmt->insert_id;
                    
                    // Insert user account
                    $user_sql = "INSERT INTO users (username, password, role, email, full_name, contact_no, gymnast_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $user_stmt = $db->executeQuery($user_sql, "sssssss", [
                        $username, $hashed_password, 'gymnast', $email, $full_name, $contact_no, $gymnast_id
                    ]);
                    
                    if ($user_stmt && $user_stmt->affected_rows > 0) {
                        $db->getConnection()->commit();
                        $success_message = "Gymnast registered successfully!";
                        ?>
                        <div class="success-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div style="background: linear-gradient(135deg, #1e1b4b, #1a1a3e); border-radius: 28px; padding: 40px; max-width: 500px; text-align: center; border: 1px solid rgba(102,126,234,0.3);">
                                <i class="fas fa-check-circle" style="font-size: 60px; color: #34d399; margin-bottom: 20px;"></i>
                                <h2 style="color: white; margin-bottom: 20px;">Registration Successful!</h2>
                                <div style="background: rgba(0,0,0,0.3); border-radius: 16px; padding: 20px; margin: 20px 0; text-align: left;">
                                    <p style="color: #a78bfa; margin-bottom: 10px;"><strong>📋 Account Details:</strong></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Password:</strong> <code style="background: rgba(0,0,0,0.5); padding: 4px 8px; border-radius: 6px;"><?php echo $password; ?></code></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Membership ID:</strong> <?php echo $membership_id; ?></p>
                                </div>
                                <div style="display: flex; gap: 12px; justify-content: center;">
                                    <button onclick="window.location.href='dashboard.php'" class="btn-primary" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 12px; color: white; cursor: pointer;">Go to Dashboard</button>
                                    <button onclick="this.parentElement.parentElement.parentElement.style.display='none'; window.location.reload();" class="btn-secondary" style="padding: 12px 24px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; cursor: pointer;">Register Another</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        throw new Exception("Failed to create user account");
                    }
                } else {
                    throw new Exception("Failed to register gymnast");
                }
            } 
            elseif ($role === 'admin') {
                // Insert into admins table
                $admin_sql = "INSERT INTO admins (full_name, email, contact_no, position, department, employee_id) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $admin_stmt = $db->executeQuery($admin_sql, "ssssss", [
                    $full_name, $email, $contact_no, $admin_position, $department, $employee_id
                ]);
                
                if ($admin_stmt && $admin_stmt->affected_rows > 0) {
                    $admin_id = $admin_stmt->insert_id;
                    
                    // Insert user account
                    $user_sql = "INSERT INTO users (username, password, role, email, full_name, contact_no, admin_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $user_stmt = $db->executeQuery($user_sql, "sssssss", [
                        $username, $hashed_password, 'admin', $email, $full_name, $contact_no, $admin_id
                    ]);
                    
                    if ($user_stmt && $user_stmt->affected_rows > 0) {
                        $db->getConnection()->commit();
                        $success_message = "Admin registered successfully!";
                        ?>
                        <div class="success-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div style="background: linear-gradient(135deg, #1e1b4b, #1a1a3e); border-radius: 28px; padding: 40px; max-width: 500px; text-align: center; border: 1px solid rgba(102,126,234,0.3);">
                                <i class="fas fa-check-circle" style="font-size: 60px; color: #34d399; margin-bottom: 20px;"></i>
                                <h2 style="color: white; margin-bottom: 20px;">Admin Registration Successful!</h2>
                                <div style="background: rgba(0,0,0,0.3); border-radius: 16px; padding: 20px; margin: 20px 0; text-align: left;">
                                    <p style="color: #a78bfa; margin-bottom: 10px;"><strong>📋 Account Details:</strong></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Password:</strong> <code style="background: rgba(0,0,0,0.5); padding: 4px 8px; border-radius: 6px;"><?php echo $password; ?></code></p>
                                    <p style="color: white; margin: 8px 0;"><strong>Employee ID:</strong> <?php echo $employee_id; ?></p>
                                </div>
                                <div style="display: flex; gap: 12px; justify-content: center;">
                                    <button onclick="window.location.href='dashboard.php'" class="btn-primary" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 12px; color: white; cursor: pointer;">Go to Dashboard</button>
                                    <button onclick="this.parentElement.parentElement.parentElement.style.display='none'; window.location.reload();" class="btn-secondary" style="padding: 12px 24px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; cursor: pointer;">Register Another</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        throw new Exception("Failed to create admin user account");
                    }
                } else {
                    throw new Exception("Failed to create admin profile");
                }
            }
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $error_message = "Registration failed: " . $e->getMessage();
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            font-size: 48px;
            background: linear-gradient(135deg, #8B5CF6, #00F2FE);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: inline-block;
        }

        .header h2 {
            font-weight: 800;
            font-size: 28px;
            color: white;
            margin-bottom: 10px;
        }

        .header p {
            color: #94a3b8;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #a78bfa;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: white;
        }

        .role-toggle {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .role-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border-radius: 12px;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .role-btn.active {
            background: #8B5CF6;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.5);
        }

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
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .alert {
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            margin-bottom: 20px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            text-align: center;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, #8B5CF6, #7c3aed);
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .info-text {
            margin-top: 20px;
            padding: 15px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 14px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        .info-text i {
            color: #8B5CF6;
            margin-right: 8px;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .glass-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="glass-card">
            <div class="header">
                <i class="fas fa-user-plus brand-icon"></i>
                <h2>Create Account</h2>
                <p>Join Gymnastics Academy Management System</p>
            </div>

            <?php if ($error_message && !$success_message): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="role-toggle">
                <a href="?role=gymnast" class="role-btn <?php echo $selected_role === 'gymnast' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Register as Gymnast
                </a>
                <a href="?role=admin" class="role-btn <?php echo $selected_role === 'admin' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i> Register as Admin
                </a>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Choose a username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_no" placeholder="+1234567890" required value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>

                <?php if ($selected_role === 'gymnast'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" required value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Training Program <span class="required">*</span></label>
                        <select name="training_program" required>
                            <option value="">Select Program</option>
                            <option value="Beginner" <?php echo (isset($_POST['training_program']) && $_POST['training_program'] == 'Beginner') ? 'selected' : ''; ?>>🥇 Beginner</option>
                            <option value="Intermediate" <?php echo (isset($_POST['training_program']) && $_POST['training_program'] == 'Intermediate') ? 'selected' : ''; ?>>🥈 Intermediate</option>
                            <option value="Advanced" <?php echo (isset($_POST['training_program']) && $_POST['training_program'] == 'Advanced') ? 'selected' : ''; ?>>🥉 Advanced</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Enrollment Date</label>
                    <input type="date" name="enrollment_date" value="<?php echo isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : date('Y-m-d'); ?>">
                </div>
                <?php endif; ?>

                <?php if ($selected_role === 'admin'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Position <span class="required">*</span></label>
                        <input type="text" name="admin_position" placeholder="e.g., Manager, Director" required value="<?php echo isset($_POST['admin_position']) ? htmlspecialchars($_POST['admin_position']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Department <span class="required">*</span></label>
                        <input type="text" name="department" placeholder="e.g., Operations, Management" required value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Employee ID <span class="required">*</span></label>
                    <input type="text" name="employee_id" placeholder="Enter employee ID" required value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>">
                </div>
                <?php endif; ?>

                <div class="info-text">
                    <i class="fas fa-info-circle"></i> 
                    All fields marked with <span class="required">*</span> are required. Password must be at least 6 characters.
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i> Register Account
                </button>
            </form>
        </div>
    </div>
</body>
</html>