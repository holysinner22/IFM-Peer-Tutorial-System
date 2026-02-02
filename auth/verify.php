<?php
include("../config/db.php");

$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Use prepared statement
    $stmt = $conn->prepare("UPDATE users SET status='active', verification_token=NULL WHERE verification_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "<p class='success'><i class=\"fas fa-check-circle\"></i> Your account has been verified! <a href='login.php'>Login here</a></p>";
    } else {
        $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Invalid or expired verification link.</p>";
    }
} else {
    $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> No verification token provided.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification - IFM Peer Tutoring</title>
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
      --success-green: #2ecc71;
      --error-red: #e74c3c;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      padding: 40px 20px;
      color: var(--white);
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

    .verify-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      padding: 50px 40px;
      width: 100%;
      max-width: 500px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.25);
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      z-index: 1;
    }

    .logo-section {
      margin-bottom: 25px;
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
      font-size: 2rem;
      margin-bottom: 20px;
      color: var(--accent-yellow);
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
      color: var(--white);
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 1rem;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
    }

    .error {
      background: rgba(231, 76, 60, 0.2);
      border-left: 4px solid var(--error-red);
      color: var(--white);
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 1rem;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
    }

    a {
      color: var(--accent-yellow);
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-top: 15px;
      padding: 12px 24px;
      background: rgba(253, 185, 19, 0.2);
      border: 2px solid var(--accent-yellow);
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    a:hover {
      background: var(--accent-yellow);
      color: var(--primary-blue);
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

    @media (max-width: 480px) {
      .verify-box {
        padding: 35px 25px;
      }

      .logo-section img {
        max-width: 120px;
      }

      h2 {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>
  <div class="verify-box">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <h2><i class="fas fa-envelope"></i> Email Verification</h2>
    <?php echo $message; ?>
  </div>
</body>
</html>
