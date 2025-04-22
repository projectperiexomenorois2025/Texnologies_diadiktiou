<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Register a new user
 * @param string $username Username
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $email Email
 * @param string $password Password (plain text)
 * @return array Status and message
 */
function registerUser($username, $first_name, $last_name, $email, $password) {
    global $conn;
    
    // Sanitize inputs
    $username = sanitize($username);
    $first_name = sanitize($first_name);
    $last_name = sanitize($last_name);
    $email = sanitize($email);
    
    // Validate inputs
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $first_name, $last_name, $email, $password_hash);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
}

/**
 * Authenticate a user
 * @param string $username Username
 * @param string $password Password
 * @return array Status, message and user ID if successful
 */
function loginUser($username, $password) {
    global $conn;
    
    // Sanitize input
    $username = sanitize($username);
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required'];
    }
    
    // Get user
    $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Start session and store user info
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        
        return [
            'success' => true, 
            'message' => 'Login successful', 
            'user_id' => $user['user_id']
        ];
    } else {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

/**
 * Update user profile
 * @param int $user_id User ID
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $email Email
 * @param string $current_password Current password (for verification)
 * @param string $new_password New password (optional)
 * @return array Status and message
 */
function updateProfile($user_id, $first_name, $last_name, $email, $current_password, $new_password = '') {
    global $conn;
    
    // Sanitize inputs
    $user_id = (int)$user_id;
    $first_name = sanitize($first_name);
    $last_name = sanitize($last_name);
    $email = sanitize($email);
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($current_password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Update user info
    if (!empty($new_password)) {
        // Update with new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $new_password_hash, $user_id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
    }
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Profile update failed: ' . $conn->error];
    }
}

/**
 * Delete a user account
 * @param int $user_id User ID
 * @param string $password Password for verification
 * @return array Status and message
 */
function deleteAccount($user_id, $password) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    // Verify password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Password is incorrect'];
    }
    
    // Delete user (cascading deletes will handle playlists and videos)
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Log out user
        logoutUser();
        return ['success' => true, 'message' => 'Account deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Account deletion failed: ' . $conn->error];
    }
}
?>
