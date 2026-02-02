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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Feedback - IFM Peer Tutoring</title>
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
      max-width: 600px;
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
      font-size: 2rem;
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

    .session-info {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      text-align: center;
    }

    .session-info h3 {
      color: var(--accent-yellow);
      font-size: 1.2rem;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
      color: var(--white);
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 25px;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
    }

    .form-group {
      margin-bottom: 22px;
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

    select,
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

    select option {
      background: var(--primary-blue);
      color: var(--white);
    }

    select:focus,
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

    .back-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-align: center;
      margin: 25px auto 0;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      font-size: 0.9rem;
      max-width: 200px;
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
        font-size: 1.7rem;
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
        font-size: 1.5rem;
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
      <h2>Leave Feedback</h2>
      <p class="subtitle">Share your experience with this session</p>
    </div>

    <div class="session-info">
      <h3>📅 <?php echo htmlspecialchars($session['title']); ?></h3>
    </div>

    <?php if ($message) echo $message; ?>

    <form method="POST">
      <div class="form-group">
        <label for="rating"><span>⭐</span> Rating</label>
        <select id="rating" name="rating" required>
          <option value="">Select Rating</option>
          <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
          <option value="4">⭐⭐⭐⭐ Very Good</option>
          <option value="3">⭐⭐⭐ Good</option>
          <option value="2">⭐⭐ Fair</option>
          <option value="1">⭐ Poor</option>
        </select>
      </div>

      <div class="form-group">
        <label for="comments"><span>💬</span> Comments</label>
        <textarea id="comments" name="comments" placeholder="Write your feedback about this session..."></textarea>
      </div>

      <button type="submit">✨ Submit Feedback</button>
    </form>

    <a href="sessions.php" class="back-btn"><span>←</span> Back to Sessions</a>
  </div>
</body>
</html>
