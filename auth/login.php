<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validate input
    if (!$email || !$password) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: login.php');
        exit;
    }

    // Get user with role
    $query = "SELECT id, email, password, role FROM users WHERE email = ?";
    $user = $db->query($query, [$email])->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'farmer') {
            header('Location: ../farmer/dashboard.php');
        } else {
            header('Location: ../retailer/dashboard.php');
        }
        exit;
    } else {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/auth.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="../img/logo.png" alt="Woolify" class="img-fluid mb-4" style="max-width: 150px;">
                            <h2 class="fw-bold">Welcome back</h2>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? <a href="register.php">Create one</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 