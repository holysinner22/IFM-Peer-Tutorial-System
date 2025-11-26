<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

$student_id = $_SESSION['user_id'];
$session_id = $_GET['id'] ?? null;

if (!$session_id) die("Session ID missing.");

// Verify student attended this session
$check = $conn->prepare("SELECT s.id, s.title, s.tutor_id 
                         FROM session_registrations r 
                         JOIN sessions s ON r.session_id=s.id 
                         WHERE r.student_id=? AND r.session_id=?");
$check->bind_param("ii", $student_id, $session_id);
$check->execute();
$res = $check->get_result();
$session = $res->fetch_assoc();

if (!$session) die("You did not attend this session.");

$message = "";

// Handle submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);
    $tutor_id = $session['tutor_id'];

    $stmt = $conn->prepare("INSERT INTO feedback (session_id, from_user_id, to_user_id, rating, comments) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iiiis", $session_id, $student_id, $tutor_id, $rating, $comments);
    $stmt->execute();

    $message = "<p class='success'>✅ Feedback submitted successfully.</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Leave Feedback</title>
  <style>
    body { font-family: Arial; background:#f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
    .box { background:#fff; padding:30px; border-radius:10px; width:450px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    label { font-weight:bold; display:block; margin-top:10px; }
    select, textarea { width:95%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:5px; }
    button { width:100%; padding:10px; background:#27ae60; border:none; color:white; border-radius:5px; cursor:pointer; margin-top:15px; }
    button:hover { background:#219150; }
    .success { color:green; }
  </style>
</head>
<body>
  <div class="box">
    <h2>📝 Feedback for <?php echo htmlspecialchars($session['title']); ?></h2>
    <?php if ($message) echo $message; ?>
    <form method="POST">
      <label>Rating</label>
      <select name="rating" required>
        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
        <option value="4">⭐⭐⭐⭐ Good</option>
        <option value="3">⭐⭐⭐ Average</option>
        <option value="2">⭐⭐ Poor</option>
        <option value="1">⭐ Very Poor</option>
      </select>

      <label>Comments</label>
      <textarea name="comments" placeholder="Your feedback..."></textarea>

      <button type="submit">Submit Feedback</button>
    </form>
    <br>
    <a href="sessions.php">⬅ Back to Sessions</a>
  </div>
</body>
</html>
