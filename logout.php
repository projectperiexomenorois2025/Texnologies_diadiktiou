<?php
// Include necessary files
require_once 'includes/header.php';

// Log out the user
logoutUser();

// Redirect to the home page
header("Location: index.php");
exit;
?>
