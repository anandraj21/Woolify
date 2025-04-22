<?php
require_once __DIR__ . '/../includes/auth.php';
$auth->requireRole('FARMER');

// Add grade configuration at the top after auth check
$VALID_GRADES = ['A' => 'Premium', 'B' => 'High', 'C' => 'Standard'];

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$dbInstance = Database::getInstance();
$stmtFarmer = $dbInstance->query("SELECT id FROM farmers WHERE user_id = ?", [$userId]);
$farmer = $stmtFarmer->fetch();
if (!$farmer) {
    die("Error: Farmer record not found for this user.");
}
$farmerId = $farmer['id'];

$pageTitle = "Create Wool Batch";
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
    $micron = filter_input(INPUT_POST, 'micron', FILTER_VALIDATE_FLOAT);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $status = 'AVAILABLE';

    // Enhanced validation
    if (!$quantity || $quantity <= 0) {
        $error = "Please enter a valid quantity (positive number).";
    } elseif (!$micron || $micron <= 0 || $micron > 40) { // Added upper limit for micron
        $error = "Please enter a valid micron value (between 0 and 40).";
    } elseif (!array_key_exists(strtoupper($grade), $VALID_GRADES)) {
        $error = "Please select a valid grade (" . implode(', ', array_keys($VALID_GRADES)) . ").";
    } elseif ($price === false || $price < 0) {
        $error = "Please enter a valid price (non-negative number).";
    } else {
        try {
            require_once __DIR__ . '/../models/WoolBatch.php';
            $woolBatchModel = new WoolBatch();
            $data = [
                'farmer_id' => $farmerId,
                'quantity' => $quantity,
                'micron' => $micron,                    
                'grade' => strtoupper($grade),
                'status' => $status,
                'price_per_kg' => $price,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($woolBatchModel->create($data)) {
                $_SESSION['success_message'] = "New wool batch created successfully!";
                header('Location: batches.php');
                exit();
            } else {
                $error = "Failed to create wool batch. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Error creating batch: " . $e->getMessage());
            // Check for specific database errors
            if (strpos($e->getMessage(), "Column 'created_at' cannot be null") !== false) {
                $error = "Database error: Missing creation timestamp.";
            } else if (strpos($e->getMessage(), "Column 'updated_at' cannot be null") !== false) {
                $error = "Database error: Missing update timestamp.";
            } else if (strpos($e->getMessage(), "foreign key constraint fails") !== false) {
                $error = "Database error: Invalid farmer ID.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        } catch (Exception $e) {
            error_log("Error creating batch: " . $e->getMessage());
            $error = "An unexpected error occurred. Please contact support. Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <div class="page-header d-flex align-items-center mb-4">
                <img src="../assets/images/wool-icon.png" alt="Wool Icon" class="me-3" style="width: 40px; height: 40px;">
                <h1 class="mb-0"><?php echo $pageTitle; ?></h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-plus-circle text-primary me-2"></i>
                                Enter New Batch Details
                            </h5>
                            <form method="POST" action="add_batch.php" class="mt-3">
                                <div class="mb-4">
                                    <label for="quantity" class="form-label fw-bold">
                                        <i class="fas fa-balance-scale me-2 text-primary"></i>
                                        Quantity (kg)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                        <input type="number" step="0.1" class="form-control" id="quantity" name="quantity" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="micron" class="form-label fw-bold">
                                        <i class="fas fa-ruler me-2 text-primary"></i>
                                        Micron
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-microscope"></i></span>
                                        <input type="number" step="0.1" class="form-control" id="micron" name="micron" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="grade" class="form-label fw-bold">
                                        <i class="fas fa-star me-2 text-primary"></i>
                                        Grade
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                        <select class="form-select" id="grade" name="grade" required>
                                            <option value="">Select Grade...</option>
                                            <option value="A">A (Premium)</option>
                                            <option value="B">B (High)</option>
                                            <option value="C">C (Standard)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="price" class="form-label fw-bold">
                                        <i class="fas fa-dollar-sign me-2 text-primary"></i>
                                        Price per kg ($)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required min="0">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Create Batch
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 d-flex align-items-center justify-content-center">
                            <img src="../assets/images/wool-batch.jpg" alt="Wool Batch" class="img-fluid rounded shadow" style="max-height: 400px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 10px 15px;
    }
    .input-group-text {
        background-color: #f8f9fa;
        border-right: none;
    }
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    .btn-primary {
        padding: 10px 25px;
        border-radius: 8px;
    }
    .btn-outline-secondary {
        padding: 10px 25px;
        border-radius: 8px;
    }
    .alert {
        border-radius: 10px;
        padding: 15px 20px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const quantity = parseFloat(document.getElementById('quantity').value);
        const micron = parseFloat(document.getElementById('micron').value);
        const price = parseFloat(document.getElementById('price').value);
        
        let isValid = true;
        let errorMessage = '';
        
        if (quantity <= 0) {
            errorMessage = 'Quantity must be a positive number.';
            isValid = false;
        } else if (micron <= 0 || micron > 40) {
            errorMessage = 'Micron must be between 0 and 40.';
            isValid = false;
        } else if (price < 0) {
            errorMessage = 'Price cannot be negative.';
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
});
</script>
<!-- <?php include __DIR__ . '/../includes/footer.php'; ?>  -->