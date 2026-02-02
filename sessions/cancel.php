<?php
session_start();
include("../config/db.php");

$response = ["success" => false, "message" => ""];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'tutor'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (isset($_GET['id'])) {
    $sid = intval($_GET['id']);
    $uid = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Student cancels: learner_id must match; Tutor cancels: tutor_id must match
    if ($role == 'student') {
        $stmt = $conn->prepare("UPDATE sessions SET status='cancelled' 
                                WHERE id=? AND learner_id=? AND (status IN ('requested','accepted') OR status='' OR status IS NULL)");
        $stmt->bind_param("ii", $sid, $uid);
    } else {
        $stmt = $conn->prepare("UPDATE sessions SET status='cancelled' 
                                WHERE id=? AND tutor_id=? AND (status IN ('requested','accepted') OR status='' OR status IS NULL)");
        $stmt->bind_param("ii", $sid, $uid);
    }
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $info = $conn->query("SELECT learner_id, tutor_id, title FROM sessions WHERE id=$sid")->fetch_assoc();
        if ($info) {
            $title = $conn->real_escape_string($info['title']);
            if ($role == 'tutor') {
                $msg = "Your session '$title' has been cancelled by your tutor.";
                $conn->query("INSERT INTO notifications (user_id,message) VALUES ({$info['learner_id']},'$msg')");
            } else {
                $msg = "A student cancelled the session: '$title'.";
                $conn->query("INSERT INTO notifications (user_id,message) VALUES ({$info['tutor_id']},'$msg')");
            }
        }
        $response["success"] = true;
        $response["message"] = "Session cancelled successfully.";
        $response["newStatus"] = "cancelled";
    } else {
        $response["message"] = "Session cannot be cancelled (maybe already processed).";
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
