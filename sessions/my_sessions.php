<?php
session_start();
include("../config/db.php");

// Ensure student only
if ($_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

$student_id = $_SESSION['user_id'];
$msg = "";

// Handle cancellation
if (isset($_GET['cancel'])) {
    $session_id = intval($_GET['cancel']);

    $stmt = $conn->prepare("DELETE FROM session_registrations WHERE session_id=? AND student_id=?");
    $stmt->bind_param("ii", $session_id, $student_id);
    $stmt->execute();

    // Notify tutor
    $getTutor = $conn->prepare("SELECT tutor_id, title FROM sessions WHERE id=?");
    $getTutor->bind_param("i", $session_id);
    $getTutor->execute();
    $tutorData = $getTutor->get_result()->fetch_assoc();

    if ($tutorData) {
        $msgText = "⚠ A student cancelled registration for your session: " . $tutorData['title'];
        $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify->bind_param("is", $tutorData['tutor_id'], $msgText);
        $notify->execute();
    }

    $msg = "✅ You have cancelled your registration.";
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback_session'])) {
    $session_id = intval($_POST['feedback_session']);
    $stars = intval($_POST['stars']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO feedback (session_id, rater_id, stars, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $session_id, $student_id, $stars, $comment);
    if ($stmt->execute()) {
        $msg = "✅ Feedback submitted successfully!";
    } else {
        $msg = "❌ Failed to submit feedback.";
    }
}

// Filter: upcoming or past
$filter = $_GET['filter'] ?? 'upcoming'; // default upcoming
$now = date("Y-m-d H:i:s");

$query = "
    SELECT s.id, s.title, s.description, s.start_time, s.end_time, s.capacity, s.is_closed,
           (SELECT COUNT(*) FROM session_registrations WHERE session_id=s.id) AS registered,
           u.first_name, u.last_name 
    FROM session_registrations sr
    JOIN sessions s ON sr.session_id=s.id
    JOIN users u ON s.tutor_id=u.id
    WHERE sr.student_id=?
";
if ($filter === 'upcoming') {
    $query .= " AND s.start_time >= ? ORDER BY s.start_time ASC";
} else {
    $query .= " AND s.start_time < ? ORDER BY s.start_time DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $student_id, $now);
$stmt->execute();
$sessions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Sessions - IFM Peer Tutoring</title>
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
      max-width: 1000px;
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

    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      font-weight: 500;
      margin-bottom: 25px;
      text-align: center;
      animation: slideIn 0.5s ease-out;
    }

    .alert.success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
      color: var(--white);
    }

    .alert.error {
      background: rgba(231, 76, 60, 0.2);
      border-left: 4px solid var(--error-red);
      color: var(--white);
    }

    .filters {
      display: flex;
      gap: 15px;
      margin-bottom: 30px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .filters a {
      padding: 12px 24px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      font-size: 0.95rem;
    }

    .filters a.active {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border-color: var(--accent-yellow);
    }

    .filters a:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    .sessions-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .session-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 25px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      transition: all 0.3s ease;
      animation: slideIn 0.5s ease-out both;
    }

    .session-card:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateX(5px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .session-card h3 {
      color: var(--accent-yellow);
      font-size: 1.4rem;
      margin-bottom: 12px;
      font-weight: 600;
    }

    .session-card p {
      color: var(--light-gray);
      margin: 8px 0;
      line-height: 1.6;
    }

    .session-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin: 15px 0;
    }

    .info-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--white);
      font-size: 0.9rem;
    }

    .info-item strong {
      color: var(--accent-yellow);
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
    }

    .status-open {
      background: rgba(46, 204, 113, 0.2);
      color: var(--success-green);
      border: 1px solid var(--success-green);
    }

    .status-closed {
      background: rgba(231, 76, 60, 0.2);
      color: var(--error-red);
      border: 1px solid var(--error-red);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 20px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      margin-top: 15px;
    }

    .btn.cancel {
      background: rgba(231, 76, 60, 0.2);
      color: var(--error-red);
      border: 2px solid var(--error-red);
    }

    .btn.cancel:hover {
      background: var(--error-red);
      color: var(--white);
      transform: translateY(-2px);
    }

    .feedback-form {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .feedback-form label {
      display: block;
      margin-bottom: 8px;
      color: var(--white);
      font-weight: 500;
      font-size: 0.95rem;
    }

    .feedback-form select,
    .feedback-form textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      outline: none;
      margin-bottom: 15px;
    }

    .feedback-form textarea {
      resize: vertical;
      min-height: 100px;
    }

    .feedback-form select:focus,
    .feedback-form textarea:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }

    .feedback-form button {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .feedback-form button:hover {
      background: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state::before {
      content: '📅';
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

      .session-info {
        grid-template-columns: 1fr;
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
      <h2>My Registered Sessions</h2>
      <p class="subtitle">View and manage your registered tutoring sessions</p>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert <?= strpos($msg, '✅') !== false ? 'success' : 'error'; ?>">
        <?= htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters">
      <a href="?filter=upcoming" class="<?php echo $filter==='upcoming' ? 'active' : ''; ?>">📅 Upcoming</a>
      <a href="?filter=past" class="<?php echo $filter==='past' ? 'active' : ''; ?>">📜 Past</a>
    </div>

    <div class="sessions-list">
      <?php if ($sessions->num_rows > 0): ?>
        <?php 
        $index = 0;
        while ($s = $sessions->fetch_assoc()): 
        ?>
          <div class="session-card" style="animation-delay: <?= $index * 0.1; ?>s;">
            <h3><?php echo htmlspecialchars($s['title']); ?></h3>
            <?php if (!empty($s['description'])): ?>
              <p><?php echo htmlspecialchars($s['description']); ?></p>
            <?php endif; ?>
            
            <div class="session-info">
              <div class="info-item">
                <span>👤</span>
                <span><strong>Tutor:</strong> <?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?></span>
              </div>
              <div class="info-item">
                <span>📆</span>
                <span><strong>Start:</strong> <?php echo date("d M Y, H:i", strtotime($s['start_time'])); ?></span>
              </div>
              <div class="info-item">
                <span>⏰</span>
                <span><strong>End:</strong> <?php echo date("d M Y, H:i", strtotime($s['end_time'])); ?></span>
              </div>
              <div class="info-item">
                <span>👥</span>
                <span><strong>Capacity:</strong> <?php echo $s['registered']." / ".$s['capacity']; ?></span>
              </div>
              <div class="info-item">
                <span>Status:</span>
                <span class="status-badge <?php echo $s['is_closed'] ? 'status-closed' : 'status-open'; ?>">
                  <?php echo $s['is_closed'] ? "Closed" : "Open"; ?>
                </span>
              </div>
            </div>

            <?php if ($filter === 'upcoming'): ?>
              <a href="?cancel=<?php echo $s['id']; ?>&filter=upcoming" class="btn cancel" onclick="return confirm('⚠️ Are you sure you want to cancel your registration?');">🚫 Cancel Registration</a>
            <?php elseif ($filter === 'past'): ?>
              <!-- Check if feedback already submitted -->
              <?php
                $checkFeedback = $conn->prepare("SELECT id FROM feedback WHERE session_id=? AND rater_id=?");
                $checkFeedback->bind_param("ii", $s['id'], $student_id);
                $checkFeedback->execute();
                $hasFeedback = $checkFeedback->get_result()->num_rows > 0;
              ?>
              <?php if ($hasFeedback): ?>
                <p style="color: var(--success-green); margin-top: 15px; font-weight: 500;">✅ Feedback already submitted for this session</p>
              <?php else: ?>
                <form method="POST" class="feedback-form">
                  <input type="hidden" name="feedback_session" value="<?php echo $s['id']; ?>">
                  <label>⭐ Rating:</label>
                  <select name="stars" required>
                    <option value="">Select Rating</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Very Good</option>
                    <option value="3">⭐⭐⭐ Good</option>
                    <option value="2">⭐⭐ Fair</option>
                    <option value="1">⭐ Poor</option>
                  </select>
                  <label>💬 Comment:</label>
                  <textarea name="comment" rows="4" placeholder="Write your feedback about this session..."></textarea>
                  <button type="submit">✨ Submit Feedback</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php 
        $index++;
        endwhile; 
        ?>
      <?php else: ?>
        <div class="empty-state">
          No <?php echo $filter==='upcoming' ? 'upcoming' : 'past'; ?> sessions found.
        </div>
      <?php endif; ?>
    </div>

    <a href="../dashboard/student.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
