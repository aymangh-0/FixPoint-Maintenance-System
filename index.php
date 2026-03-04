<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
    switch ($_SESSION['role_id']) {
        case 1: header('Location: admin/dashboard.php'); exit;
        case 2: header('Location: technician/dashboard.php'); exit;
        case 3:
        case 4: header('Location: user/dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FixPoint - University Maintenance Management System for Saudi Electronic University">
    <title>FixPoint - University Maintenance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #2563EB;
            --primary-dark: #1D4ED8;
            --green: #16A34A;
            --green-dark: #15803D;
            --dark: #0F172A;
            --dark-2: #1E293B;
            --gray: #64748B;
            --light: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--dark);
            background: var(--white);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        .container { max-width: 1140px; margin: 0 auto; padding: 0 24px; }

        /* ═══════════ HEADER ═══════════ */
        .header {
            padding: 14px 0;
            background: var(--dark);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 9px;
        }
        .logo-dot {
            width: 30px;
            height: 30px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-dot svg {
            width: 15px; height: 15px;
            stroke: #fff; fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }
        .logo-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
        }
        .logo-badge {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--primary);
            background: rgba(37,99,235,0.15);
            padding: 2px 7px;
            border-radius: 4px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .h-link {
            font-size: 0.825rem;
            font-weight: 500;
            color: rgba(255,255,255,0.6);
            padding: 7px 14px;
            border-radius: 7px;
            transition: all 0.2s;
        }
        .h-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .h-btn {
            font-size: 0.825rem;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            padding: 7px 18px;
            border-radius: 7px;
            transition: all 0.2s;
        }
        .h-btn:hover { background: var(--primary-dark); }

        /* ═══════════ HERO ═══════════ */
        .hero {
            background: var(--dark);
            padding: 64px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        /* Subtle grid pattern */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
        /* Glow behind card */
        .hero::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -40%);
            width: 500px;
            height: 400px;
            background: radial-gradient(circle, rgba(37,99,235,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 12px;
            letter-spacing: -0.03em;
        }
        .hero-sub {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.5);
            max-width: 460px;
            margin: 0 auto 48px;
        }

        /* ═══════════ THE CARD ═══════════ */
        .submit-card {
            max-width: 440px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 20px;
            padding: 40px 36px 34px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.08),
                0 20px 50px rgba(0,0,0,0.35),
                0 8px 20px rgba(0,0,0,0.2);
            text-align: center;
        }

        .submit-card-icon {
            width: 56px;
            height: 56px;
            background: #EFF6FF;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .submit-card-icon svg {
            width: 26px; height: 26px;
            stroke: var(--primary); fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        .submit-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .submit-card-desc {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 24px;
            line-height: 1.55;
        }

        .submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px 0;
            background: var(--green);
            color: #fff;
            font-family: inherit;
            font-size: 1.05rem;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(22,163,74,0.3);
        }
        .submit-btn svg {
            width: 20px; height: 20px;
            stroke: currentColor; fill: none;
            stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
            transition: transform 0.25s ease;
        }
        .submit-btn:hover {
            background: var(--green-dark);
            box-shadow: 0 6px 20px rgba(22,163,74,0.4);
            transform: translateY(-2px);
        }
        .submit-btn:hover svg {
            transform: translateX(3px);
        }

        .submit-card-hint {
            margin-top: 16px;
            font-size: 0.8rem;
            color: var(--gray);
        }
        .submit-card-hint a {
            color: var(--primary);
            font-weight: 600;
        }
        .submit-card-hint a:hover {
            text-decoration: underline;
        }

        /* ═══════════ FEATURES ═══════════ */
        .features {
            padding: 80px 0;
            background: var(--light);
        }
        .features-header {
            text-align: center;
            margin-bottom: 44px;
        }
        .features-header h2 {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        .features-header p {
            font-size: 0.95rem;
            color: var(--gray);
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .f-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 26px 22px;
            transition: all 0.25s ease;
        }
        .f-card:hover {
            border-color: transparent;
            box-shadow: 0 8px 28px rgba(0,0,0,0.06);
            transform: translateY(-3px);
        }
        .f-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }
        .f-icon svg {
            width: 20px; height: 20px;
            fill: none; stroke-width: 2;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .f-icon.blue    { background: #EFF6FF; } .f-icon.blue svg    { stroke: #2563EB; }
        .f-icon.emerald { background: #ECFDF5; } .f-icon.emerald svg { stroke: #10B981; }
        .f-icon.amber   { background: #FFFBEB; } .f-icon.amber svg   { stroke: #F59E0B; }
        .f-icon.violet  { background: #F5F3FF; } .f-icon.violet svg  { stroke: #8B5CF6; }
        .f-icon.cyan    { background: #ECFEFF; } .f-icon.cyan svg    { stroke: #06B6D4; }
        .f-icon.rose    { background: #FFF1F2; } .f-icon.rose svg    { stroke: #F43F5E; }

        .f-card h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        .f-card p {
            font-size: 0.84rem;
            color: var(--gray);
            line-height: 1.6;
        }

        /* ═══════════ CTA ═══════════ */
        .cta {
            background: var(--dark);
            padding: 64px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 500px; height: 300px;
            background: radial-gradient(circle, rgba(37,99,235,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .cta h2 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            position: relative;
            letter-spacing: -0.02em;
        }
        .cta p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.5);
            margin-bottom: 28px;
            position: relative;
        }
        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--green);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 13px 32px;
            border-radius: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(22,163,74,0.3);
            position: relative;
        }
        .cta-btn:hover {
            background: var(--green-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22,163,74,0.4);
        }

        /* ═══════════ FOOTER ═══════════ */
        .footer {
            background: #080E1A;
            padding: 44px 0 22px;
            color: rgba(255,255,255,0.5);
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
            gap: 28px;
            margin-bottom: 28px;
        }
        .footer-brand {
            font-size: 0.84rem;
            line-height: 1.7;
            margin-top: 10px;
        }
        .footer h4 {
            font-size: 0.7rem;
            font-weight: 700;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
        }
        .footer ul { list-style: none; }
        .footer ul li { margin-bottom: 7px; font-size: 0.84rem; }
        .footer ul a {
            color: rgba(255,255,255,0.45);
            transition: color 0.2s;
        }
        .footer ul a:hover { color: #fff; }
        .footer-line {
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            flex-wrap: wrap;
            gap: 6px;
        }

        /* ═══════════ RESPONSIVE ═══════════ */
        @media (max-width: 768px) {
            .hero { padding: 48px 0 64px; }
            .hero h1 { font-size: 2rem; }
            .hero-sub { font-size: 0.95rem; margin-bottom: 36px; }
            .submit-card { margin: 0 16px; padding: 32px 24px 28px; }
            .features-grid { grid-template-columns: 1fr; }
            .features-header h2 { font-size: 1.5rem; }
            .cta h2 { font-size: 1.5rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .h-link.desktop { display: none; }
        }
        @media (max-width: 480px) {
            .hero h1 { font-size: 1.65rem; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-line { flex-direction: column; text-align: center; }
        }

        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo" aria-label="FixPoint Home">
                <div class="logo-dot"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
                <span class="logo-name">FixPoint</span>
                <span class="logo-badge">SEU</span>
            </a>
            <nav class="header-right">
                <a href="#features" class="h-link desktop">Features</a>
                <a href="auth/login.php" class="h-link">Log in</a>
                <a href="auth/register.php" class="h-btn">Register</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>University Maintenance<br>Made Easy</h1>
                <p class="hero-sub">Report issues, track progress, and get problems fixed faster with FixPoint.</p>

                <div class="submit-card">
                    <div class="submit-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    <h2>Submit Your First Request</h2>
                    <p class="submit-card-desc">Something broken on campus? Report it in seconds and we'll handle the rest.</p>
                    <a href="auth/login.php" class="submit-btn">
                        Get Started
                        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <p class="submit-card-hint">New here? <a href="auth/register.php">Create an account</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features">
        <div class="container">
            <div class="features-header">
                <h2>Why Choose FixPoint?</h2>
                <p>A modern solution for university maintenance management</p>
            </div>
            <div class="features-grid">
                <div class="f-card">
                    <div class="f-icon blue"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></div>
                    <h3>Easy Reporting</h3>
                    <p>Submit maintenance requests in seconds with detailed descriptions and photos</p>
                </div>
                <div class="f-card">
                    <div class="f-icon emerald"><svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
                    <h3>Photo Upload</h3>
                    <p>Attach photos to help technicians understand the problem before arrival</p>
                </div>
                <div class="f-card">
                    <div class="f-icon amber"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                    <h3>Real-time Updates</h3>
                    <p>Get notified when your request is reviewed, assigned, and completed</p>
                </div>
                <div class="f-card">
                    <div class="f-icon violet"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <h3>Expert Technicians</h3>
                    <p>Your request is assigned to qualified maintenance staff automatically</p>
                </div>
                <div class="f-card">
                    <div class="f-icon cyan"><svg viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
                    <h3>Track Progress</h3>
                    <p>Monitor the status of all your requests from pending to completion</p>
                </div>
                <div class="f-card">
                    <div class="f-icon rose"><svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                    <h3>Fast Resolution</h3>
                    <p>Priority system ensures urgent issues get immediate attention</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join students and faculty using FixPoint to keep our campus in top condition</p>
            <a href="auth/register.php" class="cta-btn">Create Free Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="index.php" class="logo">
                        <div class="logo-dot"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
                        <span class="logo-name">FixPoint</span>
                    </a>
                    <p class="footer-brand">Making university maintenance simple, transparent, and efficient.</p>
                </div>
                <div>
                    <h4>Links</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h4>University</h4>
                    <ul>
                        <li>College of Computing & Informatics</li>
                        <li>Senior Project — 2026</li>
                    </ul>
                </div>
                <div>
                    <h4>Supervisor</h4>
                    <ul>
                        <li>Dr. Jameel Alhejely</li>
                        <li>Saudi Electronic University</li>
                    </ul>
                </div>
            </div>
            <div class="footer-line">
                <span>&copy; 2026 FixPoint — Saudi Electronic University</span>
                <span>Ayman, Al-Abbas, Omar, Yahya, Talal, Abdulaziz</span>
            </div>
        </div>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const t = document.querySelector(this.getAttribute('href'));
                if (t) window.scrollTo({ top: t.offsetTop - 60, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>