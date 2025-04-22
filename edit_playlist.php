<?php
// Include header
require_once 'includes/header.php';
require_once 'config/youtube_config.php';

// Require authentication
requireAuth();

// Check for required parameters
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0);

if ($playlist_id === 0) {
    header("Location: profile.php");
    exit;
}

// Get playlist details
$playlist = getPlaylist($playlist_id);

// Check if playlist exists and user has permission to edit
if (!$playlist || $playlist['user_id'] != $_SESSION['user_id']) {
    echo '<div class="alert alert-danger">You do not have permission to edit this playlist</div>';
    require_once 'includes/footer.php';
    exit;
}

// Process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // Add video to playlist
    if ($action === 'add_video' && isset($_POST['video_id']) && isset($_POST['title'])) {
        $video_id = sanitize($_POST['video_id']);
        $title = sanitize($_POST['title']);
        
        $stmt = $conn->prepare("INSERT INTO videos (playlist_id, youtube_id, title) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $playlist_id, $video_id, $title);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Video added successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to add video: ' . $conn->error];
        }
    }
    
    // Remove video from playlist
    elseif ($action === 'remove_video' && isset($_POST['video_id'])) {
        $video_id = (int)$_POST['video_id'];
        
        $stmt = $conn->prepare("DELETE FROM videos WHERE video_id = ? AND playlist_id = ?");
        $stmt->bind_param("ii", $video_id, $playlist_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Video removed successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to remove video: ' . $conn->error];
        }
    }
    
    // Update playlist details
    elseif ($action === 'update_playlist' && isset($_POST['title'])) {
        $title = sanitize($_POST['title']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE playlists SET title = ?, is_public = ? WHERE playlist_id = ?");
        $stmt->bind_param("sii", $title, $is_public, $playlist_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Playlist updated successfully'];
            // Update local data
            $playlist['title'] = $title;
            $playlist['is_public'] = $is_public;
        } else {
            $response = ['success' => false, 'message' => 'Failed to update playlist: ' . $conn->error];
        }
    }
    
    // Delete playlist
    elseif ($action === 'delete_playlist') {
        $stmt = $conn->prepare("DELETE FROM playlists WHERE playlist_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $playlist_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Playlist deleted successfully'];
            
            // Send JSON response for AJAX or redirect for form submit
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                header("Location: profile.php");
            }
            exit;
        } else {
            $response = ['success' => false, 'message' => 'Failed to delete playlist: ' . $conn->error];
        }
    }
    
    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Process form submissions
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validate title
    if (empty($title)) {
        $errors['title'] = 'Playlist title is required';
    }
    
    // If no errors, update playlist
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE playlists SET title = ?, is_public = ? WHERE playlist_id = ?");
        $stmt->bind_param("sii", $title, $is_public, $playlist_id);
        
        if ($stmt->execute()) {
            $success_message = 'Playlist updated successfully';
            // Update local data
            $playlist['title'] = $title;
            $playlist['is_public'] = $is_public;
        } else {
            $errors['general'] = 'Failed to update playlist: ' . $conn->error;
        }
    }
}

// Get videos in the playlist
$videos = getPlaylistVideos($playlist_id);

// Check if we should redirect to search videos
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action === 'add_videos') {
    header("Location: search_youtube.php?playlist_id=$playlist_id");
    exit;
}
?>

<section class="edit-playlist-section">
    <div class="container">
        <h1>Edit Playlist: <?php echo htmlspecialchars($playlist['title']); ?></h1>
        
        <div class="playlist-actions">
            <a href="playlist_view.php?id=<?php echo $playlist_id; ?>" class="btn">View Playlist</a>
            <a href="search_youtube.php?playlist_id=<?php echo $playlist_id; ?>" class="btn">Add Videos</a>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="edit-sections">
            <div class="edit-section">
                <h2>Playlist Details</h2>
                <form method="POST" action="edit_playlist.php?id=<?php echo $playlist_id; ?>">
                    <input type="hidden" name="update_details" value="1">
                    
                    <div class="form-group">
                        <label for="title">Playlist Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($playlist['title']); ?>">
                        <?php if (isset($errors['title'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['title']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public" <?php echo $playlist['is_public'] ? 'checked' : ''; ?>>
                            Make this playlist public
                        </label>
                        <small class="form-text">Public playlists can be viewed by anyone. Private playlists are only visible to you and users who follow you.</small>
                    </div>
                    
                    <button type="submit" class="btn">Update Playlist</button>
                </form>
            </div>
            
            <div class="edit-section">
                <h2>Playlist Videos</h2>
                
                <?php if (empty($videos)): ?>
                    <div class="alert alert-info">
                        This playlist has no videos. <a href="search_youtube.php?playlist_id=<?php echo $playlist_id; ?>">Add some videos</a> to get started.
                    </div>
                <?php else: ?>
                    <div class="video-list">
                        <?php foreach ($videos as $video): ?>
                            <div class="playlist-video" data-video-id="<?php echo htmlspecialchars($video['youtube_id']); ?>">
                                <div class="video-thumbnail">
                                    <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/mqdefault.jpg" alt="<?php echo htmlspecialchars($video['title']); ?>">
                                </div>
                                <div class="playlist-video-info">
                                    <h3 class="playlist-video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <p class="playlist-video-date">Added: <?php echo date('M j, Y g:i A', strtotime($video['added_at'])); ?></p>
                                </div>
                                <div class="playlist-video-actions">
                                    <button class="btn play-video-btn" onclick="previewVideo('<?php echo htmlspecialchars($video['youtube_id']); ?>')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button class="btn remove-video-btn" onclick="removeVideo(<?php echo $video['video_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="edit-section danger-zone">
                <h2>Danger Zone</h2>
                <p>Deleting this playlist will permanently remove it and all its videos. This action cannot be undone.</p>
                <button class="btn btn-danger" onclick="deletePlaylist(<?php echo $playlist_id; ?>)">Delete Playlist</button>
            </div>
        </div>
    </div>
</section>

<div id="video-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="modal-video-container" class="youtube-embed"></div>
    </div>
</div>

<script>
// Preview video in modal
function previewVideo(videoId) {
    const modal = document.getElementById('video-modal');
    const container = document.getElementById('modal-video-container');
    
    container.innerHTML = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    
    modal.style.display = 'block';
    
    // Close button functionality
    const closeBtn = document.querySelector('.close-modal');
    closeBtn.onclick = function() {
        modal.style.display = 'none';
        container.innerHTML = '';
    }
    
    // Close when clicking outside the modal content
    window.onclick = function(e) {
        if (e.target == modal) {
            modal.style.display = 'none';
            container.innerHTML = '';
        }
    }
}

// Remove video from playlist
function removeVideo(videoId) {
    if (!confirm('Are you sure you want to remove this video from the playlist?')) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'edit_playlist.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Remove the video element from the DOM
                        const videoElements = document.querySelectorAll('.playlist-video');
                        for (let i = 0; i < videoElements.length; i++) {
                            if (videoElements[i].querySelector('.remove-video-btn').getAttribute('onclick').includes(videoId)) {
                                videoElements[i].remove();
                                break;
                            }
                        }
                        
                        // Show message if playlist is now empty
                        if (document.querySelectorAll('.playlist-video').length === 0) {
                            document.querySelector('.video-list').innerHTML = `
                                <div class="alert alert-info">
                                    This playlist has no videos. <a href="search_youtube.php?playlist_id=<?php echo $playlist_id; ?>">Add some videos</a> to get started.
                                </div>
                            `;
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    alert('Error removing video from playlist');
                }
            } else {
                alert('Error removing video from playlist');
            }
        }
    };
    xhr.send(`action=remove_video&playlist_id=<?php echo $playlist_id; ?>&video_id=${videoId}`);
}

// Delete playlist
function deletePlaylist(playlistId) {
    if (!confirm('Are you sure you want to delete this playlist? This action cannot be undone.')) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'edit_playlist.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        window.location.href = 'profile.php';
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    alert('Error deleting playlist');
                }
            } else {
                alert('Error deleting playlist');
            }
        }
    };
    xhr.send(`action=delete_playlist&playlist_id=${playlistId}`);
}
</script>

<link rel="stylesheet" href="assets/css/modal.css">

<?php
// Include footer
require_once 'includes/footer.php';
?>
