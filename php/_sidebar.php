<?php
// This file assumes the following variables are defined in the parent file:
// $avatar_url, $initial
// $name, $email, $currentPage (e.g., 'User-Dashboard')

$links = [
    'User-Dashboard' => ['icon' => 'fa-chart-pie', 'key' => 'user.nav.dashboard', 'text' => 'Dashboard', 'path' => 'User-Dashboard.php'],
    'User-Account' => ['icon' => 'fa-user', 'key' => 'user.nav.profile', 'text' => 'My Profile', 'path' => 'User-Account.php'],
    'User-Listings' => ['icon' => 'fa-store', 'key' => 'user.nav.listings', 'text' => 'My Listings', 'path' => 'User-Listings.php'],
    'User-Messages' => ['icon' => 'fa-envelope', 'key' => 'user.nav.messages', 'text' => 'Messages', 'path' => 'User-Messages.php'],
    'User-Discussions' => ['icon' => 'fa-users', 'key' => 'user.nav.discussions', 'text' => 'My Discussions', 'path' => 'User-Discussions.php'],
    'User-Settings' => ['icon' => 'fa-gear', 'key' => 'user.nav.settings', 'text' => 'Settings', 'path' => 'User-Settings.php'],
];
$siteLinks = [
    'User-Marketplace' => ['icon' => 'fa-basket-shopping', 'key' => 'nav.marketplace', 'text' => 'Marketplace', 'path' => 'User-Marketplace.php'],
    'User-News' => ['icon' => 'fa-newspaper', 'key' => 'header.nav.news', 'text' => 'News', 'path' => 'User-News.php'],
    'User-Farming-Guidance' => ['icon' => 'fa-book', 'key' => 'header.nav.guidance', 'text' => 'Farming Guidance', 'path' => 'User-Farming-Guidance.php'],
    'User-News' => ['icon' => 'fa-newspaper', 'key' => 'nav.news', 'text' => 'News', 'path' => 'User-News.php'],
    'Farming-Guidance' => ['icon' => 'fa-book', 'key' => 'nav.guidance', 'text' => 'Farming Guidance', 'path' => '../Farming-Guidance.php'],
];
?>
<aside class="sidebar" id="sidebar">
    <ul class="sidebar-nav">
        <?php foreach ($links as $page => $link): ?>
            <li><a href="/AgriHub/php/<?php echo $link['path']; ?>" class="<?php echo $currentPage === $page ? 'active' : ''; ?>" data-i18n-key="<?php echo $link['key']; ?>"><i class="fa-solid <?php echo $link['icon']; ?>"></i> <?php echo $link['text']; ?></a></li>
        <?php endforeach; ?>
        <hr>
        <?php foreach ($siteLinks as $page => $link): ?>
            <li><a href="/AgriHub/php/<?php echo $link['path']; ?>" class="<?php echo $currentPage === $page ? 'active' : ''; ?>" data-i18n-key="<?php echo $link['key']; ?>"><i class="fa-solid <?php echo $link['icon']; ?>"></i> <?php echo $link['text']; ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <div class="profile-dropdown">
            <div class="profile-avatar">
                <?php if (!empty($avatar_url)): ?>
                    <img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar" data-i18n-alt-key="user.sidebar.avatarAlt">
                <?php else: echo e($initial);
                endif; ?>
            </div>
            <div>
                <div class="profile-name"><?php echo e($name); ?></div>
                <div class="profile-email" style="opacity:.8; font-size:12px;"><?php echo e($email); ?></div>
                <small><a href="/AgriHub/php/auth.php?action=logout" style="color:inherit; text-decoration:none;"><span data-i18n-key="user.nav.logout">Logout</span></a></small>
            </div>
        </div>
    </div>
</aside>