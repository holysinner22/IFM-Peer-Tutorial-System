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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Notifications - IFM Peer Tutoring</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #002B7F, #0044AA);
      margin: 0;
      padding: 40px 15px;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(12px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      padding: 30px;
      max-width: 700px;
      width: 100%;
      color: #fff;
      border: 1px solid rgba(255,255,255,0.2);
      animation: fadeIn 0.8s ease-in-out;
    }

    h2 {
      text-align: center;
      color: #FDB913;
      font-size: 1.6rem;
      margin-bottom: 25px;
    }

    ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    li {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 12px;
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 4px 10px rgba(0, 43, 127, 0.2);
      transition: all 0.3s;
    }

    li.unread {
      border-left: 5px solid #FDB913;
      background: rgba(255, 255, 255, 0.25);
      font-weight: 500;
    }

    li:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
    }

    small {
      display: block;
      margin-top: 5px;
      color: #ddd;
      font-size: 0.85rem;
    }

    a.btn {
      display: inline-block;
      background: #FDB913;
      color: #002B7F;
      padding: 6px 10px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.8rem;
      margin-top: 5px;
      transition: 0.3s;
    }

    a.btn:hover {
      background: #fff;
      color: #002B7F;
    }

    .back {
      display: block;
      text-align: center;
      margin-top: 30px;
      background: #FDB913;
      color: #002B7F;
      padding: 12px 15px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
    }

    .back:hover {
      background: #fff;
      color: #002B7F;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(255, 255, 255, 0.25);
    }

    p {
      text-align: center;
      color: #eee;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .container { padding: 25px 20px; }
      li { padding: 12px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🔔 All Notifications</h2>

    <?php if ($res->num_rows > 0): ?>
      <ul>
        <?php while ($n = $res->fetch_assoc()): ?>
          <li class="<?= $n['is_read'] ? '' : 'unread'; ?>">
            <?= htmlspecialchars($n['message']); ?>
            <small><?= date("d M Y H:i", strtotime($n['created_at'])); ?></small>
            <?php if (!$n['is_read']): ?>
              <a class="btn" href="?read=<?= $n['id']; ?>">Mark as Read</a>
            <?php endif; ?>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>No notifications found.</p>
    <?php endif; ?>

    <a href="../dashboard/<?= $_SESSION['role']; ?>.php" class="back">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
