<?php
session_start();

// If logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: dashboard/" . $_SESSION['role'] . ".php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Peer Tutoring System - IFM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ===== RESET ===== */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary-blue: #002B7F;
      --secondary-blue: #0044AA;
      --accent-yellow: #FDB913;
      --white: #ffffff;
      --light-gray: #E6E6E6;
      --dark-overlay: rgba(0, 0, 0, 0.3);
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      min-height: 100vh;
      color: var(--white);
      overflow-x: hidden;
      position: relative;
    }

    /* Animated Background Elements */
    body::before,
    body::after {
      content: '';
      position: fixed;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      background: rgba(253, 185, 19, 0.1);
      filter: blur(80px);
      z-index: 0;
      animation: float 20s ease-in-out infinite;
    }

    body::before {
      top: -200px;
      left: -200px;
      animation-delay: 0s;
    }

    body::after {
      bottom: -200px;
      right: -200px;
      animation-delay: 10s;
    }

    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(50px, 50px) scale(1.1); }
    }

    /* Navigation */
    nav {
      position: fixed;
      top: 0;
      width: 100%;
      padding: 20px 50px;
      z-index: 1000;
      background: rgba(0, 43, 127, 0.8);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }

    nav.scrolled {
      padding: 15px 50px;
      background: rgba(0, 43, 127, 0.95);
    }

    .nav-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--white);
      text-decoration: none;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo img {
      height: 40px;
      width: auto;
      object-fit: contain;
    }

    /* Logo Section */
    .logo-section {
      margin-bottom: 30px;
      animation: fadeInUp 1s ease-out 0.1s both;
    }

    .logo-section img {
      max-width: 200px;
      width: 100%;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
      transition: transform 0.3s ease;
    }

    .logo-section img:hover {
      transform: scale(1.05);
    }

    @media (max-width: 768px) {
      .logo-section img {
        max-width: 150px;
      }
      
      .logo img {
        height: 35px;
      }
    }

    @media (max-width: 480px) {
      .logo-section img {
        max-width: 120px;
      }
      
      .logo img {
        height: 30px;
      }
    }

    .nav-buttons {
      display: flex;
      gap: 15px;
    }

    .nav-btn {
      padding: 10px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .nav-btn.login {
      background: transparent;
      color: var(--white);
      border: 2px solid var(--white);
    }

    .nav-btn.login:hover {
      background: var(--white);
      color: var(--primary-blue);
      transform: translateY(-2px);
    }

    .nav-btn.register {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: 2px solid var(--accent-yellow);
    }

    .nav-btn.register:hover {
      background: var(--white);
      border-color: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(253, 185, 19, 0.4);
    }

    /* Hero Section */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 120px 20px 80px;
      position: relative;
      z-index: 1;
    }

    .hero-content {
      max-width: 1200px;
      width: 100%;
      text-align: center;
      animation: fadeInUp 1s ease-out;
    }

    .hero h1 {
      font-size: clamp(2.5rem, 6vw, 4.5rem);
      font-weight: 800;
      margin-bottom: 20px;
      line-height: 1.2;
      background: linear-gradient(135deg, var(--white) 0%, var(--light-gray) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: fadeInUp 1s ease-out 0.2s both;
    }

    .tagline {
      font-size: clamp(1.1rem, 2.5vw, 1.5rem);
      color: var(--light-gray);
      margin-bottom: 15px;
      font-weight: 300;
      animation: fadeInUp 1s ease-out 0.4s both;
    }

    .subtagline {
      font-size: clamp(0.9rem, 2vw, 1.1rem);
      color: rgba(230, 230, 230, 0.8);
      margin-bottom: 40px;
      font-weight: 400;
      animation: fadeInUp 1s ease-out 0.6s both;
    }

    .hero-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 60px;
      animation: fadeInUp 1s ease-out 0.8s both;
    }

    .btn {
      padding: 16px 40px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      display: inline-block;
      position: relative;
      overflow: hidden;
    }

    .btn-primary {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      box-shadow: 0 5px 20px rgba(253, 185, 19, 0.4);
    }

    .btn-primary:hover {
      background: var(--white);
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
    }

    .btn-secondary {
      background: transparent;
      color: var(--white);
      border: 2px solid var(--white);
    }

    .btn-secondary:hover {
      background: var(--white);
      color: var(--primary-blue);
      transform: translateY(-3px);
    }

    /* Features Section */
    .features {
      padding: 100px 20px;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .section-title {
      text-align: center;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--white);
    }

    .section-subtitle {
      text-align: center;
      font-size: 1.2rem;
      color: var(--light-gray);
      margin-bottom: 60px;
      font-weight: 300;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
      margin-top: 50px;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 40px 30px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.5s ease;
    }

    .feature-card:hover::before {
      left: 100%;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }

    .feature-icon {
      font-size: 3.5rem;
      margin-bottom: 20px;
      display: block;
    }

    .feature-card h3 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--white);
    }

    .feature-card p {
      font-size: 1rem;
      color: var(--light-gray);
      line-height: 1.6;
      font-weight: 300;
    }

    /* Stats Section */
    .stats {
      padding: 80px 20px;
      background: rgba(0, 0, 0, 0.2);
      position: relative;
      z-index: 1;
    }

    .stats-grid {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 40px;
      text-align: center;
    }

    .stat-item {
      padding: 20px;
    }

    .stat-number {
      font-size: 3rem;
      font-weight: 800;
      color: var(--accent-yellow);
      margin-bottom: 10px;
      display: block;
    }

    .stat-label {
      font-size: 1.1rem;
      color: var(--light-gray);
      font-weight: 400;
    }

    /* Footer */
    footer {
      padding: 50px 20px 30px;
      text-align: center;
      background: rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 1;
    }

    footer p {
      font-size: 0.95rem;
      color: var(--light-gray);
      margin-bottom: 10px;
    }

    footer a {
      color: var(--accent-yellow);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    footer a:hover {
      text-decoration: underline;
      color: var(--white);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      nav {
        padding: 15px 20px;
      }

      .nav-content {
        flex-direction: column;
        gap: 15px;
      }

      .nav-buttons {
        width: 100%;
        justify-content: center;
      }

      .nav-btn {
        flex: 1;
        text-align: center;
        max-width: 150px;
      }

      .hero {
        padding: 100px 20px 60px;
      }

      .hero-buttons {
        flex-direction: column;
        align-items: center;
      }

      .btn {
        width: 100%;
        max-width: 300px;
      }

      .features {
        padding: 60px 20px;
      }

      .features-grid {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }
    }

    @media (max-width: 480px) {
      .nav-btn {
        padding: 8px 16px;
        font-size: 0.85rem;
      }

      .stat-number {
        font-size: 2rem;
      }

      .feature-card {
        padding: 30px 20px;
      }
    }

    /* Scroll Indicator */
    .scroll-indicator {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      animation: bounce 2s infinite;
      z-index: 10;
    }

    .scroll-indicator::before {
      content: '↓';
      font-size: 2rem;
      color: var(--white);
      opacity: 0.7;
    }

    @keyframes bounce {
      0%, 100% {
        transform: translateX(-50%) translateY(0);
      }
      50% {
        transform: translateX(-50%) translateY(10px);
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav id="navbar">
    <div class="nav-content">
      <a href="#" class="logo">
        <img src="images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='images/ifm.svg'; this.onerror=function(){this.style.display='none'; this.nextElementSibling.style.display='inline';};};">
        <span style="display: none;">IFM Peer Tutoring</span>
      </a>
      <div class="nav-buttons">
        <a href="auth/login.php" class="nav-btn login">Login</a>
        <a href="auth/register.php" class="nav-btn register">Get Started</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <div class="logo-section">
        <img src="images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='images/ifm.svg';};">
      </div>
      <h1>IFM Peer Tutoring System</h1>
      <p class="tagline">"Empowering Students to Learn, Connect, and Excel Together"</p>
      <p class="subtagline">Connect with peer tutors, schedule sessions, and enhance your learning experience</p>
      <div class="hero-buttons">
        <a href="auth/register.php" class="btn btn-primary">Get Started Free</a>
        <a href="auth/login.php" class="btn btn-secondary">Sign In</a>
      </div>
    </div>
    <div class="scroll-indicator"></div>
  </section>

  <!-- Features Section -->
  <section class="features" id="features">
    <h2 class="section-title">Why Choose Our Platform?</h2>
    <p class="section-subtitle">Everything you need to succeed in your academic journey</p>
    <div class="features-grid">
      <div class="feature-card">
        <span class="feature-icon">👥</span>
        <h3>Peer-to-Peer Learning</h3>
        <p>Connect with fellow students who understand your challenges and can help you succeed in your courses.</p>
      </div>
      <div class="feature-card">
        <span class="feature-icon">📅</span>
        <h3>Easy Scheduling</h3>
        <p>Book tutoring sessions at your convenience with our intuitive scheduling system that works around your timetable.</p>
      </div>
      <div class="feature-card">
        <span class="feature-icon">⭐</span>
        <h3>Quality Feedback</h3>
        <p>Rate and review sessions to help maintain high-quality tutoring standards and help others find the best tutors.</p>
      </div>
      <div class="feature-card">
        <span class="feature-icon">🎯</span>
        <h3>Subject Specialization</h3>
        <p>Find tutors specialized in specific subjects to get targeted help exactly where you need it most.</p>
      </div>
      <div class="feature-card">
        <span class="feature-icon">🔔</span>
        <h3>Real-time Notifications</h3>
        <p>Stay updated with instant notifications about session requests, confirmations, and important updates.</p>
      </div>
      <div class="feature-card">
        <span class="feature-icon">📊</span>
        <h3>Track Your Progress</h3>
        <p>Monitor your learning journey with detailed session history and feedback to see your improvement over time.</p>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats">
    <div class="stats-grid">
      <div class="stat-item">
        <span class="stat-number">500+</span>
        <span class="stat-label">Active Students</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">100+</span>
        <span class="stat-label">Expert Tutors</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">1000+</span>
        <span class="stat-label">Sessions Completed</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">4.8★</span>
        <span class="stat-label">Average Rating</span>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>Institute of Finance Management © 2025</p>
    <p>Empowering education through peer collaboration</p>
  </footer>

  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
      const navbar = document.getElementById('navbar');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Animate stats on scroll
    const observerOptions = {
      threshold: 0.5,
      rootMargin: '0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'fadeInUp 0.8s ease-out';
        }
      });
    }, observerOptions);

    document.querySelectorAll('.feature-card, .stat-item').forEach(el => {
      observer.observe(el);
    });
  </script>
</body>
</html>
