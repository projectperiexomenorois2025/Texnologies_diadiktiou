/**
 * YouTube API integration for searching videos
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('youtube-search-form');
    const searchResults = document.getElementById('search-results');
    const playlistSelect = document.getElementById('playlist-select');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('search-query').value.trim();
            
            if (query) {
                searchYouTube(query);
            }
        });
    }
    
    /**
     * Search YouTube videos via backend API
     */
    function searchYouTube(query) {
        // Show loading state
        searchResults.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        
        // Send request to backend
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `search_youtube.php?q=${encodeURIComponent(query)}`, true);
        xhr.onreadystatechange = function() {
            if (this.readyState === XMLHttpRequest.DONE) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        displaySearchResults(response);
                    } catch (e) {
                        searchResults.innerHTML = '<div class="alert alert-danger">Error parsing response</div>';
                        console.error('Error parsing JSON:', e);
                    }
                } else {
                    searchResults.innerHTML = '<div class="alert alert-danger">Error searching YouTube</div>';
                    console.error('Error searching YouTube:', this.status);
                }
            }
        };
        xhr.send();
    }
    
    /**
     * Display search results from YouTube
     */
    function displaySearchResults(results) {
        if (!results || !results.items || results.items.length === 0) {
            searchResults.innerHTML = '<div class="alert alert-info">No videos found</div>';
            return;
        }
        
        let html = '<div class="grid">';
        
        results.items.forEach(item => {
            const videoId = item.id.videoId;
            const title = item.snippet.title;
            const thumbnail = item.snippet.thumbnails.medium.url;
            const channelTitle = item.snippet.channelTitle;
            
            html += `
                <div class="card video-result">
                    <div class="video-thumbnail">
                        <img src="${thumbnail}" alt="${title}" style="width: 100%; height: auto;">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">${title}</h3>
                        <p class="card-text">By ${channelTitle}</p>
                        <div class="flex-between">
                            <button class="btn preview-btn" onclick="previewVideo('${videoId}')">
                                <i class="fas fa-play"></i> Preview
                            </button>
                            <button class="btn add-to-playlist-btn" onclick="addToPlaylist('${videoId}', '${title.replace(/'/g, "\\'")}')">
                                <i class="fas fa-plus"></i> Add to Playlist
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        searchResults.innerHTML = html;
    }
});

/**
 * Preview a YouTube video in a modal
 */
function previewVideo(videoId) {
    // Create and open a modal with YouTube embed
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="youtube-player">
                <iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" 
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; 
                        gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
    
    // Close button functionality
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    // Close when clicking outside the modal content
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

/**
 * Add a video to a playlist
 */
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
