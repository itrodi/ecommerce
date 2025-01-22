<?php
// admin/logout.php
require_once '../includes/auth.php';

// Log the admin out
adminLogout();

// Redirect to login page
header('Location: login.php');
exit();
?>