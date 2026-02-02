<?php
session_start();
include("../config/db.php");

// Ensure admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

// Fetch all users
$users = $conn->query("SELECT id, first_name, last_name, email, role, status FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - IFM Peer Tutoring</title>
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
      --error-red: #e74c3c;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      margin: 0;
      padding: 40px 20px;
      color: var(--white);
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
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
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
      font-size: 2.2rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    .subtitle {
      color: var(--light-gray);
      font-size: 0.95rem;
      margin-bottom: 30px;
    }
    .table-wrapper {
      overflow-x: auto;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }
    thead { background: rgba(253, 185, 19, 0.2); }
    th {
      padding: 18px 20px;
      text-align: left;
      color: var(--white);
      font-weight: 600;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }
    th:first-child { text-align: center; width: 60px; }
    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }
    tbody tr:nth-child(even) { background: rgba(255, 255, 255, 0.05); }
    tbody tr:hover {
      background: rgba(253, 185, 19, 0.15);
      transform: translateX(5px);
    }
    td {
      padding: 18px 20px;
      color: var(--white);
      font-size: 0.9rem;
    }
    td:first-child {
      text-align: center;
      font-weight: 600;
      color: var(--primary-blue);
    }
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .status-active {
      background: rgba(46, 204, 113, 0.2);
      color: var(--success-green);
      border: 1px solid var(--success-green);
    }
    .status-suspended {
      background: rgba(231, 76, 60, 0.2);
      color: var(--error-red);
      border: 1px solid var(--error-red);
    }
    .btn-edit {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 8px 16px;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    .btn-edit:hover {
      background: var(--white);
      transform: translateY(-2px);
    }
    .back-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin: 30px auto 0;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      padding: 14px 28px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      max-width: 250px;
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
    @media (max-width: 768px) {
      .container { padding: 30px 25px; }
      h2 { font-size: 1.8rem; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>Manage Users</h2>
      <p class="subtitle">View and manage all system users</p>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $serial = 1;
          while ($u = $users->fetch_assoc()): 
          ?>
            <tr>
              <td><strong><?= $serial++; ?></strong></td>
              <td><strong><?= htmlspecialchars($u['first_name']." ".$u['last_name']); ?></strong></td>
              <td><?= htmlspecialchars($u['email']); ?></td>
              <td><?= ucfirst($u['role']); ?></td>
              <td>
                <span class="status-badge status-<?= strtolower($u['status']); ?>">
                  <?= ucfirst($u['status']); ?>
                </span>
              </td>
              <td>
                <a href="edit_user.php?id=<?= $u['id']; ?>" class="btn-edit">✏ Edit</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <a href="../dashboard/admin.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Dashboard</a>
  </div>
</body>
</html>
