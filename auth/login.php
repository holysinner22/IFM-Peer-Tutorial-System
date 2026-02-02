<?php
session_start();
include("../config/db.php");

// Auto-login if cookie is present
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    list($uid, $role, $hash) = explode('|', $_COOKIE['remember_user']);
    if (hash_equals(hash('sha256', $uid . $role . 'SECRET_KEY'), $hash)) {
        $_SESSION['user_id'] = $uid;
        $_SESSION['role'] = $role;

        if ($role === "admin") {
            header("Location: ../dashboard/admin.php");
        } else {
            header("Location: ../dashboard/{$role}.php");
        }
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email=? AND status='active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];

            $rolesRes = $conn->prepare("SELECT role FROM user_roles WHERE user_id=?");
            $rolesRes->bind_param("i", $user['id']);
            $rolesRes->execute();
            $rolesData = $rolesRes->get_result();

            $roles = [];
            while ($r = $rolesData->fetch_assoc()) {
                $roles[] = $r['role'];
            }

            if (in_array("admin", $roles)) {
                $_SESSION['role'] = "admin";
                if ($remember) {
                    $token = hash('sha256', $user['id'] . "admin" . 'SECRET_KEY');
                    setcookie("remember_user", $user['id'] . "|admin|" . $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
                }
                header("Location: ../dashboard/admin.php");
                exit;
            }

            if (count($roles) === 1) {
                $_SESSION['role'] = $roles[0];
                if ($remember) {
                    $token = hash('sha256', $user['id'] . $roles[0] . 'SECRET_KEY');
                    setcookie("remember_user", $user['id'] . "|" . $roles[0] . "|" . $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
                }
                header("Location: ../dashboard/{$roles[0]}.php");
                exit;
            } else {
                $_SESSION['roles'] = $roles;
                $_SESSION['remember'] = $remember;
                header("Location: select_role.php");
                exit;
            }
        }
    }
    $error = "<i class=\"fas fa-times-circle\"></i> Invalid email or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - IFM Peer Tutoring System</title>
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
      --error-red: #FF6B6B;
      --success-green: #4CAF50;
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

    .login-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 480px;
      padding: 50px 45px;
      text-align: center;
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
      font-size: 1.5rem;
      color: var(--white);
      margin-bottom: 30px;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.9rem;
      color: var(--light-gray);
      font-weight: 500;
    }

    input {
      width: 100%;
      padding: 14px 18px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      font-size: 0.95rem;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    input::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    input:focus {
      outline: none;
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--accent-yellow);
      box-shadow: 0 0 0 4px rgba(253, 185, 19, 0.2);
      transform: translateY(-2px);
    }

    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
      margin: 20px 0 25px 0;
      flex-wrap: wrap;
      gap: 10px;
    }

    .form-options label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      color: var(--light-gray);
      font-weight: 400;
    }

    .form-options input[type="checkbox"] {
      width: auto;
      margin: 0;
      cursor: pointer;
    }

    button {
      width: 100%;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1.05rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      box-shadow: 0 5px 20px rgba(253, 185, 19, 0.4);
      position: relative;
      overflow: hidden;
    }

    button::before {
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

    button:hover::before {
      width: 300px;
      height: 300px;
    }

    button:hover {
      background: var(--white);
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
    }

    button:active {
      transform: translateY(-1px);
    }

    .error {
      color: var(--error-red);
      margin-bottom: 15px;
      font-size: 0.9rem;
      padding: 12px;
      background: rgba(255, 107, 107, 0.15);
      border-radius: 8px;
      border-left: 3px solid var(--error-red);
      text-align: left;
      animation: shake 0.5s ease;
    }

    .success {
      color: var(--success-green);
      margin-bottom: 15px;
      font-size: 0.9rem;
      padding: 12px;
      background: rgba(76, 175, 80, 0.15);
      border-radius: 8px;
      border-left: 3px solid var(--success-green);
      text-align: left;
    }

    .register-link {
      margin-top: 25px;
      font-size: 0.95rem;
      color: var(--light-gray);
    }

    .home-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 20px;
      padding: 12px 20px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      text-decoration: none;
      border-radius: 10px;
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .home-link:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--accent-yellow);
      color: var(--accent-yellow);
      transform: translateY(-2px);
    }

    a {
      color: var(--accent-yellow);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    a:hover {
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

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    @media (max-width: 480px) {
      body {
        align-items: flex-start;
        padding-top: 20px;
        padding-bottom: 20px;
      }

      .login-box {
        padding: 40px 30px;
      }

      .logo-section img {
        max-width: 140px;
      }

      .system-title {
        font-size: 1.4rem;
      }

      h2 {
        font-size: 1.3rem;
      }

      .form-options {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Welcome Back</h2>

    <?php 
      if (isset($_GET['registered']) && $_GET['registered'] == 1) {
          echo "<p class='success'><i class=\"fas fa-check-circle\"></i> Account created successfully! Please login.</p>";
      }
      if (!empty($error)) echo "<p class='error'>$error</p>"; 
    ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <input type="email" name="email" placeholder="Institutional Email (e.g. john@ifm.ac.tz)" required autocomplete="email">
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
      </div>

      <div class="form-options">
        <label>
          <input type="checkbox" name="remember" id="remember"> 
          <span>Remember me</span>
        </label>
        <a href="reset_request.php">Forgot password?</a>
      </div>

      <button type="submit">Sign In</button>
    </form>

    <p class="register-link">New user? <a href="register.php">Create an account</a></p>
    
    <a href="../index.php" class="home-link">
      <span><i class="fas fa-home"></i></span> Back to Homepage
    </a>
  </div>
</body>
</html>
