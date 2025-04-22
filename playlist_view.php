<?php
// Include header
require_once 'includes/header.php';

// Check for playlist ID
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($playlist_id === 0) {
    header("Location: playlists.php");
    exit;
}

// Get playlist details
$playlist = getPlaylist($playlist_id);

// If playlist not found, show error
if (!$playlist) {
    echo '<div class="alert alert-danger">Playlist not found</div>';
    require_once 'includes/footer.php';
    exit;
}

// Check if user can access this playlist
$can_access = true;
$can_edit = false;

if (isAuthenticated()) {
    $user_id = $_SESSION['user_id'];
    $can_access = canAccessPlaylist($user_id, $playlist);
    $can_edit = ($user_id == $playlist['user_id']);
} else {
    // Non-authenticated users can only access public playlists
    $can_access = $playlist['is_public'];
}

// If user can't access, show error
if (!$can_access) {
    echo '<div class="alert alert-danger">You do not have permission to view this playlist</div>';
    require_once 'includes/footer.php';
    exit;
}

// Get videos in the playlist
$videos = getPlaylistVideos($playlist_id);

// Get first video to play or get from URL
$currentVideo = null;
$currentVideoId = null;

if (isset($_GET['video'])) {
    $requestedVideoId = $_GET['video'];
    foreach ($videos as $video) {
        if ($video['youtube_id'] === $requestedVideoId) {
            $currentVideo = $video;
            $currentVideoId = $video['youtube_id'];
            break;
        }
    }
}

// If no video specified or not found, use the first one
if (!$currentVideo && !empty($videos)) {
    $currentVideo = $videos[0];
    $currentVideoId = $currentVideo['youtube_id'];
}
?>

<section class="playlist-view-section">
    <div class="container">
        <div class="playlist-header">
            <div>
                <h1 class="playlist-title"><?php echo htmlspecialchars($playlist['title']); ?></h1>
                <p class="playlist-creator">
                    Created by <a href="profile.php?id=<?php echo $playlist['user_id']; ?>"><?php echo htmlspecialchars($playlist['username']); ?></a>
                    <?php if (!$playlist['is_public']): ?>
                        <span class="private-badge"><i class="fas fa-lock"></i> Private</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($can_edit): ?>
                <div class="playlist-actions">
                    <a href="edit_playlist.php?id=<?php echo $playlist_id; ?>" class="btn">Edit Playlist</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="playlist-container">
            <?php if ($currentVideoId): ?>
                <div class="playlist-player">
                    <div id="youtube-player" class="youtube-player" data-video-id="<?php echo htmlspecialchars($currentVideoId); ?>">
                        <iframe width="100%" height="480" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($currentVideoId); ?>?enablejsapi=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    
                    <?php if ($currentVideo): ?>
                        <div class="current-video-info">
                            <h2><?php echo htmlspecialchars($currentVideo['title']); ?></h2>
                            <p>Added: <?php echo date('F j, Y', strtotime($currentVideo['added_at'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">This playlist has no videos yet.</div>
            <?php endif; ?>
            
            <div class="playlist-videos">
                <h2>Videos in this Playlist</h2>
                
                <?php if (empty($videos)): ?>
                    <p>No videos in this playlist yet.</p>
                <?php else: ?>
                    <div class="video-list">
                        <?php foreach ($videos as $video): ?>
                            <div class="playlist-video <?php echo ($currentVideoId == $video['youtube_id']) ? 'playing' : ''; ?>" 
                                 data-video-id="<?php echo htmlspecialchars($video['youtube_id']); ?>">
                                <div class="video-thumbnail">
                                    <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/mqdefault.jpg" 
                                         alt="<?php echo htmlspecialchars($video['title']); ?>">
                                    <?php if ($currentVideoId == $video['youtube_id']): ?>
                                        <div class="now-playing"><i class="fas fa-play"></i> Now Playing</div>
                                    <?php endif; ?>
                                </div>
                                <div class="playlist-video-info">
                                    <h3 class="playlist-video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <p class="playlist-video-date">Added: <?php echo date('M j, Y', strtotime($video['added_at'])); ?></p>
                                </div>
                                <div class="playlist-video-actions">
                                    <button class="btn play-video-btn" data-video-id="<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                            data-playlist-id="<?php echo $playlist_id; ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
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
// YouTube API integration
let player;

function onYouTubeIframeAPIReady() {
    const playerContainer = document.getElementById('youtube-player');
    if (!playerContainer) return;
    
    const videoId = playerContainer.getAttribute('data-video-id');
    if (!videoId) return;
    
    player = new YT.Player('youtube-player', {
        height: '480',
        width: '100%',
        videoId: videoId,
        playerVars: {
            'autoplay': 0,
            'controls': 1,
            'rel': 0
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

function onPlayerReady(event) {
    // Player is ready
}

function onPlayerStateChange(event) {
    // When video ends, play next video
    if (event.data === YT.PlayerState.ENDED) {
        playNextVideo();
    }
}

function playVideo(videoId) {
    if (player && videoId) {
        player.loadVideoById(videoId);
        
        // Update URL without reloading page
        const newUrl = window.location.pathname + '?id=<?php echo $playlist_id; ?>&video=' + videoId;
        history.pushState(null, '', newUrl);
        
        // Update now playing indicators
        updateNowPlaying(videoId);
    }
}

function playNextVideo() {
    const videos = document.querySelectorAll('.playlist-video');
    if (!videos.length) return;
    
    let nextVideo = null;
    let currentFound = false;
    
    // Find current video and get the next one
    for (let i = 0; i < videos.length; i++) {
        const videoElement = videos[i];
        const videoId = videoElement.getAttribute('data-video-id');
        
        if (currentFound) {
            nextVideo = videoId;
            break;
        }
        
        // Check if this is the current video
        if (player && videoId === player.getVideoData().video_id) {
            currentFound = true;
        }
    }
    
    // If found next video, play it. Otherwise loop to first video.
    if (nextVideo) {
        playVideo(nextVideo);
    } else if (videos.length > 0) {
        playVideo(videos[0].getAttribute('data-video-id'));
    }
}

function updateNowPlaying(currentVideoId) {
    // Remove playing class from all videos
    document.querySelectorAll('.playlist-video').forEach(video => {
        video.classList.remove('playing');
        const nowPlaying = video.querySelector('.now-playing');
        if (nowPlaying) {
            nowPlaying.remove();
        }
    });
    
    // Add playing class to current video
    const currentVideo = document.querySelector(`.playlist-video[data-video-id="${currentVideoId}"]`);
    if (currentVideo) {
        currentVideo.classList.add('playing');
        
        const thumbnail = currentVideo.querySelector('.video-thumbnail');
        if (thumbnail && !thumbnail.querySelector('.now-playing')) {
            const nowPlaying = document.createElement('div');
            nowPlaying.className = 'now-playing';
            nowPlaying.innerHTML = '<i class="fas fa-play"></i> Now Playing';
            thumbnail.appendChild(nowPlaying);
        }
        
        // Scroll to current video
        currentVideo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Add click event for play buttons
document.addEventListener('DOMContentLoaded', function() {
    const playButtons = document.querySelectorAll('.play-video-btn');
    playButtons.forEach(button => {
        button.addEventListener('click', function() {
            const videoId = this.getAttribute('data-video-id');
            playVideo(videoId);
        });
    });
    
    // Load YouTube API
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
});
</script>

<style>
.playlist-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.playlist-player {
    position: sticky;
    top: 20px;
}

.current-video-info {
    margin-top: 15px;
}

.playlist-videos {
    max-height: 600px;
    overflow-y: auto;
}

.now-playing {
    position: absolute;
    top: 0;
    left: 0;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 10px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.playlist-video.playing {
    background-color: var(--primary-color);
    color: white;
}

.playlist-video.playing .playlist-video-title,
.playlist-video.playing .playlist-video-date {
    color: white;
}

.private-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background-color: var(--bg-secondary);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
}

.video-thumbnail {
    position: relative;
}

@media (max-width: 992px) {
    .playlist-container {
        grid-template-columns: 1fr;
    }
    
    .playlist-player {
        position: relative;
        top: 0;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
