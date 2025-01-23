<?php
// admin/logout.php
require_once './includes/user_auth.php';

// Log the admin out
logoutUser();

// Redirect to login page
header('Location: register.php');
exit();
?>