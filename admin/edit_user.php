<?php
session_start();
include("../config/db.php");

// Ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) die("User not found.");

$message = "";

// Fetch user
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, role, status FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("User not found.");

// Handle updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $role  = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, role=?, status=?, password_hash=? WHERE id=?");
        $stmt->bind_param("sssssi", $fname, $lname, $role, $status, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, role=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $fname, $lname, $role, $status, $user_id);
    }
    $stmt->execute();

    $message = "<p class='success'><i class=\"fas fa-check-circle\"></i> User profile updated successfully.</p>";

    // Reload updated user
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, role, status FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User - IFM Peer Tutoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary-blue: #002B7F;
      --secondary-blue: #0044AA;
      --accent-yellow: #FDB913;
      --white: #ffffff;
      --light-gray: #E6E6E6;
      --success-green: #2ecc71;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      margin: 0;
      padding: 40px 20px;
      color: var(--white);
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }
    body::before, body::after {
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
    body::before { top: -150px; left: -150px; }
    body::after { bottom: -150px; right: -150px; animation-delay: 7.5s; }
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(30px, 30px) scale(1.1); }
    }
    .container {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      padding: 40px 35px;
      max-width: 600px;
      width: 100%;
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
    }
    .logo-section img {
      max-width: 160px;
      width: 100%;
      height: auto;
      filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
    }
    h2 {
      color: var(--accent-yellow);
      font-size: 2rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    .subtitle {
      color: var(--light-gray);
      font-size: 0.95rem;
      margin-bottom: 30px;
    }
    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 25px;
      text-align: center;
      font-weight: 500;
    }
    .success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
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
    input, select {
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
    input[readonly] {
      background: rgba(255, 255, 255, 0.05);
      cursor: not-allowed;
    }
    input:focus, select:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }
    select option {
      background: var(--primary-blue);
      color: var(--white);
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
    .back-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin: 25px auto 0;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      max-width: 200px;
    }
    .back-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--accent-yellow);
      color: var(--accent-yellow);
      transform: translateY(-2px);
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 600px) {
      body { padding: 20px 15px; }
      .logo-section img { max-width: 120px; }
      h2 { font-size: 1.6rem; }
      .container { padding: 25px 20px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>Edit User</h2>
      <p class="subtitle">Update user information and settings</p>
    </div>

    <?php if (!empty($message)) echo "<div class='alert success'>$message</div>"; ?>

    <form method="POST">
      <div class="form-group">
        <label for="first_name"><span>👤</span> First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
      </div>

      <div class="form-group">
        <label for="last_name"><span><i class="fas fa-user"></i></span> Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
      </div>

      <div class="form-group">
        <label for="email"><span>📧</span> Email (read-only)</label>
        <input type="email" id="email" value="<?= htmlspecialchars($user['email']); ?>" readonly>
      </div>

      <div class="form-group">
        <label for="role"><span><i class="fas fa-users"></i></span> Role</label>
        <select id="role" name="role">
          <option value="student" <?= $user['role']=="student" ? "selected" : ""; ?>>Student</option>
          <option value="tutor" <?= $user['role']=="tutor" ? "selected" : ""; ?>>Tutor</option>
          <option value="admin" <?= $user['role']=="admin" ? "selected" : ""; ?>>Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label for="status"><span><i class="fas fa-chart-bar"></i></span> Status</label>
        <select id="status" name="status">
          <option value="active" <?= $user['status']=="active" ? "selected" : ""; ?>>Active</option>
          <option value="suspended" <?= $user['status']=="suspended" ? "selected" : ""; ?>>Suspended</option>
        </select>
      </div>

      <div class="form-group">
        <label for="password"><span>🔑</span> New Password (leave blank if not changing)</label>
        <input type="password" id="password" name="password" placeholder="Enter new password">
      </div>

      <button type="submit"><i class="fas fa-save"></i> Update User</button>
    </form>

    <a href="admin_users.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Users</a>
  </div>
</body>
</html>
