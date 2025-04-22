/**
 * Main JavaScript file for the application
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle mobile menu
    const userMenuBtn = document.querySelector('.user-menu-btn');
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target)) {
                const dropdown = document.querySelector('.user-dropdown');
                if (dropdown) {
                    dropdown.style.display = 'none';
                }
            }
        });
    }
    
    // Initialize YouTube player if on playlist view page
    const youtubeContainer = document.getElementById('youtube-player');
    if (youtubeContainer) {
        initializeYouTubePlayer();
    }
    
    // Handle follow/unfollow button click
    const followBtn = document.getElementById('follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', handleFollowAction);
    }
    
    // Handle video list playback
    const playButtons = document.querySelectorAll('.play-video-btn');
    if (playButtons.length > 0) {
        playButtons.forEach(button => {
            button.addEventListener('click', function() {
                const videoId = this.getAttribute('data-video-id');
                if (youtubeContainer && videoId) {
                    // If we're on a page with a YouTube player, use it
                    playVideo(videoId);
                } else {
                    // Otherwise redirect to the playlist view page
                    const playlistId = this.getAttribute('data-playlist-id');
                    window.location.href = `playlist_view.php?id=${playlistId}&video=${videoId}`;
                }
            });
        });
    }
    
    // Delete confirmation dialogs
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm(this.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            });
        });
    }
});

/**
 * Initialize YouTube player
 */
function initializeYouTubePlayer() {
    // This function will be called after the YouTube API loads
    window.onYouTubeIframeAPIReady = function() {
        const container = document.getElementById('youtube-player');
        if (!container) return;
        
        const videoId = container.getAttribute('data-video-id');
        if (!videoId) return;
        
        // Create new YouTube player
        window.player = new YT.Player('youtube-player', {
            height: '360',
            width: '640',
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
    };
    
    // Load the YouTube iframe API script
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    
    function onPlayerReady(event) {
        // Player is ready to play
        console.log('Player ready');
    }
    
    function onPlayerStateChange(event) {
        // Check if the video ended
        if (event.data === YT.PlayerState.ENDED) {
            // Play next video in playlist if available
            playNextVideo();
        }
    }
}

/**
 * Play a specific video in the YouTube player
 */
function playVideo(videoId) {
    if (window.player && videoId) {
        window.player.loadVideoById(videoId);
    }
}

/**
 * Play the next video in the playlist
 */
function playNextVideo() {
    const videos = document.querySelectorAll('.playlist-video');
    if (!videos.length) return;
    
    let nextVideo = null;
    let currentFound = false;
    
    // Find the current video and get the next one
    for (let i = 0; i < videos.length; i++) {
        const videoElement = videos[i];
        const videoId = videoElement.getAttribute('data-video-id');
        
        if (currentFound) {
            nextVideo = videoId;
            break;
        }
        
        // Check if this is the current video
        if (window.player && videoId === window.player.getVideoData().video_id) {
            currentFound = true;
        }
    }
    
    // Play next video if found, or first video to loop playlist
    if (nextVideo) {
        playVideo(nextVideo);
    } else if (videos.length > 0) {
        // Loop back to the first video
        const firstVideoId = videos[0].getAttribute('data-video-id');
        playVideo(firstVideoId);
    }
}

/**
 * Handle follow/unfollow button click
 */
function handleFollowAction(e) {
    e.preventDefault();
    
    const userId = this.getAttribute('data-user-id');
    const action = this.getAttribute('data-action'); // 'follow' or 'unfollow'
    
    // Send AJAX request to follow/unfollow
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'following.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    // Update button text and data-action
                    const button = document.getElementById('follow-btn');
                    if (action === 'follow') {
                        button.textContent = 'Unfollow';
                        button.setAttribute('data-action', 'unfollow');
                    } else {
                        button.textContent = 'Follow';
                        button.setAttribute('data-action', 'follow');
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            } catch (e) {
                console.error('Invalid JSON response:', e);
            }
        }
    };
    xhr.send(`user_id=${userId}&action=${action}`);
}
