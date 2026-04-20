<?php
session_start();
// Mock data
$totalGymnasts = 1248;
$activeSessions = 12;
$growthRate = "+14.5%";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Gymnastics | Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-blue: #1e293b;
            --brand-accent: #2563eb;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8fafc;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* High-Impact Hero Background */
        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 450px;
            background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), 
                        url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
            z-index: -1;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%);
        }

        /* Navigation */
        header {
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            width: 100%;
        }

        .welcome-section {
            color: var(--white);
            margin-bottom: 60px;
            padding-top: 20px;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: -80px; /* Pull cards into the hero section */
        }

        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: #eff6ff;
            color: var(--brand-accent);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--brand-blue);
        }

        .stat-info p {
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Portal Section */
        .portal-grid {
            display: grid;
            background: url('home.jpg');
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }

        .action-box {
            background: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--brand-accent);
            color: white;
        }

        .btn-outline {
            border: 2px solid #e2e8f0;
            color: var(--brand-blue);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Small Feature Cards */
        .features {
            display: grid;
            gap: 15px;
        }

        .feature-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .feature-item i { color: var(--brand-accent); }

        @media (max-width: 768px) {
            .portal-grid { grid-template-columns: 1fr; }
            .btn-group { flex-direction: column; }
            .welcome-section h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <div class="hero-bg"></div>

    <header>
        <div class="logo">
            <i class="fas fa-medal"></i>
            <span>ELITE GYMNASTICS</span>
        </div>
        <div class="status">
            <small><i class="fas fa-circle" style="color: #10b981; font-size: 8px;"></i> System Live</small>
        </div>
    </header>

    <div class="container">
        <section class="welcome-section">
            <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; opacity: 0.8;">Academy Management Portal</p>
            <h1>Global Dashboard</h1>
            <p style="max-width: 600px; opacity: 0.9;">Manage student progression, billing, and training schedules through our unified executive command center.</p>
        </section>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($totalGymnasts); ?></h3>
                    <p>Total Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $activeSessions; ?></h3>
                    <p>Active Sessions</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3 style="color: #10b981;"><?php echo $growthRate; ?></h3>
                    <p>Growth Velocity</p>
                </div>
            </div>
        </div>

        <div class="portal-grid">
            <div class="action-box">
                <h2 style="margin-bottom: 10px;">System Access</h2>
                <p style="color: var(--text-light);">Select your entry point to continue to the secure management environment.</p>
                
                <div class="btn-group">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-shield-check"></i> Administrator Login
                    </a>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> Gymnast Portal
                    </a>
                </div>

                <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 30px;">
                    <p style="font-size: 0.85rem; color: var(--text-light); font-weight: 600; margin-bottom: 15px;">CORE MODULES</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="font-size: 0.9rem;"><i class="fas fa-check-circle" style="color: #10b981;"></i> Automated Invoicing</div>
                        <div style="font-size: 0.9rem;"><i class="fas fa-check-circle" style="color: #10b981;"></i> Skill Tracking</div>
                        <div style="font-size: 0.9rem;"><i class="fas fa-check-circle" style="color: #10b981;"></i> Event Scheduler</div>
                        <div style="font-size: 0.9rem;"><i class="fas fa-check-circle" style="color: #10b981;"></i> Parent Messaging</div>
                    </div>
                </div>
            </div>

            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <div>
                        <strong style="display:block">Financial Control</strong>
                        <span style="color: var(--text-light)">Review pending tuitions and generate monthly reports.</span>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-trophy"></i>
                    <div>
                        <strong style="display:block">Achievement Engine</strong>
                        <span style="color: var(--text-light)">Automated notifications for gymnast level-ups.</span>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong style="display:block">Meet Management</strong>
                        <span style="color: var(--text-light)">Coordinate regional competitions and registration.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer style="margin-top: auto; padding: 40px; text-align: center; color: var(--text-light); font-size: 0.85rem;">
        &copy; 2024 Elite Gymnastics Sports Academy. All Rights Reserved.
    </footer>

</body>
</html>