<?php
session_start();
include("../config/db.php");

$programme = $conn->real_escape_string($_GET['programme'] ?? '');
$year = intval($_GET['year'] ?? 0);
$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$subjects = [];

if ($programme && $year) {
    // Only subjects that have at least one active tutor, and (when logged in)
    // at least one tutor who is not the current user (so a student who is also
    // a tutor does not see subjects where they are the only tutor).
    $stmt = $conn->prepare("
        SELECT DISTINCT ts.subject
        FROM tutor_subjects ts
        JOIN users u ON ts.tutor_id = u.id AND u.status = 'active' AND u.id <> ?
        WHERE ts.degree_programme = ? AND ts.year_of_study = ?
        ORDER BY ts.subject
    ");
    $stmt->bind_param("isi", $currentUserId, $programme, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
}

header("Content-Type: application/json");
echo json_encode($subjects);
