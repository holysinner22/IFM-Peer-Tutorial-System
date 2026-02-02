<?php
session_start();
include("../config/db.php");

// ✅ Allow both students and tutors to request
if (!in_array($_SESSION['role'], ['student', 'tutor'])) {
    die("Unauthorized");
}

$message = "";
$alertClass = "";
$uid = $_SESSION['user_id'];

// Fetch profile info (safe)
$profileRes = $conn->query("SELECT degree_programme, year_of_study FROM users WHERE id=$uid");
$profile = $profileRes && $profileRes->num_rows > 0 ? $profileRes->fetch_assoc() : ['degree_programme' => '', 'year_of_study' => ''];

$defaultProgramme = $profile['degree_programme'] ?? "";
$defaultYear = $profile['year_of_study'] ?? "";

$selectedProgramme = $_POST['programme'] ?? $defaultProgramme;
$selectedYear = $_POST['year'] ?? $defaultYear;
$selectedSubject = $_POST['subject'] ?? "";
$selectedTutor = $_POST['tutor'] ?? "";
$selectedDate = $_POST['date'] ?? "";
$selectedTime = $_POST['time'] ?? "";

// Handle request submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $programme = $conn->real_escape_string($_POST['programme']);
    $year = intval($_POST['year']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $tutor_id = intval($_POST['tutor']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $start = "$date $time";
    $end = date("Y-m-d H:i:s", strtotime($start) + 3600);

    if ($tutor_id == $uid) {
        $message = "⚠ You cannot request a session with yourself.";
        $alertClass = "warning";
    } else {
        $tutorRes = $conn->query("
            SELECT id, first_name, last_name 
            FROM users 
            WHERE id=$tutor_id 
              AND status='active'
              AND id IN (SELECT user_id FROM user_roles WHERE role='tutor')
            LIMIT 1
        ");
        $tutor = $tutorRes ? $tutorRes->fetch_assoc() : null;

        if ($tutor) {
            $sql = "INSERT INTO sessions (learner_id,tutor_id,title,start_time,end_time,status)
                    VALUES ($uid,$tutor_id,'$subject','$start','$end','requested')";
            if ($conn->query($sql)) {
                $conn->query("INSERT INTO notifications (user_id,message) 
                              VALUES ($tutor_id,'New session request for $subject')");
                $message = "✅ Session requested successfully with tutor <b>{$tutor['first_name']} {$tutor['last_name']}</b>!";
                $alertClass = "success";
            } else {
                $message = "❌ Error: " . $conn->error;
                $alertClass = "error";
            }
        } else {
            $message = "⚠ Selected tutor not available.";
            $alertClass = "warning";
        }
    }
}

// Fetch programmes
$programmes = $conn->query("SELECT DISTINCT degree_programme FROM tutor_subjects ORDER BY degree_programme");
$years = [1,2,3,4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Tutoring Session - IFM Peer Tutoring</title>
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
    --warning-yellow: #f1c40f;
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
    color: var(--primary-blue);
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
    padding: 50px 45px;
    margin: 40px 20px;
    max-width: 700px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: var(--white);
    animation: fadeInUp 0.8s ease-out;
    position: relative;
    z-index: 1;
  }

  .logo-section {
    text-align: center;
    margin-bottom: 30px;
    animation: fadeIn 0.8s ease-out 0.2s both;
  }

  .logo-section img {
    max-width: 160px;
    width: 100%;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
    margin-bottom: 15px;
  }

  h1 {
    text-align: center;
    color: var(--accent-yellow);
    font-size: 2rem;
    margin-bottom: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
  }

  .subtitle {
    text-align: center;
    color: var(--light-gray);
    font-size: 0.95rem;
    margin-bottom: 35px;
    font-weight: 400;
  }

  .form-group {
    margin-bottom: 22px;
  }

  label {
    display: block;
    font-weight: 600;
    color: var(--white);
    margin-bottom: 10px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  label::before {
    font-size: 1.1rem;
  }

  label[for="programme"]::before { content: '🎓'; }
  label[for="year"]::before { content: '📚'; }
  label[for="subject"]::before { content: '📖'; }
  label[for="tutor"]::before { content: '👨‍🏫'; }
  label[for="date"]::before { content: '📅'; }
  label[for="time"]::before { content: '🕐'; }

  select, input[type="date"], input[type="time"] {
    width: 100%;
    padding: 14px 18px;
    border-radius: 12px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.95rem;
    background: rgba(255, 255, 255, 0.95);
    color: var(--primary-blue);
    outline: none;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
  }

  select:focus, input:focus {
    background: var(--white);
    border-color: var(--accent-yellow);
    box-shadow: 0 0 0 4px rgba(253, 185, 19, 0.2);
    transform: translateY(-2px);
  }

  select option {
    padding: 10px;
    background: var(--white);
    color: var(--primary-blue);
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  button[type="submit"] {
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

  button[type="submit"]::before {
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

  button[type="submit"]:hover::before {
    width: 300px;
    height: 300px;
  }

  button[type="submit"]:hover {
    background: var(--white);
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
  }

  button[type="submit"]:active {
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

  .warning { 
    background: rgba(241, 196, 15, 0.15); 
    border-left-color: var(--warning-yellow);
    color: #fff6c2; 
  }

  .back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
    margin-top: 25px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--white);
    text-decoration: none;
    padding: 14px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
  }

  .back-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--accent-yellow);
    transform: translateY(-2px);
    color: var(--accent-yellow);
  }

  .loading {
    display: none;
    text-align: center;
    color: var(--light-gray);
    font-size: 0.9rem;
    margin-top: 10px;
    font-style: italic;
  }

  .loading.active {
    display: block;
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
      padding: 40px 30px;
      margin: 20px 15px;
    }

    .form-row {
      grid-template-columns: 1fr;
      gap: 22px;
    }
  }

  @media (max-width: 480px) {
    .container { 
      padding: 35px 25px; 
      margin: 15px 10px;
    }
    
    h1 { 
      font-size: 1.6rem; 
    }

    .logo-section img {
      max-width: 120px;
    }
  }
</style>

<script>
  async function loadSubjects() {
    const programme = document.getElementById('programme').value;
    const year = document.getElementById('year').value;
    const subjectDropdown = document.getElementById('subject');
    const tutorDropdown = document.getElementById('tutor');
    const loadingSubjects = document.getElementById('loading-subjects');
    const loadingTutors = document.getElementById('loading-tutors');

    subjectDropdown.innerHTML = "<option value=''>-- Select Subject --</option>";
    tutorDropdown.innerHTML = "<option value=''>-- Select Tutor --</option>";
    loadingTutors.classList.remove('active');

    if (programme && year) {
      loadingSubjects.classList.add('active');
      try {
        const response = await fetch(`load_subjects.php?programme=${encodeURIComponent(programme)}&year=${year}`);
        const data = await response.json();
        data.forEach(sub => {
          let option = document.createElement("option");
          option.value = sub;
          option.textContent = sub;
          if (sub === "<?php echo $selectedSubject; ?>") option.selected = true;
          subjectDropdown.appendChild(option);
        });
      } catch (error) {
        console.error('Error loading subjects:', error);
      } finally {
        loadingSubjects.classList.remove('active');
      }
    } else {
      loadingSubjects.classList.remove('active');
    }
  }

  async function loadTutors() {
    const programme = document.getElementById('programme').value;
    const year = document.getElementById('year').value;
    const subject = document.getElementById('subject').value;
    const tutorDropdown = document.getElementById('tutor');
    const loadingTutors = document.getElementById('loading-tutors');

    tutorDropdown.innerHTML = "<option value=''>-- Select Tutor --</option>";

    if (programme && year && subject) {
      loadingTutors.classList.add('active');
      try {
        const response = await fetch(`load_tutors.php?programme=${encodeURIComponent(programme)}&year=${year}&subject=${encodeURIComponent(subject)}`);
        const data = await response.json();
        if (data.length === 0) {
          tutorDropdown.innerHTML = "<option value=''>No tutors available for this subject</option>";
        } else {
          data.forEach(t => {
            if (t.id != "<?php echo $uid; ?>") {
              let option = document.createElement("option");
              option.value = t.id;
              option.textContent = t.name;
              if (t.id == "<?php echo $selectedTutor; ?>") option.selected = true;
              tutorDropdown.appendChild(option);
            }
          });
        }
      } catch (error) {
        console.error('Error loading tutors:', error);
        tutorDropdown.innerHTML = "<option value=''>Error loading tutors</option>";
      } finally {
        loadingTutors.classList.remove('active');
      }
    } else {
      loadingTutors.classList.remove('active');
    }
  }

  window.onload = function() {
    if ("<?php echo $selectedProgramme; ?>") {
      setTimeout(() => loadSubjects(), 100);
    }
    if ("<?php echo $selectedSubject; ?>") {
      setTimeout(() => loadTutors(), 300);
    }

    // Set minimum date to today
    const dateInput = document.getElementById('date');
    if (dateInput && !dateInput.value) {
      dateInput.min = new Date().toISOString().split('T')[0];
    }
  }

  // Form validation
  document.getElementById('requestForm')?.addEventListener('submit', function(e) {
    const tutor = document.getElementById('tutor').value;
    if (!tutor) {
      e.preventDefault();
      alert('Please select a tutor before submitting.');
      return false;
    }
  });
</script>
</head>
<body>

  <div class="container">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <h1>Request a Tutoring Session</h1>
    <p class="subtitle">Fill in the details below to request a session with a tutor</p>

    <?php if ($message): ?>
      <div class="alert <?= $alertClass; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="requestForm">
      <div class="form-group">
        <label for="programme">Degree Programme</label>
        <select name="programme" id="programme" required onchange="loadSubjects()">
          <option value="">-- Select Programme --</option>
          <?php if ($programmes): while($p = $programmes->fetch_assoc()): ?>
            <option value="<?= $p['degree_programme']; ?>" <?= $selectedProgramme==$p['degree_programme'] ? "selected" : ""; ?>>
              <?= $p['degree_programme']; ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
        <div class="loading" id="loading-subjects">Loading subjects...</div>
      </div>

      <div class="form-group">
        <label for="year">Year of Study</label>
        <select name="year" id="year" required onchange="loadSubjects()">
          <option value="">-- Select Year --</option>
          <?php foreach($years as $y): ?>
            <option value="<?= $y; ?>" <?= $selectedYear==$y ? "selected" : ""; ?>>Year <?= $y; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="subject">Subject</label>
        <select name="subject" id="subject" required onchange="loadTutors()">
          <option value="">-- Select Subject --</option>
        </select>
        <div class="loading" id="loading-tutors">Loading tutors...</div>
      </div>

      <div class="form-group">
        <label for="tutor">Available Tutors</label>
        <select name="tutor" id="tutor" required>
          <option value="">-- Select Tutor --</option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="date">Date</label>
          <input type="date" name="date" id="date" required value="<?= $selectedDate; ?>" min="<?= date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
          <label for="time">Start Time</label>
          <input type="time" name="time" id="time" required value="<?= $selectedTime; ?>">
        </div>
      </div>

      <button type="submit">📅 Request Session</button>
    </form>

    <?php if ($_SESSION['role'] == 'tutor'): ?>
      <a href="../dashboard/tutor.php" class="back-btn"><span>←</span> Back to Tutor Dashboard</a>
    <?php else: ?>
      <a href="../dashboard/student.php" class="back-btn"><span>←</span> Back to Student Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
