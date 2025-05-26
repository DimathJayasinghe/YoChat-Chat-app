<?php
session_start();
// Unset all session variables
$_SESSION = array();
// Destroy the session
session_destroy();
// Remove the login cookie
setcookie('yochat_logged_in', '', time() - 3600, '/');
// Redirect to login page
header('Location: login.php');
exit();
