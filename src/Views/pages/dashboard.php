<?php
require_once 'includes/auth.php'; // Ensure auth is included first

// Use the Auth class method to require login
$auth->requireLogin();

$user = $auth->getUser(); // Get user data via the Auth class method

// Check if user data exists and has a role
if (!$user || !isset($user['role'])) {
    // Handle cases where user data or role is missing
    // Log error, redirect to login, or show an error message
    error_log("User data or role missing in session for user ID: " . ($user['id'] ?? 'unknown'));
    $auth->logout(); // Log out the user to be safe
    header('Location: login.php?error=session_issue');
    exit();
}

$role = strtoupper($user['role']); // Convert role to uppercase for case-insensitive comparison

// Redirect based on role
if ($role === 'FARMER') {
    header('Location: farmer/dashboard.php');
    exit();
} elseif ($role === 'RETAILER') {
    header('Location: retailer/dashboard.php');
    exit();
} else {
    // Handle unexpected roles
    error_log("Invalid user role encountered: " . $user['role'] . " for user ID: " . $user['id']);
    echo "Invalid user role detected. Please contact support.";
    exit();
}
?> 