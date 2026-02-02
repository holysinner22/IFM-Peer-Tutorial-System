<?php
session_start();
if ($_SESSION['role'] != 'student') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];

// Student info
$stmt = $conn->prepare("SELECT first_name,last_name,profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$studentName = $user ? $user['first_name']." ".$user['last_name'] : "Student";

// Mark all as read
if (isset($_GET['mark_read'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header("Location: student.php");
    exit;
}

// Stats
$upcomingCount = $conn->query("SELECT COUNT(*) as c FROM (
    SELECT s.id FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    WHERE r.student_id=$uid AND s.status='accepted' AND s.end_time >= NOW()
    UNION
    SELECT s.id FROM sessions s
    WHERE s.learner_id=$uid AND s.status='accepted' AND s.end_time >= NOW()
) as combined")->fetch_assoc()['c'];

$completedCount = $conn->query("SELECT COUNT(*) as c FROM (
    SELECT s.id FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    WHERE r.student_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
    UNION
    SELECT s.id FROM sessions s
    WHERE s.learner_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
) as combined")->fetch_assoc()['c'];

$feedbackCount = $conn->query("SELECT COUNT(*) as c FROM feedback WHERE rater_id=$uid")->fetch_assoc()['c'];
$noteCount = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Notifications
$notes = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");

// Feedback
$feedback = $conn->query("SELECT f.stars,f.comment,f.created_at,s.title,u.first_name,u.last_name
FROM feedback f
JOIN sessions s ON f.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE f.rater_id=$uid
ORDER BY f.created_at DESC
LIMIT 5");

// Upcoming Sessions
$sessions = $conn->query("SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
FROM session_registrations r
JOIN sessions s ON r.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE r.student_id=$uid AND s.status='accepted' AND s.end_time >= NOW()
UNION
SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
FROM sessions s
JOIN users u ON s.tutor_id=u.id
WHERE s.learner_id=$uid AND s.status='accepted' AND s.end_time >= NOW()
ORDER BY start_time ASC LIMIT 5");

// Completed Sessions
$completed = $conn->query("SELECT s.title,s.end_time,u.first_name,u.last_name
FROM session_registrations r
JOIN sessions s ON r.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE r.student_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
UNION
SELECT s.title,s.end_time,u.first_name,u.last_name
FROM sessions s
JOIN users u ON s.tutor_id=u.id
WHERE s.learner_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
ORDER BY end_time DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IFM Peer Tutoring - Student Dashboard</title>
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
    --light-gray: #F7F9FC;
    --dark-gray: #333;
    --text-gray: #666;
    --border-gray: #E0E0E0;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--light-gray);
    display: flex;
    min-height: 100vh;
    color: var(--dark-gray);
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

  .profile {
    text-align: center;
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .profile img {
    width: 90px; 
    height: 90px; 
    border-radius: 50%; 
    object-fit: cover;
    border: 3px solid var(--accent-yellow);
    box-shadow: 0 4px 15px rgba(253, 185, 19, 0.3);
    margin-bottom: 12px;
    transition: transform 0.3s ease;
  }

  .profile img:hover {
    transform: scale(1.05);
  }

  .profile h3 {
    margin-top: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--white);
  }

  .profile p {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 5px;
  }

  .nav-links {
    padding: 20px 15px;
    flex: 1;
  }

  .nav-links a {
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

  .nav-links a::before {
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

  .nav-links a:hover, .nav-links a.active {
    background: rgba(253, 185, 19, 0.15);
    color: var(--white);
    transform: translateX(5px);
  }

  .nav-links a:hover::before, .nav-links a.active::before {
    height: 60%;
  }

  .nav-links a:last-child {
    margin-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 20px;
    color: rgba(255, 255, 255, 0.7);
  }

  .nav-links a:last-child:hover {
    color: #FF6B6B;
    background: rgba(255, 107, 107, 0.1);
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
  .stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
  }

  .card {
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

  .card::before {
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

  .card:hover::before {
    transform: scaleX(1);
  }

  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 43, 127, 0.15);
  }

  .card-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
    display: block;
  }

  .card h2 {
    color: var(--primary-blue);
    font-size: 2.5rem;
    margin-bottom: 8px;
    font-weight: 700;
  }

  .card p {
    color: var(--text-gray);
    font-size: 0.95rem;
    font-weight: 500;
  }

  /* Content boxes */
  .box {
    background: var(--white);
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
    border: 1px solid var(--border-gray);
  }

  .box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
  }

  .box h3 {
    color: var(--primary-blue);
    font-size: 1.4rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .box-content {
    margin-top: 20px;
  }

  ul { 
    list-style: none; 
  }

  li { 
    margin: 15px 0; 
    padding: 15px;
    background: var(--light-gray);
    border-radius: 10px;
    border-left: 3px solid var(--accent-yellow);
    transition: all 0.3s ease;
  }

  li:hover {
    background: #f0f2f5;
    transform: translateX(5px);
  }

  li.unread {
    background: rgba(253, 185, 19, 0.1);
    border-left-color: var(--accent-yellow);
    font-weight: 600;
  }

  .session-title {
    color: var(--primary-blue);
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 8px;
  }

  .session-info {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin: 5px 0;
  }

  small { 
    color: var(--text-gray); 
    font-size: 0.85rem;
    display: block;
    margin-top: 8px;
  }

  .action-links {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid var(--border-gray);
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
  }

  .action-links a {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 8px;
    background: var(--light-gray);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .action-links a:hover {
    background: var(--accent-yellow);
    color: var(--primary-blue);
    transform: translateY(-2px);
  }

  .stars { 
    color: var(--accent-yellow); 
    font-size: 1.1rem;
    margin: 8px 0;
    display: block;
  }

  .comment { 
    color: var(--text-gray); 
    font-style: italic;
    margin: 8px 0;
    display: block;
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-gray);
  }

  .empty-state .fas {
    font-size: 3rem;
    display: block;
    margin-bottom: 15px;
    opacity: 0.5;
  }

  footer {
    text-align: center;
    padding: 25px;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    color: white;
    border-radius: 12px;
    margin-top: 40px;
    font-size: 0.9rem;
  }

  /* Responsive */
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
    .stats {
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
    .stats {
      grid-template-columns: 1fr;
    }
    .welcome-section h1 {
      font-size: 1.6rem;
    }
  }

  /* Mobile menu toggle */
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
      <h2>Peer Tutoring System</h2>
    </div>
    <div class="profile">
      <img src="../uploads/profile_pics/<?= !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png'; ?>" alt="Profile">
      <h3><?= htmlspecialchars($studentName); ?></h3>
      <p>Student</p>
    </div>
    <div class="nav-links">
      <a href="../sessions/request.php"><span><i class="fas fa-calendar-alt"></i></span> Request Session</a>
      <a href="../sessions/view.php"><span><i class="fas fa-list"></i></span> My Sessions</a>
      <a href="../feedback/view_all_student.php"><span><i class="fas fa-star"></i></span> My Feedback</a>
      <a href="../profile/student.php"><span><i class="fas fa-user"></i></span> My Profile</a>
      <a href="../notifications/view_all.php"><span><i class="fas fa-bell"></i></span> Notifications</a>
      <a href="../auth/logout.php"><span><i class="fas fa-sign-out-alt"></i></span> Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="welcome-section">
      <h1>Welcome back, <?= htmlspecialchars($studentName); ?>! <i class="fas fa-hand-wave"></i></h1>
      <p>Here's an overview of your tutoring activities</p>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="card">
        <span class="card-icon"><i class="fas fa-calendar-alt"></i></span>
        <h2><?= $upcomingCount; ?></h2>
        <p>Upcoming Sessions</p>
      </div>
      <div class="card">
        <span class="card-icon"><i class="fas fa-check-circle"></i></span>
        <h2><?= $completedCount; ?></h2>
        <p>Completed Sessions</p>
      </div>
      <div class="card">
        <span class="card-icon"><i class="fas fa-star"></i></span>
        <h2><?= $feedbackCount; ?></h2>
        <p>Feedback Submitted</p>
      </div>
      <div class="card">
        <span class="card-icon"><i class="fas fa-bell"></i></span>
        <h2><?= $noteCount; ?></h2>
        <p>New Notifications</p>
      </div>
    </div>

    <!-- Notifications -->
    <div class="box">
      <div class="box-header">
        <h3><span><i class="fas fa-bell"></i></span> Recent Notifications</h3>
      </div>
      <div class="box-content">
        <?php if ($notes->num_rows > 0): ?>
          <ul>
            <?php while($n = $notes->fetch_assoc()): ?>
              <li class="<?= !$n['is_read'] ? 'unread' : ''; ?>">
                <?= htmlspecialchars($n['message']); ?>
                <small><?= date("d M Y, H:i", strtotime($n['created_at'])); ?></small>
              </li>
            <?php endwhile; ?>
          </ul>
          <div class="action-links">
            <a href="?mark_read=1"><i class="fas fa-check"></i> Mark all as read</a>
            <a href="../notifications/view_all.php">View All →</a>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-inbox"></i> No notifications at the moment</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="box">
      <div class="box-header">
        <h3><span><i class="fas fa-calendar-alt"></i></span> Upcoming Sessions</h3>
      </div>
      <div class="box-content">
        <?php if ($sessions->num_rows > 0): ?>
          <ul>
            <?php while($s = $sessions->fetch_assoc()): ?>
              <li>
                <div class="session-title"><?= htmlspecialchars($s['title']); ?></div>
                <div class="session-info"><i class="fas fa-chalkboard-teacher"></i> Tutor: <?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?></div>
                <small><i class="fas fa-clock"></i> <?= date("d M Y, H:i", strtotime($s['start_time'])); ?> - <?= date("H:i", strtotime($s['end_time'])); ?></small>
              </li>
            <?php endwhile; ?>
          </ul>
          <div class="action-links">
            <a href="../sessions/view.php">View All Sessions →</a>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-inbox"></i> No upcoming sessions scheduled</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Completed Sessions -->
    <div class="box">
      <div class="box-header">
        <h3><span><i class="fas fa-check-circle"></i></span> Recently Completed Sessions</h3>
      </div>
      <div class="box-content">
        <?php if ($completed->num_rows > 0): ?>
          <ul>
            <?php while($c = $completed->fetch_assoc()): ?>
              <li>
                <div class="session-title"><?= htmlspecialchars($c['title']); ?></div>
                <div class="session-info"><i class="fas fa-chalkboard-teacher"></i> Tutor: <?= htmlspecialchars($c['first_name']." ".$c['last_name']); ?></div>
                <small><i class="fas fa-clock"></i> Completed: <?= date("d M Y, H:i", strtotime($c['end_time'])); ?></small>
              </li>
            <?php endwhile; ?>
          </ul>
          <div class="action-links">
            <a href="../sessions/completed_student.php">View All Completed →</a>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-inbox"></i> No completed sessions yet</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Feedback -->
    <div class="box">
      <div class="box-header">
        <h3><span><i class="fas fa-star"></i></span> My Recent Feedback</h3>
      </div>
      <div class="box-content">
      <?php if ($feedback->num_rows > 0): ?>
        <ul>
          <?php while($f = $feedback->fetch_assoc()): ?>
            <li>
              <div class="session-title"><?= htmlspecialchars($f['title']); ?></div>
              <div class="session-info"><i class="fas fa-chalkboard-teacher"></i> Tutor: <?= htmlspecialchars($f['first_name']." ".$f['last_name']); ?></div>
              <span class="stars"><?= str_repeat('<i class="fas fa-star"></i>', $f['stars']); ?></span>
              <span class="comment">"<?= htmlspecialchars($f['comment']); ?>"</span>
              <small><i class="fas fa-calendar-alt"></i> <?= date("d M Y, H:i", strtotime($f['created_at'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
        <div class="action-links">
          <a href="../feedback/view_all_student.php">View All Feedback →</a>
        </div>
      <?php else: ?>
        <p>You haven’t submitted any feedback yet.</p>
      <?php endif; ?>
      </div>
    </div>

    <footer>© <?= date("Y"); ?> IFM Peer Tutoring System. All rights reserved.</footer>
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
