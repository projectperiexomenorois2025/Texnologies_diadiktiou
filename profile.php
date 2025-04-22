<?php
// Include header
require_once 'includes/header.php';

// Get user ID from URL or use current user
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (isAuthenticated() ? $_SESSION['user_id'] : 0);

// If no user ID provided and not logged in, redirect to login
if ($profile_id === 0) {
    header("Location: login.php");
    exit;
}

// Get user details
$user = getUserById($profile_id);

// If user not found, show error
if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    require_once 'includes/footer.php';
    exit;
}

// Check if current user is following this profile
$is_following = false;
$is_current_user = false;

if (isAuthenticated()) {
    $current_user_id = $_SESSION['user_id'];
    $is_current_user = ($current_user_id == $profile_id);
    
    if (!$is_current_user) {
        $is_following = isFollowing($current_user_id, $profile_id);
    }
}

// Handle follow/unfollow actions
if (isAuthenticated() && isset($_POST['action']) && !$is_current_user) {
    $action = $_POST['action'];
    
    if ($action === 'follow' && !$is_following) {
        $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $current_user_id, $profile_id);
        $stmt->execute();
        $is_following = true;
    } elseif ($action === 'unfollow' && $is_following) {
        $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $current_user_id, $profile_id);
        $stmt->execute();
        $is_following = false;
    }
}

// Get user's playlists
$show_private = $is_current_user;
$playlists = getUserPlaylists($profile_id, $show_private);

// Get users that this user is following
$following = getFollowing($profile_id);

// Get users following this user
$followers = getFollowers($profile_id);
?>

<section class="profile-section">
    <div class="profile-container">
        <div class="profile-info">
            <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
            
            <div class="profile-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <?php if ($is_current_user): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php endif; ?>
                <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                <p><strong>Playlists:</strong> <?php echo count($playlists); ?></p>
                <p><strong>Following:</strong> <?php echo count($following); ?></p>
                <p><strong>Followers:</strong> <?php echo count($followers); ?></p>
            </div>
            
            <?php if (isAuthenticated() && !$is_current_user): ?>
                <div class="profile-actions">
                    <form method="POST" action="profile.php?id=<?php echo $profile_id; ?>">
                        <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                        <button type="submit" id="follow-btn" class="follow-btn" data-user-id="<?php echo $profile_id; ?>" data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </form>
                </div>
            <?php elseif ($is_current_user): ?>
                <div class="profile-actions">
                    <a href="edit_profile.php" class="btn">Edit Profile</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-content">
            <h2>Playlists</h2>
            
            <?php if (empty($playlists)): ?>
                <p>No playlists available.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($playlists as $playlist): ?>
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">
                                    <?php echo htmlspecialchars($playlist['title']); ?>
                                    <?php if (!$playlist['is_public']): ?>
                                        <span class="private-badge"><i class="fas fa-lock"></i></span>
                                    <?php endif; ?>
                                </h3>
                                <p class="card-meta">Created: <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?></p>
                                <a href="playlist_view.php?id=<?php echo $playlist['playlist_id']; ?>" class="btn">View Playlist</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_current_user): ?>
                <div class="create-playlist">
                    <a href="create_playlist.php" class="btn">Create New Playlist</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($following)): ?>
                <h2>Following</h2>
                <div class="grid">
                    <?php foreach ($following as $follow): ?>
                        <div class="card user-card">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($follow['username']); ?></h3>
                                <p class="card-meta"><?php echo htmlspecialchars($follow['first_name'] . ' ' . $follow['last_name']); ?></p>
                                <a href="profile.php?id=<?php echo $follow['user_id']; ?>" class="btn">View Profile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
