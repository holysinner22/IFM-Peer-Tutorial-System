<?php
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    $roles = $_POST['roles'] ?? [];

    if ($password !== $confirm_password) {
        $error = "<i class=\"fas fa-times-circle\"></i> Passwords do not match.";
    } elseif (empty($roles)) {
        $error = "<i class=\"fas fa-times-circle\"></i> Please select at least one role.";
    } else {
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        if (!preg_match("/@ifm\.ac\.tz$/", $email)) {
            $error = "<i class=\"fas fa-times-circle\"></i> Invalid email domain. Use your institutional email (@ifm.ac.tz).";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (?,?,?,?, 'active')");
            $stmt->bind_param("ssss", $fname, $lname, $email, $passHash);

            if ($stmt->execute()) {
                $userId = $stmt->insert_id;

                $roleStmt = $conn->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
                foreach ($roles as $role) {
                    $role = strtolower(trim($role));
                    $roleStmt->bind_param("is", $userId, $role);
                    $roleStmt->execute();
                }

                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "<i class=\"fas fa-times-circle\"></i> Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - IFM Peer Tutoring System</title>
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

    .register-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 520px;
      padding: 50px 45px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.25);
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      z-index: 1;
      max-height: 90vh;
      overflow-y: auto;
    }

    .register-box::-webkit-scrollbar {
      width: 6px;
    }

    .register-box::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
    }

    .register-box::-webkit-scrollbar-thumb {
      background: var(--accent-yellow);
      border-radius: 10px;
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
      margin-bottom: 30px;
      color: var(--white);
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 18px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.9rem;
      color: var(--light-gray);
      font-weight: 500;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
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

    .roles {
      text-align: left;
      margin: 25px 0 20px 0;
      padding: 20px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .roles p {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--white);
    }

    .roles label {
      display: flex;
      align-items: center;
      margin: 12px 0;
      cursor: pointer;
      padding: 10px;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 400;
    }

    .roles label:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .roles input[type="checkbox"] {
      width: 20px;
      height: 20px;
      margin-right: 12px;
      cursor: pointer;
      accent-color: var(--accent-yellow);
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
      margin-top: 10px;
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

    .login-link {
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
      .register-box {
        padding: 40px 30px;
        max-height: 95vh;
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

      .roles {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="register-box">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Create Your Account</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" id="registerForm">
      <div class="form-group">
        <input type="text" name="first_name" placeholder="First Name" required autocomplete="given-name">
      </div>
      <div class="form-group">
        <input type="text" name="last_name" placeholder="Last Name" required autocomplete="family-name">
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Institutional Email (e.g. john@ifm.ac.tz)" required autocomplete="email">
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required autocomplete="new-password" minlength="6">
      </div>
      <div class="form-group">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password" minlength="6">
      </div>

      <div class="roles">
        <p>Select Role(s):</p>
        <label>
          <input type="checkbox" name="roles[]" value="student">
          <span>Student - I want to learn from tutors</span>
        </label>
        <label>
          <input type="checkbox" name="roles[]" value="tutor">
          <span>Tutor - I want to help other students</span>
        </label>
      </div>

      <button type="submit">Create Account</button>
    </form>

    <p class="login-link">Already have an account? <a href="login.php">Sign in here</a></p>
    
    <a href="../index.php" class="home-link">
      <span><i class="fas fa-home"></i></span> Back to Homepage
    </a>
  </div>
</body>
</html>
