<?php
/**
 * YouTube API Configuration
 * 
 * This file contains configuration and helper functions
 * for the YouTube Data API integration
 */

// YouTube API Constants
define('YOUTUBE_API_KEY', getenv('YOUTUBE_API_KEY') ?: '');
define('YOUTUBE_CLIENT_ID', getenv('YOUTUBE_CLIENT_ID') ?: '');
define('YOUTUBE_CLIENT_SECRET', getenv('YOUTUBE_CLIENT_SECRET') ?: '');
define('YOUTUBE_REDIRECT_URI', 'http://' . $_SERVER['HTTP_HOST'] . '/search_youtube.php');
define('YOUTUBE_API_URL', 'https://www.googleapis.com/youtube/v3/');

/**
 * Get Google OAuth URL for user authentication
 * @return string Google auth URL
 */
function getGoogleAuthUrl() {
    $scope = urlencode('https://www.googleapis.com/auth/youtube.readonly');
    $redirect_uri = urlencode(YOUTUBE_REDIRECT_URI);
    
    return "https://accounts.google.com/o/oauth2/auth?client_id=" . YOUTUBE_CLIENT_ID 
         . "&redirect_uri={$redirect_uri}&scope={$scope}&response_type=code&access_type=offline";
}

/**
 * Exchange authorization code for access token
 * @param string $code Authorization code from Google
 * @return array|false Access token data or false on failure
 */
function getAccessToken($code) {
    $url = 'https://oauth2.googleapis.com/token';
    $data = [
        'code' => $code,
        'client_id' => YOUTUBE_CLIENT_ID,
        'client_secret' => YOUTUBE_CLIENT_SECRET,
        'redirect_uri' => YOUTUBE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Search YouTube for videos
 * @param string $query Search query
 * @param string $access_token OAuth access token
 * @param int $max_results Maximum number of results to return
 * @return array Search results
 */
function searchYouTube($query, $access_token, $max_results = 10) {
    $url = YOUTUBE_API_URL . 'search?part=snippet&type=video&q=' . urlencode($query) 
         . '&maxResults=' . $max_results;
    
    $options = [
        'http' => [
            'header' => "Authorization: Bearer {$access_token}\r\n",
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return ['error' => 'Failed to search YouTube'];
    }
    
    return json_decode($result, true);
}

/**
 * Simple fallback search using API key instead of OAuth
 * @param string $query Search query
 * @param int $max_results Maximum number of results to return
 * @return array Search results
 */
function searchYouTubeWithApiKey($query, $max_results = 10) {
    $url = YOUTUBE_API_URL . 'search?part=snippet&type=video&q=' . urlencode($query) 
         . '&maxResults=' . $max_results . '&key=' . YOUTUBE_API_KEY;
    
    $result = file_get_contents($url);
    
    if ($result === FALSE) {
        return ['error' => 'Failed to search YouTube'];
    }
    
    return json_decode($result, true);
}

/**
 * Get video details by ID
 * @param string $video_id YouTube video ID
 * @return array Video details
 */
function getVideoDetails($video_id) {
    $url = YOUTUBE_API_URL . 'videos?part=snippet,contentDetails&id=' . $video_id . '&key=' . YOUTUBE_API_KEY;
    
    $result = file_get_contents($url);
    
    if ($result === FALSE) {
        return ['error' => 'Failed to get video details'];
    }
    
    return json_decode($result, true);
}
