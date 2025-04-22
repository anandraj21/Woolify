<?php
// includes/topnav.php

// Expect $auth, $user, $pageTitle, $unreadNotifications to be available

if (!isset($auth) || !$auth->isLoggedIn()) {
    return; // Don't display if not logged in
}
if (!isset($user)) $user = $auth->getUser();
if (!isset($pageTitle)) $pageTitle = 'Dashboard'; // Default page title
$unread_notifications_count = isset($unreadNotifications) ? (int)$unreadNotifications : 0;

// Define base path for links depending on current directory
$rootPath = (basename(dirname($_SERVER['PHP_SELF'])) === 'farmer' || basename(dirname($_SERVER['PHP_SELF'])) === 'retailer') ? '../' : '';
$farmerPath = $rootPath . 'farmer/'; // Path to farmer-specific actions
$retailerPath = $rootPath . 'retailer/'; // Path to retailer-specific actions
?>
<nav class="top-nav shadow-sm">
    <div class="nav-left">
        <button class="sidebar-toggle btn btn-link me-2 d-lg-none"> 
            <i class="fas fa-bars"></i>
        </button>
       <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
   </div>
   <div class="nav-right">
       <?php if ($user['role'] === 'FARMER'): ?>
            <!-- Farmer Actions -->
            <div class="nav-item d-none d-md-block"> <!-- Hide on smaller screens -->
                <a href="<?php echo $farmerPath; ?>add_farm.php" class="btn btn-sm btn-outline-primary"> <i class="fas fa-plus me-1"></i> Add Farm </a>
            </div>
            <div class="nav-item d-none d-md-block">
                <a href="<?php echo $farmerPath; ?>add_batch.php" class="btn btn-sm btn-success"> <i class="fas fa-plus-circle me-1"></i> Create Batch </a>
            </div>
       <?php elseif ($user['role'] === 'RETAILER'): ?>
           <!-- Retailer Actions (Example) -->
            <div class="nav-item d-none d-md-block">
                <a href="<?php echo $retailerPath; ?>browse_batches.php" class="btn btn-sm btn-outline-primary"> <i class="fas fa-search me-1"></i> Browse Batches </a>
            </div>
             <div class="nav-item d-none d-md-block">
                <a href="<?php echo $retailerPath; ?>access_requests.php?new=1" class="btn btn-sm btn-success"> <i class="fas fa-plus me-1"></i> Request Access </a>
            </div>
       <?php endif; ?>

        <!-- Common Actions -->
       <div class="nav-item position-relative">
            <a href="<?php echo $rootPath; ?>notifications.php" class="btn btn-icon position-relative" id="topnav-notification-icon">
               <i class="fas fa-bell"></i>
               <span id="topnav-notification-bubble">
               <?php if ($unread_notifications_count > 0): ?>
               <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em; padding: 0.3em 0.5em;">
                   <?php echo $unread_notifications_count; ?>
                   <span class="visually-hidden">unread messages</span>
               </span>
               <?php endif; ?>
               </span>
           </a>
       </div>
       <div class="nav-item dropdown">
           <button class="btn btn-icon dropdown-toggle" type="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
               <i class="fas fa-cog"></i>
           </button>
           <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="settingsDropdown">
                <li><a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>profile.php">
                    <i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath . ($user['role'] === 'FARMER' ? 'farmer/' : 'retailer/'); ?>settings.php">
                    <i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath . ($user['role'] === 'FARMER' ? 'farmer/' : 'retailer/'); ?>help.php">
                    <i class="fas fa-question-circle me-2"></i>Help Center</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item d-flex align-items-center text-danger" href="<?php echo $rootPath; ?>logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
           </ul>
       </div>
   </div>
</nav>

<!-- Add necessary styles and scripts -->
<style>
/* Dropdown styles */
.dropdown-menu {
    min-width: 200px;
    border: 0;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.7rem 1.2rem;
    color: #2c3e50;
}

.dropdown-item:hover, .dropdown-item:focus {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.dropdown-item.text-danger:hover {
    background-color: #fff5f5;
    color: #dc3545;
}

.btn-icon {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #2c3e50;
}

.btn-icon:hover {
    background-color: #f8f9fa;
}

.btn-icon.dropdown-toggle::after {
    display: none;
}
</style>

<script>
// Initialize dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Load Bootstrap's dropdown component
    if (typeof bootstrap !== 'undefined') {
        var dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        dropdowns.map(function (dropdownToggle) {
            return new bootstrap.Dropdown(dropdownToggle);
        });
    }
});
</script>
