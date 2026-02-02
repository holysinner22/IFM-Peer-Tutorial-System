<?php
session_start();
if ($_SESSION['role'] != 'student') { 
    die("Unauthorized"); 
}

include("../config/db.php");

$student_id = $_SESSION['user_id'];

// Fetch all feedback submitted by this student
$stmt = $conn->prepare("
    SELECT f.id, f.stars, f.comment, f.created_at,
           t.first_name AS tutor_first, t.last_name AS tutor_last,
           s.title AS session_title
    FROM feedback f
    JOIN sessions s ON f.session_id = s.id
    JOIN users t ON s.tutor_id = t.id
    WHERE f.rater_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Feedback - IFM Peer Tutoring</title>
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
  }

  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
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

  .container {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    padding: 40px 35px;
    margin: 40px 20px;
    max-width: 1100px;
    width: 95%;
    border: 1px solid rgba(255, 255, 255, 0.25);
    animation: fadeInUp 0.8s ease-out;
    position: relative;
    z-index: 1;
  }

  .header-section {
    text-align: center;
    margin-bottom: 35px;
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
    padding: 2px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
    border-radius: 12px;
    overflow: hidden;
  }

  th, td {
    padding: 16px 18px;
    text-align: left;
    color: var(--white);
  }

  th {
    background: rgba(0, 0, 0, 0.3);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
  }

  tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
  }

  tbody tr:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: scale(1.01);
  }

  .stars {
    color: var(--accent-yellow);
    font-size: 1.3rem;
    letter-spacing: 3px;
    font-weight: 600;
  }

  .comment-cell {
    max-width: 300px;
    word-wrap: break-word;
    line-height: 1.6;
  }

  .back {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
    margin: 30px auto 0;
    background: rgba(255, 255, 255, 0.1);
    color: var(--white);
    font-weight: 600;
    padding: 14px 28px;
    border-radius: 12px;
    text-decoration: none;
    max-width: 280px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.95rem;
  }

  .back:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--accent-yellow);
    color: var(--accent-yellow);
    transform: translateY(-2px);
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

  .feedback-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid var(--accent-yellow);
    transition: all 0.3s ease;
  }

  .feedback-card:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
  }

  .feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
  }

  .tutor-name {
    font-weight: 600;
    color: var(--accent-yellow);
    font-size: 1.1rem;
  }

  .session-title {
    color: var(--light-gray);
    font-size: 0.95rem;
    font-style: italic;
  }

  .feedback-rating {
    margin: 10px 0;
  }

  .feedback-comment {
    color: var(--white);
    line-height: 1.6;
    margin: 15px 0;
    padding: 15px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    font-style: italic;
  }

  .feedback-date {
    color: var(--light-gray);
    font-size: 0.85rem;
    text-align: right;
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

  @media (max-width: 1024px) {
    .container {
      padding: 35px 25px;
    }

    table {
      font-size: 0.9rem;
    }

    th, td {
      padding: 12px 14px;
    }
  }

  @media (max-width: 768px) {
    .container {
      padding: 30px 20px;
      margin: 20px 15px;
    }

    h2 {
      font-size: 1.8rem;
    }

    .table-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      min-width: 700px;
    }

    .comment-cell {
      max-width: 200px;
    }
  }

  @media (max-width: 600px) {
    .logo-section img {
      max-width: 120px;
    }

    h2 {
      font-size: 1.6rem;
    }

    table, thead, tbody, th, td, tr { 
      display: block; 
    }

    thead tr {
      position: absolute;
      top: -9999px;
      left: -9999px;
    }

    tr {
      margin-bottom: 20px;
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 12px;
      border-left: 4px solid var(--accent-yellow);
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

    .stars {
      font-size: 1.1rem;
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
      <h2>My Submitted Feedback</h2>
      <p class="subtitle">View all feedback you've submitted for tutoring sessions</p>
    </div>

    <?php if ($res->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Tutor</th>
              <th>Session</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($f = $res->fetch_assoc()): ?>
              <tr>
                <td data-label="ID"><?= $f['id']; ?></td>
                <td data-label="Tutor">
                  <strong style="color: var(--accent-yellow);"><?= htmlspecialchars($f['tutor_first']." ".$f['tutor_last']); ?></strong>
                </td>
                <td data-label="Session"><?= htmlspecialchars($f['session_title']); ?></td>
                <td class="stars" data-label="Rating"><?= str_repeat("⭐", $f['stars']); ?></td>
                <td class="comment-cell" data-label="Comment"><?= htmlspecialchars($f['comment']); ?></td>
                <td data-label="Date"><?= date("d M Y, H:i", strtotime($f['created_at'])); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        You have not submitted any feedback yet.
      </div>
    <?php endif; ?>

    <a href="../dashboard/student.php" class="back"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
