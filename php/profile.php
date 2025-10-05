<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGet($conn, $userId);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePost($conn, $userId);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

function handleGet($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT
            u.name, u.email, u.phone, u.location, u.avatar_url,
            p.bio, p.farm_size_hectares, p.specialization, p.experience_years,
            p.business_name, p.business_address, p.language_preference,
            p.pref_email_notifications, p.pref_theme
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'User profile not found.']);
        exit();
    }

    // Set defaults for any null values from the LEFT JOIN
    $profile['bio'] = $profile['bio'] ?? '';
    $profile['farm_size_hectares'] = $profile['farm_size_hectares'] ?? '';
    $profile['specialization'] = $profile['specialization'] ?? '';
    $profile['experience_years'] = $profile['experience_years'] ?? '';
    $profile['business_name'] = $profile['business_name'] ?? '';
    $profile['business_address'] = $profile['business_address'] ?? '';
    $profile['language_preference'] = $profile['language_preference'] ?? 'en';
    $profile['pref_email_notifications'] = (bool)($profile['pref_email_notifications'] ?? true);
    $profile['pref_theme'] = $profile['pref_theme'] ?? 'light';

    echo json_encode($profile);
}

function handlePost($conn, $userId) {
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
    $lang_pref = trim($_POST['language_preference'] ?? 'en');
    $email_notif = isset($_POST['pref_email_notifications']) ? 1 : 0;
    $theme_pref = trim($_POST['pref_theme'] ?? 'light');

    // Handle avatar upload
    $avatar_path = $_POST['existing_avatar_url'] ?? ''; // Keep existing if no new upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = $userId . '_' . uniqid() . '_' . basename($_FILES['avatar']['name']);
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
            $avatar_path = 'uploads/avatars/' . $filename;
        }
    }

    $conn->begin_transaction();
    try {
        // Update users table
        $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, location = ?, avatar_url = ? WHERE id = ?');
        $stmt->bind_param('ssssi', $name, $phone, $location, $avatar_path, $userId);
        $stmt->execute();
        $stmt->close();

        // Keep session name in sync
        if ($name !== '') {
            $_SESSION['name'] = $name;
        }

        // Upsert into user_profiles
        $stmt = $conn->prepare('INSERT INTO user_profiles (user_id, bio, farm_size_hectares, specialization, experience_years, business_name, business_address, language_preference, pref_email_notifications, pref_theme) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio), farm_size_hectares = VALUES(farm_size_hectares), specialization = VALUES(specialization), experience_years = VALUES(experience_years), business_name = VALUES(business_name), business_address = VALUES(business_address), language_preference = VALUES(language_preference), pref_email_notifications = VALUES(pref_email_notifications), pref_theme = VALUES(pref_theme)');
        $stmt->bind_param('isdsisssis', $userId, $bio, $farm_size, $specialization, $experience, $business_name, $business_address, $lang_pref, $email_notif, $theme_pref);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>