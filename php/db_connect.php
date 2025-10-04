<?php
header("Content-Type: application/json");

$servername = "localhost"; // Or your db host
$username = "root"; // Your db username
$password = ""; // Your db password
$dbname = "agrihub_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Stop script and output error
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}
?>