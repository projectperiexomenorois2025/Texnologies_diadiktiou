<?php
// Include necessary files
require_once 'includes/db_config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require authentication
if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Function to generate anonymous ID for a user
function getAnonymousId($user_id, $username) {
    return hash('sha256', $user_id . $username . 'streamify_salt');
}

// Function to build YAML data for playlists
function buildPlaylistsYaml($conn) {
    global $user_id;
    
    // Get all playlists (public ones and those created by the current user)
    $sql = "SELECT p.*, u.username 
            FROM playlists p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.is_public = 1 OR p.user_id = ?
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $playlists_result = $stmt->get_result();
    
    // Build YAML structure
    $yaml = "# Streamify Open Data Export\n";
    $yaml .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $yaml .= "playlists:\n";
    
    while ($playlist = $playlists_result->fetch_assoc()) {
        $playlist_id = $playlist['playlist_id'];
        $anonymous_user_id = getAnonymousId($playlist['user_id'], $playlist['username']);
        
        $yaml .= "  - id: " . $playlist_id . "\n";
        $yaml .= "    title: \"" . str_replace('"', '\"', $playlist['title']) . "\"\n";
        $yaml .= "    user_id: \"" . $anonymous_user_id . "\"\n";
        $yaml .= "    is_public: " . ($playlist['is_public'] ? "true" : "false") . "\n";
        $yaml .= "    created_at: " . $playlist['created_at'] . "\n";
        
        // Get videos in the playlist
        $videos_sql = "SELECT * FROM videos WHERE playlist_id = ? ORDER BY added_at ASC";
        $videos_stmt = $conn->prepare($videos_sql);
        $videos_stmt->bind_param("i", $playlist_id);
        $videos_stmt->execute();
        $videos_result = $videos_stmt->get_result();
        
        if ($videos_result->num_rows > 0) {
            $yaml .= "    videos:\n";
            
            while ($video = $videos_result->fetch_assoc()) {
                $yaml .= "      - youtube_id: \"" . $video['youtube_id'] . "\"\n";
                $yaml .= "        title: \"" . str_replace('"', '\"', $video['title']) . "\"\n";
                $yaml .= "        added_at: " . $video['added_at'] . "\n";
            }
        }
        
        $yaml .= "\n";
    }
    
    // Get users who have public playlists or are followed by the current user
    $users_sql = "SELECT DISTINCT u.user_id, u.username 
                  FROM users u
                  LEFT JOIN playlists p ON u.user_id = p.user_id
                  LEFT JOIN followers f ON u.user_id = f.following_id
                  WHERE p.is_public = 1 OR f.follower_id = ?";
    
    $users_stmt = $conn->prepare($users_sql);
    $users_stmt->bind_param("i", $user_id);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    
    if ($users_result->num_rows > 0) {
        $yaml .= "users:\n";
        
        while ($user = $users_result->fetch_assoc()) {
            $anonymous_user_id = getAnonymousId($user['user_id'], $user['username']);
            $yaml .= "  - id: \"" . $anonymous_user_id . "\"\n";
            
            // Get followers for this user (anonymized)
            $followers_sql = "SELECT u.user_id, u.username 
                             FROM followers f
                             JOIN users u ON f.follower_id = u.user_id
                             WHERE f.following_id = ?";
            
            $followers_stmt = $conn->prepare($followers_sql);
            $followers_stmt->bind_param("i", $user['user_id']);
            $followers_stmt->execute();
            $followers_result = $followers_stmt->get_result();
            
            if ($followers_result->num_rows > 0) {
                $yaml .= "    followers:\n";
                
                while ($follower = $followers_result->fetch_assoc()) {
                    $anonymous_follower_id = getAnonymousId($follower['user_id'], $follower['username']);
                    $yaml .= "      - \"" . $anonymous_follower_id . "\"\n";
                }
            }
            
            $yaml .= "\n";
        }
    }
    
    return $yaml;
}

// Set headers for YAML download
header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="streamify_export_' . date('Y-m-d') . '.yaml"');

// Generate and output YAML
echo buildPlaylistsYaml($conn);
exit;
?>
