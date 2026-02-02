<?php
session_start();
include("../config/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (!empty($password)) {
        if ($password !== $confirm) {
            $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Passwords do not match.</p>";
        } elseif (strlen($password) < 8) {
            $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Password must be at least 8 characters.</p>";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, password_hash=? WHERE id=?");
            $stmt->bind_param("sssi", $fname, $lname, $hashed, $user_id);
            $stmt->execute();
            $message = "<p class='success'>✅ Profile and password updated successfully.</p>";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=? WHERE id=?");
        $stmt->bind_param("ssi", $fname, $lname, $user_id);
        $stmt->execute();
        $message = "<p class='success'><i class=\"fas fa-check-circle\"></i> Profile updated successfully.</p>";
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT first_name,last_name,email,role,status FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile - IFM Peer Tutoring</title>
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
      --success-green: #2ecc71;
      --error-red: #e74c3c;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      margin: 0;
      padding: 40px 20px;
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

    .profile-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      padding: 40px 35px;
      width: 100%;
      max-width: 600px;
      border: 1px solid rgba(255, 255, 255, 0.25);
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      z-index: 1;
    }

    .header-section {
      text-align: center;
      margin-bottom: 30px;
    }

    .logo-section {
      margin-bottom: 20px;
      animation: fadeIn 0.8s ease-out 0.2s both;
    }

    .logo-section img {
      max-width: 160px;
      width: 100%;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
    }

    h2 {
      font-size: 2rem;
      margin-bottom: 10px;
      color: var(--accent-yellow);
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .subtitle {
      color: var(--light-gray);
      font-size: 0.95rem;
      margin-bottom: 30px;
      font-weight: 400;
    }

    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      font-weight: 500;
      margin-bottom: 25px;
      animation: slideIn 0.5s ease-out;
    }

    .success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
      color: var(--white);
    }

    .error {
      background: rgba(231, 76, 60, 0.2);
      border-left: 4px solid var(--error-red);
      color: var(--white);
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--white);
      font-weight: 500;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      outline: none;
    }

    input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    input.readonly {
      background: rgba(255, 255, 255, 0.05);
      cursor: not-allowed;
    }

    input:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }

    button {
      width: 100%;
      padding: 16px;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
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
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(253, 185, 19, 0.4);
    }

    .actions {
      margin-top: 25px;
      text-align: center;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .actions a {
      color: var(--white);
      text-decoration: none;
      font-weight: 500;
      padding: 10px 20px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }

    .actions a:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--accent-yellow);
      color: var(--accent-yellow);
      transform: translateY(-2px);
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

    @media (max-width: 600px) {
      body {
        padding: 20px 15px;
      }

      .logo-section img {
        max-width: 120px;
      }

      h2 {
        font-size: 1.6rem;
      }

      .profile-box {
        padding: 25px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="profile-box">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>User Profile</h2>
      <p class="subtitle">Manage your account information</p>
    </div>

    <?php if (!empty($message)) echo "<div class='alert ". (strpos($message, 'success') !== false ? 'success' : 'error') ."'>$message</div>"; ?>

    <form method="POST">
      <div class="form-group">
        <label for="first_name"><span><i class="fas fa-user"></i></span> First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
      </div>

      <div class="form-group">
        <label for="last_name"><span>👤</span> Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
      </div>

      <div class="form-group">
        <label for="email"><span><i class="fas fa-envelope"></i></span> Email (read-only)</label>
        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="readonly" readonly>
      </div>

      <div class="form-group">
        <label for="role"><span>👥</span> Role</label>
        <input type="text" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" class="readonly" readonly>
      </div>

      <div class="form-group">
        <label for="status"><span><i class="fas fa-chart-bar"></i></span> Status</label>
        <input type="text" id="status" value="<?php echo htmlspecialchars($user['status']); ?>" class="readonly" readonly>
      </div>

      <div class="form-group">
        <label for="password"><span>🔑</span> New Password (leave blank if not changing)</label>
        <input type="password" id="password" name="password" placeholder="Enter new password (min. 8 characters)">
      </div>

      <div class="form-group">
        <label for="confirm_password"><span><i class="fas fa-lock"></i></span> Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
      </div>

      <button type="submit">💾 Update Profile</button>
    </form>

    <div class="actions">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <a href="../dashboard/<?php echo $user['role']; ?>.php"><i class="fas fa-home"></i> Dashboard</a>
    </div>
  </div>
</body>
</html>
