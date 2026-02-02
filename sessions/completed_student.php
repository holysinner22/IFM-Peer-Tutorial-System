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

// ✅ Completed sessions (union of registered + learner_id)
$completed = $conn->query("
    SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
    FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    JOIN users u ON s.tutor_id=u.id
    WHERE r.student_id=$uid 
      AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
    UNION
    SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
    FROM sessions s
    JOIN users u ON s.tutor_id=u.id
    WHERE s.learner_id=$uid 
      AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
    ORDER BY end_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completed Sessions - IFM Peer Tutoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      --text-gray: #666;
      --border-gray: #E0E0E0;
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

    .container {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      padding: 40px 35px;
      max-width: 1000px;
      width: 100%;
      color: var(--white);
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
      text-align: center;
      color: var(--accent-yellow);
      font-size: 2.2rem;
      margin-bottom: 10px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .subtitle {
      text-align: center;
      color: var(--light-gray);
      font-size: 0.95rem;
      margin-bottom: 30px;
      font-weight: 400;
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
      min-width: 700px;
    }

    thead {
      background: rgba(253, 185, 19, 0.2);
    }

    th {
      padding: 18px 20px;
      text-align: left;
      color: var(--white);
      font-weight: 600;
      font-size: 0.95rem;
      letter-spacing: 0.5px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    th:first-child {
      text-align: center;
      width: 60px;
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
      animation: slideIn 0.5s ease-out both;
    }

    tbody tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.05);
    }

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
      color: var(--primary-blue);
      font-weight: 600;
    }

    .session-title {
      font-weight: 600;
      color: var(--white);
    }

    .session-time {
      color: var(--light-gray);
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state::before {
      content: '✅';
      font-size: 4rem;
      display: block;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .back-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-align: center;
      margin: 30px auto 0;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      padding: 14px 28px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      font-size: 0.95rem;
      max-width: 250px;
    }

    .back-btn:hover {
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

    @media (max-width: 768px) {
      .container {
        padding: 30px 25px;
        margin: 20px 15px;
      }

      h2 {
        font-size: 1.8rem;
      }

      th, td {
        padding: 12px 15px;
        font-size: 0.85rem;
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

      .container {
        padding: 25px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>Completed Sessions</h2>
      <p class="subtitle">View all your completed tutoring sessions</p>
    </div>

    <?php if ($completed->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Session Title</th>
              <th>Tutor</th>
              <th>Start Time</th>
              <th>End Time</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $serial = 1;
            while($c = $completed->fetch_assoc()): 
            ?>
              <tr style="animation-delay: <?= ($serial - 1) * 0.05; ?>s;">
                <td><strong><?= $serial++; ?></strong></td>
                <td>
                  <div class="session-title"><?= htmlspecialchars($c['title']); ?></div>
                </td>
                <td><strong><?= htmlspecialchars($c['first_name']." ".$c['last_name']); ?></strong></td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($c['start_time'])); ?></div>
                </td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($c['end_time'])); ?></div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        No completed sessions found.
      </div>
    <?php endif; ?>

    <a href="../dashboard/student.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
