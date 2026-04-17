<?php 
session_start();
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
$selected_role = $_GET['role'] ?? 'gymnast';
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

        .footer-links {
            text-align: center;
            margin-top: 25px;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: white;
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
        <div class="glass-card">
            <div class="header">
                <i class="fas fa-user-plus brand-icon"></i>
                <h2>Create Account</h2>
                <p>Join Gymnastics Academy Management System</p>
            </div>

            <?php if ($error_message): ?>
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

            <form action="auth.php" method="POST">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_no" placeholder="+1234567890" required>
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
                        <input type="date" name="date_of_birth" required>
                    </div>
                    <div class="form-group">
                        <label>Training Program <span class="required">*</span></label>
                        <select name="training_program" required>
                            <option value="">Select Program</option>
                            <option value="Beginner">🥇 Beginner</option>
                            <option value="Intermediate">🥈 Intermediate</option>
                            <option value="Advanced">🥉 Advanced</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Enrollment Date</label>
                    <input type="date" name="enrollment_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <?php endif; ?>

                <?php if ($selected_role === 'admin'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Position <span class="required">*</span></label>
                        <input type="text" name="admin_position" placeholder="e.g., Manager, Director" required>
                    </div>
                    <div class="form-group">
                        <label>Department <span class="required">*</span></label>
                        <input type="text" name="department" placeholder="e.g., Operations, Management" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Employee ID <span class="required">*</span></label>
                    <input type="text" name="employee_id" placeholder="Enter employee ID" required>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i> Register Account
                </button>
            </form>

            <div class="footer-links">
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>