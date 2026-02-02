<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Mark single notification as read
if (isset($_GET['read'])) {
    $nid = intval($_GET['read']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    header("Location: view_all.php"); 
    exit;
}

// Fetch all notifications
$res = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC");
$notifications = [];
$unreadCount = 0;
while ($n = $res->fetch_assoc()) {
    $notifications[] = $n;
    if (!$n['is_read']) $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Notifications - IFM Peer Tutoring</title>
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
      margin-bottom: 35px;
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

    .notifications-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .notification-item {
      padding: 20px 24px;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      animation: slideIn 0.5s ease-out both;
    }

    .notification-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: var(--accent-yellow);
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    .notification-item.unread {
      background: rgba(253, 185, 19, 0.15);
      border-left: 4px solid var(--accent-yellow);
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(253, 185, 19, 0.2);
    }

    .notification-item.unread::before {
      transform: scaleY(1);
    }

    .notification-item:hover {
      transform: translateX(5px);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .notification-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 15px;
      flex-wrap: wrap;
    }

    .notification-message {
      flex: 1;
      font-size: 1rem;
      line-height: 1.6;
      color: var(--white);
      min-width: 200px;
    }

    .notification-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
      flex-shrink: 0;
    }

    .notification-time {
      color: var(--light-gray);
      font-size: 0.85rem;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .notification-time::before {
      content: '🕐';
      font-size: 0.9rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
    }

    .btn:hover {
      background: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    .unread-badge {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--accent-yellow);
      margin-right: 10px;
      box-shadow: 0 0 8px var(--accent-yellow);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.7; transform: scale(1.2); }
    }

    .back {
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
      max-width: 280px;
    }

    .back:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--accent-yellow);
      color: var(--accent-yellow);
      transform: translateY(-2px);
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state::before {
      content: '🔔';
      font-size: 4rem;
      display: block;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .actions-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding: 15px 20px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .mark-all-btn {
      background: rgba(253, 185, 19, 0.2);
      color: var(--accent-yellow);
      border: 2px solid var(--accent-yellow);
    }

    .mark-all-btn:hover {
      background: var(--accent-yellow);
      color: var(--primary-blue);
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

      .notification-item {
        padding: 18px 20px;
      }

      .notification-content {
        flex-direction: column;
      }

      .notification-meta {
        align-items: flex-start;
        width: 100%;
      }
    }

    @media (max-width: 600px) {
      .logo-section img {
        max-width: 120px;
      }

      h2 {
        font-size: 1.6rem;
      }

      .notification-item {
        padding: 15px 18px;
      }

      .notification-message {
        font-size: 0.95rem;
      }

      .actions-bar {
        flex-direction: column;
        align-items: stretch;
      }

      .mark-all-btn {
        width: 100%;
        text-align: center;
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
      <h2>All Notifications</h2>
      <p class="subtitle">Stay updated with all your notifications</p>
    </div>

    <?php if (count($notifications) > 0): ?>
      <?php if ($unreadCount > 0): ?>
        <div class="actions-bar">
          <span style="color: var(--light-gray); font-size: 0.9rem;">
            <strong style="color: var(--accent-yellow);"><?= $unreadCount; ?></strong> unread notification<?= $unreadCount > 1 ? 's' : ''; ?>
          </span>
          <a href="../dashboard/<?= $_SESSION['role']; ?>.php?mark_read=1" class="btn mark-all-btn">✓ Mark All as Read</a>
        </div>
      <?php endif; ?>

      <ul class="notifications-list">
        <?php foreach ($notifications as $index => $n): ?>
          <li class="notification-item <?= $n['is_read'] ? '' : 'unread'; ?>" style="animation-delay: <?= $index * 0.1; ?>s;">
            <div class="notification-content">
              <div class="notification-message">
                <?php if (!$n['is_read']): ?>
                  <span class="unread-badge"></span>
                <?php endif; ?>
                <?= htmlspecialchars($n['message']); ?>
              </div>
              <div class="notification-meta">
                <span class="notification-time"><?= date("d M Y, H:i", strtotime($n['created_at'])); ?></span>
                <?php if (!$n['is_read']): ?>
                  <a class="btn" href="?read=<?= $n['id']; ?>">Mark as Read</a>
                <?php else: ?>
                  <span style="color: var(--light-gray); font-size: 0.8rem; opacity: 0.7;">✓ Read</span>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty-state">
        No notifications found.
      </div>
    <?php endif; ?>

    <a href="../dashboard/<?= $_SESSION['role']; ?>.php" class="back"><span>←</span> Back to Dashboard</a>
  </div>
</body>
</html>
