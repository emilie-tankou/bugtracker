<?php
/**
 * Index page - redirects users based on authentication status
 */
require_once 'config.php';

// If user is authenticated, redirect to dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
} else {
    // Otherwise, redirect to login page
    header('Location: login.php');
}
exit;