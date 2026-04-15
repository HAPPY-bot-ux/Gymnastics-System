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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$gymnast_id = intval($_GET['id']);
$gymnast = $gymnastManager->getGymnastById($gymnast_id);

if (!$gymnast) {
    header('Location: dashboard.php');
    exit();
}

// Check if user has permission to view this profile
if (!$auth->isAdmin() && $_SESSION['gymnast_id'] != $gymnast_id) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gymnast Profile - Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .profile-avatar i {
            font-size: 60px;
            color: white;
        }
        
        .card-header h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .membership-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            color: #374151;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-section h3 i {
            color: #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 15px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-onhold {
            background: #fed7aa;
            color: #92400e;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .program-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .program-beginner { background: #d1fae5; color: #065f46; }
        .program-intermediate { background: #fed7aa; color: #92400e; }
        .program-advanced { background: #fee2e2; color: #991b1b; }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 30px 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-card" id="profileContent">
            <div class="card-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2><?php echo htmlspecialchars($gymnast['full_name']); ?></h2>
                <div class="membership-badge">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($gymnast['membership_id']); ?>
                </div>
            </div>
            
            <div class="card-body">
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($gymnast['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Contact Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($gymnast['contact_no']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Date of Birth</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($gymnast['date_of_birth'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-graduation-cap"></i> Training Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-dumbbell"></i> Training Program</div>
                            <div class="info-value">
                                <span class="program-badge program-<?php echo strtolower($gymnast['training_program']); ?>">
                                    <?php echo htmlspecialchars($gymnast['training_program']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-check"></i> Enrollment Date</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($gymnast['enrollment_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-chart-line"></i> Progress Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $gymnast['progress_status'])); ?>">
                                    <i class="fas <?php echo $gymnast['progress_status'] == 'Active' ? 'fa-check-circle' : ($gymnast['progress_status'] == 'On Hold' ? 'fa-pause-circle' : 'fa-check-double'); ?>"></i>
                                    <?php echo $gymnast['progress_status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-clock"></i> Member Since</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($gymnast['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button onclick="downloadProfilePDF()" class="btn btn-success">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                    <?php if ($auth->isAdmin()): ?>
                    <a href="update.php?id=<?php echo $gymnast['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function downloadProfilePDF() {
            const element = document.getElementById('profileContent');
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: 'gymnast_profile_<?php echo $gymnast['membership_id']; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, letterRendering: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>