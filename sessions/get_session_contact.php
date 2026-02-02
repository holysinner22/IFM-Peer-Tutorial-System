<?php
session_start();
include("../config/db.php");

header("Content-Type: application/json");

$sid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$uid = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if (!$sid || !$uid) {
    echo json_encode(["success" => false]);
    exit;
}

// Tutor gets learner contact; student gets tutor contact
if ($role === 'tutor') {
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.phone
        FROM sessions s
        JOIN users u ON s.learner_id = u.id
        WHERE s.id = ? AND s.tutor_id = ? AND s.status IN ('accepted', 'completed')
    ");
    $stmt->bind_param("ii", $sid, $uid);
} elseif ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.phone
        FROM sessions s
        JOIN users u ON s.tutor_id = u.id
        WHERE s.id = ? AND s.learner_id = ? AND s.status IN ('accepted', 'completed')
    ");
    $stmt->bind_param("ii", $sid, $uid);
} else {
    echo json_encode(["success" => false]);
    exit;
}

$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    echo json_encode(["success" => false]);
    exit;
}

$name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
$email = htmlspecialchars($row['email'] ?? '');
$phone = htmlspecialchars($row['phone'] ?? '');

echo json_encode([
    "success" => true,
    "name" => $name,
    "email" => $email,
    "phone" => $phone
]);
