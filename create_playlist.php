<?php
// Include header
require_once 'includes/header.php';

// Require authentication
requireAuth();

$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    // Validate data
    if (empty($title)) {
        $errors['title'] = 'Playlist title is required';
    }
    
    // If no errors, create the playlist
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO playlists (user_id, title, is_public) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $title, $is_public);
        
        if ($stmt->execute()) {
            $playlist_id = $conn->insert_id;
            $success = true;
            
            // Redirect to edit playlist page to add videos
            header("Location: edit_playlist.php?id=$playlist_id&action=add_videos");
            exit;
        } else {
            $errors['general'] = 'Failed to create playlist: ' . $conn->error;
        }
    }
}
?>

<section class="create-playlist-section">
    <div class="form-container">
        <h1 class="form-title">Create New Playlist</h1>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Playlist created successfully! Redirecting to add videos...
            </div>
        <?php endif; ?>
        
        <form id="playlist-form" method="POST" action="create_playlist.php">
            <div class="form-group">
                <label for="title">Playlist Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                <?php if (isset($errors['title'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_public" <?php echo (!isset($_POST['is_public']) || $_POST['is_public']) ? 'checked' : ''; ?>>
                    Make this playlist public
                </label>
                <small class="form-text">Public playlists can be viewed by anyone. Private playlists are only visible to you and users who follow you.</small>
            </div>
            
            <button type="submit" class="form-submit">Create Playlist</button>
        </form>
        
        <div class="form-footer">
            <p>Want to see your existing playlists? <a href="profile.php">Go to your profile</a></p>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
