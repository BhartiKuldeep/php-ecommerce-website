<?php
/**
 * User Logout
 * 
 * Destroys session and redirects to homepage.
 */
require_once __DIR__ . '/includes/init.php';

$_SESSION = [];
session_destroy();

// Start a new session to set flash message
session_start();
setFlash('info', 'You have been logged out.');
redirect('login.php');
