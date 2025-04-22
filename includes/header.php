<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and functions
require_once 'includes/db_config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is authenticated
$is_authenticated = isAuthenticated();
$current_user = $is_authenticated ? getUserById($_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streamify - Your Streaming Playlists</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js" defer></script>
</head>
<body class="light-theme">
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <svg width="150" height="40" viewBox="0 0 150 40" xmlns="http://www.w3.org/2000/svg">
                        <text x="5" y="30" font-family="Arial" font-size="24" font-weight="bold" class="logo-text">Streamify</text>
                        <path d="M140 15 L135 25 L145 25 Z" fill="currentColor" class="logo-icon" />
                    </svg>
                </a>
            </div>
            
            <nav>
                <ul class="main-nav">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="help.php">Help</a></li>
                    <li><a href="playlists.php">Playlists</a></li>
                    <li><a href="search.php">Search</a></li>
                    
                    <?php if ($is_authenticated): ?>
                        <li><a href="create_playlist.php">Create Playlist</a></li>
                        <li><a href="following.php">Following</a></li>
                        <li><a href="export.php">Export Data</a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="user-nav">
                    <button id="theme-toggle" class="theme-toggle">
                        <i class="fas fa-moon dark-icon"></i>
                        <i class="fas fa-sun light-icon"></i>
                    </button>
                    
                    <?php if ($is_authenticated): ?>
                        <div class="user-menu">
                            <button class="user-menu-btn">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['username']); ?>
                            </button>
                            <div class="user-dropdown">
                                <a href="profile.php">My Profile</a>
                                <a href="edit_profile.php">Edit Profile</a>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn login-btn">Login</a>
                        <a href="register.php" class="btn register-btn">Register</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <main class="container">
