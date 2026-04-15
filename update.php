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
    } else {
        $dob = new DateTime($data['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        if ($age < 4 || $age > 100) {
            $errors[] = "Age must be between 4 and 100 years";
        }
    }
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Gymnast - Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B5CF6;
            --accent: #00F2FE;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark-bg: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: var(--dark-bg);
            min-height: 100vh;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
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
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 10px;
            transition: 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 20px;
            border-radius: 50px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(0, 242, 254, 0.1));
            padding: 30px;
            border-bottom: 1px solid var(--border);
        }
        
        .card-header h1 {
            color: white;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card-header p {
            color: #94a3b8;
            margin-top: 8px;
        }
        
        .membership-badge {
            display: inline-block;
            background: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            color: var(--primary);
            margin-top: 12px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: #94a3b8;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group label .required {
            color: var(--danger);
            margin-left: 4px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .form-group input::placeholder {
            color: #4a5568;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #4ade80;
        }
        
        .alert ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        /* Buttons */
        .btn-update {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.4);
        }
        
        .btn-cancel {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Status Badge Preview */
        .status-preview {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            text-align: center;
        }
        
        .status-preview span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .card-header h1 {
                font-size: 22px;
            }
            
            .card-body {
                padding: 25px;
            }
        }
        
        /* Loading State */
        .btn-update.loading {
            pointer-events: none;
            opacity: 0.7;
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
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 6px; font-size: 10px;">ADMIN</span>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-card">
            <div class="card-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    Update Gymnast Information
                </h1>
                <p>Edit the details for the selected gymnast</p>
                <div class="membership-badge">
                    <i class="fas fa-id-card"></i> Membership ID: <?php echo htmlspecialchars($gymnast['membership_id']); ?>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
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
                        <i class="fas fa-check-circle"></i>
                        <span>Gymnast information updated successfully! Redirecting...</span>
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
                                   value="<?php echo $gymnast['date_of_birth']; ?>">
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
                                   value="<?php echo $gymnast['enrollment_date']; ?>">
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
    
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .status-onhold {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .status-completed {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
    </style>
    
    <script>
        // Form submission loading state
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('updateBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        });
        
        // Real-time validation for contact number
        const contactInput = document.querySelector('input[name="contact_no"]');
        contactInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
        });
        
        // Real-time validation for full name (only letters, spaces, hyphens, apostrophes)
        const nameInput = document.querySelector('input[name="full_name"]');
        nameInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\s'-]/g, '');
        });
        
        // Show warning when changing status
        const statusSelect = document.querySelector('select[name="progress_status"]');
        statusSelect.addEventListener('change', function() {
            if (this.value === 'Completed') {
                if (confirm('⚠️ Changing status to "Completed" means this gymnast has graduated. Are you sure?')) {
                    // Proceed
                } else {
                    this.value = '<?php echo $gymnast['progress_status']; ?>';
                }
            } else if (this.value === 'On Hold') {
                if (confirm('⏸️ This will pause the gymnast\'s training. Continue?')) {
                    // Proceed
                } else {
                    this.value = '<?php echo $gymnast['progress_status']; ?>';
                }
            }
        });
        
        // Calculate age and show warning if under 4 or over 100
        const dobInput = document.querySelector('input[name="date_of_birth"]');
        dobInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 4) {
                alert('⚠️ Warning: Gymnast is under 4 years old. Minimum age requirement is 4 years.');
            } else if (age > 100) {
                alert('⚠️ Warning: Age seems too high. Please verify the date of birth.');
            }
        });
    </script>
</body>
</html>