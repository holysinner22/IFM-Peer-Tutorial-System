<?php
session_start();
include("../config/db.php");

// ✅ Current logged-in user ID
$currentUserId = $_SESSION['user_id'] ?? 0;

$programme = $conn->real_escape_string($_GET['programme']);
$year = intval($_GET['year']);
$subject = $conn->real_escape_string($_GET['subject']);

$result = $conn->query("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name
    FROM tutor_subjects ts
    JOIN users u ON ts.tutor_id = u.id
    WHERE ts.degree_programme='$programme'
      AND ts.year_of_study=$year
      AND ts.subject='$subject'
      AND u.status='active'
      AND u.id <> $currentUserId   -- 🚫 Exclude logged-in tutor from list
    ORDER BY name
");

$tutors = [];
while($row = $result->fetch_assoc()) {
    $tutors[] = $row;
}

echo json_encode($tutors);
