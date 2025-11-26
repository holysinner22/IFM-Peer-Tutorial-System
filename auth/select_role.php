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
  <title>Select Role - IFM Peer Tutoring System</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #002B7F, #0044AA);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      color: #fff;
    }

    .box {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(14px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      text-align: center;
      width: 90%;
      max-width: 460px;
      padding: 50px 40px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: fadeIn 0.8s ease-in-out;
    }

    .system-title {
      font-size: 1.7rem;
      font-weight: 600;
      color: #fff;
    }

    .system-subtitle {
      color: #FDB913;
      font-size: 0.95rem;
      margin-bottom: 25px;
      font-weight: 500;
      letter-spacing: 0.4px;
    }

    h2 {
      color: #fff;
      margin-bottom: 10px;
      font-size: 1.4rem;
    }

    p {
      color: #E6E6E6;
      font-size: 0.95rem;
      margin-bottom: 25px;
      line-height: 1.5;
    }

    .role-btn {
      background: #FDB913;
      color: #002B7F;
      font-weight: 600;
      font-size: 1rem;
      border: none;
      border-radius: 10px;
      padding: 12px 30px;
      margin: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(253, 185, 19, 0.3);
    }

    .role-btn:hover {
      background: #fff;
      color: #002B7F;
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(255, 255, 255, 0.25);
    }

    .footer {
      margin-top: 25px;
      font-size: 0.9rem;
      color: #ddd;
    }

    .footer a {
      color: #FDB913;
      text-decoration: none;
      font-weight: 500;
    }

    .footer a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .box {
        padding: 40px 25px;
      }
      .role-btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="box">
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Select Your Role</h2>
    <p>Choose how you would like to continue. You can switch roles anytime after logging in.</p>

    <?php foreach ($roles as $r): ?>
      <form method="POST" action="set_role.php" style="display:inline;">
        <input type="hidden" name="role" value="<?= htmlspecialchars($r) ?>">
        <button type="submit" class="role-btn">🚀 <?= ucfirst($r) ?></button>
      </form>
    <?php endforeach; ?>

    <div class="footer">
      <a href="logout.php">Not you? Logout</a>
    </div>
  </div>
</body>
</html>
