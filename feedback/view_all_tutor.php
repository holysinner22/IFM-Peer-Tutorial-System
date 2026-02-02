<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') {
    die("Unauthorized");
}

$uid = $_SESSION['user_id'];

// Fetch all feedback for sessions taught by this tutor
$stmt = $conn->prepare("
    SELECT f.id, f.stars, f.comment, f.created_at,
           s.title,
           u.first_name AS student_first, u.last_name AS student_last
    FROM feedback f
    JOIN sessions s ON f.session_id = s.id
    JOIN users u ON f.rater_id = u.id
    WHERE s.tutor_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$feedbackRes = $stmt->get_result();

// Calculate stats
$feedbackArray = [];
$totalStars = 0;
$totalCount = 0;
while ($f = $feedbackRes->fetch_assoc()) {
    $feedbackArray[] = $f;
    $totalStars += $f['stars'];
    $totalCount++;
}
$averageRating = $totalCount > 0 ? round($totalStars / $totalCount, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Feedback - IFM Peer Tutoring</title>
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
      max-width: 1200px;
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

    .stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-item {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 25px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.15);
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-5px);
    }

    .stat-item .icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
      display: block;
    }

    .stat-item .number {
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--accent-yellow);
      display: block;
      margin-bottom: 5px;
    }

    .stat-item .label {
      font-size: 0.9rem;
      color: var(--light-gray);
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

    .session-title {
      font-weight: 600;
      color: var(--white);
      max-width: 250px;
    }

    .student-name {
      color: var(--white);
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .student-name::before {
      content: '👤';
      font-size: 1rem;
    }

    .stars {
      color: var(--accent-yellow);
      font-size: 1.2rem;
      letter-spacing: 3px;
      font-weight: 600;
    }

    .comment {
      font-style: italic;
      color: var(--light-gray);
      max-width: 300px;
      word-wrap: break-word;
    }

    .date-time {
      color: var(--light-gray);
      font-size: 0.85rem;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .date-time::before {
      content: '🕐';
      font-size: 0.9rem;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state::before {
      content: '⭐';
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

      .stats-bar {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      th, td {
        padding: 12px 15px;
        font-size: 0.85rem;
      }

      .session-title,
      .comment {
        max-width: 200px;
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

      .stats-bar {
        grid-template-columns: 1fr;
      }

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 12px;
      }

      /* Mobile card layout */
      .table-wrapper {
        overflow: visible;
      }

      table, thead, tbody, th, td, tr {
        display: block;
      }

      thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      tbody tr {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid rgba(255, 255, 255, 0.15);
      }

      td {
        padding: 10px 0;
        border: none;
        position: relative;
        padding-left: 50%;
      }

      td::before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 45%;
        font-weight: 600;
        color: var(--accent-yellow);
        font-size: 0.85rem;
      }

      .session-title,
      .comment {
        max-width: 100%;
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
      <h2>All Feedback Received</h2>
      <p class="subtitle">View feedback from students on your tutoring sessions</p>
    </div>

    <?php if (count($feedbackArray) > 0): ?>
      <div class="stats-bar">
        <div class="stat-item">
          <span class="icon">⭐</span>
          <span class="number"><?= $averageRating; ?></span>
          <span class="label">Average Rating</span>
        </div>
        <div class="stat-item">
          <span class="icon">📝</span>
          <span class="number"><?= $totalCount; ?></span>
          <span class="label">Total Feedback</span>
        </div>
        <div class="stat-item">
          <span class="icon">💬</span>
          <span class="number"><?= count(array_filter($feedbackArray, function($f) { return !empty($f['comment']); })); ?></span>
          <span class="label">With Comments</span>
        </div>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Session</th>
              <th>Student</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $index = 0;
            foreach($feedbackArray as $f): 
            ?>
              <tr style="animation-delay: <?= $index * 0.05; ?>s;">
                <td data-label="Session">
                  <div class="session-title"><?= htmlspecialchars($f['title']); ?></div>
                </td>
                <td data-label="Student">
                  <div class="student-name"><?= htmlspecialchars($f['student_first']." ".$f['student_last']); ?></div>
                </td>
                <td data-label="Rating">
                  <div class="stars"><?= str_repeat("⭐", $f['stars']); ?></div>
                </td>
                <td data-label="Comment">
                  <div class="comment"><?= htmlspecialchars($f['comment'] ?: 'No comment'); ?></div>
                </td>
                <td data-label="Date">
                  <div class="date-time"><?= date("d M Y, H:i", strtotime($f['created_at'])); ?></div>
                </td>
              </tr>
            <?php 
            $index++;
            endforeach; 
            ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        No feedback received yet. Keep teaching and students will share their feedback!
      </div>
    <?php endif; ?>

    <a href="../dashboard/tutor.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
