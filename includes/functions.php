<?php
// Common functions used throughout the application

/**
 * Sanitize user input
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = $conn->real_escape_string($data);
    }
    return $data;
}

/**
 * Check if a user is authenticated
 * @return boolean Authentication status
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page if not authenticated
 * @param string $redirect URL to redirect after login
 */
function requireAuth($redirect = null) {
    if (!isAuthenticated()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $redirect;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        header("Location: login.php");
        exit;
    }
}

/**
 * Get user details by ID
 * @param int $user_id User ID
 * @return array User details or null if not found
 */
function getUserById($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $sql = "SELECT user_id, username, first_name, last_name, email, created_at FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get playlists for a specific user
 * @param int $user_id User ID
 * @param boolean $include_private Whether to include private playlists
 * @return array Playlists
 */
function getUserPlaylists($user_id, $include_private = false) {
    global $conn;
    $user_id = (int)$user_id;
    
    $sql = "SELECT * FROM playlists WHERE user_id = ?";
    if (!$include_private) {
        $sql .= " AND is_public = 1";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $playlists = [];
    while ($row = $result->fetch_assoc()) {
        $playlists[] = $row;
    }
    
    return $playlists;
}

/**
 * Check if a user is following another user
 * @param int $follower_id The follower user ID
 * @param int $following_id The user being followed ID
 * @return boolean True if following, false otherwise
 */
function isFollowing($follower_id, $following_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Get videos in a playlist
 * @param int $playlist_id Playlist ID
 * @return array Videos in the playlist
 */
function getPlaylistVideos($playlist_id) {
    global $conn;
    $playlist_id = (int)$playlist_id;
    
    $sql = "SELECT * FROM videos WHERE playlist_id = ? ORDER BY added_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    
    return $videos;
}

/**
 * Get a specific playlist with details
 * @param int $playlist_id Playlist ID
 * @return array|null Playlist details or null if not found
 */
function getPlaylist($playlist_id) {
    global $conn;
    $playlist_id = (int)$playlist_id;
    
    $sql = "SELECT p.*, u.username, u.first_name, u.last_name FROM playlists p 
            JOIN users u ON p.user_id = u.user_id
            WHERE p.playlist_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Check if a user can access a playlist
 * @param int $user_id User ID
 * @param array $playlist Playlist details
 * @return boolean Access permission
 */
function canAccessPlaylist($user_id, $playlist) {
    // Owner can always access
    if ($user_id == $playlist['user_id']) {
        return true;
    }
    
    // Public playlists are accessible to anyone
    if ($playlist['is_public']) {
        return true;
    }
    
    // For private playlists, check if the user is following the playlist owner
    return isFollowing($user_id, $playlist['user_id']);
}

/**
 * Get users that a specific user is following
 * @param int $user_id User ID
 * @return array List of users being followed
 */
function getFollowing($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name 
            FROM followers f
            JOIN users u ON f.following_id = u.user_id
            WHERE f.follower_id = ?
            ORDER BY f.followed_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $following = [];
    while ($row = $result->fetch_assoc()) {
        $following[] = $row;
    }
    
    return $following;
}

/**
 * Get users who are following a specific user
 * @param int $user_id User ID
 * @return array List of followers
 */
function getFollowers($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name 
            FROM followers f
            JOIN users u ON f.follower_id = u.user_id
            WHERE f.following_id = ?
            ORDER BY f.followed_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $followers = [];
    while ($row = $result->fetch_assoc()) {
        $followers[] = $row;
    }
    
    return $followers;
}

/**
 * Search playlists based on criteria
 * @param string $query Text to search in titles
 * @param string $start_date Start date range (YYYY-MM-DD)
 * @param string $end_date End date range (YYYY-MM-DD)
 * @param string $user User search term (name, username, email)
 * @param int $page Page number
 * @param int $per_page Items per page
 * @return array Search results and pagination info
 */
function searchPlaylists($query = '', $start_date = '', $end_date = '', $user = '', $page = 1, $per_page = 10) {
    global $conn;
    
    $conditions = [];
    $params = [];
    $types = '';
    
    // Base query for public playlists or those belonging to users that the current user follows
    $sql_base = "SELECT DISTINCT p.*, u.username, u.first_name, u.last_name 
                FROM playlists p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN videos v ON p.playlist_id = v.playlist_id";
    
    // Add WHERE clause only if we have conditions
    if (!empty($query)) {
        $conditions[] = "(p.title LIKE ? OR v.title LIKE ?)";
        $search_term = "%" . $query . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }
    
    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = "(p.created_at BETWEEN ? AND ?)";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
        $types .= "ss";
    } else if (!empty($start_date)) {
        $conditions[] = "p.created_at >= ?";
        $params[] = $start_date . " 00:00:00";
        $types .= "s";
    } else if (!empty($end_date)) {
        $conditions[] = "p.created_at <= ?";
        $params[] = $end_date . " 23:59:59";
        $types .= "s";
    }
    
    if (!empty($user)) {
        $conditions[] = "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $user_term = "%" . $user . "%";
        $params[] = $user_term;
        $params[] = $user_term;
        $params[] = $user_term;
        $params[] = $user_term;
        $types .= "ssss";
    }
    
    // Only show public playlists unless the user is authenticated
    if (isAuthenticated()) {
        $current_user_id = $_SESSION['user_id'];
        $conditions[] = "(p.is_public = 1 OR p.user_id = ? OR p.user_id IN (SELECT following_id FROM followers WHERE follower_id = ?))";
        $params[] = $current_user_id;
        $params[] = $current_user_id;
        $types .= "ii";
    } else {
        $conditions[] = "p.is_public = 1";
    }
    
    // Combine conditions
    $where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
    
    // Count total results
    $count_sql = "SELECT COUNT(DISTINCT p.playlist_id) as total FROM playlists p 
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN videos v ON p.playlist_id = v.playlist_id" . $where_clause;
    
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    
    // Pagination
    $total_pages = ceil($total / $per_page);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;
    
    // Get results
    $sql = $sql_base . $where_clause . " GROUP BY p.playlist_id ORDER BY p.created_at DESC LIMIT ?, ?";
    $types .= "ii";
    $params[] = $offset;
    $params[] = $per_page;
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $playlists = [];
    while ($row = $result->fetch_assoc()) {
        $playlists[] = $row;
    }
    
    return [
        'playlists' => $playlists,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ];
}
?>
