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
           <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>profile.php">Profile</a></li>
                <li><a class="dropdown-item" href="<?php echo $rootPath . ($user['role'] === 'FARMER' ? 'farmer/' : 'retailer/'); ?>settings.php">Settings</a></li>
                <li><a class="dropdown-item" href="<?php echo $rootPath . ($user['role'] === 'FARMER' ? 'farmer/' : 'retailer/'); ?>help.php">Help Center</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo $rootPath; ?>logout.php">Logout</a></li>
           </ul>
       </div>
   </div>
</nav>
