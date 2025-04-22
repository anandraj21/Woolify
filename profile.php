<?php
// This profile page might be accessible by multiple roles, or just logged-in users
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

$auth->requireLogin(); // Require login, but not a specific role initially

$user = $auth->getUser();
$userId = $user['id'];
$userEmail = $user['email']; // Keep original email for password verification
$role = $user['role']; // Get role to potentially adjust includes/display

$userModel = new User();

$pageTitle = "My Profile";
$profileSuccess = '';
$profileError = '';
$passwordSuccess = '';
$passwordError = '';

// --- Handle Profile Info Update --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (empty($name)) {
        $profileError = "Name cannot be empty.";
    } elseif (!$email) {
        $profileError = "Please enter a valid email address.";
    } elseif ($email !== $userEmail && $userModel->findByEmail($email)) { 
        // Check if email exists ONLY if it's being changed
        $profileError = "This email address is already in use.";
    } else {
        $updateData = [
            'name' => $name,
            'email' => $email,
        ];
        try {
            if ($userModel->updateUser($userId, $updateData)) {
                $profileSuccess = "Profile details updated successfully.";
                // Refresh user data in session? Auth class might need a refresh method
                // For now, just show success. User might need to log out/in to see changes everywhere.
                $user['name'] = $name; // Update local variable for immediate display
                $user['email'] = $email;
                $userEmail = $email; // Update email used for password check
            } else {
                $profileError = "Failed to update profile details.";
            }
        } catch (Exception $e) {
            error_log("Error updating profile for user {$userId}: " . $e->getMessage());
            $profileError = "An unexpected error occurred while updating profile.";
        }
    }
}

// --- Handle Password Change --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordError = "Please fill in all password fields.";
    } elseif (!$userModel->verifyPassword($userEmail, $currentPassword)) { // Verify against current user email
        $passwordError = "Incorrect current password.";
    } elseif (strlen($newPassword) < 6) { // Basic password strength check
        $passwordError = "New password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "New passwords do not match.";
    } else {
        $updateData = ['password' => $newPassword];
        try {
            if ($userModel->updateUser($userId, $updateData)) {
                $passwordSuccess = "Password changed successfully.";
            } else {
                $passwordError = "Failed to change password.";
            }
        } catch (Exception $e) {
             error_log("Error changing password for user {$userId}: " . $e->getMessage());
            $passwordError = "An unexpected error occurred while changing password.";
        }
    }
}

include __DIR__ . '/includes/header.php'; 
?>
    <div class="dashboard-container">
    <?php 
    // Include the correct sidebar based on role
    if ($role === 'FARMER') {
        include __DIR__ . '/includes/sidebar.php';
    } elseif ($role === 'RETAILER') {
        include __DIR__ . '/includes/sidebar.php'; // Use the same sidebar for now
    } else {
        include __DIR__ . '/includes/sidebar.php'; 
    }
    ?>
        <main class="main-content">
        <?php include __DIR__ . '/includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">Update Profile Information</div>
                        <div class="card-body">
                            <?php if ($profileSuccess): ?>
                                <div class="alert alert-success"><?php echo $profileSuccess; ?></div>
                            <?php endif; ?>
                            <?php if ($profileError): ?>
                                <div class="alert alert-danger"><?php echo $profileError; ?></div>
                                <?php endif; ?>
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                                </div>

                <div class="col-lg-6 mb-4">
                     <div class="card">
                        <div class="card-header">Change Password</div>
                        <div class="card-body">
                             <?php if ($passwordSuccess): ?>
                                <div class="alert alert-success"><?php echo $passwordSuccess; ?></div>
                            <?php endif; ?>
                            <?php if ($passwordError): ?>
                                <div class="alert alert-danger"><?php echo $passwordError; ?></div>
                            <?php endif; ?>
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                 <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
             <!-- TODO: Add Profile Picture Upload -->

            </div>
        </main>
    </div>
<!-- <?php include __DIR__ . '/includes/footer.php'; ?>  -->