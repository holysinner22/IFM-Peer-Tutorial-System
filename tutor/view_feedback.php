<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') die("Unauthorized");

$tutor_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT f.rating, f.comments, f.created_at, s.title, u.first_name, u.last_name
                        FROM feedback f
                        JOIN sessions s ON f.session_id=s.id
                        JOIN users u ON f.from_user_id=u.id
                        WHERE f.to_user_id=? 
                        ORDER BY f.created_at DESC");
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Feedback</title>
  <style>
    body { font-family: Arial; margin:20px; background:#f4f6f9; }
    h2 { color:#2c3e50; }
    .card { background:#fff; padding:20px; margin:10px 0; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .meta { font-size:14px; color:#555; }
  </style>
</head>
<body>
  <h2>⭐ My Feedback</h2>
  <?php if ($res->num_rows > 0): ?>
    <?php while ($f = $res->fetch_assoc()): ?>
      <div class="card">
        <p><b>Session:</b> <?php echo htmlspecialchars($f['title']); ?></p>
        <p><b>Student:</b> <?php echo htmlspecialchars($f['first_name']." ".$f['last_name']); ?></p>
        <p><b>Rating:</b> <?php echo str_repeat("⭐", $f['rating']); ?></p>
        <p><b>Comments:</b> <?php echo htmlspecialchars($f['comments']); ?></p>
        <p class="meta">📅 <?php echo date("d M Y H:i", strtotime($f['created_at'])); ?></p>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No feedback yet.</p>
  <?php endif; ?>
  <br>
  <a href="../dashboard/tutor.php">⬅ Back to Dashboard</a>
</body>
</html>
