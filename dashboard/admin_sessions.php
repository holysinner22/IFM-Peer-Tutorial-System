<?php
session_start();
if ($_SESSION['role'] != 'admin') { die("Unauthorized"); }
include("../config/db.php");

// Handle session deletion
if (isset($_GET['delete'])) {
    $sid = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM sessions WHERE id=?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    header("Location: admin_sessions.php");
    exit;
}

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$where = $search ? "WHERE s.title LIKE '%$search%' OR t.first_name LIKE '%$search%' OR t.last_name LIKE '%$search%' OR l.first_name LIKE '%$search%' OR l.last_name LIKE '%$search%' OR s.status LIKE '%$search%'" : "";

// Fetch sessions
$sessions = $conn->query("
    SELECT s.id, s.title, s.start_time, s.end_time, s.status,
           t.first_name AS tutor_first, t.last_name AS tutor_last,
           l.first_name AS learner_first, l.last_name AS learner_last
    FROM sessions s
    JOIN users t ON s.tutor_id = t.id
    JOIN users l ON s.learner_id = l.id
    $where
    ORDER BY s.start_time DESC
");

// Calculate stats
$totalSessions = $conn->query("SELECT COUNT(*) as c FROM sessions")->fetch_assoc()['c'];
$pendingSessions = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE status='pending'")->fetch_assoc()['c'];
$acceptedSessions = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE status='accepted'")->fetch_assoc()['c'];
$completedSessions = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE status='completed'")->fetch_assoc()['c'];
$cancelledSessions = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE status='cancelled' OR status='rejected'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Sessions - IFM Peer Tutoring</title>
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
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

    /* Section */
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

    /* Table */
    .table-wrapper {
      overflow-x: auto;
      border-radius: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
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

    td:first-child {
      text-align: center;
      color: var(--primary-blue);
      font-weight: 600;
    }

    .session-title {
      font-weight: 600;
      color: var(--primary-blue);
      max-width: 250px;
    }

    .session-time {
      color: var(--text-gray);
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-pending {
      background: rgba(243, 156, 18, 0.1);
      color: var(--warning-orange);
      border: 1px solid var(--warning-orange);
    }

    .status-accepted {
      background: rgba(52, 152, 219, 0.1);
      color: #3498db;
      border: 1px solid #3498db;
    }

    .status-completed {
      background: rgba(46, 204, 113, 0.1);
      color: var(--success-green);
      border: 1px solid var(--success-green);
    }

    .status-cancelled,
    .status-rejected {
      background: rgba(231, 76, 60, 0.1);
      color: var(--error-red);
      border: 1px solid var(--error-red);
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
    }

    .btn.delete {
      background: var(--error-red);
    }

    .btn.delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
      opacity: 0.9;
    }

    .back-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-align: center;
      margin: 30px auto 0;
      background: rgba(0, 43, 127, 0.1);
      color: var(--primary-blue);
      padding: 14px 28px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid var(--primary-blue);
      font-size: 0.95rem;
      max-width: 250px;
    }

    .back-btn:hover {
      background: var(--primary-blue);
      color: var(--white);
      transform: translateY(-2px);
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
      <a href="admin.php"><span><i class="fas fa-chart-bar"></i></span> Dashboard</a>
      <a href="admin_sessions.php" class="active"><span><i class="fas fa-calendar-alt"></i></span> Manage Sessions</a>
      <a href="../notifications/view_all.php"><span><i class="fas fa-bell"></i></span> Notifications</a>
    </div>
    <div class="sidebar-footer">
      <a href="../auth/logout.php"><span><i class="fas fa-sign-out-alt"></i></span> Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="welcome-section">
      <h1>Manage All Sessions</h1>
      <p>View and manage all tutoring sessions in the system</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-calendar-alt"></i></span>
        <h3><?= $totalSessions; ?></h3>
        <p>Total Sessions</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-clock"></i></span>
        <h3><?= $pendingSessions; ?></h3>
        <p>Pending</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-check-circle"></i></span>
        <h3><?= $acceptedSessions; ?></h3>
        <p>Accepted</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-graduation-cap"></i></span>
        <h3><?= $completedSessions; ?></h3>
        <p>Completed</p>
      </div>
      <div class="stat-card">
        <span class="stat-card-icon"><i class="fas fa-times-circle"></i></span>
        <h3><?= $cancelledSessions; ?></h3>
        <p>Cancelled</p>
      </div>
    </div>

    <!-- Search Bar -->
    <form class="search-bar" method="GET">
      <input type="text" name="search" placeholder="Search sessions, tutors, learners, or status..." value="<?= htmlspecialchars($search); ?>">
      <button type="submit">Search</button>
    </form>

    <!-- Sessions Table -->
    <section>
      <h2><span><i class="fas fa-calendar-alt"></i></span> All Sessions</h2>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Tutor</th>
              <th>Learner</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($sessions && $sessions->num_rows > 0): ?>
              <?php 
              $sessionSerial = 1;
              while ($s = $sessions->fetch_assoc()): 
              ?>
              <tr>
                <td><strong><?= $sessionSerial++; ?></strong></td>
                <td>
                  <div class="session-title"><?= htmlspecialchars($s['title']); ?></div>
                </td>
                <td><strong><?= htmlspecialchars($s['tutor_first']." ".$s['tutor_last']); ?></strong></td>
                <td><?= htmlspecialchars($s['learner_first']." ".$s['learner_last']); ?></td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($s['start_time'])); ?></div>
                </td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($s['end_time'])); ?></div>
                </td>
                <td>
                  <span class="status-badge status-<?= strtolower($s['status']); ?>">
                    <?= ucfirst($s['status']); ?>
                  </span>
                </td>
                <td>
                  <a href="?delete=<?= $s['id']; ?>" class="btn delete" onclick="return confirm('⚠️ Delete this session permanently?');">Delete</a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="empty-state"><i class="fas fa-inbox"></i> No sessions found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <a href="admin.php" class="back-btn"><span>←</span> Back to Dashboard</a>
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
  </script>
</body>
</html>
