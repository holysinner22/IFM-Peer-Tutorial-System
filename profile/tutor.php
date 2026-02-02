<?php
session_start();
if ($_SESSION['role'] != 'tutor') { die("Unauthorized"); }
include("../config/db.php");

$uid = $_SESSION['user_id'];
$message = "";

// Fetch tutor data
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone,password_hash,profile_pic,year_of_study,degree_programme 
                        FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Fetch tutor subjects
$tutorSubjects = [];
$subRes = $conn->query("SELECT subject FROM tutor_subjects WHERE tutor_id=$uid");
while ($row = $subRes->fetch_assoc()) {
    $tutorSubjects[] = $row['subject'];
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fname   = trim($_POST['first_name']);
    $lname   = trim($_POST['last_name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $year    = intval($_POST['year_of_study']);
    $degree  = trim($_POST['degree_programme']);
    $subjects = $_POST['subjects'] ?? [];

    // Profile picture
    $picPath = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $targetDir = "../uploads/profile_pics/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newName = "tutor_" . $uid . "_" . time() . "." . strtolower($ext);
        $targetFile = $targetDir . $newName;

        if (in_array(strtolower($ext), ['jpg','jpeg','png'])) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $picPath = $newName;
            }
        }
    }

    // Update users table
    $upd = $conn->prepare("UPDATE users 
                           SET first_name=?, last_name=?, email=?, phone=?, profile_pic=?, year_of_study=?, degree_programme=? 
                           WHERE id=?");
    $upd->bind_param("sssssssi", $fname, $lname, $email, $phone, $picPath, $year, $degree, $uid);
    if ($upd->execute()) {
        // Update tutor_subjects table
        $conn->query("DELETE FROM tutor_subjects WHERE tutor_id=$uid");
        foreach ($subjects as $sub) {
            $sub = $conn->real_escape_string(trim($sub));
            if ($sub !== "") {
                $conn->query("INSERT INTO tutor_subjects (tutor_id,subject,year_of_study,degree_programme) 
                              VALUES ($uid,'$sub',$year,'$degree')");
            }
        }

        $message = "<div class='alert success'><i class=\"fas fa-check-circle\"></i> Profile updated successfully.</div>";
        $user['first_name'] = $fname;
        $user['last_name']  = $lname;
        $user['email']      = $email;
        $user['phone']      = $phone;
        $user['profile_pic'] = $picPath;
        $user['year_of_study'] = $year;
        $user['degree_programme'] = $degree;
        $tutorSubjects = $subjects;
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
        $message = "<div class='alert error'>❌ Current password is incorrect.</div>";
    } elseif ($new !== $confirm) {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> New passwords do not match.</div>";
    } elseif (strlen($new) < 6) {
        $message = "<div class='alert error'><i class=\"fas fa-times-circle\"></i> Password must be at least 6 characters.</div>";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid);
        if ($upd->execute()) {
            $message = "<div class='alert success'>✅ Password changed successfully.</div>";
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
  <title>Tutor Profile - IFM Peer Tutoring</title>
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
      max-width: 900px;
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

    .info-panel {
      text-align: center;
      margin-bottom: 35px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 30px;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .profile-pic-wrapper {
      position: relative;
      display: inline-block;
      margin-bottom: 15px;
    }

    .info-panel img {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      border: 4px solid var(--accent-yellow);
      object-fit: cover;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      transition: transform 0.3s ease;
    }

    .info-panel img:hover {
      transform: scale(1.05);
    }

    .info-panel h3 {
      margin: 15px 0 10px;
      color: var(--white);
      font-size: 1.5rem;
      font-weight: 600;
    }

    .info-panel .info-item {
      margin: 8px 0;
      color: var(--light-gray);
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .section {
      background: rgba(255, 255, 255, 0.1);
      padding: 28px;
      border-radius: 16px;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .section h3 {
      color: var(--accent-yellow);
      margin-bottom: 20px;
      font-size: 1.4rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--white);
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"],
    select {
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

    input:focus,
    select:focus {
      border-color: var(--accent-yellow);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(253, 185, 19, 0.2);
    }

    input[type="file"] {
      padding: 10px;
      cursor: pointer;
    }

    input[type="file"]::file-selector-button {
      background: var(--accent-yellow);
      color: var(--primary-blue);
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      margin-right: 10px;
      font-family: 'Poppins', sans-serif;
    }

    .image-preview {
      margin-top: 10px;
      text-align: center;
    }

    .image-preview img {
      max-width: 150px;
      max-height: 150px;
      border-radius: 12px;
      border: 2px solid var(--accent-yellow);
      margin-top: 10px;
    }

    button[type="submit"],
    .add-btn {
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

    button[type="submit"]::before,
    .add-btn::before {
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

    button[type="submit"]:hover::before,
    .add-btn:hover::before {
      width: 300px;
      height: 300px;
    }

    button[type="submit"]:hover,
    .add-btn:hover {
      background: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(253, 185, 19, 0.4);
    }

    .add-btn {
      background: rgba(253, 185, 19, 0.3);
      color: var(--white);
      border: 2px solid var(--accent-yellow);
      margin-top: 0;
    }

    .add-btn:hover {
      background: var(--accent-yellow);
      color: var(--primary-blue);
    }

    .subjects-list {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      gap: 10px;
    }

    .subjects-list input {
      flex: 1;
      margin: 0;
    }

    .subjects-list button {
      width: 45px;
      height: 45px;
      background: rgba(231, 76, 60, 0.3);
      color: var(--white);
      border: 2px solid var(--error-red);
      padding: 0;
      border-radius: 10px;
      cursor: pointer;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      flex-shrink: 0;
    }

    .subjects-list button:hover {
      background: var(--error-red);
      transform: scale(1.1);
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

      .section {
        padding: 20px;
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

      .info-panel img {
        width: 120px;
        height: 120px;
      }
    }
  </style>
  <script>
    function addSubjectField(value="") {
      const container = document.getElementById('subjects-container');
      const div = document.createElement('div');
      div.classList.add('subjects-list');
      div.innerHTML = `
        <input type="text" name="subjects[]" value="${value}" placeholder="Enter subject" required>
        <button type="button" onclick="this.parentElement.remove()" aria-label="Remove"><i class="fas fa-times"></i></button>
      `;
      container.appendChild(div);
    }

    // Image preview
    document.addEventListener('DOMContentLoaded', function() {
      const fileInput = document.querySelector('input[type="file"][name="profile_pic"]');
      if (fileInput) {
        fileInput.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              let preview = document.getElementById('image-preview');
              if (!preview) {
                preview = document.createElement('div');
                preview.id = 'image-preview';
                preview.className = 'image-preview';
                fileInput.parentElement.appendChild(preview);
              }
              preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(file);
          }
        });
      }
    });
  </script>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div class="logo-section">
        <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
      </div>
      <h2>Tutor Profile</h2>
      <p class="subtitle">Manage your profile information and settings</p>
    </div>

    <?php if ($message) echo $message; ?>

    <div class="info-panel">
      <div class="profile-pic-wrapper">
        <img src="../uploads/profile_pics/<?php echo $user['profile_pic'] ?: 'default.png'; ?>" alt="Profile Picture" id="current-profile-pic">
      </div>
      <h3><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h3>
      <div class="info-item"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']); ?></div>
      <div class="info-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
      <div class="info-item"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($user['degree_programme']); ?> - Year <?= htmlspecialchars($user['year_of_study']); ?></div>
      <div class="info-item"><i class="fas fa-book"></i> Subjects: <?= htmlspecialchars(implode(", ", $tutorSubjects) ?: "None listed"); ?></div>
    </div>

    <div class="section">
      <h3><span><i class="fas fa-edit"></i></span> Update Profile Information</h3>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="first_name"><span>👤</span> First Name</label>
          <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
        </div>

        <div class="form-group">
          <label for="last_name"><span><i class="fas fa-user"></i></span> Last Name</label>
          <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
        </div>

        <div class="form-group">
          <label for="email"><span><i class="fas fa-envelope"></i></span> Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
          <label for="phone"><span><i class="fas fa-phone"></i></span> Phone Number</label>
          <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="year_of_study"><span><i class="fas fa-calendar-alt"></i></span> Year of Study</label>
          <select id="year_of_study" name="year_of_study" required>
            <option value="">-- Select Year --</option>
            <?php for ($i=1;$i<=4;$i++): ?>
              <option value="<?= $i; ?>" <?= $user['year_of_study']==$i ? "selected" : ""; ?>>Year <?= $i; ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="degree_programme"><span><i class="fas fa-graduation-cap"></i></span> Degree Programme</label>
          <input type="text" id="degree_programme" name="degree_programme" value="<?= htmlspecialchars($user['degree_programme']); ?>" required>
        </div>

        <div class="form-group">
          <label><span><i class="fas fa-book"></i></span> Subjects You Can Teach</label>
          <div id="subjects-container"></div>
          <button type="button" class="add-btn" onclick="addSubjectField()">+ Add Subject</button>
        </div>

        <div class="form-group">
          <label for="profile_pic"><span><i class="fas fa-image"></i></span> Profile Picture</label>
          <input type="file" id="profile_pic" name="profile_pic" accept="image/png, image/jpeg, image/jpg">
        </div>

        <button type="submit" name="update_profile"><i class="fas fa-save"></i> Update Profile</button>
      </form>
    </div>

    <div class="section">
      <h3><span>🔑</span> Change Password</h3>
      <form method="POST">
        <div class="form-group">
          <label for="current_password"><span><i class="fas fa-lock"></i></span> Current Password</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
          <label for="new_password"><span><i class="fas fa-key"></i></span> New Password</label>
          <input type="password" id="new_password" name="new_password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password"><span><i class="fas fa-key"></i></span> Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" name="change_password"><i class="fas fa-lock"></i> Change Password</button>
      </form>
    </div>

    <a href="../dashboard/tutor.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Dashboard</a>
  </div>

  <script>
    const existingSubjects = <?php echo json_encode($tutorSubjects); ?>;
    existingSubjects.forEach(sub => addSubjectField(sub));
  </script>
</body>
</html>
