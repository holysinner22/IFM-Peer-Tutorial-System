<?php
session_start();
include("../config/db.php");

$uid = $_SESSION['user_id'] ?? 0;

if ($uid > 0) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=$uid AND is_read=0");
    $row = $res->fetch_assoc();
    echo json_encode(["count" => $row['cnt']]);
} else {
    echo json_encode(["count" => 0]);
}
?>
