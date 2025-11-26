<?php
session_start();
include("../config/db.php");

$programme = $conn->real_escape_string($_GET['programme'] ?? '');
$year = intval($_GET['year'] ?? 0);

$subjects = [];

if ($programme && $year) {
    $stmt = $conn->prepare("SELECT DISTINCT subject 
                            FROM tutor_subjects 
                            WHERE degree_programme=? AND year_of_study=?");
    $stmt->bind_param("si", $programme, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
}

header("Content-Type: application/json");
echo json_encode($subjects);
