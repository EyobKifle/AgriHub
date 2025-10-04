<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}

require_once __DIR__ . '/../php/config.php'; // connects to agrihub DB

$userId = (int)$_SESSION['user_id'];

// Handle profile update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic info from users table
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    // Extended info from user_profiles table
    $bio = trim($_POST['bio'] ?? '');
    $farm_size = !empty($_POST['farm_size_hectares']) ? (float)$_POST['farm_size_hectares'] : null;
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
    $business_name = trim($_POST['business_name'] ?? '');
    $business_address = trim($_POST['business_address'] ?? '');

    // Handle avatar upload
    $avatar_path = $_POST['existing_avatar_url'] ?? ''; // Keep existing if no new upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = $userId . '_' . basename($_FILES['avatar']['name']);
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
            $avatar_path = 'uploads/avatars/' . $filename;
        }
    }

    // Update users table (name, phone, location)
    if ($name !== '' || $phone !== '' || $location !== '') {
        $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, location = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('sssi', $name, $phone, $location, $userId);
            $stmt->execute();
            $stmt->close();
            // Keep session name in sync
            if ($name !== '') {
                $_SESSION['name'] = $name;
            }
        }
    }

    // Upsert into user_profiles
    $stmt = $conn->prepare('INSERT INTO user_profiles (user_id, bio, farm_size_hectares, specialization, experience_years, business_name, business_address) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio), farm_size_hectares = VALUES(farm_size_hectares), specialization = VALUES(specialization), experience_years = VALUES(experience_years), business_name = VALUES(business_name), business_address = VALUES(business_address)');
    if ($stmt) {
        $stmt->bind_param('isdsiss', $userId, $bio, $farm_size, $specialization, $experience, $business_name, $business_address);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: User-Profile.php?updated=1');
    exit();
}

// Load user + profile
$user = [
    'name' => $_SESSION['name'] ?? 'User',
    'email' => $_SESSION['email'] ?? '',
    'phone' => '',
    'location' => '',
    'avatar_url' => ''
];
$profile = [
    'bio' => '',
    'farm_size_hectares' => '',
    'specialization' => '',
    'experience_years' => '',
    'business_name' => '',
    'business_address' => ''
];

$stmt = $conn->prepare('SELECT name, email, phone, location, avatar_url FROM users WHERE id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user = array_merge($user, $row);
    }
    $stmt->close();
}

$stmt = $conn->prepare('SELECT bio, farm_size_hectares, specialization, experience_years, business_name, business_address FROM user_profiles WHERE user_id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $profile = array_merge($profile, $row);
    }
    $stmt->close();
}

$name = $user['name'] ?: 'User';
$email = $user['email'] ?: ($_SESSION['email'] ?? '');
$phone = $user['phone'] ?: '';
$location = $user['location'] ?: '';
$bio = $profile['bio'] ?: '';
$avatar_url = $user['avatar_url'] ?: '';
$farm_size = $profile['farm_size_hectares'] ?: '';
$specialization = $profile['specialization'] ?: '';
$experience = $profile['experience_years'] ?: '';
$business_name = $profile['business_name'] ?: '';
$business_address = $profile['business_address'] ?: '';
$initial = strtoupper(mb_substr($name, 0, 1));

include '../HTML/User-Profile.html';
