<?php
session_start();
if (!isset($_SESSION['roles'])) { 
    header("Location: login.php"); 
    exit; 
}

$roles = $_SESSION['roles'];

// Redirect immediately if user has only one role
if (count($roles) === 1) {
    $_SESSION['role'] = $roles[0];

    // Handle "Remember me" cookie
    if (!empty($_SESSION['remember']) && isset($_SESSION['user_id'])) {
        $token = hash('sha256', $_SESSION['user_id'] . $roles[0] . 'SECRET_KEY');
        setcookie(
            "remember_user",
            $_SESSION['user_id'] . "|" . $roles[0] . "|" . $token,
            time() + (30 * 24 * 60 * 60),
            "/",
            "",
            false,
            true
        );
        unset($_SESSION['remember']);
    }

    header("Location: ../dashboard/{$roles[0]}.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Role - IFM Peer Tutoring System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
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
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
      color: var(--white);
      position: relative;
      overflow-x: hidden;
      overflow-y: auto;
    }

    /* Animated Background */
    body::before,
    body::after {
      content: '';
      position: fixed;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: rgba(253, 185, 19, 0.1);
      filter: blur(60px);
      z-index: 0;
      animation: float 15s ease-in-out infinite;
    }

    body::before {
      top: -150px;
      left: -150px;
    }

    body::after {
      bottom: -150px;
      right: -150px;
      animation-delay: 7.5s;
    }

    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(30px, 30px) scale(1.1); }
    }

    .box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      text-align: center;
      width: 100%;
      max-width: 520px;
      padding: 50px 45px;
      border: 1px solid rgba(255, 255, 255, 0.25);
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      z-index: 1;
      margin: auto;
    }

    .logo-section {
      margin-bottom: 25px;
      animation: fadeIn 0.8s ease-out 0.2s both;
    }

    .logo-section img {
      max-width: 180px;
      width: 100%;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
      transition: transform 0.3s ease;
    }

    .logo-section img:hover {
      transform: scale(1.05);
    }

    .system-title {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--white);
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }

    .system-subtitle {
      color: var(--accent-yellow);
      font-size: 0.9rem;
      margin-bottom: 35px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    h2 {
      color: var(--white);
      margin-bottom: 15px;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .description {
      color: var(--light-gray);
      font-size: 1rem;
      margin-bottom: 35px;
      line-height: 1.6;
      font-weight: 300;
    }

    .roles-container {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-bottom: 30px;
    }

    .role-form {
      display: block;
      width: 100%;
    }

    .role-btn {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      font-weight: 600;
      font-size: 1.1rem;
      border: none;
      border-radius: 12px;
      padding: 18px 30px;
      width: 100%;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 5px 20px rgba(253, 185, 19, 0.4);
      position: relative;
      overflow: hidden;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .role-btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .role-btn:hover::before {
      width: 400px;
      height: 400px;
    }

    .role-btn:hover {
      background: var(--white);
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
    }

    .role-btn:active {
      transform: translateY(-1px);
    }

    .role-icon {
      font-size: 1.5rem;
      display: inline-block;
      margin-right: 8px;
    }

    .footer {
      margin-top: 30px;
      padding-top: 25px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      font-size: 0.95rem;
      color: var(--light-gray);
    }

    .footer a {
      color: var(--accent-yellow);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .footer a:hover {
      color: var(--white);
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

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

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .role-form {
      animation: slideIn 0.5s ease-out both;
    }

    .role-form:nth-child(1) {
      animation-delay: 0.2s;
    }

    .role-form:nth-child(2) {
      animation-delay: 0.3s;
    }

    .role-form:nth-child(3) {
      animation-delay: 0.4s;
    }

    @media (max-width: 480px) {
      body {
        align-items: flex-start;
        padding-top: 20px;
        padding-bottom: 20px;
      }

      .box {
        padding: 40px 30px;
      }

      .logo-section img {
        max-width: 140px;
      }

      .system-title {
        font-size: 1.4rem;
      }

      h2 {
        font-size: 1.5rem;
      }

      .description {
        font-size: 0.95rem;
      }

      .role-btn {
        padding: 16px 25px;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="box">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Select Your Role</h2>
    <p class="description">Choose how you would like to continue. You can switch roles anytime after logging in.</p>

    <div class="roles-container">
      <?php foreach ($roles as $r): ?>
        <form method="POST" action="set_role.php" class="role-form">
          <input type="hidden" name="role" value="<?= htmlspecialchars($r) ?>">
          <button type="submit" class="role-btn <?= strtolower($r) ?>">
            <span class="role-icon"><i class="fas fa-<?= $r === 'student' ? 'user-graduate' : ($r === 'tutor' ? 'chalkboard-teacher' : 'user-tie') ?>"></i></span>
            <span>Continue as <?= ucfirst($r) ?></span>
          </button>
        </form>
      <?php endforeach; ?>
    </div>

    <div class="footer">
      <a href="logout.php">Not you? Logout</a>
    </div>
  </div>
</body>
</html>
