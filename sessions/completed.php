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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completed Sessions - IFM Peer Tutoring</title>
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
      --text-gray: #666;
      --border-gray: #E0E0E0;
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
      max-width: 1200px;
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

    .table-wrapper {
      overflow-x: auto;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    thead {
      background: rgba(253, 185, 19, 0.2);
    }

    th {
      padding: 18px 20px;
      text-align: left;
      color: var(--white);
      font-weight: 600;
      font-size: 0.95rem;
      letter-spacing: 0.5px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    th:first-child {
      text-align: center;
      width: 60px;
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
      animation: slideIn 0.5s ease-out both;
    }

    tbody tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.05);
    }

    tbody tr:hover {
      background: rgba(253, 185, 19, 0.15);
      transform: translateX(5px);
    }

    td {
      padding: 18px 20px;
      color: var(--white);
      font-size: 0.9rem;
    }

    td:first-child {
      text-align: center;
      color: var(--primary-blue);
      font-weight: 600;
    }

    .session-title {
      font-weight: 600;
      color: var(--white);
      max-width: 250px;
    }

    .session-desc {
      color: var(--light-gray);
      font-size: 0.85rem;
      max-width: 300px;
    }

    .session-time {
      color: var(--light-gray);
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(46, 204, 113, 0.2);
      color: var(--success-green);
      border: 1px solid var(--success-green);
    }

    .contact-info {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .contact-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.3s ease;
    }

    .contact-btn.email {
      background: rgba(142, 68, 173, 0.2);
      color: #9b59b6;
      border: 1px solid #9b59b6;
    }

    .contact-btn.email:hover {
      background: #9b59b6;
      color: var(--white);
      transform: translateY(-2px);
    }

    .contact-btn.call {
      background: rgba(22, 160, 133, 0.2);
      color: #16a085;
      border: 1px solid #16a085;
    }

    .contact-btn.call:hover {
      background: #16a085;
      color: var(--white);
      transform: translateY(-2px);
    }

    .contact-btn.whatsapp {
      background: rgba(37, 211, 102, 0.2);
      color: #25d366;
      border: 1px solid #25d366;
    }

    .contact-btn.whatsapp:hover {
      background: #25d366;
      color: var(--white);
      transform: translateY(-2px);
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state::before {
      content: '✅';
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

      th, td {
        padding: 12px 15px;
        font-size: 0.85rem;
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
      <h2>Completed Sessions</h2>
      <p class="subtitle">View all your completed tutoring sessions</p>
    </div>

    <?php if ($sessions->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Description</th>
              <th>Student</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
              <th>Contact</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $serial = 1;
            while($s = $sessions->fetch_assoc()): 
            ?>
              <tr style="animation-delay: <?= ($serial - 1) * 0.05; ?>s;">
                <td><strong><?= $serial++; ?></strong></td>
                <td>
                  <div class="session-title"><?= htmlspecialchars($s['title']); ?></div>
                </td>
                <td>
                  <div class="session-desc"><?= htmlspecialchars($s['description'] ?: '—'); ?></div>
                </td>
                <td><strong><?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?></strong></td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($s['start_time'])); ?></div>
                </td>
                <td>
                  <div class="session-time"><?= date("d M Y, H:i", strtotime($s['end_time'])); ?></div>
                </td>
                <td>
                  <span class="status-badge">Completed</span>
                </td>
                <td>
                  <div class="contact-info">
                    <a href="mailto:<?= htmlspecialchars($s['email']); ?>" class="contact-btn email">✉ Email</a>
                    <a href="tel:<?= htmlspecialchars($s['phone']); ?>" class="contact-btn call">📞 Call</a>
                    <?php
                      $rawPhone = preg_replace('/\D/', '', $s['phone']);
                      $waPhone = (strpos($rawPhone, "255") !== 0) ? "255".$rawPhone : $rawPhone;
                    ?>
                    <a href="https://wa.me/<?= $waPhone; ?>" target="_blank" class="contact-btn whatsapp">💬 WhatsApp</a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        No completed sessions yet.
      </div>
    <?php endif; ?>

    <a href="../dashboard/tutor.php" class="back-btn"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
