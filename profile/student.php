<?php
session_start();
if ($_SESSION['role'] != 'student') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];
$message = "";

// Fetch student data
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone,password_hash,profile_pic,year_of_study,degree_programme 
                        FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fname   = trim($_POST['first_name']);
    $lname   = trim($_POST['last_name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $year    = intval($_POST['year_of_study']);
    $degree  = trim($_POST['degree_programme']);

    // Handle profile picture upload
    $picPath = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $targetDir = "../uploads/profile_pics/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newName = "student_" . $uid . "_" . time() . "." . strtolower($ext);
        $targetFile = $targetDir . $newName;

        if (in_array(strtolower($ext), ['jpg','jpeg','png'])) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $picPath = $newName;
            }
        }
    }

    $upd = $conn->prepare("UPDATE users 
                           SET first_name=?, last_name=?, email=?, phone=?, profile_pic=?, year_of_study=?, degree_programme=? 
                           WHERE id=?");
    $upd->bind_param("sssssssi", $fname, $lname, $email, $phone, $picPath, $year, $degree, $uid);
    if ($upd->execute()) {
        $message = "<div class='alert success'><i class=\"fas fa-check-circle\"></i> Profile updated successfully.</div>";
        $user['first_name'] = $fname;
        $user['last_name']  = $lname;
        $user['email']      = $email;
        $user['phone']      = $phone;
        $user['profile_pic'] = $picPath;
        $user['year_of_study'] = $year;
        $user['degree_programme'] = $degree;
    } else {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> Failed to update profile.</div>";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password_hash'])) {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> Current password is incorrect.</div>";
    } elseif ($new !== $confirm) {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> New passwords do not match.</div>";
    } elseif (strlen($new) < 6) {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> Password must be at least 6 characters.</div>";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid);
        if ($upd->execute()) {
            $message = "<div class='alert success'><i class=\"fas fa-check-circle\"></i> Password changed successfully.</div>";
        } else {
            $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> Failed to change password.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - IFM Peer Tutoring</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
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

  .profile-box {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    padding: 50px 45px;
    margin: 40px 20px;
    max-width: 900px;
    width: 95%;
    border: 1px solid rgba(255, 255, 255, 0.25);
    animation: fadeInUp 0.8s ease-out;
    position: relative;
    z-index: 1;
  }

  .header-section {
    text-align: center;
    margin-bottom: 40px;
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

  h3 {
    color: var(--accent-yellow);
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 1.4rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .form-group {
    margin-bottom: 22px;
  }

  label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--white);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  label .fas {
    margin-right: 6px;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="file"],
  select {
    width: 100%;
    padding: 14px 18px;
    border-radius: 12px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.95);
    color: var(--primary-blue);
    font-size: 0.95rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
  }

  input[type="file"] {
    padding: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--white);
    cursor: pointer;
  }

  input[type="file"]::file-selector-button {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    background: var(--accent-yellow);
    color: var(--primary-blue);
    font-weight: 600;
    cursor: pointer;
    margin-right: 12px;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
  }

  input[type="file"]::file-selector-button:hover {
    background: var(--white);
  }

  input:focus, select:focus {
    outline: none;
    background: var(--white);
    border-color: var(--accent-yellow);
    box-shadow: 0 0 0 4px rgba(253, 185, 19, 0.2);
    transform: translateY(-2px);
  }

  button {
    width: 100%;
    padding: 16px;
    background: var(--accent-yellow);
    color: var(--primary-blue);
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 5px 20px rgba(253, 185, 19, 0.4);
    position: relative;
    overflow: hidden;
    margin-top: 10px;
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
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
  }

  button:active {
    transform: translateY(-1px);
  }

  .alert {
    padding: 16px 20px;
    border-radius: 12px;
    font-weight: 500;
    margin-bottom: 25px;
    font-size: 0.95rem;
    border-left: 4px solid;
    animation: slideIn 0.5s ease-out;
  }

  .success { 
    background: rgba(46, 204, 113, 0.15); 
    border-left-color: var(--success-green);
    color: #d4f8e8; 
  }

  .error { 
    background: rgba(231, 76, 60, 0.15); 
    border-left-color: var(--error-red);
    color: #ffd6d6; 
  }

  .info-panel {
    text-align: center;
    margin-bottom: 40px;
    padding: 35px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
  }

  .info-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-blue), var(--accent-yellow), var(--primary-blue));
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
  }

  @keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }

  .profile-picture-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
  }

  .info-panel img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid var(--accent-yellow);
    object-fit: cover;
    box-shadow: 0 8px 25px rgba(253, 185, 19, 0.4);
    transition: transform 0.3s ease;
  }

  .info-panel img:hover {
    transform: scale(1.05);
  }

  .info-panel h3 {
    margin: 15px 0 10px;
    font-size: 1.5rem;
    color: var(--white);
    font-weight: 700;
  }

  .info-panel .info-item {
    margin: 12px 0;
    color: var(--light-gray);
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }

  .info-panel .info-item strong {
    color: var(--accent-yellow);
    font-weight: 600;
  }

  .section {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    transition: all 0.3s ease;
  }

  .section:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
    font-weight: 600;
    padding: 14px 28px;
    border-radius: 12px;
    text-decoration: none;
    max-width: 280px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.95rem;
  }

  .back:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--accent-yellow);
    color: var(--accent-yellow);
    transform: translateY(-2px);
  }

  .preview-image {
    margin-top: 15px;
    text-align: center;
  }

  .preview-image img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 50%;
    border: 3px solid var(--accent-yellow);
    object-fit: cover;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
    .profile-box {
      padding: 35px 25px;
      margin: 20px 15px;
    }

    .form-row {
      grid-template-columns: 1fr;
      gap: 22px;
    }

    h2 {
      font-size: 1.8rem;
    }

    h3 {
      font-size: 1.2rem;
    }
  }

  @media (max-width: 600px) {
    .logo-section img {
      max-width: 120px;
    }

    h2 {
      font-size: 1.6rem;
    }

    .info-panel {
      padding: 25px 20px;
    }

    .info-panel img {
      width: 120px;
      height: 120px;
    }

    .section {
      padding: 25px 20px;
    }
  }
</style>
</head>
<body>

  <div class="profile-box">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>My Profile</h2>
      <p class="subtitle">Manage your account information and settings</p>
    </div>

    <?php if ($message) echo $message; ?>

    <div class="info-panel">
      <div class="profile-picture-wrapper">
        <img src="../uploads/profile_pics/<?php echo $user['profile_pic'] ?: 'default.png'; ?>" alt="Profile Picture" id="profilePreview">
      </div>
      <h3><?= htmlspecialchars($user['first_name']." ".$user['last_name']); ?></h3>
      <div class="info-item">
        <span>📧</span>
        <span><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></span>
      </div>
      <div class="info-item">
        <span><i class="fas fa-phone"></i></span>
        <span><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
      </div>
      <div class="info-item">
        <span><i class="fas fa-graduation-cap"></i></span>
        <span><strong><?= htmlspecialchars($user['degree_programme']); ?></strong> - Year <?= htmlspecialchars($user['year_of_study']); ?></span>
      </div>
    </div>

    <div class="section">
      <h3><span>📝</span> Update Profile Information</h3>
      <form method="POST" enctype="multipart/form-data" id="profileForm">
        <div class="form-row">
          <div class="form-group">
            <label for="first_name"><i class="fas fa-user"></i> First Name</label>
            <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email"><i class="fas fa-envelope"></i> Email</label>
          <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="year_of_study"><i class="fas fa-book"></i> Year of Study</label>
            <select name="year_of_study" id="year_of_study" required>
              <option value="">-- Select Year --</option>
              <?php for ($i=1;$i<=4;$i++): ?>
                <option value="<?= $i; ?>" <?= $user['year_of_study']==$i ? "selected" : ""; ?>>Year <?= $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="degree_programme"><i class="fas fa-graduation-cap"></i> Degree Programme</label>
            <input type="text" name="degree_programme" id="degree_programme" value="<?= htmlspecialchars($user['degree_programme']); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="profile_pic">Profile Picture</label>
          <input type="file" name="profile_pic" id="profile_pic" accept="image/png, image/jpeg, image/jpg" onchange="previewImage(this)">
          <div class="preview-image" id="imagePreview" style="display: none;">
            <p style="margin-bottom: 10px; color: var(--light-gray); font-size: 0.9rem;">Preview:</p>
            <img id="previewImg" src="" alt="Preview">
          </div>
        </div>

        <button type="submit" name="update_profile"><i class="fas fa-save"></i> Update Profile</button>
      </form>
    </div>

    <div class="section">
      <h3><span>🔑</span> Change Password</h3>
      <form method="POST" id="passwordForm">
        <div class="form-group">
          <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
          <input type="password" name="current_password" id="current_password" required>
        </div>

        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" required minlength="6">
        </div>

        <div class="form-group">
          <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
          <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
        </div>

        <button type="submit" name="change_password">🔒 Change Password</button>
      </form>
    </div>

    <a href="../dashboard/student.php" class="back"><span><i class="fas fa-arrow-left"></i></span> Back to Dashboard</a>
  </div>

  <script>
    // Image preview functionality
    function previewImage(input) {
      const preview = document.getElementById('imagePreview');
      const previewImg = document.getElementById('previewImg');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
      } else {
        preview.style.display = 'none';
      }
    }

    // Password confirmation validation
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('❌ New passwords do not match!');
        return false;
      }
      
      if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
      }
    });

    // Form validation feedback
    const inputs = document.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
      input.addEventListener('invalid', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--error-red)';
        this.style.boxShadow = '0 0 0 4px rgba(231, 76, 60, 0.2)';
      });

      input.addEventListener('input', function() {
        if (this.checkValidity()) {
          this.style.borderColor = 'var(--accent-yellow)';
          this.style.boxShadow = '0 0 0 4px rgba(253, 185, 19, 0.2)';
        }
      });
    });
  </script>

</body>
</html>
