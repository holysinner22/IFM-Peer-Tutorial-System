<?php
session_start();
include("../config/db.php");

// Ensure tutor only
if ($_SESSION['role'] != 'tutor') {
    die("Unauthorized access.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    $cap   = intval($_POST['capacity']);
    $tutor_id = $_SESSION['user_id'];

    // Insert session
    $stmt = $conn->prepare("INSERT INTO sessions (tutor_id, title, description, start_time, end_time, capacity) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("issssi", $tutor_id, $title, $desc, $start, $end, $cap);
    $stmt->execute();

    // 🔔 Notify all active students about new session
    $res = $conn->query("SELECT id FROM users WHERE role='student' AND status='active'");
    while ($row = $res->fetch_assoc()) {
        $msg = "📢 New session available: $title";
        $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify->bind_param("is", $row['id'], $msg);
        $notify->execute();
    }

    $message = "<p class='success'>✅ Session created successfully.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Session - Tutor</title>
  <style>
    body { font-family: Arial; background:#f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
    .box { background:#fff; padding:30px; border-radius:10px; width:450px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    label { font-weight:bold; display:block; margin-top:10px; }
    input, textarea { width:95%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:5px; }
    button { width:100%; padding:10px; background:#27ae60; border:none; color:white; border-radius:5px; cursor:pointer; margin-top:15px; }
    button:hover { background:#219150; }
    .success { color:green; }
  </style>
</head>
<body>
  <div class="box">
    <h2>📅 Create Session</h2>
    <?php if ($message) echo $message; ?>
    <form method="POST">
      <label>Title</label>
      <input type="text" name="title" required>

      <label>Description</label>
      <textarea name="description"></textarea>

      <label>Start Time</label>
      <input type="datetime-local" name="start_time" required>

      <label>End Time</label>
      <input type="datetime-local" name="end_time" required>

      <label>Capacity</label>
      <input type="number" name="capacity" value="10" min="1" required>

      <button type="submit">Create Session</button>
    </form>
    <br>
    <a href="../dashboard/tutor.php">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
