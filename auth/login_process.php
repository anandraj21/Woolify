<?php

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];

    // Redirect based on user role
    if ($user['role'] === 'farmer') {
        header('Location: ../farmer/dashboard.php');
    } else {
        header('Location: ../retailer/dashboard.php');
    }
    exit;
} else {
    $_SESSION['error'] = 'Invalid email or password';
    header('Location: ../login.php');
    exit;
} 