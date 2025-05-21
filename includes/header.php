
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Playlist Manager</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="container">
                <div class="nav-brand">
                    <a href="index.php">YouTube Playlist Manager</a>
                </div>
                <ul class="nav-menu">
                    <!-- Πάντα ορατά -->
                    <li><a href="index.php">Αρχική</a></li>
                    <li><a href="playlists.php">Δημόσιες Λίστες</a></li>
                    <li><a href="help.php">Βοήθεια</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Ορατά μόνο σε συνδεδεμένους χρήστες -->
                        <li><a href="create_playlist.php">Νέα Λίστα</a></li>
                        <li><a href="following.php">Ακολουθώ</a></li>
                        <li><a href="profile.php">Το Προφίλ μου</a></li>
                        <li><a href="logout.php">Αποσύνδεση</a></li>
                    <?php else: ?>
                        <!-- Ορατά μόνο σε μη συνδεδεμένους χρήστες -->
                        <li><a href="login.php">Σύνδεση</a></li>
                        <li><a href="register.php">Εγγραφή</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>
