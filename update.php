<?php
session_start();
require_once 'auth.php';
require_once 'includes/GymnastManager.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

$gymnastManager = new GymnastManager();

// Initialize variables with defaults
$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';

// Get gymnast ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error'] = "Invalid gymnast ID";
    header('Location: dashboard.php');
    exit();
}

$gymnast = $gymnastManager->getGymnastById($id);

if (!$gymnast) {
    $_SESSION['error'] = "Gymnast not found";
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set the updated_by variable for the trigger
    $db = Database::getInstance();
    $connection = $db->getConnection();
    $username = $_SESSION['username'] ?? 'system';
    $connection->query("SET @updated_by = '" . $connection->real_escape_string($username) . "'");
    
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'contact_no' => trim($_POST['contact_no'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'training_program' => $_POST['training_program'] ?? '',
        'enrollment_date' => $_POST['enrollment_date'] ?? '',
        'progress_status' => $_POST['progress_status'] ?? 'Active'
    ];
    
    // Validation
    if (empty($data['full_name'])) {
        $errors[] = "Full name is required";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $data['full_name'])) {
        $errors[] = "Full name should contain only letters, spaces, apostrophes, and hyphens";
    }
    
    if (empty($data['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($data['contact_no'])) {
        $errors[] = "Contact number is required";
    } elseif (!preg_match("/^[0-9+\-\s()]{10,20}$/", $data['contact_no'])) {
        $errors[] = "Contact number should be valid (10-20 digits)";
    }
    
    if (empty($data['date_of_birth'])) {
        $errors[] = "Date of birth is required";
    }
    // Removed age validation to allow any date
    
    if (empty($data['training_program'])) {
        $errors[] = "Training program is required";
    }
    
    if (empty($data['enrollment_date'])) {
        $errors[] = "Enrollment date is required";
    }
    
    if (empty($errors)) {
        $sql = "UPDATE gymnasts SET 
                full_name = ?, 
                email = ?, 
                contact_no = ?, 
                date_of_birth = ?, 
                training_program = ?, 
                enrollment_date = ?, 
                progress_status = ? 
                WHERE id = ?";
        
        $stmt = $db->executeQuery($sql, "sssssssi", [
            $data['full_name'],
            $data['email'],
            $data['contact_no'],
            $data['date_of_birth'],
            $data['training_program'],
            $data['enrollment_date'],
            $data['progress_status'],
            $id
        ]);
        
        if ($stmt && $stmt->affected_rows >= 0) {
            $success = true;
            $_SESSION['success'] = "Gymnast information updated successfully!";
            // Refresh gymnast data
            $gymnast = $gymnastManager->getGymnastById($id);
        } else {
            $errors[] = "Failed to update gymnast information.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Update Gymnast - Gymnastics Academy</title>
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

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.05);
            border: 1px solid #eef2ff;
        }

        .card-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 2rem;
            color: white;
        }

        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .membership-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 12px;
        }

        .card-body {
            padding: 2rem;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: #1e293b;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        /* Allow manual date input */
        .form-group input[type="date"] {
            cursor: text;
            position: relative;
        }
        
        .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .form-group input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #e6f7ec;
            border: 1px solid #b2dfc2;
            color: #0e6b3e;
        }

        .alert ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .alert li {
            margin: 4px 0;
        }

        /* Buttons */
        .btn-update {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
        }

        .btn-cancel {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            width: 100%;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #1e293b;
            transform: translateY(-2px);
        }

        /* Status Badge Preview */
        .status-preview {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-active {
            background: #e6f7ec;
            color: #0e6b3e;
        }

        .status-onhold {
            background: #fffbeb;
            color: #b45309;
        }

        .status-completed {
            background: #eef2ff;
            color: #3730a3;
        }

        /* Loading State */
        .btn-update.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        /* Helper text */
        .helper-text {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 6px;
            display: block;
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
            .card-header h1 {
                font-size: 1.4rem;
            }
            .card-body {
                padding: 1.5rem;
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
    <div class="form-card">
        <div class="card-header">
            <h1>
                <i class="fas fa-edit"></i>
                Update Gymnast
            </h1>
            <p>Edit athlete information and track progress</p>
            <div class="membership-badge">
                <i class="fas fa-id-card"></i> Membership ID: <?php echo htmlspecialchars($gymnast['membership_id']); ?>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                    <span>Gymnast information updated successfully! Redirecting to dashboard...</span>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                </script>
            <?php endif; ?>
            
            <form method="POST" action="" id="updateForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" required 
                               value="<?php echo htmlspecialchars($gymnast['full_name']); ?>"
                               placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" required 
                               value="<?php echo htmlspecialchars($gymnast['email']); ?>"
                               placeholder="email@example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_no" required 
                               value="<?php echo htmlspecialchars($gymnast['contact_no']); ?>"
                               placeholder="+1234567890">
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" required 
                               value="<?php echo $gymnast['date_of_birth']; ?>"
                               placeholder="YYYY-MM-DD">
                        <small class="helper-text"><i class="fas fa-info-circle"></i> You can type the date manually or use the calendar picker (YYYY-MM-DD format)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Training Program <span class="required">*</span></label>
                        <select name="training_program" required>
                            <option value="Beginner" <?php echo $gymnast['training_program'] == 'Beginner' ? 'selected' : ''; ?>>
                                🥇 Beginner
                            </option>
                            <option value="Intermediate" <?php echo $gymnast['training_program'] == 'Intermediate' ? 'selected' : ''; ?>>
                                🥈 Intermediate
                            </option>
                            <option value="Advanced" <?php echo $gymnast['training_program'] == 'Advanced' ? 'selected' : ''; ?>>
                                🥉 Advanced
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Enrollment Date <span class="required">*</span></label>
                        <input type="date" name="enrollment_date" required 
                               value="<?php echo $gymnast['enrollment_date']; ?>"
                               placeholder="YYYY-MM-DD">
                        <small class="helper-text"><i class="fas fa-info-circle"></i> You can type the date manually or use the calendar picker (YYYY-MM-DD format)</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Progress Status</label>
                    <select name="progress_status">
                        <option value="Active" <?php echo $gymnast['progress_status'] == 'Active' ? 'selected' : ''; ?>>
                            ✅ Active - Currently training
                        </option>
                        <option value="On Hold" <?php echo $gymnast['progress_status'] == 'On Hold' ? 'selected' : ''; ?>>
                            ⏸️ On Hold - Temporarily paused
                        </option>
                        <option value="Completed" <?php echo $gymnast['progress_status'] == 'Completed' ? 'selected' : ''; ?>>
                            🏆 Completed - Graduated
                        </option>
                    </select>
                </div>
                
                <div class="status-preview">
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $gymnast['progress_status'])); ?>">
                        <i class="fas <?php echo $gymnast['progress_status'] == 'Active' ? 'fa-check-circle' : ($gymnast['progress_status'] == 'On Hold' ? 'fa-pause-circle' : 'fa-check-double'); ?>"></i>
                        Current Status: <?php echo $gymnast['progress_status']; ?>
                    </span>
                </div>
                
                <button type="submit" class="btn-update" id="updateBtn">
                    <i class="fas fa-save"></i> Update Information
                </button>
            </form>
            
            <a href="dashboard.php" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Cancel & Return to Dashboard
            </a>
        </div>
    </div>
</div>

<script>
    // Form submission loading state
    document.getElementById('updateForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('updateBtn');
        btn.classList.add('loading');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    });
    
    // Real-time validation for contact number
    const contactInput = document.querySelector('input[name="contact_no"]');
    if (contactInput) {
        contactInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
        });
    }
    
    // Real-time validation for full name (only letters, spaces, hyphens, apostrophes)
    const nameInput = document.querySelector('input[name="full_name"]');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\s'-]/g, '');
        });
    }
    
    // Allow manual date entry without restrictions
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Remove any readonly or disabled attributes that might block input
        input.removeAttribute('readonly');
        input.removeAttribute('disabled');
        
        // Allow manual typing
        input.addEventListener('keydown', function(e) {
            // Allow typing numbers, hyphens, and navigation keys
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
            if (allowedKeys.includes(e.key)) {
                return;
            }
            // Allow digits and hyphen
            if (!/[\d\-]/.test(e.key) && e.key.length === 1) {
                e.preventDefault();
            }
        });
        
        // Auto-format as user types (add hyphens)
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length >= 4) {
                value = value.slice(0, 4) + '-' + value.slice(4);
            }
            if (value.length >= 7) {
                value = value.slice(0, 7) + '-' + value.slice(7, 9);
            }
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            // Only update if the value is changing and user is typing
            if (this.value !== value && !this.value.includes('-')) {
                this.value = value;
            }
        });
    });
    
    // Show warning when changing status (informational only, no blocking)
    const statusSelect = document.querySelector('select[name="progress_status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            if (this.value === 'Completed') {
                if (!confirm('ℹ️ Changing status to "Completed" means this gymnast has graduated. Continue?')) {
                    this.value = '<?php echo $gymnast['progress_status']; ?>';
                }
            } else if (this.value === 'On Hold') {
                if (!confirm('ℹ️ This will pause the gymnast\'s training. Continue?')) {
                    this.value = '<?php echo $gymnast['progress_status']; ?>';
                }
            }
        });
    }
    
    // Optional: Display age info without blocking submission
    const dobInput = document.querySelector('input[name="date_of_birth"]');
    if (dobInput) {
        dobInput.addEventListener('change', function() {
            if (this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                if (age < 4) {
                    // Just show info, don't block submission
                    console.log('Age warning: Gymnast is under 4 years old');
                } else if (age > 100) {
                    console.log('Age warning: Age seems high');
                }
            }
        });
    }
</script>
</body>
</html>