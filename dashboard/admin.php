<?php
session_start();
if ($_SESSION['role'] != 'admin') { die("Unauthorized"); }
include("../config/db.php");

/* ==============================
   HANDLE ACCOUNT ACTIONS
   ============================== */

// Suspend
if (isset($_GET['suspend'])) {
    $id = intval($_GET['suspend']);
    $stmt = $conn->prepare("UPDATE users SET status='suspended' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Unsuspend
if (isset($_GET['unsuspend'])) {
    $id = intval($_GET['unsuspend']);
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Deactivate with reason
if (isset($_POST['deactivate_id']) && !empty($_POST['reason'])) {
    $id = intval($_POST['deactivate_id']);
    $reason = trim($_POST['reason']);
    $stmt = $conn->prepare("UPDATE users SET status='deactivated', deactivation_reason=? WHERE id=?");
    $stmt->bind_param("si", $reason, $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Reactivate
if (isset($_GET['reactivate'])) {
    $id = intval($_GET['reactivate']);
    $stmt = $conn->prepare("UPDATE users SET status='active', deactivation_reason=NULL WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Permanent delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

/* ==============================
   FETCH DATA
   ============================== */

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$where = $search ? "WHERE u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%'" : "";

$users = $conn->query("
  SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.deactivation_reason,
         GROUP_CONCAT(ur.role SEPARATOR ', ') AS roles
  FROM users u
  LEFT JOIN user_roles ur ON u.id = ur.user_id
  $where
  GROUP BY u.id
  ORDER BY u.status DESC, u.id ASC
");

$feedback = $conn->query("
  SELECT f.id, f.stars, f.comment, f.created_at,
         t.first_name AS tutor_first, t.last_name AS tutor_last,
         s.first_name AS student_first, s.last_name AS student_last
  FROM feedback f
  JOIN sessions se ON f.session_id = se.id
  JOIN users t ON se.tutor_id = t.id
  JOIN users s ON f.rater_id = s.id
  ORDER BY f.created_at DESC
");

$avgRatings = $conn->query("
  SELECT t.id, t.first_name, t.last_name, 
         ROUND(AVG(f.stars),1) AS avg_rating,
         COUNT(f.id) AS total_reviews
  FROM feedback f
  JOIN sessions se ON f.session_id = se.id
  JOIN users t ON se.tutor_id = t.id
  GROUP BY t.id
  ORDER BY avg_rating DESC
");

$user_id = $_SESSION['user_id'];
$notes = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");

// Calculate stats
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$activeUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='active'")->fetch_assoc()['c'];
$totalTutors = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM user_roles WHERE role='tutor'")->fetch_assoc()['c'];
$totalSessions = $conn->query("SELECT COUNT(*) as c FROM sessions")->fetch_assoc()['c'];
$totalFeedback = $conn->query("SELECT COUNT(*) as c FROM feedback")->fetch_assoc()['c'];

/* ==============================
   HANDLE AJAX REQUESTS
   ============================== */
if (isset($_GET['ajax'])) {
    ob_clean();
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $where = $search ? "WHERE u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%'" : "";
    $users = $conn->query("
      SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.deactivation_reason,
             GROUP_CONCAT(ur.role SEPARATOR ', ') AS roles
      FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      $where
      GROUP BY u.id
      ORDER BY u.status DESC, u.id ASC
    ");

    echo "<div class='table-wrapper'><table><thead><tr><th>#</th><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Reason</th><th>Action</th></tr></thead><tbody>";
    if ($users->num_rows > 0) {
        $ajaxSerial = 1;
        while ($u = $users->fetch_assoc()) {
            $statusClass = strtolower($u['status']);
            echo "<tr>
                <td><strong>".$ajaxSerial++."</strong></td>
                <td><strong>".htmlspecialchars($u['first_name']." ".$u['last_name'])."</strong></td>
                <td>".htmlspecialchars($u['email'])."</td>
                <td>".htmlspecialchars($u['roles'] ?: '—')."</td>
                <td><span class='status-badge status-{$statusClass}'>".ucfirst($u['status'])."</span></td>
                <td class='reason'>".htmlspecialchars($u['deactivation_reason'] ?? '—')."</td>
                <td><div style='display: flex; gap: 5px; flex-wrap: wrap;'>";

            if ($u['status'] == "active") {
                echo "<a class='btn suspend' href='?suspend={$u['id']}'>Suspend</a>
                      <form method='POST' class='action-form' style='display:inline;'>
                        <input type='hidden' name='deactivate_id' value='{$u['id']}'>
                        <input type='text' name='reason' placeholder='Reason' required>
                        <button type='submit' class='btn suspend'>Deactivate</button>
                      </form>";
            } elseif ($u['status'] == "suspended") {
                echo "<a class='btn unsuspend' href='?unsuspend={$u['id']}'>Unsuspend</a>";
            } elseif ($u['status'] == "deactivated") {
                echo "<a class='btn unsuspend' href='?reactivate={$u['id']}'>Reactivate</a>";
            }

            echo "<a class='btn delete' href='?delete={$u['id']}' onclick=\"return confirm('Delete permanently?');\">Delete</a>
                </div></td></tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='empty-state'>No users found</td></tr>";
    }
    echo "</tbody></table></div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - IFM Peer Tutoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
      --warning-orange: #f39c12;
      --purple: #8e44ad;
      --text-gray: #666;
      --border-gray: #E0E0E0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--light-gray);
      margin: 0;
      display: flex;
      color: var(--text-gray);
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      background: linear-gradient(180deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
      color: white;
      display: flex;
      flex-direction: column;
      padding: 0;
      position: fixed;
      height: 100vh;
      box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
      overflow-y: auto;
    }

    .sidebar-header {
      padding: 30px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(0, 0, 0, 0.1);
    }

    .sidebar-header .logo {
      max-width: 120px;
      height: auto;
      margin-bottom: 15px;
      filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
    }

    .sidebar-header h2 {
      color: var(--accent-yellow);
      margin-bottom: 0;
      font-size: 1.1rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .nav {
      padding: 20px 15px;
      flex: 1;
    }

    .nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      color: rgba(255, 255, 255, 0.9);
      padding: 14px 16px;
      border-radius: 10px;
      text-decoration: none;
      margin: 8px 0;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      position: relative;
    }

    .nav a::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 0;
      background: var(--accent-yellow);
      border-radius: 0 3px 3px 0;
      transition: height 0.3s ease;
    }

    .nav a:hover, .nav a.active {
      background: rgba(253, 185, 19, 0.15);
      color: var(--white);
      transform: translateX(5px);
    }

    .nav a:hover::before, .nav a.active::before {
      height: 60%;
    }

    .sidebar-footer {
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      text-align: center;
    }

    .sidebar-footer a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      font-weight: 500;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .sidebar-footer a:hover {
      background: rgba(231, 76, 60, 0.2);
      color: #FF6B6B;
    }

    /* Main */
    .main {
      margin-left: 280px;
      padding: 40px;
      flex: 1;
      max-width: calc(100% - 280px);
    }

    .welcome-section {
      margin-bottom: 35px;
    }

    .welcome-section h1 {
      font-size: 2.2rem;
      color: var(--primary-blue);
      margin-bottom: 8px;
      font-weight: 700;
    }

    .welcome-section p {
      color: var(--text-gray);
      font-size: 1rem;
      font-weight: 400;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
      margin-bottom: 35px;
    }

    .stat-card {
      background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
      border-radius: 16px;
      padding: 28px 24px;
      box-shadow: 0 4px 20px rgba(0, 43, 127, 0.08);
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid var(--border-gray);
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-blue), var(--accent-yellow));
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }

    .stat-card:hover::before {
      transform: scaleX(1);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0, 43, 127, 0.15);
    }

    .stat-card-icon {
      font-size: 2.5rem;
      margin-bottom: 12px;
      display: block;
    }

    .stat-card h3 {
      margin: 0;
      color: var(--primary-blue);
      font-size: 2.5rem;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .stat-card p {
      color: var(--text-gray);
      font-size: 0.95rem;
      font-weight: 500;
      margin: 0;
    }

    /* Search Bar */
    .search-bar {
      display: flex;
      margin-bottom: 30px;
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      overflow: hidden;
      border: 1px solid var(--border-gray);
    }

    .search-bar input {
      flex: 1;
      padding: 16px 20px;
      border: none;
      background: transparent;
      color: var(--text-gray);
      font-size: 0.95rem;
      font-family: 'Poppins', sans-serif;
      outline: none;
    }

    .search-bar input::placeholder {
      color: var(--text-gray);
      opacity: 0.6;
    }

    .search-bar button {
      background: var(--accent-yellow);
      border: none;
      padding: 16px 28px;
      color: var(--primary-blue);
      cursor: pointer;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .search-bar button:hover {
      background: var(--primary-blue);
      color: var(--white);
    }

    /* Sections */
    section {
      background: var(--white);
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      margin-bottom: 30px;
      border: 1px solid var(--border-gray);
    }

    section h2 {
      color: var(--primary-blue);
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-gray);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Tables */
    .table-wrapper {
      overflow-x: auto;
      border-radius: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    thead {
      background: rgba(0, 43, 127, 0.05);
    }

    th {
      padding: 18px 20px;
      text-align: left;
      color: var(--primary-blue);
      font-weight: 600;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--light-gray);
      position: sticky;
      top: 0;
      background: var(--white);
      z-index: 10;
    }

    th:first-child {
      text-align: center;
      width: 60px;
    }

    td:first-child {
      text-align: center;
      color: var(--primary-blue);
      font-weight: 600;
    }

    tbody tr {
      border-bottom: 1px solid var(--border-gray);
      transition: all 0.3s ease;
    }

    tbody tr:nth-child(even) {
      background: rgba(0, 43, 127, 0.02);
    }

    tbody tr:hover {
      background: rgba(253, 185, 19, 0.1);
      transform: translateX(5px);
    }

    td {
      padding: 18px 20px;
      color: var(--text-gray);
      font-size: 0.9rem;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 8px;
      color: var(--white);
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      margin: 2px;
    }

    .btn.suspend {
      background: var(--error-red);
    }

    .btn.unsuspend {
      background: var(--success-green);
    }

    .btn.delete {
      background: var(--purple);
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      opacity: 0.9;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-active {
      background: rgba(46, 204, 113, 0.1);
      color: var(--success-green);
      border: 1px solid var(--success-green);
    }

    .status-suspended {
      background: rgba(243, 156, 18, 0.1);
      color: var(--warning-orange);
      border: 1px solid var(--warning-orange);
    }

    .status-deactivated {
      background: rgba(231, 76, 60, 0.1);
      color: var(--error-red);
      border: 1px solid var(--error-red);
    }

    .stars {
      color: var(--accent-yellow);
      font-size: 1.1rem;
      letter-spacing: 2px;
      font-weight: 600;
    }

    .comment {
      font-style: italic;
      color: var(--text-gray);
      max-width: 300px;
    }

    .reason {
      font-size: 0.85rem;
      color: var(--text-gray);
      font-style: italic;
    }

    ul {
      list-style: none;
      padding: 0;
    }

    ul li {
      padding: 15px;
      background: var(--light-gray);
      border-radius: 10px;
      margin-bottom: 10px;
      border-left: 3px solid var(--accent-yellow);
      transition: all 0.3s ease;
    }

    ul li:hover {
      background: rgba(253, 185, 19, 0.1);
      transform: translateX(5px);
    }

    ul li.unread {
      background: rgba(253, 185, 19, 0.15);
      font-weight: 600;
    }

    ul li small {
      display: block;
      color: var(--text-gray);
      font-size: 0.85rem;
      margin-top: 5px;
    }

    #userTable {
      transition: opacity 0.2s ease-in-out;
    }

    #userTable.loading {
      opacity: 0.5;
    }

    .action-form {
      display: inline-block;
    }

    .action-form input[type="text"] {
      padding: 6px 10px;
      border: 1px solid var(--border-gray);
      border-radius: 6px;
      font-size: 0.85rem;
      margin-right: 5px;
      width: 150px;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-gray);
      font-size: 1.1rem;
    }

    .empty-state .fas {
      font-size: 4rem;
      display: block;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    @media (max-width: 1024px) {
      .main {
        padding: 30px 25px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 240px;
      }
      .main {
        margin-left: 240px;
        padding: 25px 20px;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }
    }

    @media (max-width: 600px) {
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1000;
      }
      .sidebar.open {
        transform: translateX(0);
      }
      .main {
        margin-left: 0;
        padding: 20px 15px;
        max-width: 100%;
      }
      .stats-grid {
        grid-template-columns: 1fr;
      }
      .welcome-section h1 {
        font-size: 1.6rem;
      }
    }

    .menu-toggle {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: var(--primary-blue);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 1.2rem;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 600px) {
      .menu-toggle {
        display: block;
      }
    }
  </style>
</head>
<body>
  <!-- Mobile Menu Toggle -->
  <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="Menu"><i class="fas fa-bars"></i></button>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <img src="../images/ifm.png" alt="IFM Logo" class="logo" onerror="this.style.display='none';">
      <h2>Admin Panel</h2>
    </div>
    <div class="nav">
      <a href="#" class="active"><span>📊</span> Dashboard</a>
      <a href="#users"><span>👥</span> Manage Users</a>
      <a href="#ratings"><span>⭐</span> Tutor Ratings</a>
      <a href="#feedback"><span>💬</span> Feedback</a>
      <a href="admin_sessions.php"><span>📅</span> Sessions</a>
      <a href="#notes"><span>🔔</span> Notifications</a>
    </div>
    <div class="sidebar-footer">
      <a href="../auth/logout.php"><span>🚪</span> Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="welcome-section">
      <h1>Admin Dashboard</h1>
      <p>Manage users, monitor feedback, and oversee the tutoring system</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-users"></i></span>
        <h3><?= $totalUsers; ?></h3>
        <p>Total Users</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-check-circle"></i></span>
        <h3><?= $activeUsers; ?></h3>
        <p>Active Users</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-chalkboard-teacher"></i></span>
        <h3><?= $totalTutors; ?></h3>
        <p>Total Tutors</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-calendar-alt"></i></span>
        <h3><?= $totalSessions; ?></h3>
        <p>Total Sessions</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-star"></i></span>
        <h3><?= $totalFeedback; ?></h3>
        <p>Total Feedback</p>
      </div>
    </div>

    <!-- Live Search -->
    <div class="search-bar">
      <input type="text" id="liveSearch" placeholder="Search users, tutors, or emails...">
      <button type="button" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>

    <!-- Notifications -->
    <section id="notes">
      <h2><span><i class="fas fa-bell"></i></span> Notifications</h2>
      <?php if ($notes && $notes->num_rows > 0): ?>
        <ul>
          <?php while($n = $notes->fetch_assoc()): ?>
            <li class="<?= !$n['is_read'] ? 'unread' : ''; ?>">
              <?= htmlspecialchars($n['message']); ?>
              <small><i class="fas fa-clock"></i> <?= date("d M Y, H:i", strtotime($n['created_at'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="empty-state"><i class="fas fa-inbox"></i> No notifications</div>
      <?php endif; ?>
    </section>

    <!-- User Management -->
    <section id="users">
      <h2><span><i class="fas fa-users"></i></span> User Management</h2>
      <div id="userTable" class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Roles</th>
              <th>Status</th>
              <th>Reason</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $userSerial = 1;
            while ($u = $users->fetch_assoc()): 
            ?>
            <tr>
              <td><strong><?= $userSerial++; ?></strong></td>
              <td><strong><?= htmlspecialchars($u['first_name']." ".$u['last_name']); ?></strong></td>
              <td><?= htmlspecialchars($u['email']); ?></td>
              <td><?= htmlspecialchars($u['roles'] ?: '—'); ?></td>
              <td>
                <span class="status-badge status-<?= strtolower($u['status']); ?>">
                  <?= ucfirst($u['status']); ?>
                </span>
              </td>
              <td class="reason"><?= htmlspecialchars($u['deactivation_reason'] ?? '—'); ?></td>
              <td>
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                  <?php if ($u['status']=="active"): ?>
                    <a class="btn suspend" href="?suspend=<?= $u['id']; ?>">Suspend</a>
                    <form method="POST" class="action-form">
                      <input type="hidden" name="deactivate_id" value="<?= $u['id']; ?>">
                      <input type="text" name="reason" placeholder="Reason" required>
                      <button type="submit" class="btn suspend">Deactivate</button>
                    </form>
                  <?php elseif ($u['status']=="suspended"): ?>
                    <a class="btn unsuspend" href="?unsuspend=<?= $u['id']; ?>">Unsuspend</a>
                  <?php elseif ($u['status']=="deactivated"): ?>
                    <a class="btn unsuspend" href="?reactivate=<?= $u['id']; ?>">Reactivate</a>
                  <?php endif; ?>
                  <a class="btn delete" href="?delete=<?= $u['id']; ?>" onclick="return confirm('Delete permanently?');">Delete</a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Ratings -->
    <section id="ratings">
      <h2><span><i class="fas fa-star"></i></span> Tutor Ratings Summary</h2>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tutor</th>
              <th>Average Rating</th>
              <th>Total Reviews</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($avgRatings && $avgRatings->num_rows > 0): ?>
              <?php 
              $ratingSerial = 1;
              while ($a = $avgRatings->fetch_assoc()): 
              ?>
              <tr>
                <td><strong><?= $ratingSerial++; ?></strong></td>
                <td><strong><?= htmlspecialchars($a['first_name']." ".$a['last_name']); ?></strong></td>
                <td class="stars"><?= str_repeat('<i class="fas fa-star"></i>', floor($a['avg_rating'])); ?> <strong>(<?= $a['avg_rating']; ?>/5)</strong></td>
                <td><?= $a['total_reviews']; ?> review<?= $a['total_reviews'] > 1 ? 's' : ''; ?></td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="empty-state"><i class="fas fa-inbox"></i> No tutor ratings yet</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Feedback -->
    <section id="feedback">
      <h2><span><i class="fas fa-comment"></i></span> Tutor Feedback</h2>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tutor</th>
              <th>Student</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($feedback && $feedback->num_rows > 0): ?>
              <?php 
              $feedbackSerial = 1;
              while ($f = $feedback->fetch_assoc()): 
              ?>
              <tr>
                <td><strong><?= $feedbackSerial++; ?></strong></td>
                <td><strong><?= htmlspecialchars($f['tutor_first']." ".$f['tutor_last']); ?></strong></td>
                <td><?= htmlspecialchars($f['student_first']." ".$f['student_last']); ?></td>
                <td class="stars"><?= str_repeat('<i class="fas fa-star"></i>', $f['stars']); ?></td>
                <td class="comment"><?= htmlspecialchars($f['comment']); ?></td>
                <td><?= date("d M Y, H:i", strtotime($f['created_at'])); ?></td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="empty-state"><i class="fas fa-inbox"></i> No feedback available</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      const sidebar = document.querySelector('.sidebar');
      const menuToggle = document.querySelector('.menu-toggle');
      
      if (window.innerWidth <= 600) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && sidebar.classList.contains('open')) {
          sidebar.classList.remove('open');
        }
      }
    });

    // Prevent body scroll when sidebar is open on mobile
    document.querySelector('.menu-toggle')?.addEventListener('click', function() {
      const sidebar = document.querySelector('.sidebar');
      if (sidebar.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    });

    const searchInput = document.getElementById("liveSearch");
    const userTable = document.getElementById("userTable");
    const refreshBtn = document.getElementById("refreshBtn");

    function fetchUsers(query = "") {
      userTable.classList.add("loading");
      fetch(`?ajax=1&search=${encodeURIComponent(query)}`)
        .then(res => res.text())
        .then(data => {
          userTable.innerHTML = data;
          userTable.classList.remove("loading");
        });
    }

    searchInput.addEventListener("input", function() {
      fetchUsers(this.value.trim());
    });
    refreshBtn.addEventListener("click", () => fetchUsers(searchInput.value.trim()));
    setInterval(() => fetchUsers(searchInput.value.trim()), 10000);
  </script>
</body>
</html>
