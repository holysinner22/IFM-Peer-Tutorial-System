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
<title>My Feedback - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    color: #fff;
  }

  .container {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    padding: 35px;
    margin: 60px 20px;
    max-width: 950px;
    width: 95%;
    border: 1px solid rgba(255,255,255,0.2);
    animation: fadeIn 0.8s ease-in-out;
  }

  h2 {
    text-align: center;
    color: #FDB913;
    font-size: 1.8rem;
    margin-bottom: 25px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    overflow: hidden;
  }

  th, td {
    padding: 12px 15px;
    text-align: left;
    color: #fff;
  }

  th {
    background: rgba(0,0,0,0.3);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  tr:nth-child(even) {
    background: rgba(255,255,255,0.05);
  }

  tr:hover {
    background: rgba(255,255,255,0.2);
    transition: 0.3s;
  }

  .stars {
    color: #FDB913;
    font-size: 1.1rem;
    letter-spacing: 2px;
  }

  .back {
    display: block;
    text-align: center;
    margin-top: 25px;
    background: #FDB913;
    color: #002B7F;
    font-weight: 600;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    width: 240px;
    transition: 0.3s;
  }

  .back:hover {
    background: #fff;
    color: #002B7F;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(255,255,255,0.25);
  }

  p.no-feedback {
    text-align: center;
    color: #eee;
    margin-top: 25px;
    font-size: 1rem;
  }

  @media (max-width: 800px) {
    table, thead, tbody, th, td, tr { display: block; }
    th { display: none; }
    tr { margin-bottom: 20px; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; }
    td { padding: 8px 0; }
    td::before {
      content: attr(data-label);
      font-weight: bold;
      color: #FDB913;
      display: block;
      margin-bottom: 4px;
    }
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
</head>
<body>
  <div class="container">
    <h2>⭐ My Submitted Feedback</h2>

    <?php if ($res->num_rows > 0): ?>
      <table>
        <tr>
          <th>ID</th>
          <th>Tutor</th>
          <th>Session</th>
          <th>Rating</th>
          <th>Comment</th>
          <th>Date</th>
        </tr>
        <?php while ($f = $res->fetch_assoc()): ?>
          <tr>
            <td data-label="ID"><?= $f['id']; ?></td>
            <td data-label="Tutor"><?= htmlspecialchars($f['tutor_first']." ".$f['tutor_last']); ?></td>
            <td data-label="Session"><?= htmlspecialchars($f['session_title']); ?></td>
            <td class="stars" data-label="Rating"><?= str_repeat("⭐", $f['stars']); ?></td>
            <td data-label="Comment"><?= htmlspecialchars($f['comment']); ?></td>
            <td data-label="Date"><?= date("d M Y H:i", strtotime($f['created_at'])); ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p class="no-feedback">You have not submitted any feedback yet.</p>
    <?php endif; ?>

    <a href="../dashboard/student.php" class="back">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
