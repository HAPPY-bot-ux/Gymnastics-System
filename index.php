<?php
session_start();
// Mock data for the "Advanced" feel
$totalGymnasts = 1248;
$activeSessions = 12;
$growthRate = "+14.5%";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Gymnastics | Executive Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B5CF6;
            --primary-glow: rgba(139, 92, 246, 0.5);
            --accent: #00F2FE;
            --bg-dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow: hidden; /* Dashboard feel */
            height: 100vh;
            display: flex;
        }

        /* Animated Mesh Background */
        .mesh-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(at 0% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 242, 254, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(59, 130, 246, 0.15) 0px, transparent 50%);
            filter: blur(80px);
        }

        /* Sidebar Navigation */
        nav {
            width: 280px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            gap: 40px;
            z-index: 10;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, var(--text-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-logo i {
            -webkit-text-fill-color: var(--primary);
            font-size: 28px;
            filter: drop-shadow(0 0 10px var(--primary-glow));
        }

        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-links a {
            color: var(--text-dim);
            text-decoration: none;
            padding: 14px 18px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .nav-links a:hover, .nav-links a.active {
            background: var(--glass);
            color: #fff;
            transform: translateX(5px);
            box-shadow: inset 0 0 10px rgba(255,255,255,0.02);
        }

        /* Main Content Area */
        main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
        }

        .welcome-text h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: var(--text-dim);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto;
            gap: 25px;
            animation: fadeInUp 1s ease-out;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: 0.5s;
        }

        .glass-card:hover::before {
            left: 100%;
        }

        .glass-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255,255,255,0.2);
            background: rgba(255, 255, 255, 0.05);
        }

        /* Specific Component Styles */
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .stat-val {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-dim);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .action-card {
            grid-column: span 3;
            display: flex;
            justify-content: space-around;
            padding: 40px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(0, 242, 254, 0.1));
        }

        .btn-premium {
            padding: 16px 32px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
            position: relative;
        }

        .btn-admin { 
            background: #fff; 
            color: #000; 
        }
        .btn-gymnast { 
            background: var(--glass); 
            color: #fff; 
            border: 1px solid var(--glass-border);
        }

        .btn-premium:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Custom Scrollbar */
        main::-webkit-scrollbar { width: 6px; }
        main::-webkit-scrollbar-track { background: transparent; }
        main::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 10px; }

    </style>
</head>
<body>

    <div class="mesh-gradient"></div>

    <nav>
        <div class="nav-logo">
            <i class="fas fa-bolt"></i>
            <span>ELITE GYMNASTICS</span>
        </div>
        
       

        <div style="margin-top: auto;">
            <div class="glass-card" style="padding: 15px; text-align: center;">
                <p style="font-size: 12px; color: var(--text-dim);">System Status</p>
                <p style="font-size: 14px; color: #10b981;"><i class="fas fa-circle" style="font-size: 8px;"></i> Operational</p>
            </div>
        </div>
    </nav>

    <main>
        <div class="header-top">
            <div class="welcome-text">
                <h1>Command Center</h1>
                <p>Welcome back, Administrator. Here's what's happening today.</p>
            </div>
            <div class="user-profile" style="display: flex; align-items: center; gap: 15px;">
                <div style="text-align: right;">
                    <p style="font-weight: 600;">System Guest</p>
                    <p style="font-size: 12px; color: var(--text-dim);">Internal Network</p>
                </div>
                <div class="glass-card" style="padding: 10px; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="glass-card stat-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.2); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-val"><?php echo number_format($totalGymnasts); ?></div>
                <div class="stat-label">Active Gymnasts</div>
            </div>

            <div class="glass-card stat-card">
                <div class="icon" style="background: rgba(0, 242, 254, 0.2); color: var(--accent);">
                    <i class="fas fa-running"></i>
                </div>
                <div class="stat-val"><?php echo $activeSessions; ?></div>
                <div class="stat-label">Live Sessions</div>
            </div>

            <div class="glass-card stat-card">
                <div class="icon" style="background: rgba(236, 72, 153, 0.2); color: #ec4899;">
                    <i class="fas fa-trending-up"></i>
                </div>
                <div class="stat-val"><?php echo $growthRate; ?></div>
                <div class="stat-label">Monthly Growth</div>
            </div>

            <div class="glass-card action-card">
                <div style="text-align: center;">
                    <h3 style="margin-bottom: 20px;">System Entry Points</h3>
                    <div style="display: flex; gap: 20px;">
                        <a href="login.php?role=admin" class="btn-premium btn-admin">
                            <i class="fas fa-shield-halved"></i> Admin Portal
                        </a>
                        <a href="login.php?role=gymnast" class="btn-premium btn-gymnast">
                            <i class="fas fa-user-graduate"></i> Gymnast Access
                        </a>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <i class="fas fa-file-invoice-dollar" style="font-size: 24px; color: var(--primary); margin-bottom: 15px;"></i>
                <h4>Financial Tracking</h4>
                <p style="color: var(--text-dim); font-size: 14px; margin-top: 10px;">Automated tuition billing and membership renewals with PDF invoicing.</p>
            </div>

            <div class="glass-card">
                <i class="fas fa-medal" style="font-size: 24px; color: #fbbf24; margin-bottom: 15px;"></i>
                <h4>Skill Assessment</h4>
                <p style="color: var(--text-dim); font-size: 14px; margin-top: 10px;">Track level advancement, move-up requirements, and skill mastery.</p>
            </div>

            <div class="glass-card">
                <i class="fas fa-calendar-check" style="font-size: 24px; color: var(--accent); margin-bottom: 15px;"></i>
                <h4>Event Management</h4>
                <p style="color: var(--text-dim); font-size: 14px; margin-top: 10px;">Schedule meets, workshops, and intensive training camps.</p>
            </div>
        </div>
    </main>

    <script>
        // Subtle Mouse Move Effect for the Mesh Gradient
        document.addEventListener('mousemove', (e) => {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            document.querySelector('.mesh-gradient').style.transform = 
                `translate(${x * 20}px, ${y * 20}px) scale(1.1)`;
        });
    </script>
</body>
</html>