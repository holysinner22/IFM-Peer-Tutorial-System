<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') die("Unauthorized");

$tutor_id = $_SESSION['user_id'];
$sessions = $conn->query("SELECT * FROM sessions WHERE tutor_id=$tutor_id ORDER BY start_time DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Sessions</title>
  <style>
    body { font-family: Arial; margin:20px; background:#f4f6f9; }
    h2 { color:#2c3e50; }
    table { width:100%; border-collapse: collapse; margin-top:20px; }
    table, th, td { border:1px solid #ccc; }
    th, td { padding:10px; text-align:center; }
    a { color:#3498db; text-decoration:none; }
  </style>
</head>
<body>
  <h2>📘 My Sessions</h2>
  <table>
    <tr><th>Title</th><th>Start</th><th>End</th><th>Capacity</th><th>Registered</th><th>Action</th></tr>
    <?php while($s = $sessions->fetch_assoc()): 
      $count = $conn->query("SELECT COUNT(*) as c FROM session_registrations WHERE session_id=".$s['id'])->fetch_assoc()['c'];
    ?>
      <tr>
        <td><?php echo htmlspecialchars($s['title']); ?></td>
        <td><?php echo date("d M Y H:i", strtotime($s['start_time'])); ?></td>
        <td><?php echo date("d M Y H:i", strtotime($s['end_time'])); ?></td>
        <td><?php echo $s['capacity']; ?></td>
        <td><?php echo $count; ?></td>
        <td><a href="view_registrations.php?id=<?php echo $s['id']; ?>">👥 View Students</a></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <br>
  <a href="../dashboard/tutor.php">⬅ Back</a>
</body>
</html>
