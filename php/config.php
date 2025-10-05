<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost"; // Or "127.0.0.1"
$username = "root";
$password = ""; // Default is empty for XAMPP
$dbname = "agrihub"; // Make sure this database exists

// Create connection using MySQLi (Object-Oriented style)
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop the script and show the error
    die("Connection failed: " . $conn->connect_error);
}

// If you see this message, your connection is successful!
// echo "Connected successfully to the '$dbname' database!";

// You can now include this file in other PHP scripts like this:
// require_once 'db_connect.php';
// And use the $conn variable to run queries.
?>
