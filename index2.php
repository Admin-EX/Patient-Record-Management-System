<?php
// redirect_to_login.php - Enhanced redirect page

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing session data (optional)
session_unset();

// Set a message to display on the login page (optional)
$_SESSION['redirect_message'] = "You were redirected to the login page";

// Perform the redirect
header("Location: login.php");
exit(); // Ensure no further code is executed
?>