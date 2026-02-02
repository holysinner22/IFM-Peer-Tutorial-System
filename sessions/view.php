<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { 
    die("Unauthorized"); 
}

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

$perPage = 6;

// Total count for pagination
if ($role == 'student') {
    $totalCount = (int) $conn->query("SELECT COUNT(*) AS c FROM sessions WHERE learner_id=$uid")->fetch_assoc()['c'];
} elseif ($role == 'tutor') {
    $totalCount = (int) $conn->query("SELECT COUNT(*) AS c FROM sessions WHERE tutor_id=$uid")->fetch_assoc()['c'];
} else {
    $totalCount = (int) $conn->query("SELECT COUNT(*) AS c FROM sessions")->fetch_assoc()['c'];
}

$totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
$page = isset($_GET['page']) ? max(1, min((int) $_GET['page'], $totalPages)) : 1;
$offset = ($page - 1) * $perPage;

$select = "s.*, u.first_name, u.last_name, u.email, u.phone";
$order = "ORDER BY s.start_time DESC";
$limit = "LIMIT $perPage OFFSET $offset";

if ($role == 'student') {
    $res = $conn->query("
        SELECT $select
        FROM sessions s 
        LEFT JOIN users u ON s.tutor_id=u.id 
        WHERE s.learner_id=$uid
        $order
        $limit
    ");
} elseif ($role == 'tutor') {
    $res = $conn->query("
        SELECT $select
        FROM sessions s 
        LEFT JOIN users u ON s.learner_id=u.id 
        WHERE s.tutor_id=$uid
        $order
        $limit
    ");
} else {
    $res = $conn->query("
        SELECT $select
        FROM sessions s 
        LEFT JOIN users u ON s.learner_id=u.id
        $order
        $limit
    ");
}

// Stats for summary bar
$allSessions = $role == 'student'
    ? $conn->query("SELECT status, COUNT(*) AS c FROM sessions WHERE learner_id=$uid GROUP BY status")
    : ($role == 'tutor'
        ? $conn->query("SELECT status, COUNT(*) AS c FROM sessions WHERE tutor_id=$uid GROUP BY status")
        : $conn->query("SELECT status, COUNT(*) AS c FROM sessions GROUP BY status"));
$stats = ['requested' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0, 'rejected' => 0];
while ($row = $allSessions->fetch_assoc()) {
    $status = $row['status'] ?: 'requested';
    if (isset($stats[$status])) $stats[$status] = (int)$row['c'];
}
$totalSessions = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Sessions - IFM Peer Tutoring</title>
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
    --warning-yellow: #f1c40f;
    --info-blue: #3498db;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, #0055CC 100%);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    color: var(--white);
    display: flex;
    justify-content: center;
    align-items: flex-start;
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
    width: 95%;
    max-width: 1200px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    padding: 40px 35px;
    margin: 40px 20px;
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

  h1 {
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

  /* 🔍 Search Bar */
  .search-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    margin-bottom: 30px;
    padding: 4px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
  }

  .search-bar:focus-within {
    border-color: var(--accent-yellow);
    background: rgba(255, 255, 255, 0.15);
  }

  .search-bar input {
    flex: 1;
    padding: 14px 18px;
    border: none;
    outline: none;
    background: transparent;
    color: var(--white);
    font-size: 0.95rem;
    font-family: 'Poppins', sans-serif;
  }

  .search-bar input::placeholder {
    color: rgba(255, 255, 255, 0.6);
  }

  .search-bar button {
    background: var(--error-red);
    border: none;
    padding: 14px 24px;
    color: var(--white);
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
  }

  .search-bar button:hover {
    background: #c0392b;
    transform: translateY(-2px);
  }

  .table-wrapper {
    overflow-x: auto;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.1);
    padding: 2px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
    border-radius: 12px;
    overflow: hidden;
  }

  th, td {
    padding: 16px 18px;
    text-align: left;
    color: var(--white);
  }

  th {
    background: rgba(0, 0, 0, 0.3);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
  }

  tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
  }

  tbody tr:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: scale(1.01);
  }

  .status {
    font-weight: 600;
    text-transform: capitalize;
    border-radius: 8px;
    padding: 6px 12px;
    display: inline-block;
    font-size: 0.85rem;
    letter-spacing: 0.3px;
    border: 1px solid;
  }

  .requested { 
    background: rgba(253, 185, 19, 0.2); 
    color: var(--accent-yellow);
    border-color: var(--accent-yellow);
  }
  .assigned { 
    background: rgba(52, 152, 219, 0.2); 
    color: var(--info-blue);
    border-color: var(--info-blue);
  }
  .accepted { 
    background: rgba(52, 152, 219, 0.2); 
    color: #74b9ff;
    border-color: #74b9ff;
  }
  .completed { 
    background: rgba(46, 204, 113, 0.2); 
    color: var(--success-green);
    border-color: var(--success-green);
  }
  .cancelled { 
    background: rgba(231, 76, 60, 0.2); 
    color: var(--error-red);
    border-color: var(--error-red);
  }
  .rejected { 
    background: rgba(231, 76, 60, 0.2); 
    color: var(--error-red);
    border-color: var(--error-red);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 4px 3px;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--white);
    background: var(--accent-yellow);
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-family: 'Poppins', sans-serif;
  }
  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    opacity: 0.9;
  }
  .btn.cancel { background: var(--error-red); }
  .btn.join { background: var(--success-green); }
  .btn.finish { background: #8e44ad; }
  .btn.feedback { background: var(--warning-yellow); color: var(--primary-blue); }
  .btn.email { background: #8e44ad; }
  .btn.call { background: #16a085; }
  .btn.whatsapp { background: #25d366; }

  .contact-info {
    font-size: 0.9rem;
    line-height: 1.8;
  }

  .contact-info strong {
    color: var(--accent-yellow);
    display: block;
    margin-bottom: 8px;
  }

  .contact-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
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
    font-weight: 600;
    padding: 14px 28px;
    border-radius: 12px;
    text-decoration: none;
    max-width: 280px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.95rem;
  }

  .back-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--accent-yellow);
    color: var(--accent-yellow);
    transform: translateY(-2px);
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

  .stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
  }

  .stat-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 18px 16px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.15);
    transition: all 0.3s ease;
  }

  .stat-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
  }

  .stat-item .num {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--accent-yellow);
    display: block;
    line-height: 1.2;
  }

  .stat-item .label {
    font-size: 0.85rem;
    color: var(--light-gray);
    margin-top: 4px;
  }

  .requested-on {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 24px;
    padding: 16px 0;
  }

  .pagination a,
  .pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    color: var(--white);
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
  }

  .pagination a:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--accent-yellow);
    color: var(--accent-yellow);
    transform: translateY(-2px);
  }

  .pagination span.current {
    background: var(--accent-yellow);
    color: var(--primary-blue);
    border-color: var(--accent-yellow);
    cursor: default;
  }

  .pagination span.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }

  .pagination .page-info {
    margin: 0 12px;
    font-size: 0.9rem;
    color: var(--light-gray);
  }

  /* Premium toast notification */
  #toast-container {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    pointer-events: none;
  }

  .toast {
    padding: 18px 28px;
    border-radius: 14px;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(12px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    animation: toastIn 0.4s ease-out;
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 90vw;
  }

  .toast.success {
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.95), rgba(39, 174, 96, 0.95));
    color: #fff;
    border-color: rgba(255, 255, 255, 0.5);
  }

  .toast.error {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.95), rgba(192, 57, 43, 0.95));
    color: #fff;
    border-color: rgba(255, 255, 255, 0.4);
  }

  .toast.warning {
    background: linear-gradient(135deg, rgba(241, 196, 15, 0.95), rgba(243, 156, 18, 0.95));
    color: #1a1a1a;
    border-color: rgba(255, 255, 255, 0.5);
  }

  .toast .toast-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
  }

  @keyframes toastIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .registered-count {
    font-size: 0.9rem;
    color: var(--accent-yellow);
    font-weight: 600;
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

  @media (max-width: 1024px) {
    .container {
      padding: 35px 25px;
    }

    table {
      font-size: 0.9rem;
    }

    th, td {
      padding: 12px 14px;
    }
  }

  @media (max-width: 768px) {
    .container {
      padding: 30px 20px;
      margin: 20px 15px;
    }

    h1 {
      font-size: 1.8rem;
    }

    .table-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      min-width: 720px;
    }
  }

  @media (max-width: 600px) {
    .logo-section img {
      max-width: 120px;
    }

    h1 {
      font-size: 1.6rem;
    }

    .search-bar {
      flex-direction: column;
    }

    .search-bar input,
    .search-bar button {
      width: 100%;
    }
  }
</style>
<script>
function showToast(message, type) {
  type = type || "success";
  var container = document.getElementById("toast-container");
  var toast = document.createElement("div");
  toast.className = "toast " + type;
  var icons = { success: "<i class=\"fas fa-check-circle\"></i>", error: "<i class=\"fas fa-times-circle\"></i>", warning: "<i class=\"fas fa-exclamation-triangle\"></i>" };
  toast.innerHTML = "<span class=\"toast-icon\">" + (icons[type] || icons.success) + "</span><span>" + message + "</span>";
  container.appendChild(toast);
  setTimeout(function() {
    toast.style.animation = "toastIn 0.3s ease-out reverse";
    setTimeout(function() { toast.remove(); }, 280);
  }, 3200);
}

function buildContactHtml(c) {
  var raw = (c.phone || "").replace(/\D/g, "");
  var wa = raw ? (raw.indexOf("255") !== 0 ? "255" + raw : raw) : "";
  var phoneLine = c.phone ? "<div><i class=\"fas fa-phone\"></i> " + escapeHtml(c.phone) + "</div>" : "";
  var actions = "<div class=\"contact-actions\">";
  actions += "<a href=\"mailto:" + escapeHtml(c.email) + "\" class=\"btn email\"><i class=\"fas fa-envelope\"></i> Email</a>";
  actions += " <a href=\"tel:" + escapeHtml(c.phone || "") + "\" class=\"btn call\"><i class=\"fas fa-phone\"></i> Call</a>";
  if (wa) actions += " <a href=\"https://wa.me/" + wa + "\" target=\"_blank\" class=\"btn whatsapp\"><i class=\"fas fa-comment\"></i> WhatsApp</a>";
  actions += "</div>";
  return "<div class=\"contact-info\"><strong><i class=\"fas fa-user\"></i> " + escapeHtml(c.name) + "</strong><div><i class=\"fas fa-envelope\"></i> " + escapeHtml(c.email) + "</div>" + phoneLine + actions + "</div>";
}

function escapeHtml(s) {
  if (!s) return "";
  var div = document.createElement("div");
  div.textContent = s;
  return div.innerHTML;
}

async function handleAction(action, id, el) {
  if (!confirm("Are you sure you want to proceed?")) return;
  try {
    var response = await fetch(action + ".php?id=" + id, { method: "GET" });
    var data = await response.json().catch(function() { return {}; });
    if (data.success) {
      var row = el.closest("tr");
      var statusCell = row.querySelector(".status");
      var contactTd = row.querySelector("td[data-label='Contact']");
      var actionsTd = row.querySelector("td[data-label='Actions']");

      if (action === "accept") {
        statusCell.textContent = "Accepted";
        statusCell.className = "status accepted";
        if (actionsTd) actionsTd.innerHTML = "<button class=\"btn finish\" onclick=\"handleAction('finish', " + id + ", this)\">Finish</button>";
        showToast(data.message || "Session accepted! Student details are now visible.", "success");
        var contactRes = await fetch("get_session_contact.php?id=" + id);
        var contactData = await contactRes.json().catch(function() { return {}; });
        if (contactData.success && contactTd) {
          contactTd.innerHTML = buildContactHtml(contactData);
        }
      } else if (action === "reject") {
        statusCell.textContent = "Rejected";
        statusCell.className = "status rejected";
        if (actionsTd) actionsTd.innerHTML = "";
        showToast(data.message || "Session rejected.", "warning");
      } else if (action === "finish") {
        statusCell.textContent = "Completed";
        statusCell.className = "status completed";
        if (actionsTd) actionsTd.innerHTML = "";
        showToast(data.message || "Session marked as completed.", "success");
      } else if (action === "cancel") {
        statusCell.textContent = "Cancelled";
        statusCell.className = "status cancelled";
        if (actionsTd) actionsTd.innerHTML = "";
        showToast(data.message || "Session cancelled.", "warning");
      }
    } else {
      showToast(data.message || "Something went wrong.", "error");
    }
  } catch (err) {
    showToast("Error: " + err.message, "error");
  }
}
</script>
</head>
<body>

<div id="toast-container" aria-live="polite"></div>

<div class="container">
  <div class="header-section">
    <div class="logo-section">
      <img src="../images/ifm.png" alt="IFM Logo" onerror="this.onerror=null; this.src='../images/ifm.jpg'; this.onerror=function(){this.onerror=null; this.src='../images/ifm.svg';};">
    </div>
    <h1>My Sessions</h1>
    <p class="subtitle">View and manage all your tutoring sessions</p>
  </div>

  <!-- Stats summary -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="num"><?= $totalSessions; ?></span>
      <span class="label">Total Sessions</span>
    </div>
    <div class="stat-item">
      <span class="num"><?= $stats['requested']; ?></span>
      <span class="label">Pending</span>
    </div>
    <div class="stat-item">
      <span class="num"><?= $stats['accepted']; ?></span>
      <span class="label">Accepted</span>
    </div>
    <div class="stat-item">
      <span class="num"><?= $stats['completed']; ?></span>
      <span class="label">Completed</span>
    </div>
    <div class="stat-item">
      <span class="num"><?= $stats['cancelled'] + $stats['rejected']; ?></span>
      <span class="label">Cancelled / Rejected</span>
    </div>
  </div>

  <!-- Search bar -->
  <div class="search-bar">
    <input type="text" id="sessionSearch" placeholder="Search by title or status...">
    <button onclick="document.getElementById('sessionSearch').value=''; filterSessions();">Clear</button>
  </div>

  <div class="table-wrapper">
    <table id="sessionsTable">
    <thead>
    <tr>
      <th>#</th>
      <th>Title</th>
      <th>Start</th>
      <th>Requested on</th>
      <th>Status</th>
      <?php if ($role == 'student'): ?><th>Tutor Details</th><?php elseif ($role == 'tutor'): ?><th>Student / Learner</th><?php elseif ($role == 'admin'): ?><th>Contact</th><?php endif; ?>
      <?php if ($role == 'student' || $role == 'tutor'): ?><th>Actions</th><?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php if ($res->num_rows > 0): ?>
      <?php
      $serial = $offset + 1;
      while ($s = $res->fetch_assoc()):
        $startTs = strtotime($s['start_time']);
      ?>
        <tr data-session-id="<?= $s['id']; ?>">
          <td data-label="#"><strong><?= $serial++; ?></strong></td>
          <td data-label="Title"><strong><?= htmlspecialchars($s['title']); ?></strong></td>
          <td data-label="Start"><?= date("d M Y, H:i", $startTs); ?></td>
          <td data-label="Requested on" class="requested-on"><?= date("d M Y", strtotime($s['created_at'])); ?></td>
          <td><span class="status <?= $s['status'] ?: 'requested'; ?>" data-label="Status"><?= ucfirst($s['status'] ?: 'requested'); ?></span></td>
          <td data-label="Contact">
            <?php if (($s['status'] == 'accepted' || $s['status'] == 'completed') && !empty($s['email'])): ?>
              <div class="contact-info">
                <strong>👤 <?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?></strong>
                <div>📧 <?= htmlspecialchars($s['email']); ?></div>
                <?php if (!empty($s['phone'])): ?>
                <div>📞 <?= htmlspecialchars($s['phone']); ?></div>
                <?php endif; ?>
                <?php
                  $rawPhone = preg_replace('/\D/', '', $s['phone'] ?? '');
                  $waPhone = $rawPhone ? ((strpos($rawPhone, "255") !== 0) ? "255".$rawPhone : $rawPhone) : '';
                ?>
                <?php if ($waPhone): ?>
                <div class="contact-actions">
                  <a href="mailto:<?= htmlspecialchars($s['email']); ?>" class="btn email"><i class="fas fa-envelope"></i> Email</a>
                  <a href="tel:<?= htmlspecialchars($s['phone']); ?>" class="btn call"><i class="fas fa-phone"></i> Call</a>
                  <a href="https://wa.me/<?= $waPhone; ?>" target="_blank" class="btn whatsapp"><i class="fas fa-comment"></i> WhatsApp</a>
                </div>
                <?php else: ?>
                <div class="contact-actions">
                  <a href="mailto:<?= htmlspecialchars($s['email']); ?>" class="btn email"><i class="fas fa-envelope"></i> Email</a>
                </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <em style="color: rgba(255, 255, 255, 0.6);"><?= ($role == 'tutor' || $role == 'admin') ? 'Learner details when accepted' : 'Details available once accepted'; ?></em>
            <?php endif; ?>
          </td>
          <?php if ($role == 'student' || $role == 'tutor'): ?>
          <td data-label="Actions">
            <?php if ($role == 'student'): ?>
              <?php if (in_array($s['status'], ['requested', 'accepted', '']) && ($s['status'] !== 'completed' && $s['status'] !== 'cancelled' && $s['status'] !== 'rejected')): ?>
                <button class="btn cancel" onclick="handleAction('cancel', <?= $s['id']; ?>, this)">Cancel</button>
              <?php endif; ?>
              <?php if ($s['status'] == 'completed'): ?>
                <a href="../feedback/submit.php?session_id=<?= $s['id']; ?>" class="btn feedback">Give Feedback</a>
              <?php endif; ?>
            <?php elseif ($role == 'tutor'): ?>
              <?php
              $showAcceptReject = ($s['status'] === 'requested' || $s['status'] === '' || $s['status'] === null);
              if ($showAcceptReject): ?>
                <button class="btn join" onclick="handleAction('accept', <?= $s['id']; ?>, this)">Accept</button>
                <button class="btn cancel" onclick="handleAction('reject', <?= $s['id']; ?>, this)">Reject</button>
              <?php elseif ($s['status'] === 'accepted'): ?>
                <button class="btn finish" onclick="handleAction('finish', <?= $s['id']; ?>, this)">Finish</button>
              <?php endif; ?>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="<?= ($role == 'student' || $role == 'tutor') ? 7 : 6; ?>" class="empty-state"><i class="fas fa-inbox"></i> No sessions found.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1; ?>">← Prev</a>
    <?php else: ?>
      <span class="disabled">← Prev</span>
    <?php endif; ?>

    <span class="page-info">Page <?= $page; ?> of <?= $totalPages; ?></span>

    <?php
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    if ($startPage > 1): ?>
      <a href="?page=1">1</a>
      <?php if ($startPage > 2): ?><span class="disabled">…</span><?php endif;
    endif;
    for ($i = $startPage; $i <= $endPage; $i++): ?>
      <?php if ($i == $page): ?>
        <span class="current"><?= $i; ?></span>
      <?php else: ?>
        <a href="?page=<?= $i; ?>"><?= $i; ?></a>
      <?php endif; ?>
    <?php endfor;
    if ($endPage < $totalPages): ?>
      <?php if ($endPage < $totalPages - 1): ?><span class="disabled">…</span><?php endif; ?>
      <a href="?page=<?= $totalPages; ?>"><?= $totalPages; ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1; ?>">Next →</a>
    <?php else: ?>
      <span class="disabled">Next →</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($role == 'student'): ?>
    <a href="../dashboard/student.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Student Dashboard</a>
  <?php elseif ($role == 'tutor'): ?>
    <a href="../dashboard/tutor.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Tutor Dashboard</a>
  <?php elseif ($role == 'admin'): ?>
    <a href="../dashboard/admin.php" class="back-btn"><span><i class="fas fa-arrow-left"></i></span> Back to Admin Dashboard</a>
  <?php endif; ?>
</div>

<script>
// Client-side table filter
function filterSessions() {
  const input = document.getElementById("sessionSearch");
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll("#sessionsTable tr:not(:first-child)");

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
}
document.getElementById("sessionSearch").addEventListener("input", filterSessions);
</script>

</body>
</html>
