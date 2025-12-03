<?php
// Start the session to be able to access it
include 'php/language_init.php';
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login or home page
header('Location: index.php');
exit;
?>