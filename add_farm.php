<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farm_name = filter_input(INPUT_POST, 'farm_name', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $registration_number = filter_input(INPUT_POST, 'registration_number', FILTER_SANITIZE_STRING);
    $sheep_count = filter_input(INPUT_POST, 'sheep_count', FILTER_VALIDATE_INT);

    if ($farm_name && $location && $registration_number && $sheep_count !== false) {
        try {
            $stmt = $conn->prepare("INSERT INTO farms (user_id, farm_name, location, registration_number, sheep_count) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $farm_name, $location, $registration_number, $sheep_count])) {
                $success_message = "Farm added successfully!";
            } else {
                $error_message = "Failed to add farm. Please try again.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error_message = "Registration number already exists.";
            } else {
                $error_message = "An error occurred. Please try again.";
            }
        }
    } else {
        $error_message = "Please fill all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Farm - Woolify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Add New Farm</h2>
                        <p class="mt-1 text-sm text-gray-600">Enter your farm details below</p>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="add_farm.php" class="space-y-6">
                        <div>
                            <label for="farm_name" class="block text-sm font-medium text-gray-700">Farm Name</label>
                            <input type="text" name="farm_name" id="farm_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter farm name">
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" name="location" id="location" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter farm location">
                        </div>

                        <div>
                            <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                            <input type="text" name="registration_number" id="registration_number" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter registration number">
                        </div>

                        <div>
                            <label for="sheep_count" class="block text-sm font-medium text-gray-700">Number of Sheep</label>
                            <input type="number" name="sheep_count" id="sheep_count" required min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter number of sheep">
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-900">Cancel</a>
                            <button type="submit"
                                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Add Farm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html> 