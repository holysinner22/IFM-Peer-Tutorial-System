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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>📅 Admin - Manage Sessions | IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    display: flex;
    color: #fff;
  }

  /* Sidebar */
  .sidebar {
    width: 230px;
    background: rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    height: 100vh;
    padding: 25px 15px;
    position: fixed;
    left: 0; top: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid rgba(255,255,255,0.1);
  }

  .sidebar h2 {
    color: #FDB913;
    text-align: center;
    margin-bottom: 25px;
  }

  .nav a {
    display: block;
    padding: 12px 15px;
    margin-bottom: 8px;
    text-decoration: none;
    border-radius: 8px;
    color: #fff;
    transition: 0.3s;
    font-weight: 500;
  }
  .nav a:hover, .nav a.active {
    background: #e74c3c;
    color: #fff;
    transform: translateX(4px);
  }

  .sidebar-footer {
    text-align: center;
  }
  .sidebar-footer a {
    display: inline-block;
    color: #e74c3c;
    font-weight: bold;
    text-decoration: none;
    transition: 0.3s;
  }
  .sidebar-footer a:hover { color: #FDB913; }

  /* Main content */
  .main {
    margin-left: 240px;
    flex: 1;
    padding: 40px;
  }

  h1 {
    color: #FDB913;
    margin-bottom: 10px;
  }

  /* Search bar */
  .search-bar {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    overflow: hidden;
  }
  .search-bar input {
    flex: 1;
    padding: 12px 14px;
    border: none;
    outline: none;
    background: transparent;
    color: #fff;
    font-size: 15px;
  }
  .search-bar button {
    background: #e74c3c;
    border: none;
    padding: 12px 20px;
    color: #fff;
    cursor: pointer;
    font-weight: 600;
  }
  .search-bar button:hover { background: #c0392b; }

  table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    overflow: hidden;
  }

  th, td {
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    text-align: left;
  }
  th {
    background: rgba(0,0,0,0.3);
    font-weight: 600;
  }
  tr:hover { background: rgba(255,255,255,0.1); }

  .status {
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
  }
  .pending { background: rgba(255,165,0,0.2); color: orange; }
  .accepted { background: rgba(0,128,255,0.2); color: #1e90ff; }
  .completed { background: rgba(0,255,0,0.2); color: #2ecc71; }
  .cancelled, .rejected { background: rgba(255,0,0,0.2); color: #e74c3c; }

  .btn {
    padding: 6px 12px;
    border-radius: 6px;
    color: white;
    font-size: 14px;
    text-decoration: none;
    transition: 0.3s;
  }
  .delete { background: #e74c3c; }
  .delete:hover { background: #c0392b; }

  .back {
    display: inline-block;
    margin-top: 25px;
    background: #27ae60;
    padding: 10px 18px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-weight: bold;
  }
  .back:hover {
    background: #2ecc71;
  }

  @media (max-width: 850px) {
    .sidebar { display: none; }
    .main { margin: 0; padding: 20px; }
    table, thead, tbody, th, td, tr { display: block; }
    th { display: none; }
    tr { background: rgba(255,255,255,0.1); margin-bottom: 15px; padding: 12px; border-radius: 8px; }
    td::before {
      content: attr(data-label);
      font-weight: 600;
      color: #FDB913;
      display: block;
      margin-bottom: 4px;
    }
  }
</style>
</head>
<body>
  <div class="sidebar">
    <div>
      <h2>IFM Admin</h2>
      <div class="nav">
        <a href="admin.php">📊 Dashboard</a>
        <a href="admin_sessions.php" class="active">📅 Manage Sessions</a>
        <a href="../notifications/view_all.php">🔔 Notifications</a>
      </div>
    </div>
    <div class="sidebar-footer">
      <a href="../auth/logout.php">🚪 Logout</a>
    </div>
  </div>

  <div class="main">
    <h1>📅 Manage All Sessions</h1>

    <!-- Search Bar -->
    <form class="search-bar" method="GET">
      <input type="text" name="search" placeholder="Search sessions, tutors, learners, or status..." value="<?= htmlspecialchars($search); ?>">
      <button type="submit">Search</button>
    </form>

    <!-- Table -->
    <table>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Tutor</th>
        <th>Learner</th>
        <th>Start</th>
        <th>End</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
      <?php if ($sessions && $sessions->num_rows > 0): ?>
        <?php while ($s = $sessions->fetch_assoc()): ?>
          <tr>
            <td data-label="ID"><?= $s['id']; ?></td>
            <td data-label="Title"><?= htmlspecialchars($s['title']); ?></td>
            <td data-label="Tutor"><?= htmlspecialchars($s['tutor_first']." ".$s['tutor_last']); ?></td>
            <td data-label="Learner"><?= htmlspecialchars($s['learner_first']." ".$s['learner_last']); ?></td>
            <td data-label="Start"><?= date("d M Y H:i", strtotime($s['start_time'])); ?></td>
            <td data-label="End"><?= date("d M Y H:i", strtotime($s['end_time'])); ?></td>
            <td data-label="Status"><span class="status <?= strtolower($s['status']); ?>"><?= ucfirst($s['status']); ?></span></td>
            <td data-label="Action">
              <a href="?delete=<?= $s['id']; ?>" class="btn delete" onclick="return confirm('⚠️ Delete this session permanently?');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No sessions found.</td></tr>
      <?php endif; ?>
    </table>

    <a href="admin.php" class="back">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
