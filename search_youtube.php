<?php
// Include header
require_once 'includes/header.php';
require_once 'config/youtube_config.php';

// Require authentication
requireAuth();

// Set content type to JSON if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Handle OAuth code callback
if (isset($_GET['code'])) {
    $token_data = getAccessToken($_GET['code']);
    
    if ($token_data && isset($token_data['access_token'])) {
        // Store the access token in session
        $_SESSION['youtube_access_token'] = $token_data['access_token'];
        
        // Store refresh token if provided
        if (isset($token_data['refresh_token'])) {
            $_SESSION['youtube_refresh_token'] = $token_data['refresh_token'];
        }
        
        // Redirect back to the search page
        if (isset($_SESSION['youtube_search_query'])) {
            $query = $_SESSION['youtube_search_query'];
            unset($_SESSION['youtube_search_query']);
            header("Location: search_youtube.php?q=" . urlencode($query));
            exit;
        } else {
            header("Location: search_youtube.php");
            exit;
        }
    }
}

// Process search query
$search_results = null;
$error_message = null;

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $query = $_GET['q'];
    
    // Check if we have an access token
    if (isset($_SESSION['youtube_access_token'])) {
        // Search with OAuth access token
        $search_results = searchYouTube($query, $_SESSION['youtube_access_token']);
        
        // Check if token expired or other error
        if (isset($search_results['error'])) {
            // Fall back to API key search
            $search_results = searchYouTubeWithApiKey($query);
        }
    } else {
        // If no OAuth token, use API key as fallback
        $search_results = searchYouTubeWithApiKey($query);
    }
    
    // If this is an AJAX request, return JSON
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($search_results);
        exit;
    }
}

// Get user's playlists for the dropdown
$user_id = $_SESSION['user_id'];
$playlists = getUserPlaylists($user_id, true);

// If no playlists, redirect to create one
if (empty($playlists) && !isset($_GET['q'])) {
    header("Location: create_playlist.php");
    exit;
}

// Check if we're adding to a specific playlist
$playlist_id = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : null;
$selected_playlist = null;

if ($playlist_id) {
    foreach ($playlists as $playlist) {
        if ($playlist['playlist_id'] == $playlist_id) {
            $selected_playlist = $playlist;
            break;
        }
    }
}
?>

<section class="youtube-search-section">
    <div class="container">
        <h1>Search YouTube Videos</h1>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="search-container">
            <form id="youtube-search-form" method="GET" action="search_youtube.php">
                <div class="form-group">
                    <input type="text" class="form-control" id="search-query" name="q" 
                           placeholder="Search for videos on YouTube" 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn">Search</button>
            </form>
            
            <?php if (!isset($_SESSION['youtube_access_token'])): ?>
                <div class="oauth-container">
                    <p>For better results, authorize with your Google account:</p>
                    <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn">Authorize with Google</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($playlists)): ?>
                <div class="playlist-selector">
                    <label for="playlist-select">Add videos to:</label>
                    <select id="playlist-select" class="form-control">
                        <?php foreach ($playlists as $playlist): ?>
                            <option value="<?php echo $playlist['playlist_id']; ?>" 
                                    <?php echo ($selected_playlist && $selected_playlist['playlist_id'] == $playlist['playlist_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($playlist['title']); ?>
                                <?php echo $playlist['is_public'] ? '' : ' (Private)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="search-results" class="video-results">
            <?php if ($search_results && isset($search_results['items']) && !empty($search_results['items'])): ?>
                <h2>Search Results</h2>
                <div class="grid">
                    <?php foreach ($search_results['items'] as $item): ?>
                        <?php 
                        $videoId = $item['id']['videoId'];
                        $title = $item['snippet']['title'];
                        $thumbnail = $item['snippet']['thumbnails']['medium']['url'];
                        $channelTitle = $item['snippet']['channelTitle'];
                        ?>
                        <div class="card video-result">
                            <div class="video-thumbnail">
                                <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="<?php echo htmlspecialchars($title); ?>">
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($title); ?></h3>
                                <p class="card-meta">By <?php echo htmlspecialchars($channelTitle); ?></p>
                                <div class="flex-between">
                                    <button class="btn" onclick="previewVideo('<?php echo $videoId; ?>')">
                                        <i class="fas fa-play"></i> Preview
                                    </button>
                                    <button class="btn" onclick="addToPlaylist('<?php echo $videoId; ?>', '<?php echo htmlspecialchars(addslashes($title)); ?>')">
                                        <i class="fas fa-plus"></i> Add to Playlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (isset($_GET['q'])): ?>
                <div class="alert alert-info">No videos found for your search query.</div>
            <?php endif; ?>
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
// Video preview functionality
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

// Add video to playlist
function addToPlaylist(videoId, videoTitle) {
    const playlistSelect = document.getElementById('playlist-select');
    
    if (!playlistSelect || playlistSelect.options.length === 0) {
        alert('Please create a playlist first');
        return;
    }
    
    const playlistId = playlistSelect.value;
    
    // Send request to add video to playlist
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'edit_playlist.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        alert('Video added to playlist successfully');
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error adding video to playlist');
                    console.error('Error parsing JSON:', e);
                }
            } else {
                alert('Error adding video to playlist');
                console.error('Error adding video:', this.status);
            }
        }
    };
    xhr.send(`action=add_video&playlist_id=${playlistId}&video_id=${videoId}&title=${encodeURIComponent(videoTitle)}`);
}
</script>

<link rel="stylesheet" href="assets/css/modal.css">

<?php
// Include footer
require_once 'includes/footer.php';
?>
