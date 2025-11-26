<?php
session_start();
if ($_SESSION['role'] != 'tutor') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];

// Fetch completed sessions with student info
$stmt = $conn->prepare("
    SELECT s.id, s.title, s.description, s.start_time, s.end_time, 
           u.first_name, u.last_name, u.email, u.phone
    FROM sessions s
    JOIN users u ON s.learner_id = u.id
    WHERE s.tutor_id = ? AND s.status = 'completed'
    ORDER BY s.end_time DESC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$sessions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Completed Sessions</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
    .container { width:90%; margin:30px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    h1 { text-align:center; color:#2c3e50; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#2c3e50; color:white; }
    tr:hover { background:#f9f9f9; }
    .status { font-weight:bold; color:green; }
    .back-btn { display:block; width:220px; text-align:center; margin:25px auto 0; padding:12px; background:#2c3e50; color:white; border-radius:6px; text-decoration:none; font-weight:bold; transition:0.3s; }
    .back-btn:hover { background:#1a252f; }
    .contact a { margin-right:10px; text-decoration:none; font-size:14px; }
    .email { color:#8e44ad; }
    .call { color:#16a085; }
    .whatsapp { color:#25d366; }
  </style>
</head>
<body>
  <div class="container">
    <h1>✅ Completed Sessions</h1>

    <?php if ($sessions->num_rows > 0): ?>
      <table>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Description</th>
          <th>Student</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
          <th>Contact</th>
        </tr>
        <?php while($s = $sessions->fetch_assoc()): ?>
          <tr>
            <td><?= $s['id']; ?></td>
            <td><?= htmlspecialchars($s['title']); ?></td>
            <td><?= htmlspecialchars($s['description']); ?></td>
            <td><?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?></td>
            <td><?= date("d M Y H:i", strtotime($s['start_time'])); ?></td>
            <td><?= date("d M Y H:i", strtotime($s['end_time'])); ?></td>
            <td class="status">Completed</td>
            <td class="contact">
              <a href="mailto:<?= htmlspecialchars($s['email']); ?>" class="email">✉ Email</a>
              <a href="tel:<?= htmlspecialchars($s['phone']); ?>" class="call">📞 Call</a>
              <?php
                $rawPhone = preg_replace('/\D/', '', $s['phone']);
                $waPhone = (strpos($rawPhone, "255") !== 0) ? "255".$rawPhone : $rawPhone;
              ?>
              <a href="https://wa.me/<?= $waPhone; ?>" target="_blank" class="whatsapp">💬 WhatsApp</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p style="text-align:center;">No completed sessions yet.</p>
    <?php endif; ?>

    <a href="../dashboard/tutor.php" class="back-btn">⬅ Back to Tutor Dashboard</a>
  </div>
</body>
</html>
