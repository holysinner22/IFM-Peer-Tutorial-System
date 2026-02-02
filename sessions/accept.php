<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') { 
    die(json_encode(["success" => false, "message" => "Unauthorized"])); 
}

$response = ["success" => false, "message" => ""];

if (isset($_GET['id'])) {
    $sid = intval($_GET['id']);
    $tid = $_SESSION['user_id'];

    // Accept session ONLY if it's still requested (or empty status from legacy data)
    $stmt = $conn->prepare("UPDATE sessions SET status='accepted' WHERE id=? AND tutor_id=? AND (status='requested' OR status='' OR status IS NULL)");
    $stmt->bind_param("ii", $sid, $tid);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Notify student
        $info = $conn->query("SELECT learner_id, title FROM sessions WHERE id=$sid")->fetch_assoc();
        if ($info) {
            $studentId = $info['learner_id'];
            $title     = $conn->real_escape_string($info['title']);
            $msg = "Your session request '$title' has been accepted by your tutor.";
            $conn->query("INSERT INTO notifications (user_id,message) VALUES ($studentId,'$msg')");
        }
        $response["success"] = true;
        $response["message"] = "Session accepted successfully!";
    } else {
        $response["message"] = "Session could not be accepted (maybe already accepted/rejected).";
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
