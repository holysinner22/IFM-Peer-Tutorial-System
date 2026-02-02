<?php
session_start();
include("../config/db.php");

// Ensure student only
if ($_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

$student_id = $_SESSION['user_id'];

// Handle registration
if (isset($_GET['join'])) {
    $session_id = intval($_GET['join']);

    // Check if session is closed
    $checkClosed = $conn->prepare("SELECT is_closed, capacity FROM sessions WHERE id=?");
    $checkClosed->bind_param("i", $session_id);
    $checkClosed->execute();
    $resultClosed = $checkClosed->get_result()->fetch_assoc();

    if (!$resultClosed || $resultClosed['is_closed']) {
        $msg = "⚠ This session is closed for registration.";
    } else {
        // Check if already registered
        $check = $conn->prepare("SELECT * FROM session_registrations WHERE session_id=? AND student_id=?");
        $check->bind_param("ii", $session_id, $student_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO session_registrations (session_id, student_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $session_id, $student_id);
            $stmt->execute();

            // Notify tutor
            $getTutor = $conn->prepare("SELECT tutor_id, title FROM sessions WHERE id=?");
            $getTutor->bind_param("i", $session_id);
            $getTutor->execute();
            $tutorData = $getTutor->get_result()->fetch_assoc();

            if ($tutorData) {
                $msgText = "📥 A student registered for your session: " . $tutorData['title'];
                $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notify->bind_param("is", $tutorData['tutor_id'], $msgText);
                $notify->execute();
            }

            $msg = "✅ Registered successfully!";
        } else {
            $msg = "⚠ You are already registered for this session.";
        }
    }
}

// Fetch available sessions
$sessions = $conn->query("
    SELECT s.id, s.title, s.description, s.start_time, s.end_time, s.capacity, s.is_closed,
           (SELECT COUNT(*) FROM session_registrations WHERE session_id=s.id) AS registered,
           u.first_name, u.last_name 
    FROM sessions s
    JOIN users u ON s.tutor_id=u.id 
    ORDER BY s.start_time ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Available Sessions - IFM Peer Tutoring</title>
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

    .alert.warning {
      background: rgba(243, 156, 18, 0.2);
      border-left: 4px solid #f39c12;
      color: var(--white);
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

    .capacity-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 10px 0;
    }

    .capacity-progress {
      flex: 1;
      height: 8px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 4px;
      overflow: hidden;
      max-width: 200px;
    }

    .capacity-fill {
      height: 100%;
      background: var(--accent-yellow);
      border-radius: 4px;
      transition: width 0.3s ease;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      margin-top: 15px;
    }

    .btn.join {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: 2px solid var(--accent-yellow);
    }

    .btn.join:hover {
      background: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    .status-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 10px;
      font-weight: 600;
      margin-top: 15px;
    }

    .status-closed {
      background: rgba(231, 76, 60, 0.2);
      color: var(--error-red);
      border: 2px solid var(--error-red);
    }

    .status-full {
      background: rgba(243, 156, 18, 0.2);
      color: #f39c12;
      border: 2px solid #f39c12;
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
      <h2>Available Sessions</h2>
      <p class="subtitle">Browse and join available tutoring sessions</p>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert <?= strpos($msg, '✅') !== false ? 'success' : 'warning'; ?>">
        <?= htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <div class="sessions-list">
      <?php 
      $index = 0;
      $hasSessions = false;
      while ($s = $sessions->fetch_assoc()): 
        $hasSessions = true;
        $capacityPercent = $s['capacity'] > 0 ? ($s['registered'] / $s['capacity']) * 100 : 0;
        $isFull = $s['registered'] >= $s['capacity'];
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
          </div>

          <div class="capacity-bar">
            <span style="font-size: 0.85rem; color: var(--light-gray);">Registration:</span>
            <div class="capacity-progress">
              <div class="capacity-fill" style="width: <?= min($capacityPercent, 100); ?>%;"></div>
            </div>
          </div>

          <?php if ($s['is_closed']): ?>
            <div class="status-badge status-closed">❌ Session Closed for Registration</div>
          <?php elseif ($isFull): ?>
            <div class="status-badge status-full">❌ Session Full</div>
          <?php else: ?>
            <a href="?join=<?php echo $s['id']; ?>" class="btn join">➡ Join Session</a>
          <?php endif; ?>
        </div>
      <?php 
      $index++;
      endwhile; 
      ?>

      <?php if (!$hasSessions): ?>
        <div class="empty-state">
          No available sessions at the moment. Check back later!
        </div>
      <?php endif; ?>
    </div>

    <a href="../dashboard/student.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
