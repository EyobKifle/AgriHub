<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Load current user preferences from DB
$prefs = [
    'language_preference' => 'en',
    'pref_email_notifications' => true,
    'pref_theme' => 'light'
];
if ($stmt = $conn->prepare('SELECT language_preference, pref_email_notifications, pref_theme FROM user_profiles WHERE user_id = ?')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $prefs['language_preference'] = $row['language_preference'] ?? 'en';
        $prefs['pref_email_notifications'] = (bool)$row['pref_email_notifications'];
        $prefs['pref_theme'] = $row['pref_theme'] ?? 'light';
    }
    $stmt->close();
}
$currentLang = $prefs['language_preference'];
$prefEmailNotif = $prefs['pref_email_notifications'];
$prefDarkMode = ($prefs['pref_theme'] === 'dark');

// Handle settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Checkboxes: if missing from POST, treat as off
    $newPrefEmailNotif = isset($_POST['email_notifications']);
    $newPrefTheme = isset($_POST['dark_mode']) ? 'dark' : 'light';

    // Language preference persists to DB
    $newLang = $_POST['language'] ?? $currentLang;
    if (!in_array($newLang, ['en', 'am', 'om', 'ti'], true)) {
        $newLang = 'en';
    }
    
    // Upsert into user_profiles
    if ($stmt = $conn->prepare('INSERT INTO user_profiles (user_id, language_preference, pref_email_notifications, pref_theme) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE language_preference = VALUES(language_preference), pref_email_notifications = VALUES(pref_email_notifications), pref_theme = VALUES(pref_theme)')) {
        $stmt->bind_param('isis', $userId, $newLang, $newPrefEmailNotif, $newPrefTheme);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: User-Settings.php?saved=1');
    exit();
}

include '../HTML/User-Settings.html';
