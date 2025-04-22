<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();

// Get list of farms for the user
$query = "SELECT id, farm_name FROM farms WHERE user_id = ?";
$farms = $db->query($query, [$userId])->fetchAll(PDO::FETCH_ASSOC);

// Get list of wool types
$query = "SELECT id, type_name FROM wool_types";
$woolTypes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    
    try {
        // Validate input
        if (empty($_POST['farm_id']) || empty($_POST['wool_type_id']) || 
            empty($_POST['weight']) || empty($_POST['quality'])) {
            throw new Exception("All fields are required");
        }
        
        $farmId = filter_var($_POST['farm_id'], FILTER_VALIDATE_INT);
        $woolTypeId = filter_var($_POST['wool_type_id'], FILTER_VALIDATE_INT);
        $weight = filter_var($_POST['weight'], FILTER_VALIDATE_FLOAT);
        $quality = $_POST['quality'];
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$farmId || !$woolTypeId || !$weight) {
            throw new Exception("Invalid input values");
        }
        
        if (!in_array($quality, ['A', 'B', 'C', 'D', 'E'])) {
            throw new Exception("Invalid quality grade");
        }
        
        if ($weight <= 0 || $weight > 10000) { // Max 10 tons
            throw new Exception("Weight must be between 0 and 10,000 kg");
        }
        
        // Verify farm belongs to user
        $query = "SELECT id FROM farms WHERE id = ? AND user_id = ?";
        $farm = $db->query($query, [$farmId, $userId])->fetch(PDO::FETCH_ASSOC);
        
        if (!$farm) {
            throw new Exception("Invalid farm selected");
        }
        
        // Verify wool type exists
        $query = "SELECT id FROM wool_types WHERE id = ?";
        $woolType = $db->query($query, [$woolTypeId])->fetch(PDO::FETCH_ASSOC);
        
        if (!$woolType) {
            throw new Exception("Invalid wool type selected");
        }
        
        // Generate batch number (format: WB + YYYYMMDDHHMMSS)
        $batchNumber = "WB" . date('YmdHis');
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert new batch
            $query = "INSERT INTO wool_batches (batch_number, farm_id, wool_type_id, weight_kg, quality_grade, notes, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $db->query($query, [$batchNumber, $farmId, $woolTypeId, $weight, $quality, $notes]);
            
            // Get the inserted batch ID
            $query = "SELECT id FROM wool_batches WHERE batch_number = ?";
            $result = $db->query($query, [$batchNumber])->fetch(PDO::FETCH_ASSOC);
            $batchId = $result['id'];
            
            // Add to batch history
            $query = "INSERT INTO batch_history (batch_id, status, notes, created_at) VALUES (?, 'created', 'Batch created', NOW())";
            $db->query($query, [$batchId]);
            
            // Commit transaction
            $db->commit();
            
            $_SESSION['success_message'] = "Batch $batchNumber has been created successfully";
            header('Location: batches.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Database error: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        $error = "Error adding batch: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Batch - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.png" alt="Woolify" class="img-fluid" style="max-width: 120px;">
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="add_batch.php">
                        <i class="fas fa-plus-circle"></i> Add New Batch
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="batches.php">
                        <i class="fas fa-list"></i> View All Batches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="farms.php">
                        <i class="fas fa-warehouse"></i> View All Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <div class="container">
                <div class="row mb-4">
                    <div class="col">
                        <h1>Add New Batch</h1>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="add_batch.php" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="farm_id" class="form-label">Farm</label>
                                <select class="form-select" id="farm_id" name="farm_id" required>
                                    <option value="">Select Farm</option>
                                    <?php foreach ($farms as $farm): ?>
                                        <option value="<?php echo $farm['id']; ?>">
                                            <?php echo htmlspecialchars($farm['farm_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a farm.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="wool_type_id" class="form-label">Wool Type</label>
                                <select class="form-select" id="wool_type_id" name="wool_type_id" required>
                                    <option value="">Select Wool Type</option>
                                    <?php foreach ($woolTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a wool type.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="weight" name="weight" 
                                       step="0.01" min="0.01" max="10000" required>
                                <div class="invalid-feedback">
                                    Please enter a valid weight between 0.01 and 10,000 kg.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="quality" class="form-label">Quality Grade</label>
                                <select class="form-select" id="quality" name="quality" required>
                                    <option value="">Select Quality Grade</option>
                                    <option value="A">A - Excellent</option>
                                    <option value="B">B - Good</option>
                                    <option value="C">C - Average</option>
                                    <option value="D">D - Below Average</option>
                                    <option value="E">E - Poor</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a quality grade.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Add Batch</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Enable Bootstrap form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 