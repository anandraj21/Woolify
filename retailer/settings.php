<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Retailer.php';
require_once __DIR__ . '/../models/User.php';

// Authentication & Authorization
$auth->requireRole('RETAILER'); 
$user = $auth->getUser();
if (!$user) {
    header('Location: ../login.php');
    exit();
}

$userId = $user['id'];
$retailerModel = new Retailer();
$retailerData = $retailerModel->findByUserId($userId);

$pageTitle = "Settings";
$settingsSuccess = '';
$settingsError = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_store_settings'])) {
        $storeName = filter_input(INPUT_POST, 'store_name', FILTER_SANITIZE_STRING);
        $storeAddress = filter_input(INPUT_POST, 'store_address', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $businessLicense = filter_input(INPUT_POST, 'business_license', FILTER_SANITIZE_STRING);

        try {
            $updateData = [
                'store_name' => $storeName,
                'store_address' => $storeAddress,
                'phone' => $phone,
                'business_license' => $businessLicense
            ];
            
            if ($retailerModel->update($retailerData['id'], $updateData)) {
                $settingsSuccess = "Store settings updated successfully.";
                $retailerData = $retailerModel->findByUserId($userId); // Refresh data
            } else {
                $settingsError = "Failed to update store settings.";
            }
        } catch (Exception $e) {
            error_log("Error updating retailer settings: " . $e->getMessage());
            $settingsError = "An unexpected error occurred.";
        }
    }

    if (isset($_POST['update_notification_preferences'])) {
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $priceAlerts = isset($_POST['price_alerts']) ? 1 : 0;
        $newBatchAlerts = isset($_POST['new_batch_alerts']) ? 1 : 0;

        try {
            $updateData = [
                'email_notifications' => $emailNotifications,
                'sms_notifications' => $smsNotifications,
                'price_alerts' => $priceAlerts,
                'new_batch_alerts' => $newBatchAlerts
            ];
            
            if ($retailerModel->updatePreferences($retailerData['id'], $updateData)) {
                $settingsSuccess = "Notification preferences updated successfully.";
                $retailerData = $retailerModel->findByUserId($userId); // Refresh data
            } else {
                $settingsError = "Failed to update notification preferences.";
            }
        } catch (Exception $e) {
            error_log("Error updating notification preferences: " . $e->getMessage());
            $settingsError = "An unexpected error occurred.";
        }
    }
}

include __DIR__ . '/../includes/header.php'; 
?>

<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <nav class="top-nav shadow-sm">
            <div class="nav-left">
                <button class="sidebar-toggle btn btn-link me-2 d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="nav-right">
                <!-- User Profile -->
                <div class="nav-item user-profile me-3">
                    <div class="d-flex align-items-center">
                        <div class="user-info me-3">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role">Retailer</div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Dropdown -->
                <div class="nav-item dropdown">
                    <button class="btn btn-icon dropdown-toggle" type="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="help.php"><i class="fas fa-question-circle me-2"></i>Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="dashboard-content">
            <?php if ($settingsSuccess): ?>
                <div class="alert alert-success"><?php echo $settingsSuccess; ?></div>
            <?php endif; ?>
            <?php if ($settingsError): ?>
                <div class="alert alert-danger"><?php echo $settingsError; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Store Settings -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Store Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="settings.php">
                                <input type="hidden" name="update_store_settings" value="1">
                                <div class="mb-3">
                                    <label for="store_name" class="form-label">Store Name</label>
                                    <input type="text" class="form-control" id="store_name" name="store_name" 
                                           value="<?php echo htmlspecialchars($retailerData['store_name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="store_address" class="form-label">Store Address</label>
                                    <textarea class="form-control" id="store_address" name="store_address" rows="3" required><?php echo htmlspecialchars($retailerData['store_address'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($retailerData['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="business_license" class="form-label">Business License Number</label>
                                    <input type="text" class="form-control" id="business_license" name="business_license" 
                                           value="<?php echo htmlspecialchars($retailerData['business_license'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Store Settings</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notification Preferences -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Notification Preferences</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="settings.php">
                                <input type="hidden" name="update_notification_preferences" value="1">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications"
                                               <?php echo ($retailerData['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications">Email Notifications</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications"
                                               <?php echo ($retailerData['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sms_notifications">SMS Notifications</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="price_alerts" name="price_alerts"
                                               <?php echo ($retailerData['price_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="price_alerts">Price Alerts</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="new_batch_alerts" name="new_batch_alerts"
                                               <?php echo ($retailerData['new_batch_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="new_batch_alerts">New Batch Alerts</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Notification Preferences</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
/* Settings Page Specific Styles */
.form-switch {
    padding-left: 2.5em;
}

.form-switch .form-check-input {
    width: 3em;
}

.card {
    border: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.25rem;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.form-label {
    color: #2c3e50;
    font-weight: 500;
}

.btn-primary {
    padding: 0.5rem 1.5rem;
    font-weight: 500;
}

/* Dropdown Styles */
.dropdown-menu {
    display: none;
    position: absolute;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    z-index: 1000;
    border-radius: 0.375rem;
    border: 1px solid rgba(0, 0, 0, 0.15);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    display: block;
    width: 100%;
}

.dropdown-item:hover, .dropdown-item:focus {
    color: #1e2125;
    background-color: #f8f9fa;
}

.dropdown-item.active, .dropdown-item:active {
    color: #fff;
    text-decoration: none;
    background-color: #0d6efd;
}
</style>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Handle dropdown toggle manually if needed
    const settingsDropdown = document.getElementById('settingsDropdown');
    if (settingsDropdown) {
        settingsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = bootstrap.Dropdown.getInstance(settingsDropdown);
            if (dropdown) {
                dropdown.toggle();
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?> 
