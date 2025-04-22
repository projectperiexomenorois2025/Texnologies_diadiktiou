<?php
// Include header
require_once 'includes/header.php';

// Require authentication
requireAuth();

$user_id = $_SESSION['user_id'];

// Process follow/unfollow requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $target_user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    // Check if target user exists
    $target_user = getUserById($target_user_id);
    
    if (!$target_user) {
        $response = ['success' => false, 'message' => 'User not found'];
    } else if ($target_user_id == $user_id) {
        $response = ['success' => false, 'message' => 'You cannot follow yourself'];
    } else {
        if ($action === 'follow') {
            // Check if already following
            if (isFollowing($user_id, $target_user_id)) {
                $response = ['success' => false, 'message' => 'You are already following this user'];
            } else {
                // Add follower
                $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $target_user_id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'You are now following this user'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to follow user: ' . $conn->error];
                }
            }
        } else if ($action === 'unfollow') {
            // Check if actually following
            if (!isFollowing($user_id, $target_user_id)) {
                $response = ['success' => false, 'message' => 'You are not following this user'];
            } else {
                // Remove follower
                $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
                $stmt->bind_param("ii", $user_id, $target_user_id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'You have unfollowed this user'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to unfollow user: ' . $conn->error];
                }
            }
        }
    }
    
    // If AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Get users that the current user is following
$following = getFollowing($user_id);

// Get public playlists from users that the current user is following
$followed_playlists = [];

if (!empty($following)) {
    $following_ids = array_map(function($user) {
        return $user['user_id'];
    }, $following);
    
    $placeholders = str_repeat('?,', count($following_ids) - 1) . '?';
    $sql = "SELECT p.*, u.username, u.first_name, u.last_name 
            FROM playlists p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.user_id IN ($placeholders) AND p.is_public = 1
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($following_ids));
    $stmt->bind_param($types, ...$following_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $followed_playlists[] = $row;
    }
}

// Suggest users to follow
$suggested_users = [];

$sql = "SELECT u.user_id, u.username, u.first_name, u.last_name 
        FROM users u
        WHERE u.user_id != ?
        AND u.user_id NOT IN (SELECT following_id FROM followers WHERE follower_id = ?)
        ORDER BY RAND()
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $suggested_users[] = $row;
}
?>

<section class="following-section">
    <div class="container">
        <div class="section-header">
            <h1>Following</h1>
        </div>
        
        <div class="following-container">
            <div class="following-users">
                <h2>Users You Follow</h2>
                
                <?php if (empty($following)): ?>
                    <div class="alert alert-info">
                        You are not following any users yet. Check out the <a href="playlists.php">public playlists</a> to find users to follow.
                    </div>
                <?php else: ?>
                    <div class="user-grid">
                        <?php foreach ($following as $followed_user): ?>
                            <div class="user-card">
                                <h3><?php echo htmlspecialchars($followed_user['username']); ?></h3>
                                <p><?php echo htmlspecialchars($followed_user['first_name'] . ' ' . $followed_user['last_name']); ?></p>
                                <div class="user-actions">
                                    <a href="profile.php?id=<?php echo $followed_user['user_id']; ?>" class="btn">View Profile</a>
                                    <button class="btn unfollow-btn" 
                                            onclick="unfollowUser(<?php echo $followed_user['user_id']; ?>, '<?php echo htmlspecialchars($followed_user['username']); ?>')">
                                        Unfollow
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($suggested_users)): ?>
                    <div class="suggested-users">
                        <h2>Suggested Users to Follow</h2>
                        <div class="user-grid">
                            <?php foreach ($suggested_users as $suggested_user): ?>
                                <div class="user-card">
                                    <h3><?php echo htmlspecialchars($suggested_user['username']); ?></h3>
                                    <p><?php echo htmlspecialchars($suggested_user['first_name'] . ' ' . $suggested_user['last_name']); ?></p>
                                    <div class="user-actions">
                                        <a href="profile.php?id=<?php echo $suggested_user['user_id']; ?>" class="btn">View Profile</a>
                                        <button class="btn follow-btn" 
                                                onclick="followUser(<?php echo $suggested_user['user_id']; ?>, '<?php echo htmlspecialchars($suggested_user['username']); ?>')">
                                            Follow
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="following-playlists">
                <h2>Playlists from Users You Follow</h2>
                
                <?php if (empty($followed_playlists)): ?>
                    <div class="alert alert-info">
                        No public playlists from users you follow yet.
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($followed_playlists as $playlist): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($playlist['title']); ?></h3>
                                    <p class="card-meta">
                                        By <a href="profile.php?id=<?php echo $playlist['user_id']; ?>"><?php echo htmlspecialchars($playlist['username']); ?></a> • 
                                        Created: <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?>
                                    </p>
                                    <a href="playlist_view.php?id=<?php echo $playlist['playlist_id']; ?>" class="btn">View Playlist</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function followUser(userId, username) {
    if (!confirm(`Are you sure you want to follow ${username}?`)) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'following.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    alert(response.message);
                    
                    if (response.success) {
                        // Reload the page to show the updated following list
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    alert('Error processing your request');
                }
            } else {
                alert('Error processing your request');
            }
        }
    };
    
    xhr.send(`user_id=${userId}&action=follow`);
}

function unfollowUser(userId, username) {
    if (!confirm(`Are you sure you want to unfollow ${username}?`)) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'following.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    alert(response.message);
                    
                    if (response.success) {
                        // Reload the page to show the updated following list
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    alert('Error processing your request');
                }
            } else {
                alert('Error processing your request');
            }
        }
    };
    
    xhr.send(`user_id=${userId}&action=unfollow`);
}
</script>

<style>
.following-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
}

.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.user-card {
    background-color: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 15px;
    box-shadow: 0 2px 5px var(--shadow-color);
}

.user-card h3 {
    margin-bottom: 5px;
}

.user-card p {
    margin-bottom: 15px;
    color: var(--text-color);
    opacity: 0.8;
}

.user-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.suggested-users {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

@media (max-width: 992px) {
    .following-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
