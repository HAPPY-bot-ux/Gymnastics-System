<?php 
session_start(); 
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Access | Gymnastics Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B5CF6;
            --accent: #00F2FE;
            --success: #10b981;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-heavy: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.15);
            --text-main: #ffffff;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            perspective: 1000px;
        }

        .bg-mesh {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 30%, rgba(139, 92, 246, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(0, 242, 254, 0.15) 0%, transparent 40%);
            filter: blur(60px);
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            padding: 20px;
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            font-size: 48px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: inline-block;
        }

        .header h2 {
            font-weight: 800;
            font-size: 28px;
            letter-spacing: -1px;
            color: white;
        }

        .header p {
            color: var(--text-dim);
            font-size: 14px;
            margin-top: 8px;
        }

        .role-toggle {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid var(--glass-border);
        }

        .role-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text-dim);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border-radius: 12px;
            transition: 0.3s;
        }

        .role-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.5);
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            color: var(--text-dim);
            font-size: 16px;
        }

        .input-wrapper input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            padding: 15px 15px 15px 50px;
            border-radius: 16px;
            color: white;
            font-size: 15px;
            transition: 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
            box-shadow: 0 15px 30px -10px rgba(139, 92, 246, 0.5);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            text-align: center;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        /* Test Credentials Box */
        .test-credentials {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 16px;
            margin-top: 25px;
        }
        .test-credentials h4 {
            color: var(--success);
            font-size: 13px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .credential-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .credential-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            flex: 1;
            min-width: 180px;
        }
        .credential-item strong {
            color: var(--primary);
        }
        .credential-item span {
            color: #cbd5e1;
            font-family: monospace;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
        }

        .footer-links a {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Quick Fill Button */
        .quick-fill {
            margin-top: 15px;
            text-align: center;
        }
        .quick-fill button {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-dim);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .quick-fill button:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>
    <div class="login-container" id="parallaxCard">
        <div class="glass-card">
            <div class="header">
                <i class="fas fa-bolt brand-icon"></i>
                <h2>Elite Access</h2>
                <p>Gymnastics Academy Management System</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert">⚠️ <?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert-success" style="padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                    ✅ <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="role-toggle">
                <button type="button" class="role-btn active" onclick="setRole('admin', this)">
                    <i class="fas fa-user-shield"></i> Admin
                </button>
                <button type="button" class="role-btn" onclick="setRole('gymnast', this)">
                    <i class="fas fa-user-graduate"></i> Gymnast
                </button>
            </div>

            <form action="auth.php" method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="roleInput" value="admin">

                <div class="input-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <div class="input-wrapper">
                        <i class="far fa-user"></i>
                        <input type="text" name="username" id="username" placeholder="Enter your username" required autocomplete="username">
                    </div>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="loginBtn">
                    <i class="fas fa-arrow-right-to-bracket"></i> Access Dashboard
                </button>
            </form>

            <!-- Test Credentials for Lecturers -->
            <div class="test-credentials">
                <h4><i class="fas fa-flask"></i> Test Credentials</h4>
                <div class="credential-row">
                    <div class="credential-item">
                        <strong>👑 Admin:</strong><br>
                        <span>Username: admin</span><br>
                        <span>Password: admin123</span>
                    </div>
                    <div class="credential-item">
                        <strong>🤸 Gymnast:</strong><br>
                        <span>Username: gymnast1</span><br>
                        <span>Password: gymnast123</span>
                    </div>
                </div>
            </div>

            <div class="quick-fill">
                <button type="button" onclick="quickFill()">
                    <i class="fas fa-magic"></i> Quick Fill Demo Credentials
                </button>
            </div>

            <div class="footer-links">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        // Set role and update UI
        function setRole(role, btn) {
            document.getElementById('roleInput').value = role;
            document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Update quick fill based on role
            updateQuickFillForRole(role);
        }
        
        // Update quick fill credentials based on selected role
        function updateQuickFillForRole(role) {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            if (role === 'admin') {
                usernameField.placeholder = "Enter admin username (e.g., admin)";
            } else {
                usernameField.placeholder = "Enter gymnast username (e.g., gymnast1)";
            }
        }
        
        // Quick fill demo credentials
        function quickFill() {
            const role = document.getElementById('roleInput').value;
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            if (role === 'admin') {
                usernameField.value = 'admin';
                passwordField.value = 'admin123';
                showNotification('Admin credentials filled! Click Access Dashboard to login.', 'success');
            } else {
                usernameField.value = 'gymnast1';
                passwordField.value = 'gymnast123';
                showNotification('Gymnast credentials filled! Click Access Dashboard to login.', 'success');
            }
            
            // Highlight the fields briefly
            usernameField.style.borderColor = '#10b981';
            passwordField.style.borderColor = '#10b981';
            setTimeout(() => {
                usernameField.style.borderColor = '';
                passwordField.style.borderColor = '';
            }, 1500);
        }
        
        // Show temporary notification
        function showNotification(message, type) {
            const existingAlert = document.querySelector('.alert, .alert-success');
            if (existingAlert) existingAlert.remove();
            
            const notification = document.createElement('div');
            notification.className = type === 'success' ? 'alert-success' : 'alert';
            notification.style.cssText = 'padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center;';
            notification.innerHTML = type === 'success' ? `✅ ${message}` : `⚠️ ${message}`;
            
            const form = document.querySelector('form');
            form.parentNode.insertBefore(notification, form);
            
            setTimeout(() => notification.remove(), 3000);
        }
        
        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            btn.disabled = true;
        });
        
        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
        
        // 3D Parallax Effect
        const card = document.getElementById('parallaxCard');
        document.addEventListener('mousemove', (e) => {
            let xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            let yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });
        
        // Initialize
        updateQuickFillForRole('admin');
    </script>
</body>
</html>