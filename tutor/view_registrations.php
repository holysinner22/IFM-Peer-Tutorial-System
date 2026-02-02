<?php
session_start();
include("../config/db.php");

// Ensure tutor only
if ($_SESSION['role'] != 'tutor') {
    die("Unauthorized access.");
}

$tutor_id = $_SESSION['user_id'];
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    // Show a user-friendly error page instead of just dying
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Session ID Missing - IFM Peer Tutoring</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
          align-items: center;
          min-height: 100vh;
          position: relative;
          overflow-x: hidden;
        }
        .error-container {
          background: rgba(255, 255, 255, 0.15);
          backdrop-filter: blur(20px);
          border-radius: 24px;
          box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
          padding: 50px 40px;
          max-width: 500px;
          width: 100%;
          text-align: center;
          border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .error-icon {
          font-size: 5rem;
          margin-bottom: 20px;
        }
        h2 {
          color: var(--accent-yellow);
          font-size: 1.8rem;
          margin-bottom: 15px;
        }
        p {
          color: var(--light-gray);
          margin-bottom: 30px;
          line-height: 1.6;
        }
        .btn {
          display: inline-block;
          background: var(--accent-yellow);
          color: var(--primary-blue);
          padding: 14px 28px;
          border-radius: 12px;
          text-decoration: none;
          font-weight: 600;
          transition: all 0.3s ease;
        }
        .btn:hover {
          background: var(--white);
          transform: translateY(-2px);
          box-shadow: 0 8px 20px rgba(253, 185, 19, 0.4);
        }
      </style>
    </head>
    <body>
      <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h2>Session ID Missing</h2>
        <p>Please select a session from your sessions list to view registered students.</p>
        <a href="../sessions/view.php" class="btn">Go to My Sessions</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Verify session belongs to this tutor
$stmt = $conn->prepare("SELECT title, start_time, end_time FROM sessions WHERE id=? AND tutor_id=?");
$stmt->bind_param("ii", $session_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();

if (!$session) {
    die("Session not found or not owned by you.");
}

// Fetch registered students
$registrations = $conn->prepare("SELECT u.first_name, u.last_name, u.email, r.registered_at 
                                FROM session_registrations r
                                JOIN users u ON r.student_id = u.id
                                WHERE r.session_id=?");
$registrations->bind_param("i", $session_id);
$registrations->execute();
$students = $registrations->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registered Students - IFM Peer Tutoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
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

    .session-info {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 30px;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .session-info h3 {
      color: var(--accent-yellow);
      font-size: 1.3rem;
      margin-bottom: 15px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .session-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 15px;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--white);
      font-size: 0.95rem;
    }

    .detail-item strong {
      color: var(--accent-yellow);
      font-weight: 600;
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
      min-width: 600px;
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

    .student-name {
      font-weight: 600;
      color: var(--white);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .student-name .fas {
      margin-right: 8px;
      font-size: 1.1rem;
    }

    .student-email {
      color: var(--light-gray);
      word-break: break-word;
    }

    .registered-time {
      color: var(--light-gray);
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .registered-time .fas {
      margin-right: 6px;
      font-size: 0.9rem;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--light-gray);
      font-size: 1.1rem;
    }

    .empty-state .fas {
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

    .count-badge {
      display: inline-block;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-left: 10px;
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

      .session-details {
        grid-template-columns: 1fr;
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

      .session-info {
        padding: 20px;
      }

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 12px;
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
      <h2>Registered Students<span class="count-badge"><?= $students->num_rows; ?></span></h2>
      <p class="subtitle">View all students registered for this session</p>
    </div>

    <div class="session-info">
      <h3><span><i class="fas fa-calendar-alt"></i></span> Session Details</h3>
      <div class="session-details">
        <div class="detail-item">
          <strong>Title:</strong> <?= htmlspecialchars($session['title']); ?>
        </div>
        <div class="detail-item">
          <strong>🕐 Start:</strong> <?= date("d M Y, H:i", strtotime($session['start_time'])); ?>
        </div>
        <div class="detail-item">
          <strong><i class="fas fa-clock"></i> End:</strong> <?= date("d M Y, H:i", strtotime($session['end_time'])); ?>
        </div>
      </div>
    </div>

    <?php if ($students->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Email Address</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $index = 0;
            while ($s = $students->fetch_assoc()): 
            ?>
              <tr style="animation-delay: <?= $index * 0.1; ?>s;">
                <td>
                  <div class="student-name">
                    <?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?>
                  </div>
                </td>
                <td>
                  <div class="student-email"><?= htmlspecialchars($s['email']); ?></div>
                </td>
                <td>
                  <div class="registered-time"><i class="fas fa-clock"></i> <?= date("d M Y, H:i", strtotime($s['registered_at'])); ?></div>
                </td>
              </tr>
            <?php 
            $index++;
            endwhile; 
            ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        No students have registered for this session yet.
      </div>
    <?php endif; ?>

    <a href="../sessions/view.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to My Sessions</a>
  </div>
</body>
</html>
