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

// Initialize variables with defaults
$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';

$error_message = '';
$success_message = '';
$generatedPassword = '';
$selected_role = $_GET['role'] ?? 'gymnast';

// Initialize POST variables
$full_name = '';
$email = '';
$contact_no = '';
$username = '';
$date_of_birth = '';
$training_program = '';
$enrollment_date = date('Y-m-d');
$admin_position = '';
$department = '';
$employee_id = '';

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
    $admin_position = trim($_POST['admin_position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    
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
                        <div class="success-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div style="background: white; border-radius: 32px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
                                <i class="fas fa-check-circle" style="font-size: 60px; color: #10b981; margin-bottom: 20px;"></i>
                                <h2 style="color: #0f172a; margin-bottom: 20px;">Registration Successful!</h2>
                                <div style="background: #f8fafc; border-radius: 20px; padding: 20px; margin: 20px 0; text-align: left;">
                                    <p style="color: #4f46e5; margin-bottom: 10px; font-weight: 600;"><i class="fas fa-user-circle"></i> Account Details:</p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Password:</strong> <code style="background: #e2e8f0; padding: 4px 8px; border-radius: 8px;"><?php echo htmlspecialchars($password); ?></code></p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Membership ID:</strong> <?php echo htmlspecialchars($membership_id); ?></p>
                                </div>
                                <div style="display: flex; gap: 12px; justify-content: center;">
                                    <button onclick="window.location.href='dashboard.php'" style="padding: 12px 24px; background: #4f46e5; border: none; border-radius: 12px; color: white; cursor: pointer; font-weight: 600;">Go to Dashboard</button>
                                    <button onclick="this.closest('.success-modal').style.display='none'; window.location.reload();" style="padding: 12px 24px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; color: #1e293b; cursor: pointer; font-weight: 600;">Register Another</button>
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
                // Check if admins table exists
                $check_table_sql = "SHOW TABLES LIKE 'admins'";
                $table_check = $db->executeQuery($check_table_sql);
                if (!$table_check || $table_check->get_result()->num_rows == 0) {
                    $create_table_sql = "CREATE TABLE IF NOT EXISTS admins (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        full_name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        contact_no VARCHAR(50) NOT NULL,
                        position VARCHAR(100) NOT NULL,
                        department VARCHAR(100) NOT NULL,
                        employee_id VARCHAR(50) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    $db->executeQuery($create_table_sql);
                }
                
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
                        <div class="success-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div style="background: white; border-radius: 32px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
                                <i class="fas fa-check-circle" style="font-size: 60px; color: #10b981; margin-bottom: 20px;"></i>
                                <h2 style="color: #0f172a; margin-bottom: 20px;">Admin Registration Successful!</h2>
                                <div style="background: #f8fafc; border-radius: 20px; padding: 20px; margin: 20px 0; text-align: left;">
                                    <p style="color: #4f46e5; margin-bottom: 10px; font-weight: 600;"><i class="fas fa-user-tie"></i> Account Details:</p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Password:</strong> <code style="background: #e2e8f0; padding: 4px 8px; border-radius: 8px;"><?php echo htmlspecialchars($password); ?></code></p>
                                    <p style="color: #1e293b; margin: 8px 0;"><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee_id); ?></p>
                                </div>
                                <div style="display: flex; gap: 12px; justify-content: center;">
                                    <button onclick="window.location.href='dashboard.php'" style="padding: 12px 24px; background: #4f46e5; border: none; border-radius: 12px; color: white; cursor: pointer; font-weight: 600;">Go to Dashboard</button>
                                    <button onclick="this.closest('.success-modal').style.display='none'; window.location.reload();" style="padding: 12px 24px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; color: #1e293b; cursor: pointer; font-weight: 600;">Register Another</button>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Create Account | Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Glass Card */
        .glass-card {
            background: white;
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.05);
            border: 1px solid #eef2ff;
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header-icon {
            font-size: 56px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: inline-block;
        }

        .header h2 {
            font-weight: 700;
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        /* Role Toggle */
        .role-toggle {
            display: flex;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 60px;
            margin-bottom: 30px;
            gap: 6px;
        }

        .role-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border-radius: 50px;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .role-btn i {
            margin-right: 8px;
        }

        .role-btn.active {
            background: white;
            color: #4f46e5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            color: #475569;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: #1e293b;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Alert */
        .alert {
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 14px;
            margin-bottom: 25px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            margin-top: 25px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
        }

        /* Info Text */
        .info-text {
            margin-top: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 14px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .info-text i {
            color: #4f46e5;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .glass-card {
                padding: 25px;
            }
            .app-nav {
                padding: 0 1rem;
            }
            .role-btn {
                padding: 10px 16px;
                font-size: 12px;
            }
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
        <div class="glass-card">
            <div class="header">
                <i class="fas fa-user-plus header-icon"></i>
                <h2>Create New Account</h2>
                <p>Register gymnasts or administrators to the system</p>
            </div>

            <?php if ($error_message && empty($success_message)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="role-toggle">
                <a href="?role=gymnast" class="role-btn <?php echo $selected_role === 'gymnast' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Gymnast Registration
                </a>            
            </div>

            <form action="" method="POST">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($selected_role); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" placeholder="Enter full name" required value="<?php echo htmlspecialchars($full_name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Choose username" required value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="your@email.com" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_no" placeholder="+1234567890" required value="<?php echo htmlspecialchars($contact_no); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                </div>

                <?php if ($selected_role === 'gymnast'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($date_of_birth); ?>">
                    </div>
                    <div class="form-group">
                        <label>Training Program <span class="required">*</span></label>
                        <select name="training_program" required>
                            <option value="">Select Program</option>
                            <option value="Beginner" <?php echo $training_program === 'Beginner' ? 'selected' : ''; ?>>🥇 Beginner</option>
                            <option value="Intermediate" <?php echo $training_program === 'Intermediate' ? 'selected' : ''; ?>>🥈 Intermediate</option>
                            <option value="Advanced" <?php echo $training_program === 'Advanced' ? 'selected' : ''; ?>>🥉 Advanced</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Enrollment Date</label>
                    <input type="date" name="enrollment_date" value="<?php echo htmlspecialchars($enrollment_date); ?>">
                </div>
                <?php endif; ?>

                <?php if ($selected_role === 'admin'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Position <span class="required">*</span></label>
                        <input type="text" name="admin_position" placeholder="e.g., Manager, Director" required value="<?php echo htmlspecialchars($admin_position); ?>">
                    </div>
                    <div class="form-group">
                        <label>Department <span class="required">*</span></label>
                        <input type="text" name="department" placeholder="e.g., Operations, Management" required value="<?php echo htmlspecialchars($department); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Employee ID <span class="required">*</span></label>
                    <input type="text" name="employee_id" placeholder="Enter employee ID" required value="<?php echo htmlspecialchars($employee_id); ?>">
                </div>
                <?php endif; ?>

                <div class="info-text">
                    <i class="fas fa-info-circle"></i> 
                    All fields marked with <span style="color: #ef4444;">*</span> are required. Password must be at least 6 characters for security.
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i> Register Account
                </button>
            </form>
        </div>
    </div>
</body>
</html>