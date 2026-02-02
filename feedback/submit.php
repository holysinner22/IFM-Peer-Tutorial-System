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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Feedback - IFM Peer Tutoring</title>
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
      --success-green: #2ecc71;
      --error-red: #e74c3c;
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
      max-width: 800px;
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

    .feedback-item {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      animation: slideIn 0.5s ease-out both;
    }

    .session-title {
      color: var(--accent-yellow);
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .already-submitted {
      padding: 20px;
      border-radius: 12px;
      background: rgba(46, 204, 113, 0.15);
      border-left: 4px solid var(--success-green);
      color: var(--white);
      margin-bottom: 20px;
    }

    .already-submitted strong {
      color: var(--accent-yellow);
    }

    .stars-display {
      color: var(--accent-yellow);
      font-size: 1.2rem;
      margin: 10px 0;
    }

    form {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--white);
      font-weight: 500;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    input[type="number"],
    textarea {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      outline: none;
    }

    textarea {
      resize: vertical;
      min-height: 120px;
    }

    input::placeholder,
    textarea::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    input:focus,
    textarea:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }

    button {
      width: 100%;
      padding: 16px;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      margin-top: 10px;
      position: relative;
      overflow: hidden;
    }

    button::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    button:hover::before {
      width: 300px;
      height: 300px;
    }

    button:hover {
      background: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(253, 185, 19, 0.4);
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
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>Submit Feedback</h2>
      <p class="subtitle">Share your experience and help tutors improve</p>
    </div>

    <?php if ($res->num_rows > 0): ?>
      <?php 
      $index = 0;
      while ($s = $res->fetch_assoc()): 
        $check = $conn->prepare("SELECT stars, comment FROM feedback WHERE session_id=? AND rater_id=?");
        $check->bind_param("ii", $s['id'], $_SESSION['user_id']);
        $check->execute();
        $exists = $check->get_result();
      ?>
        <div class="feedback-item" style="animation-delay: <?= $index * 0.1; ?>s;">
          <?php if ($exists->num_rows > 0): 
            $f = $exists->fetch_assoc(); ?>
            <div class="already-submitted">
              <div class="session-title">✅ Feedback Submitted</div>
              <p><strong>Session:</strong> <?php echo htmlspecialchars($s['title']); ?></p>
              <p><strong>Date:</strong> <?php echo date("d M Y", strtotime($s['start_time'])); ?></p>
              <div class="stars-display">Your Rating: <?php echo str_repeat("⭐", $f['stars']); ?></div>
              <p><strong>Comment:</strong> <i><?php echo htmlspecialchars($f['comment']); ?></i></p>
            </div>
          <?php else: ?>
            <div class="session-title">📅 <?php echo htmlspecialchars($s['title']); ?></div>
            <p style="color: var(--light-gray); margin-bottom: 20px;">Session Date: <?php echo date("d M Y", strtotime($s['start_time'])); ?></p>
            <form method="POST">
              <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
              <div class="form-group">
                <label for="stars"><span>⭐</span> Rating (1-5):</label>
                <input type="number" id="stars" name="stars" min="1" max="5" required placeholder="Enter rating from 1 to 5">
              </div>
              
              <div class="form-group">
                <label for="comment"><span>💬</span> Comment:</label>
                <textarea id="comment" name="comment" placeholder="Write your feedback about this session..."></textarea>
              </div>
              
              <button type="submit">✨ Submit Feedback</button>
            </form>
          <?php endif; ?>
        </div>
      <?php 
      $index++;
      endwhile; 
      ?>
    <?php else: ?>
      <div class="empty-state">
        No completed sessions available for feedback.
      </div>
    <?php endif; ?>

    <a href="../dashboard/student.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
