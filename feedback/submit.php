<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sid = intval($_POST['session_id']);
    $stars = intval($_POST['stars']);
    $comment = $conn->real_escape_string($_POST['comment']);

    // Prevent duplicate feedback
    $check = $conn->prepare("SELECT id FROM feedback WHERE session_id=? AND rater_id=?");
    $check->bind_param("ii", $sid, $_SESSION['user_id']);
    $check->execute();
    $exists = $check->get_result();

    if ($exists->num_rows > 0) {
        echo "<p style='color:red; font-weight:bold;'>⚠ You already submitted feedback for this session.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (session_id, rater_id, stars, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $sid, $_SESSION['user_id'], $stars, $comment);
        $stmt->execute();
        echo "<p style='color:green; font-weight:bold;'>✅ Feedback submitted successfully!</p>";
    }
}

// Get all completed sessions for this student
$res = $conn->query("SELECT * FROM sessions WHERE learner_id={$_SESSION['user_id']} AND status='completed'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Feedback</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background:#f4f6f9;
      margin:0; padding:0;
    }
    .container {
      width:70%;
      margin:40px auto;
      background:#fff;
      padding:30px;
      border-radius:10px;
      box-shadow:0 4px 10px rgba(0,0,0,0.1);
    }
    h1 {
      text-align:center;
      color:#2c3e50;
      margin-bottom:20px;
    }
    form {
      margin-bottom:25px;
      padding:20px;
      border:1px solid #ddd;
      border-radius:8px;
      background:#fafafa;
    }
    label {
      display:block;
      font-weight:bold;
      margin-bottom:8px;
      color:#2c3e50;
    }
    input[type="number"], textarea {
      width:100%;
      padding:10px;
      margin-bottom:15px;
      border:1px solid #ccc;
      border-radius:6px;
      font-size:14px;
    }
    textarea {
      resize: vertical;
      min-height:80px;
    }
    button {
      padding:10px 18px;
      background:#3498db;
      color:white;
      border:none;
      border-radius:6px;
      cursor:pointer;
      font-weight:bold;
    }
    button:hover {
      background:#2980b9;
    }
    .session-title {
      font-size:16px;
      font-weight:bold;
      margin-bottom:10px;
      color:#34495e;
    }
    .already-submitted {
      padding:12px;
      border-radius:6px;
      background:#ecf0f1;
      color:#555;
      font-style:italic;
      margin-bottom:20px;
    }
    .back-btn {
      display:block;
      text-align:center;
      margin-top:20px;
      padding:12px 20px;
      background:#2c3e50;
      color:white;
      text-decoration:none;
      border-radius:6px;
      font-weight:bold;
      width:200px;
      margin-left:auto;
      margin-right:auto;
    }
    .back-btn:hover {
      background:#1a252f;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>⭐ Submit Feedback</h1>
    <?php if ($res->num_rows > 0): ?>
      <?php while ($s = $res->fetch_assoc()): ?>
        <?php
          // Check if feedback already exists for this session
          $check = $conn->prepare("SELECT stars, comment FROM feedback WHERE session_id=? AND rater_id=?");
          $check->bind_param("ii", $s['id'], $_SESSION['user_id']);
          $check->execute();
          $exists = $check->get_result();
        ?>
        <?php if ($exists->num_rows > 0): 
          $f = $exists->fetch_assoc(); ?>
          <div class="already-submitted">
            ✅ You already submitted feedback for <b><?php echo htmlspecialchars($s['title']); ?></b>
            (<?php echo date("d M Y", strtotime($s['start_time'])); ?>).<br>
            Your Rating: <?php echo str_repeat("⭐", $f['stars']); ?><br>
            Comment: <i><?php echo htmlspecialchars($f['comment']); ?></i>
          </div>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
            <div class="session-title">
              Session: <?php echo htmlspecialchars($s['title']); ?> 
              <small>(<?php echo date("d M Y", strtotime($s['start_time'])); ?>)</small>
            </div>
            <label for="stars">Rating (1-5):</label>
            <input type="number" name="stars" min="1" max="5" required>
            
            <label for="comment">Comment:</label>
            <textarea name="comment" placeholder="Write your feedback..."></textarea>
            
            <button type="submit">Submit Feedback</button>
          </form>
        <?php endif; ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center; color:#777;">No completed sessions available for feedback.</p>
    <?php endif; ?>

    <!-- Back to Dashboard -->
    <a href="../dashboard/student.php" class="back-btn">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
