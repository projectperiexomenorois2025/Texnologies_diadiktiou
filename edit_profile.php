<?php
// Include header
require_once 'includes/header.php';

// Require authentication
requireAuth();

// Get current user details
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// If user not found, show error
if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    require_once 'includes/footer.php';
    exit;
}

$errors = [];
$success_message = '';

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate data
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    }
    
    if (empty($current_password)) {
        $errors['current_password'] = 'Current password is required to update profile';
    }
    
    // Validate new password if provided
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors['new_password'] = 'New password must be at least 6 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $result = updateProfile($user_id, $first_name, $last_name, $email, $current_password, $new_password);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Refresh user data
            $user = getUserById($user_id);
        } else {
            $errors['general'] = $result['message'];
        }
    }
}

// Process account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = isset($_POST['delete_password']) ? $_POST['delete_password'] : '';
    
    if (empty($password)) {
        $errors['delete_password'] = 'Password is required to delete account';
    } else {
        $result = deleteAccount($user_id, $password);
        
        if ($result['success']) {
            // User will be logged out and redirected by the deleteAccount function
            header("Location: index.php");
            exit;
        } else {
            $errors['delete_account'] = $result['message'];
        }
    }
}
?>

<section class="edit-profile-section">
    <div class="form-container">
        <h1 class="form-title">Edit Your Profile</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <form id="profile-form" method="POST" action="edit_profile.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                <small class="form-text">Username cannot be changed</small>
            </div>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                <?php if (isset($errors['first_name'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                <?php if (isset($errors['last_name'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
                <small class="form-text">Required to update your profile</small>
                <?php if (isset($errors['current_password'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['current_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
                <small class="form-text">Leave blank to keep current password</small>
                <?php if (isset($errors['new_password'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['new_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit" name="update_profile" class="form-submit">Update Profile</button>
        </form>
        
        <div class="delete-account-section">
            <h2>Delete Account</h2>
            <p>Once deleted, your account and all associated playlists cannot be recovered.</p>
            
            <?php if (isset($errors['delete_account'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errors['delete_account']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="edit_profile.php" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                <div class="form-group">
                    <label for="delete_password">Password</label>
                    <input type="password" class="form-control" id="delete_password" name="delete_password">
                    <small class="form-text">Enter your password to confirm account deletion</small>
                    <?php if (isset($errors['delete_password'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['delete_password']); ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="delete_account" class="btn btn-danger" data-confirm="Are you sure you want to delete your account? This action cannot be undone.">Delete Account</button>
            </form>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
