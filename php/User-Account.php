<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /AgriHub/HTML/Login.html');
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Account';

// Load user data
$data = [];
if ($stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, u.location, u.avatar_url,
           p.bio, p.farm_size_hectares, p.specialization, p.experience_years,
           p.business_name, p.business_address, p.language_preference, p.pref_theme, p.pref_email_notifications
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    WHERE u.id = ?
")) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $data = $row;
        $name = $data['name'];
        $email = $data['email'];
    }
    $stmt->close();
}

$currentLang = $data['language_preference'] ?? 'en';
$prefEmailNotif = isset($data['pref_email_notifications']) ? (bool)$data['pref_email_notifications'] : true;
$prefDarkMode = ($data['pref_theme'] ?? 'light') === 'dark';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $newName = trim($_POST['full_name'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');
        $newLocation = trim($_POST['location'] ?? '');

        // Avatar upload
        $avatar_path = $data['avatar_url'] ?? null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = $userId . '_' . uniqid() . '_' . basename($_FILES['avatar']['name']);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
                $avatar_path = 'uploads/avatars/' . $filename;
            }
        }

        $stmt = $conn->prepare('UPDATE users SET name=?, phone=?, location=?, avatar_url=? WHERE id=?');
        $stmt->bind_param('ssssi', $newName, $newPhone, $newLocation, $avatar_path, $userId);
        $stmt->execute();
        $stmt->close();

        if ($newName !== '') $_SESSION['name'] = $newName;

        $bio = trim($_POST['bio'] ?? '');
        $farm_size = !empty($_POST['farm_size_hectares']) ? (float)$_POST['farm_size_hectares'] : null;
        $specialization = !empty(trim($_POST['specialization'])) ? trim($_POST['specialization']) : null;
        $experience = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
        $business_name = !empty(trim($_POST['business_name'])) ? trim($_POST['business_name']) : null;
        $business_address = !empty(trim($_POST['business_address'])) ? trim($_POST['business_address']) : null;
        $lang_pref = trim($_POST['language'] ?? 'en');
        $theme_pref = isset($_POST['dark_mode']) ? 'dark' : 'light';
        $email_notif_pref = isset($_POST['email_notifications']);

        $stmt = $conn->prepare('
            INSERT INTO user_profiles (user_id, bio, farm_size_hectares, specialization, experience_years, business_name, business_address, language_preference, pref_theme, pref_email_notifications)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                bio=VALUES(bio), farm_size_hectares=VALUES(farm_size_hectares), specialization=VALUES(specialization),
                experience_years=VALUES(experience_years), business_name=VALUES(business_name), business_address=VALUES(business_address),
                language_preference=VALUES(language_preference), pref_theme=VALUES(pref_theme), pref_email_notifications=VALUES(pref_email_notifications)
        ');
        $stmt->bind_param('isdsissssi', $userId, $bio, $farm_size, $specialization, $experience, $business_name, $business_address, $lang_pref, $theme_pref, $email_notif_pref);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header('Location: /AgriHub/php/User-Account.php?saved=1');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("An error occurred: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-i18n-key="user.account.pageTitle">My Account - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="/AgriHub/Css/User-Account.css">
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span data-i18n-key="brand.name">AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="/AgriHub/php/User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($data['avatar_url'])): ?><img src="/AgriHub/<?php echo e($data['avatar_url']); ?>" alt="User Avatar">
                    <?php else: ?><?php echo e($initial); ?><?php endif; ?>
                </div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="user.account.title">My Account</h1>
                <p data-i18n-key="user.account.subtitle">Manage your personal information, farm details, and account settings.</p>
                 <?php if (isset($_GET['saved'])): ?>
                    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-top: 1.5rem;">
                        <span data-i18n-key="user.account.savedSuccess">Your settings have been saved successfully.</span>
                    </div>
                <?php endif; ?>
            </div>

            <form id="profile-form" class="profile-layout" method="POST" enctype="multipart/form-data">
                <!-- Profile Picture Card -->
                <div class="card profile-picture-card">
                    <h3 class="card-title" data-i18n-key="user.profile.picture.title">Profile Picture</h3>
                    <div class="profile-picture-preview">
                        <img src="<?php echo !empty($data['avatar_url']) ? '/AgriHub/' . e($data['avatar_url']) : 'https://placehold.co/150x150/cccccc/FFF?text=' . e($initial); ?>" alt="Current profile picture" id="profile-image-preview">
                    </div>
                    <label for="avatar-upload" class="btn btn-secondary fullwidth" data-i18n-key="user.profile.picture.upload">Upload New Picture</label>
                    <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display:none;" data-base-url="/AgriHub/">
                    <p class="form-text" data-i18n-key="user.profile.picture.help">For best results, use an image at least 200x200px in .jpg or .png format.</p>
                </div>

                <!-- Profile Details Card -->
                <div class="card profile-details-card">
                    <div class="settings-form">
                        <h3 class="card-title" data-i18n-key="user.profile.details.title">Personal Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full-name" data-i18n-key="user.profile.details.name">Full Name</label>
                                <input type="text" id="full-name" name="full_name" value="<?php echo e($data['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" data-i18n-key="user.profile.details.email">Email Address</label>
                                <input type="email" id="email" value="<?php echo e($data['email'] ?? ''); ?>" disabled>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="phone" data-i18n-key="user.profile.details.phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo e($data['phone'] ?? ''); ?>" placeholder="+251 9...">
                            </div>
                            <div class="form-group">
                                <label for="location" data-i18n-key="user.profile.details.location">Location / Region</label>
                                <input type="text" id="location" name="location" value="<?php echo e($data['location'] ?? ''); ?>" placeholder="e.g., Amhara, Gojjam">
                            </div>
                        </div>
                        <hr style="margin: 2rem 0;">
                        <h3 class="card-title" data-i18n-key="user.profile.farmInfo.title">Farm & Business Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="farm-size" data-i18n-key="user.profile.details.farmSize">Farm Size (Hectares)</label>
                                <input type="number" step="0.1" id="farm-size" name="farm_size_hectares" value="<?php echo e($data['farm_size_hectares'] ?? ''); ?>" placeholder="e.g., 5.5">
                            </div>
                            <div class="form-group">
                                <label for="experience" data-i18n-key="user.profile.details.experience">Years of Experience</label>
                                <input type="number" id="experience" name="experience_years" value="<?php echo e($data['experience_years'] ?? ''); ?>" placeholder="e.g., 10">
                            </div>
                        </div>
                         <div class="form-group">
                            <label for="specialization" data-i18n-key="user.profile.details.specialization">Specialization</label>
                            <input type="text" id="specialization" name="specialization" value="<?php echo e($data['specialization'] ?? ''); ?>" placeholder="e.g., Grains, Dairy, Coffee">
                        </div>
                        <div class="form-group">
                            <label for="bio" data-i18n-key="user.profile.details.about">About Me / My Farm (Optional)</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Describe your farm, the crops you grow, or your farming practices..."><?php echo e($data['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="business-name" data-i18n-key="user.profile.details.businessName">Business Name (Optional)</label>
                            <input type="text" id="business-name" name="business_name" value="<?php echo e($data['business_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="business-address" data-i18n-key="user.profile.details.businessAddress">Business Address (Optional)</label>
                            <textarea id="business-address" name="business_address" rows="2"><?php echo e($data['business_address'] ?? ''); ?></textarea>
                        </div>

                        <hr style="margin: 2rem 0;">
                        <h3 class="card-title" data-i18n-key="user.profile.preferences.title">Preferences</h3>
                        <div class="form-group form-group-toggle">
                            <label for="email-notifications" data-i18n-key="user.settings.notifications.email">Email Notifications</label>
                            <label class="switch">
                                <input type="checkbox" id="email-notifications" name="email_notifications" <?php echo $prefEmailNotif ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label for="dark-mode-toggle" data-i18n-key="user.settings.appearance.darkMode">Dark Mode</label>
                            <label class="switch">
                                <input type="checkbox" id="dark-mode-toggle" name="dark_mode" <?php echo $prefDarkMode ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="language-select" data-i18n-key="user.settings.language.preferred">Preferred Language</label>
                            <select id="language-select" name="language">
                                <option value="en" <?php echo $currentLang==='en'?'selected':''; ?> data-i18n-key="lang.en">English</option>
                                <option value="am" <?php echo $currentLang==='am'?'selected':''; ?> data-i18n-key="lang.am">Amharic</option>
                                <option value="om" <?php echo $currentLang==='om'?'selected':''; ?> data-i18n-key="lang.om">Oromo</option>
                                <option value="ti" <?php echo $currentLang==='ti'?'selected':''; ?> data-i18n-key="lang.ti">Tigrinya</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" data-i18n-key="user.profile.details.save">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
</body>
</html>
