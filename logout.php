<?php
require_once 'utils/Session.php';

// Start session
Session::start();

// Destroy session
Session::destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>