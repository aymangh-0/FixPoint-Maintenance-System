<?php

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FixPoint - University Maintenance Management System for Saudi Electronic University">
    <title>FixPoint - University Maintenance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header / Navigation -->
    <header class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">
                    <span class="logo-icon">🔧</span>
                    <span class="logo-text">FixPoint</span>
                    <span class="logo-subtitle">SEU</span>
                </div>
                <nav class="nav-links">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#how-it-works" class="nav-link">How It Works</a>
                    <a href="auth/login.php" class="btn btn-outline">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">University Maintenance Made Easy</h1>
                <p class="hero-subtitle">Report issues, track progress, and get problems fixed faster with FixPoint</p>
                <div class="hero-buttons">
                    <a href="auth/register.php" class="btn btn-primary btn-large">Submit Your First Request</a>
                    <a href="#how-it-works" class="btn btn-secondary btn-large">How It Works</a>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-number">⚡</div>
                        <div class="stat-label">Quick Response</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">📊</div>
                        <div class="stat-label">Track Status</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Get Fixed</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose FixPoint?</h2>
            <p class="section-subtitle">A modern solution for university maintenance management</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3 class="feature-title">Easy Reporting</h3>
                    <p class="feature-description">Submit maintenance requests in seconds with detailed descriptions and photos</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📸</div>
                    <h3 class="feature-title">Photo Upload</h3>
                    <p class="feature-description">Attach photos to help technicians understand the problem before arrival</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3 class="feature-title">Real-time Updates</h3>
                    <p class="feature-description">Get notified when your request is reviewed, assigned, and completed</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👨‍🔧</div>
                    <h3 class="feature-title">Expert Technicians</h3>
                    <p class="feature-description">Your request is assigned to qualified maintenance staff automatically</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Track Progress</h3>
                    <p class="feature-description">Monitor the status of all your requests from pending to completion</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">Fast Resolution</h3>
                    <p class="feature-description">Priority system ensures urgent issues get immediate attention</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Get your maintenance issues resolved in 4 simple steps</p>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3 class="step-title">Create Account</h3>
                        <p class="step-description">Register with your university email in less than a minute</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3 class="step-title">Submit Request</h3>
                        <p class="step-description">Report the issue with location, description, and photos</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3 class="step-title">Get Assigned</h3>
                        <p class="step-description">Admin reviews and assigns to the right technician</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3 class="step-title">Problem Solved</h3>
                        <p class="step-description">Technician fixes the issue and marks it as complete</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Get Started?</h2>
                <p class="cta-description">Join hundreds of students and faculty using FixPoint to keep our campus in top condition</p>
                <a href="auth/register.php" class="btn btn-primary btn-large">Create Free Account</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4 class="footer-title">FixPoint</h4>
                    <p class="footer-text">Making university maintenance simple, transparent, and efficient.</p>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Support</h4>
                    <ul class="footer-links">
                        <li><a href="help-center.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Saudi Electronic University</h4>
                    <p class="footer-text">College of Computing & Informatics<br>Senior Project - 2026</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 FixPoint - Saudi Electronic University. All rights reserved.</p>
                <p class="footer-team">Developed by: Ayman, Al-Abbas, Omar, Yahya, Talal, Abdulaziz</p>
            </div>
        </div>
    </footer>
</body>
</html>