<?php
// Include header
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Welcome to Streamify</h1>
        <p>Create, share, and enjoy YouTube content playlists with friends and followers.</p>
        <?php if (!isAuthenticated()): ?>
            <div class="hero-buttons">
                <a href="register.php" class="btn">Sign Up Now</a>
                <a href="login.php" class="btn">Login</a>
            </div>
        <?php else: ?>
            <div class="hero-buttons">
                <a href="create_playlist.php" class="btn">Create Playlist</a>
                <a href="playlists.php" class="btn">Browse Playlists</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="features">
    <h2>About Streamify</h2>
    
    <div class="accordion-container">
        <div class="accordion">
            <div class="accordion-header">
                What is Streamify?
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <p>Streamify is a platform that allows you to create customized playlists of YouTube content. You can organize your favorite videos into collections, share them with others, or keep them private for your personal use.</p>
                <p>With Streamify, you can follow other users and discover new content through their public playlists. It's a great way to organize and share video content with friends, family, or followers.</p>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                How to Get Started
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <p>Getting started with Streamify is easy:</p>
                <ol>
                    <li>Sign up for a free account</li>
                    <li>Create your first playlist</li>
                    <li>Search for YouTube videos directly on our platform</li>
                    <li>Add videos to your playlist</li>
                    <li>Share your playlist with others or keep it private</li>
                </ol>
                <p>You can also follow other users to see their public playlists on your profile page.</p>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Why Use Streamify?
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <p>Streamify offers several advantages over regular YouTube playlists:</p>
                <ul>
                    <li>Privacy control - choose which playlists are public or private</li>
                    <li>Follow system - discover content from users with similar interests</li>
                    <li>Organized collections - manage your video content in one place</li>
                    <li>Custom playback - play all videos in a playlist without interruptions</li>
                    <li>Simplified searching - find videos and add them to playlists in one place</li>
                </ul>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Features and Benefits
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <p>Streamify comes with a variety of features designed to enhance your video content experience:</p>
                <ul>
                    <li><strong>Customizable Playlists:</strong> Create as many playlists as you want</li>
                    <li><strong>Privacy Settings:</strong> Control who can see your playlists</li>
                    <li><strong>Social Following:</strong> Follow other users and see their public playlists</li>
                    <li><strong>YouTube Integration:</strong> Search and add videos directly from YouTube</li>
                    <li><strong>Advanced Search:</strong> Find playlists by title, date, or creator</li>
                    <li><strong>Data Export:</strong> Export your playlists as YAML for backup or sharing</li>
                    <li><strong>Theme Options:</strong> Choose between light and dark mode for comfortable viewing</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="popular-playlists">
    <h2>Popular Playlists</h2>
    
    <div class="grid">
        <?php
        // Get some public playlists to display
        $sql = "SELECT p.*, u.username, COUNT(v.video_id) as video_count 
                FROM playlists p 
                JOIN users u ON p.user_id = u.user_id 
                LEFT JOIN videos v ON p.playlist_id = v.playlist_id 
                WHERE p.is_public = 1 
                GROUP BY p.playlist_id 
                ORDER BY p.created_at DESC 
                LIMIT 6";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($playlist = $result->fetch_assoc()) {
                echo '<div class="card">';
                echo '<div class="card-body">';
                echo '<h3 class="card-title">' . htmlspecialchars($playlist['title']) . '</h3>';
                echo '<p class="card-meta">By ' . htmlspecialchars($playlist['username']) . ' • ' . htmlspecialchars($playlist['video_count']) . ' videos</p>';
                echo '<a href="playlist_view.php?id=' . $playlist['playlist_id'] . '" class="btn">View Playlist</a>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>No playlists available yet. Be the first to create one!</p>';
        }
        ?>
    </div>
    
    <div class="text-center">
        <a href="playlists.php" class="btn">View All Playlists</a>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
