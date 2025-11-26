<?php
if (!isset($_SESSION)) { session_start(); }
$role = $_SESSION['role'] ?? 'guest';
?>
<nav class="navbar">
  <a href="../dashboard/<?php echo $role; ?>.php">🏠 Home</a>

  <?php if ($role == 'student'): ?>
    <a href="../sessions/request.php">📅 Request Session</a>
    <a href="../sessions/view.php">👀 My Sessions</a>
    <a href="../feedback/submit.php">⭐ Feedback</a>
  <?php elseif ($role == 'tutor'): ?>
    <a href="../sessions/manage.php">📅 Manage Sessions</a>
    <a href="../sessions/view.php">👀 My Sessions</a>
  <?php elseif ($role == 'admin'): ?>
    <a href="../dashboard/admin.php">👥 Manage Users</a>
    <a href="../sessions/view.php">📅 All Sessions</a>
  <?php endif; ?>

  <a href="../notifications/notify.php" id="notifLink">
    🔔 Notifications <span id="notifCount" class="badge"></span>
  </a>

  <!-- Sound toggle -->
  <button id="soundToggle" class="sound-btn" title="Toggle notification sound">🔕</button>

  <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<style>
.navbar {
  background: #2c3e50;
  padding: 12px;
  display: flex;
  gap: 15px;
  align-items: center;
}
.navbar a, .sound-btn {
  color: #ecf0f1;
  text-decoration: none;
  font-weight: bold;
  padding: 6px 10px;
  position: relative;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 14px;
}
.navbar a:hover, .sound-btn:hover {
  background: #34495e;
  border-radius: 5px;
}
.badge {
  background: red;
  color: white;
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 50%;
  margin-left: 4px;
  display: none;
}
.sound-on { filter: drop-shadow(0 0 2px #2ecc71); }
</style>

<script>
// === Notification polling + sound ===
let prevCount = -1;
const badge = document.getElementById("notifCount");
const soundBtn = document.getElementById("soundToggle");

// Persisted preference
const SOUND_KEY = "notifSoundEnabled";
let soundEnabled = localStorage.getItem(SOUND_KEY) === "true";

// UI indicator
function updateSoundIcon() {
  soundBtn.textContent = soundEnabled ? "🔔" : "🔕";
  soundBtn.classList.toggle("sound-on", soundEnabled);
}
updateSoundIcon();

soundBtn.addEventListener("click", () => {
  soundEnabled = !soundEnabled;
  localStorage.setItem(SOUND_KEY, soundEnabled ? "true" : "false");
  // Attempt to resume AudioContext after user gesture (browser policy)
  if (soundEnabled && window._audioCtx && window._audioCtx.state === "suspended") {
    window._audioCtx.resume();
  }
  updateSoundIcon();
});

// Simple chime using Web Audio API (no audio files needed)
function playDing() {
  if (!soundEnabled) return;

  try {
    const ctx = (window._audioCtx ||= new (window.AudioContext || window.webkitAudioContext)());
    const o = ctx.createOscillator();
    const g = ctx.createGain();

    // Soft bell: quick up, quick decay
    o.type = "sine";
    o.frequency.setValueAtTime(880, ctx.currentTime);          // A5
    o.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.08); // brief pitch rise
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime + 0.03);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);

    o.connect(g).connect(ctx.destination);
    o.start();
    o.stop(ctx.currentTime + 0.4);
  } catch (e) {
    // Ignore audio errors silently
  }
}

function renderCount(count) {
  if (count > 0) {
    badge.textContent = count;
    badge.style.display = "inline-block";
  } else {
    badge.style.display = "none";
  }
}

function updateNotifCount() {
  fetch("../notifications/count.php", { cache: "no-store" })
    .then(res => res.json())
    .then(data => {
      const count = Number(data.count || 0);

      // First run sets baseline only
      if (prevCount >= 0 && count > prevCount) {
        playDing();
      }
      prevCount = count;

      renderCount(count);
    })
    .catch(() => {}); // ignore network errors in nav
}

// Poll every 5s
setInterval(updateNotifCount, 5000);
updateNotifCount();
</script>
