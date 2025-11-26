<?php
$host = "localhost";
$user = "root";
$pass = "";   // set your MySQL password
$db   = "peer_tutoring";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
