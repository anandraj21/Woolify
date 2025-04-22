<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Farm.php';

$auth->requireRole('FARMER');
$pageTitle = "Add/Update Farm Details";

$user = $auth->getUser();
$userId = $user['id'];

$farmModel = new Farm();
$farmData = null;
$error = '';
$success = '';

// Attempt to fetch existing farm data
try {
    $farmData = $farmModel->findFarmByUserId($userId);
} catch (Exception $e) {
    error_log("Error fetching farm data for user {$userId} in add_farm.php: " . $e->getMessage());
    $error = "Could not retrieve farm details. Please try again later.";
    // Optional: You might want to stop execution here if fetching fails critically
}

// If farm details already seem complete, maybe redirect or just show info
// For simplicity, we'll allow updating via this form for now, 
// but a dedicated edit page (`edit_farm.php`) would be better.

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $farmData) { // Ensure we have the farmer record ID
    $farmerId = $farmData['id']; // Get the ID from the farmers table
    
    // Sanitize and validate input
    $farmName = filter_input(INPUT_POST, 'farm_name', FILTER_SANITIZE_STRING);
    $farmLocation = filter_input(INPUT_POST, 'farm_location', FILTER_SANITIZE_STRING);
    $farmSize = filter_input(INPUT_POST, 'farm_size_acres', FILTER_VALIDATE_FLOAT);
    $contactNumber = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);

    // Basic Validation
    if (empty($farmName)) {
        $error = "Farm Name is required.";
    } elseif (empty($farmLocation)) {
        $error = "Farm Location is required.";
    } else {
        $updateData = [
            'farm_name' => $farmName,
            'farm_location' => $farmLocation,
            // Only include size if it's a valid positive number
            'farm_size_acres' => ($farmSize !== false && $farmSize > 0) ? $farmSize : null, 
            'contact_number' => $contactNumber ?: null // Store null if empty
        ];

        try {
            if ($farmModel->updateFarmDetails($farmerId, $updateData)) {
                $success = "Farm details updated successfully!";
                // Re-fetch data to display updated info
                $farmData = $farmModel->findFarmByFarmerId($farmerId); 
                 // Optionally redirect
                 // header('Location: farms.php?success=updated');
                 // exit();
            } else {
                $error = "Failed to update farm details. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Error updating farm details for farmer {$farmerId}: " . $e->getMessage());
            $error = "An unexpected error occurred during update.";
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
            <h1><?php echo $pageTitle; ?></h1>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                     <?php echo $error; ?>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!$farmData && !$error): // Show only if fetch didn't error but no data found ?>
                 <div class="alert alert-danger" role="alert">
                    Error: Could not find the farmer record associated with your user account (ID: <?php echo $userId; ?>). Cannot add/update details.
                 </div>
            <?php elseif ($farmData): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Enter/Update Your Farm Information</h5>
                        <p class="card-text text-muted">Please provide the following details about your farm.</p>
                        
                        <form method="POST" action="add_farm.php" class="mt-3">
                            <div class="mb-3">
                                <label for="farm_name" class="form-label">Farm Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="farm_name" name="farm_name" required value="<?php echo htmlspecialchars($farmData['farm_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="farm_location" class="form-label">Location / Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="farm_location" name="farm_location" required value="<?php echo htmlspecialchars($farmData['farm_location'] ?? ''); ?>">
                            </div>
                             <div class="mb-3">
                                <label for="farm_size_acres" class="form-label">Farm Size (Acres)</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="farm_size_acres" name="farm_size_acres" value="<?php echo htmlspecialchars($farmData['farm_size_acres'] ?? ''); ?>">
                            </div>
                             <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($farmData['contact_number'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Farm Details
                            </button>
                            <a href="farms.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            <?php endif; // end if $farmData exists ?>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?> 