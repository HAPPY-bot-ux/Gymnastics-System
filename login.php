<?php 
session_start(); 
require_once 'config/db.php';

$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);
$selected_role = $_GET['role'] ?? 'admin';
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
            --bg-dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-dim: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* --- PROFESSIONAL LOADING OVERLAY --- */
        #loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('home.jpg');
            z-index: 10000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .loader-content {
            text-align: center;
            width: 100%;
            max-width: 320px;
        }

        .loader-logo {
            font-size: 45px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
            animation: loader-pulse 2s infinite;
        }

        .progress-container {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid var(--glass-border);
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .status-text {
            color: white;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 8px;
            height: 20px;
        }

        .sub-status {
            color: var(--text-dim);
            font-size: 11px;
            font-weight: 400;
        }

        @keyframes loader-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }

        /* --- ORIGINAL FORM DESIGN --- */
        .bg-blobs {
            position: fixed;
            width: 100vw; height: 100vh;
            z-index: -1;
            filter: blur(80px);
        }

        .blob {
            position: absolute; border-radius: 50%; opacity: 0.4;
            animation: move 20s infinite alternate;
        }

        .blob-1 { width: 400px; height: 400px; background: var(--primary); top: -10%; left: -10%; }
        .blob-2 { width: 350px; height: 350px; background: var(--accent); bottom: -10%; right: -5%; animation-delay: -5s; }

        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(50px, 50px) scale(1.1); }
        }

        .login-container {
            width: 100%; max-width: 440px; padding: 20px;
            animation: fadeIn 0.8s ease-out;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.4);
        }

        .brand { text-align: center; margin-bottom: 35px; }
        .brand-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            color: white; font-size: 28px;
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }

        .brand h2 { color: white; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .brand p { color: var(--text-dim); font-size: 14px; margin-top: 5px; }

        .role-switch {
            display: flex; background: rgba(0,0,0,0.3);
            padding: 5px; border-radius: 14px; margin-bottom: 25px;
        }

        .role-option {
            flex: 1; padding: 10px; border: none; background: transparent;
            color: var(--text-dim); font-weight: 600; font-size: 13px;
            cursor: pointer; border-radius: 10px; transition: all 0.3s;
        }

        .role-option.active { background: var(--primary); color: white; }

        .input-box { margin-bottom: 20px; }
        .input-box label { 
            display: block; color: var(--text-dim); 
            font-size: 11px; font-weight: 700; 
            text-transform: uppercase; letter-spacing: 1.5px;
            margin: 0 0 8px 5px;
        }

        .field { position: relative; display: flex; align-items: center; }
        .field i { position: absolute; left: 15px; color: var(--text-dim); }
        .field input {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            padding: 14px 15px 14px 45px; border-radius: 14px;
            color: white; transition: 0.3s;
        }

        .field input:focus {
            outline: none; border-color: var(--primary);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        .btn-glow {
            width: 100%; padding: 16px; border-radius: 14px; border: none;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white; font-weight: 700; cursor: pointer;
            margin-top: 10px; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }

        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }

        .alert {
            padding: 12px; border-radius: 12px; font-size: 13px;
            margin-bottom: 20px; text-align: center;
        }

        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; }

        .helper-box {
            margin-top: 30px; padding-top: 20px;
            border-top: 1px solid var(--glass-border);
        }

        .helper-title {
            font-size: 11px; color: var(--text-dim);
            text-align: center; margin-bottom: 15px;
            text-transform: uppercase; letter-spacing: 1px;
        }

        .cred-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .cred-pill {
            background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);
            padding: 10px; border-radius: 12px; font-size: 11px;
            cursor: pointer; transition: 0.2s;
        }

        .cred-pill:hover { background: rgba(255,255,255,0.08); border-color: var(--accent); }
        .cred-pill strong { display: block; color: var(--accent); margin-bottom: 2px; }
        .cred-pill span { color: #cbd5e1; font-family: monospace; font-size: 10px; }

        .footer-links { text-align: center; margin-top: 25px; }
        .footer-links a { color: var(--text-dim); text-decoration: none; font-size: 13px; }
        .footer-links a:hover { color: white; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div id="loading-overlay">
        <div class="loader-content">
            <div class="loader-logo"><i class="fas fa-medal"></i></div>
            <div class="status-text" id="status-msg">Initiating...</div>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <div class="sub-status" id="sub-status">Secure Academy Gateway</div>
        </div>
    </div>

    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="login-container">
        <div class="glass-card">
            <div class="brand">
                <div class="brand-logo"><i class="fas fa-medal"></i></div>
                <h2>Elite Access</h2>
                <p>Academy Management Portal</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="role-switch">
                <button class="role-option <?php echo $selected_role === 'admin' ? 'active' : ''; ?>" onclick="setRole('admin', this)">
                    <i class="fas fa-user-shield"></i> Admin
                </button>
                <button class="role-option <?php echo $selected_role === 'gymnast' ? 'active' : ''; ?>" onclick="setRole('gymnast', this)">
                    <i class="fas fa-user-graduate"></i> Gymnast
                </button>
            </div>

            <form action="auth.php" method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="roleInput" value="<?php echo $selected_role; ?>">
                
                <div class="input-box">
                    <label><i class="fas fa-user-circle"></i> Username</label>
                    <div class="field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" placeholder="Enter username" required autocomplete="username">
                    </div>
                </div>

                <div class="input-box">
                    <label><i class="fas fa-shield-alt"></i> Password</label>
                    <div class="field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-glow" id="loginBtn">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    <span>Access Dashboard</span>
                </button>
            </form>

            <div class="helper-box">
                <div class="helper-title"><i class="fas fa-flask"></i> Testing Credentials</div>
                <div class="cred-grid">
                    <div class="cred-pill" onclick="fillCredentials('admin', 'admin', 'admin123')">
                        <strong>👑 Admin Access</strong>
                        <span>Username: admin</span>
                        <span>Password: admin123</span>
                    </div>
                    <div class="cred-pill" onclick="fillCredentials('gymnast', 'kbw', '12345678')">
                        <strong>🤸 Gymnast Access</strong>
                        <span>Username: kbw</span>
                        <span>Password: 12345678</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-links">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        let isSubmitting = false;
        
        function setRole(role, btn) {
            document.getElementById('roleInput').value = role;
            document.querySelectorAll('.role-option').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        function fillCredentials(role, username, password) {
            const btns = document.querySelectorAll('.role-option');
            setRole(role, role === 'admin' ? btns[0] : btns[1]);
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Visual feedback
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            usernameField.style.borderColor = '#10b981';
            passwordField.style.borderColor = '#10b981';
            setTimeout(() => {
                usernameField.style.borderColor = '';
                passwordField.style.borderColor = '';
            }, 1500);
        }
        
        function showLoadingScreen() {
            const overlay = document.getElementById('loading-overlay');
            const bar = document.getElementById('progress-bar');
            const msg = document.getElementById('status-msg');
            const sub = document.getElementById('sub-status');
            const role = document.getElementById('roleInput').value;

            // Reset progress bar
            bar.style.width = '0%';
            
            // Update messages based on role
            if (role === 'admin') {
                msg.innerText = 'Admin Access';
                sub.innerText = 'Loading management dashboard...';
            } else {
                msg.innerText = 'Gymnast Portal';
                sub.innerText = 'Loading personal dashboard...';
            }
            
            overlay.style.display = 'flex';
            
            // Animated sequence
            const sequence = [
                { p: '25%', m: role === 'admin' ? 'Admin Access' : 'Gymnast Portal', s: 'Authenticating credentials...' },
                { p: '55%', m: 'Secure Connection', s: 'Establishing encrypted session...' },
                { p: '85%', m: 'Loading Profile', s: 'Fetching user data...' },
                { p: '100%', m: 'Welcome Back!', s: 'Redirecting to dashboard...' }
            ];
            
            let step = 0;
            const interval = setInterval(() => {
                if (step < sequence.length) {
                    bar.style.width = sequence[step].p;
                    msg.innerText = sequence[step].m;
                    sub.innerText = sequence[step].s;
                    step++;
                } else {
                    clearInterval(interval);
                    // Submit the form after loading animation completes
                    setTimeout(() => {
                        document.getElementById('loginForm').submit();
                    }, 300);
                }
            }, 700);
        }
        
        // Handle form submission - only show loading AFTER validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default submission
            
            if (isSubmitting) return;
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Validate fields are not empty
            if (!username || !password) {
                showNotification('Please enter both username and password', 'error');
                return;
            }
            
            // Check if we should show loading (will be determined by AJAX)
            // Show loading and submit via AJAX to verify credentials first
            isSubmitting = true;
            
            // Create form data for AJAX validation
            const formData = new FormData(this);
            
            // First validate credentials via AJAX
            fetch('validate_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Credentials are correct, show loading screen then submit
                    showLoadingScreen();
                } else {
                    // Credentials are wrong, show error without loading screen
                    showNotification(data.message || 'Invalid username or password', 'error');
                    isSubmitting = false;
                    
                    // Shake animation for error feedback
                    const form = document.querySelector('.glass-card');
                    form.style.animation = 'shake 0.5s ease';
                    setTimeout(() => {
                        form.style.animation = '';
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Connection error. Please try again.', 'error');
                isSubmitting = false;
            });
        });
        
        function showNotification(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-error, .alert-success');
            existingAlerts.forEach(alert => alert.remove());
            
            const notification = document.createElement('div');
            notification.className = type === 'success' ? 'alert alert-success' : 'alert alert-error';
            notification.innerHTML = type === 'success' ? `<i class="fas fa-check-circle"></i> ${message}` : `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            notification.style.animation = 'slideIn 0.3s ease';
            
            const form = document.querySelector('form');
            const roleSwitch = document.querySelector('.role-switch');
            roleSwitch.parentNode.insertBefore(notification, roleSwitch.nextSibling);
            
            setTimeout(() => notification.remove(), 4000);
        }
        
        // Add shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-fade alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-error, .alert-success');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Enter key support
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('loginBtn').click();
            }
        });
    </script>
</body>
</html>