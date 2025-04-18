<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Sanitize and validate input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Validation
    $errors = [];
    if (!$name || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['farmer', 'retailer'])) {
        $errors[] = 'Please select a valid role.';
    }

    // Check if email already exists
    $query = "SELECT COUNT(*) FROM users WHERE email = ?";
    $count = $db->query($query, [$email])->fetchColumn();
    if ($count > 0) {
        $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        try {
            $db->query($query, [$name, $email, $hashed_password, $role]);
            $_SESSION['success'] = 'Registration successful! Please login.';
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Woolify</title>
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
                            <h2 class="fw-bold">Create Account</h2>
                            <p class="text-muted">Join the Woolify platform</p>
                        </div>

                        <?php if (isset($_SESSION['errors'])): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php 
                                    foreach ($_SESSION['errors'] as $error) {
                                        echo "<li>$error</li>";
                                    }
                                    unset($_SESSION['errors']);
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">I am a...</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select your role</option>
                                    <option value="farmer" <?php echo isset($_POST['role']) && $_POST['role'] === 'farmer' ? 'selected' : ''; ?>>
                                        Farmer
                                    </option>
                                    <option value="retailer" <?php echo isset($_POST['role']) && $_POST['role'] === 'retailer' ? 'selected' : ''; ?>>
                                        Retailer
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? <a href="login.php">Sign in</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 