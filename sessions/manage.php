<?php
session_start();
include("../config/db.php");

// Tutor only
if ($_SESSION['role'] != 'tutor') {
    die("Unauthorized access.");
}
$tutor_id = $_SESSION['user_id'];

// Fetch tutor’s sessions with registration counts
$sessions = $conn->prepare("
    SELECT s.id, s.title, s.description, s.start_time, s.end_time, s.capacity, s.is_closed,
           (SELECT COUNT(*) FROM session_registrations WHERE session_id=s.id) AS registered
    FROM sessions s
    WHERE s.tutor_id=?
    ORDER BY s.start_time ASC
");
$sessions->bind_param("i", $tutor_id);
$sessions->execute();
$sessions = $sessions->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage My Sessions</title>
  <style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; background:#f4f6f9; margin:20px; }
    h2 { color:#2c3e50; margin-bottom:20px; text-align:center; }
    .card {
      background:#fff; padding:20px; margin:20px auto;
      border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.2s;
      max-width:900px;
    }
    .card:hover { transform: scale(1.01); }
    h3 { margin:0; color:#34495e; }
    .meta { font-size:14px; color:#555; margin:6px 0; }
    .status { font-weight:bold; }
    .status.open { color:green; }
    .status.closed { color:red; }
    .badge {
      display:inline-block; padding:4px 10px; border-radius:20px;
      font-size:13px; font-weight:bold; color:#fff; margin-left:5px;
    }
    .badge.open { background:#27ae60; }
    .badge.closed { background:#e74c3c; }
    .btn {
      display:inline-block; padding:8px 14px; margin:6px 4px 0 0;
      border-radius:6px; font-size:14px; text-decoration:none; font-weight:bold; color:#fff;
    }
    .btn.close { background:#e67e22; }
    .btn.remove { background:#c0392b; }
    .btn:hover { opacity:0.85; }
    table { width:100%; border-collapse: collapse; margin-top:15px; font-size:14px; }
    th, td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#2c3e50; color:#fff; }
    .back {
      display:inline-block; margin-top:20px; text-decoration:none;
      background:#3498db; color:white; padding:10px 16px; border-radius:8px;
    }
    .back:hover { background:#2980b9; }
  </style>
</head>
<body>
  <h2>📅 Manage My Sessions</h2>

  <?php if ($sessions->num_rows > 0): ?>
    <?php while ($s = $sessions->fetch_assoc()): ?>
      <div class="card">
        <h3><?php echo htmlspecialchars($s['title']); ?>
          <span class="badge <?php echo $s['is_closed'] ? 'closed' : 'open'; ?>">
            <?php echo $s['is_closed'] ? "Closed" : "Open"; ?>
          </span>
        </h3>
        <p><?php echo nl2br(htmlspecialchars($s['description'])); ?></p>
        <p class="meta">📆 <?php echo date("d M Y H:i", strtotime($s['start_time'])); ?> → <?php echo date("d M Y H:i", strtotime($s['end_time'])); ?></p>
        <p class="meta">👥 <strong><?php echo $s['registered']." / ".$s['capacity']; ?></strong> students registered</p>

        <?php if (!$s['is_closed']): ?>
          <a class="btn close" href="?close=<?php echo $s['id']; ?>" onclick="return confirm('Close registration for this session?');">Close Registration</a>
        <?php endif; ?>

        <!-- Registered students -->
        <?php
          $students = $conn->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone
            FROM session_registrations sr
            JOIN users u ON sr.student_id=u.id
            WHERE sr.session_id=?
          ");
          $students->bind_param("i", $s['id']);
          $students->execute();
          $students = $students->get_result();
        ?>

        <?php if ($students->num_rows > 0): ?>
          <table>
            <tr><th>Student</th><th>Email</th><th>Phone</th><th>Action</th></tr>
            <?php while ($st = $students->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($st['first_name']." ".$st['last_name']); ?></td>
                <td><?php echo htmlspecialchars($st['email']); ?></td>
                <td><?php echo htmlspecialchars($st['phone']); ?></td>
                <td><a class="btn remove" href="?session=<?php echo $s['id']; ?>&remove=<?php echo $st['id']; ?>" onclick="return confirm('Remove this student?');">Remove</a></td>
              </tr>
            <?php endwhile; ?>
          </table>
        <?php else: ?>
          <p><em>No students registered yet.</em></p>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No sessions created yet.</p>
  <?php endif; ?>

  <a class="back" href="../dashboard/tutor.php">⬅ Back to Dashboard</a>
</body>
</html>
