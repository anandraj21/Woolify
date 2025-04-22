<?php
require_once 'includes/auth.php';

// Check if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $farmName = $_POST['farm_name'] ?? '';
    $storeName = $_POST['store_name'] ?? '';
    $address = $_POST['address'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif ($role === 'FARMER' && empty($farmName)) {
        $error = 'Farm name is required for farmers';
    } elseif ($role === 'RETAILER' && empty($storeName)) {
        $error = 'Store name is required for retailers';
    } else {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Check if email already exists
            $stmt = $db->query("SELECT id FROM users WHERE email = ?", [$email]);
            if ($stmt->fetch()) {
                throw new Exception('Email already registered');
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->query(
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)",
                [$name, $email, $hashedPassword, $role]
            );
            $userId = $db->lastInsertId();

            // Create role-specific record
            if ($role === 'FARMER') {
                $db->query(
                    "INSERT INTO farmers (user_id, farm_name, farm_address) VALUES (?, ?, ?)",
                    [$userId, $farmName, $address]
                );
            } elseif ($role === 'RETAILER') {
                $db->query(
                    "INSERT INTO retailers (user_id, store_name, store_address) VALUES (?, ?, ?)",
                    [$userId, $storeName, $address]
                );
            }

            $db->commit();
            $success = 'Registration successful! You can now login.';
            
            // Redirect to login page after successful registration
            header('Location: login.php?registered=1');
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Woolify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">Join the Woolify community</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="name" class="sr-only">Full Name</label>
                        <input id="name" name="name" type="text" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Full Name">
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                    <div>
                        <label for="confirm_password" class="sr-only">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Confirm Password">
                    </div>
                    <div>
                        <label for="role" class="sr-only">Role</label>
                        <select id="role" name="role" required 
                                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm">
                            <option value="">Select Role</option>
                            <option value="FARMER">Farmer</option>
                            <option value="RETAILER">Retailer</option>
                        </select>
                    </div>
                    <div id="farmer-fields" class="hidden">
                        <label for="farm_name" class="sr-only">Farm Name</label>
                        <input id="farm_name" name="farm_name" type="text" 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Farm Name">
                    </div>
                    <div id="retailer-fields" class="hidden">
                        <label for="store_name" class="sr-only">Store Name</label>
                        <input id="store_name" name="store_name" type="text" 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Store Name">
                    </div>
                    <div>
                        <label for="address" class="sr-only">Address</label>
                        <input id="address" name="address" type="text" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Address">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus text-green-500 group-hover:text-green-400"></i>
                        </span>
                        Register
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-green-600 hover:text-green-500">
                        Sign in
                    </a>
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    <a href="index.php" class="font-medium text-green-600 hover:text-green-500">
                        <i class="fas fa-home"></i> Go to Home Page
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('role').addEventListener('change', function() {
            const farmerFields = document.getElementById('farmer-fields');
            const retailerFields = document.getElementById('retailer-fields');
            
            if (this.value === 'FARMER') {
                farmerFields.classList.remove('hidden');
                retailerFields.classList.add('hidden');
            } else if (this.value === 'RETAILER') {
                farmerFields.classList.add('hidden');
                retailerFields.classList.remove('hidden');
            } else {
                farmerFields.classList.add('hidden');
                retailerFields.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 