<?php
include("../config/db.php");

$token = $_GET['token'] ?? null;
$message = "";
$formVisible = false;

if ($token) {
    $formVisible = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? null;
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$token) {
        $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Invalid request. No token provided.</p>";
    } elseif ($password !== $confirm) {
        $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Passwords do not match.</p>";
        $formVisible = true;
    } elseif (strlen($password) < 8) {
        $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Password must be at least 8 characters long.</p>";
        $formVisible = true;
    } else {
        $newpass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash=?, verification_token=NULL WHERE verification_token=?");
        $stmt->bind_param("ss", $newpass, $token);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "<p class='success'><i class=\"fas fa-check-circle\"></i> Password updated successfully. 
                        <a href='login.php'>Login here</a></p>";
            $formVisible = false;
        } else {
            $message = "<p class='error'><i class=\"fas fa-times-circle\"></i> Failed to update password. The reset link may be invalid or expired.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - IFM Peer Tutoring</title>
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
      align-items: flex-start;
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

    .reset-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 480px;
      padding: 45px 40px;
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
      margin-bottom: 10px;
      color: var(--accent-yellow);
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .subtitle {
      color: var(--light-gray);
      font-size: 0.95rem;
      margin-bottom: 30px;
      font-weight: 400;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
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

    input {
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

    input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    input:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }

    button {
      width: 100%;
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      font-family: 'Poppins', sans-serif;
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

    .error {
      background: rgba(231, 76, 60, 0.2);
      border-left: 4px solid var(--error-red);
      color: var(--white);
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
    }

    .success {
      background: rgba(46, 204, 113, 0.2);
      border-left: 4px solid var(--success-green);
      color: var(--white);
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
    }

    .success a {
      color: var(--accent-yellow);
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-top: 10px;
      padding: 8px 16px;
      background: rgba(253, 185, 19, 0.2);
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .success a:hover {
      background: var(--accent-yellow);
      color: var(--primary-blue);
    }

    #pwError {
      color: var(--error-red);
      display: none;
      font-size: 0.9rem;
      margin-top: 8px;
      font-weight: 500;
      text-align: left;
      padding: 8px 12px;
      background: rgba(231, 76, 60, 0.1);
      border-radius: 8px;
      border-left: 3px solid var(--error-red);
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
      body {
        padding: 20px 15px;
        align-items: flex-start;
      }

      .reset-box {
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
  <div class="reset-box">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <h2><i class="fas fa-key"></i> Reset Password</h2>
    <p class="subtitle">Enter your new password below</p>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($formVisible): ?>
      <form method="POST" onsubmit="return validatePassword()">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
          <label for="password"><span>🔒</span> New Password</label>
          <input type="password" id="password" name="password" placeholder="Enter new password (min. 8 characters)" required minlength="8">
        </div>
        <div class="form-group">
          <label for="confirm_password"><span><i class="fas fa-lock"></i></span> Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
          <p id="pwError"><i class="fas fa-times-circle"></i> Passwords do not match</p>
        </div>
        <button type="submit">✨ Update Password</button>
      </form>
    <?php endif; ?>
  </div>

  <script>
    function validatePassword() {
      const pw = document.getElementById("password").value;
      const cpw = document.getElementById("confirm_password").value;
      const errorMsg = document.getElementById("pwError");

      if (pw.length < 8) {
        errorMsg.innerHTML = "<i class=\"fas fa-times-circle\"></i> Password must be at least 8 characters long";
        errorMsg.style.display = "block";
        return false;
      }

      if (pw !== cpw) {
        errorMsg.innerHTML = "<i class=\"fas fa-times-circle\"></i> Passwords do not match";
        errorMsg.style.display = "block";
        return false;
      }
      errorMsg.style.display = "none";
      return true;
    }

    // Real-time validation
    document.getElementById('confirm_password')?.addEventListener('input', function() {
      validatePassword();
    });
  </script>
</body>
</html>
