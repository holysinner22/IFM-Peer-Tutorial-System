<?php
session_start();
include("../config/db.php");

$uid = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC");

echo "<h1>Notifications</h1>";
while ($n = $res->fetch_assoc()) {
    $status = $n['is_read'] ? "✔" : "🆕";
    echo "$status ".$n['message']." <small>(".$n['created_at'].")</small><br>";
    if (!$n['is_read']) {
        $conn->query("UPDATE notifications SET is_read=1 WHERE id=".$n['id']);
    }
}
echo "<br><a href='../dashboard/{$_SESSION['role']}.php'>⬅ Back to Dashboard</a>";
?>
