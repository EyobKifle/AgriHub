<?php
session_start();
require_once __DIR__ . '/config.php';

function redirect($path) {
    header("Location: $path");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'signup') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('../HTML/guest/Signup.html');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || $location === '' || $password === '' || $confirm === '') {
        redirect('../HTML/guest/Signup.html?error=missing');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('../HTML/guest/Signup.html?error=invalid_email');
    }
    if ($password !== $confirm) {
        redirect('../HTML/guest/Signup.html?error=password_mismatch');
    }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    if (!$stmt) {
        redirect('../HTML/guest/Signup.html?error=server');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect('../HTML/guest/Signup.html?error=email_taken');
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, name, phone, location, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'active')");
    if (!$stmt) {
        redirect('../HTML/Signup.html?error=server');
    }
    $stmt->bind_param('sssss', $email, $hash, $name, $phone, $location);
    if ($stmt->execute()) {
        $stmt->close();
        redirect('../HTML/Login.html?registered=1');
    } else {
        $stmt->close();
        redirect('../HTML/Signup.html?error=server');
    }
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('../HTML/Login.html');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        redirect('../HTML/Login.html?error=missing');
    }

    $stmt = $conn->prepare('SELECT id, password_hash, name, role FROM users WHERE email = ? AND status = "active"');
    if (!$stmt) {
        redirect('../HTML/Login.html?error=server');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            // Update last login timestamp
            $uid = (int)$row['id'];
            $conn->query("UPDATE users SET last_login = NOW() WHERE id = $uid");

            $stmt->close();
            redirect('../HTML/user/User-Dashboard.php');
        }
    }

    $stmt->close();
    redirect('../HTML/guest/Login.html?error=invalid');
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    redirect('../HTML/guest/Login.html?logged_out=1');
}

http_response_code(400);
echo 'Invalid action';
