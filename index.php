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
  <title>Peer Tutoring System - IFM</title>
  <style>
    /* ===== RESET ===== */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #002B7F, #0044AA);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }

    .container {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(14px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      padding: 55px 45px;
      text-align: center;
      width: 90%;
      max-width: 420px;
      animation: fadeIn 0.8s ease-in-out;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 10px;
      color: #fff;
      letter-spacing: 0.5px;
    }

    .tagline {
      font-size: 1rem;
      color: #E6E6E6;
      margin-bottom: 30px;
      font-weight: 300;
    }

    .buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .btn {
      background: #FDB913;
      color: #002B7F;
      text-decoration: none;
      padding: 12px 28px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      box-shadow: 0 3px 10px rgba(253, 185, 19, 0.3);
    }

    .btn:hover {
      background: #fff;
      color: #002B7F;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(255, 255, 255, 0.25);
    }

    footer {
      margin-top: 30px;
      font-size: 0.85rem;
      color: #f0f0f0;
      opacity: 0.85;
    }

    footer a {
      color: #FDB913;
      text-decoration: none;
      font-weight: 500;
    }

    footer a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .btn {
        padding: 10px 22px;
      }
      .container {
        padding: 40px 30px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>IFM Peer Tutoring System</h1>
    <p class="tagline">"Empowering Students to Learn, Connect, and Excel Together"</p>
    <div class="buttons">
      <a href="auth/register.php" class="btn">Register</a>
      <a href="auth/login.php" class="btn">Login</a>
    </div>
    <footer>
      <p>Institute of Finance Management © 2025 | <a href="#">Learn More</a></p>
    </footer>
  </div>
</body>
</html>
