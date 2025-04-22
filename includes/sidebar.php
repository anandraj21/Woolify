<?php
// includes/sidebar.php

// We expect $auth and $user to be available from the including page
// We also need Notification model to get unread count
require_once __DIR__ . '/../models/Notification.php';

if (!isset($auth) || !$auth->isLoggedIn()) {
    // Should not happen if pages use requireLogin/requireRole
    return; // Don't display sidebar if not logged in
}

if (!isset($user)) {
    $user = $auth->getUser(); // Attempt to get user if not explicitly passed
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $user['id'] ?? null;
$user_role = $user['role'] ?? null;

// Fetch unread notification count
$unread_notifications_count = 0;
if ($user_id) {
    $notificationModel = new Notification();
    $unread_notifications_count = $notificationModel->getUnreadCount($user_id);
} 

// Define base path for links depending on current directory
$isSubDir = (basename(dirname($_SERVER['PHP_SELF'])) === 'farmer' || basename(dirname($_SERVER['PHP_SELF'])) === 'retailer');
$farmerBasePath = $isSubDir ? '' : 'farmer/'; 
$retailerBasePath = $isSubDir ? '' : 'retailer/';
$rootPath = $isSubDir ? '../' : '';

?>
<aside class="sidebar shadow-sm">
    <div class="sidebar-header">
        <a href="<?php echo $rootPath; ?>index.php" class="woolify-brand">
            <img src="<?php echo $rootPath; ?>public/assets/images/logo.png" alt="Woolify">
            <span>Woolify</span>
        </a>
        <button class="sidebar-toggle btn btn-link d-lg-none"> 
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <style>
    /* Enhanced Brand Styling */
    .brand-link,
    .brand-logo,
    .brand-text,
    .space-x-3 {
        display: none;
    }

    /* Enhance sidebar header */
    .sidebar-header {
        padding: 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Improve sidebar toggle button */
    .sidebar-toggle {
        color: #5F975F;
        transition: color 0.3s ease;
        padding: 0.5rem;
    }

    .sidebar-toggle:hover {
        color: #4C794C;
    }

    .woolify-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
        padding: 0.5rem;
    }

    .woolify-brand img {
        height: 40px;
        width: auto;
        border-radius: 8px;
    }

    .woolify-brand span {
        font-size: 24px;
        color: #5F975F;
        font-weight: 600;
        margin-left: 12px;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.5px;
    }

    .woolify-brand:hover {
        text-decoration: none;
    }

    .woolify-brand:hover span {
        color: #4C794C;
    }
    </style>

    <div class="sidebar-user">
         <a href="<?php echo $rootPath; ?>profile.php" class="d-flex align-items-center text-decoration-none text-dark">
            <img src="<?php echo $rootPath . htmlspecialchars($user['profile_image'] ?? 'img/avatar-placeholder.png'); ?>" alt="User" class="user-avatar">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                <div class="user-role text-muted"><?php echo htmlspecialchars(ucfirst(strtolower($user_role))) ?? 'Role'; ?></div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php if ($user_role === 'FARMER'): ?>
            <!-- Farmer Navigation -->
            <div class="nav-section">
                <div class="nav-section-header">FARM MANAGEMENT</div>
                <a href="<?php echo $farmerBasePath; ?>dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"> <i class="fas fa-home"></i> <span>Overview</span> </a>
                <a href="<?php echo $farmerBasePath; ?>farms.php" class="nav-link <?php echo $current_page == 'farms.php' ? 'active' : ''; ?>"> <i class="fas fa-warehouse"></i> <span>My Farms</span> </a>
                <a href="<?php echo $farmerBasePath; ?>add_farm.php" class="nav-link <?php echo $current_page == 'add_farm.php' ? 'active' : ''; ?>"> <i class="fas fa-plus"></i> <span>Add New Farm</span> </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-header">PRODUCTION</div>
                <a href="<?php echo $farmerBasePath; ?>batches.php" class="nav-link <?php echo $current_page == 'batches.php' ? 'active' : ''; ?>"> 
                    <i class="fas fa-box"></i> <span>Wool Batches</span> 
                </a>
                <a href="<?php echo $farmerBasePath; ?>add_batch.php" class="nav-link <?php echo $current_page == 'add_batch.php' ? 'active' : ''; ?>"> <i class="fas fa-plus-circle"></i> <span>Create Batch</span> </a>
                <a href="<?php echo $farmerBasePath; ?>quality_control.php" class="nav-link <?php echo $current_page == 'quality_control.php' ? 'active' : ''; ?>"> <i class="fas fa-check-circle"></i> <span>Quality Control</span> </a>
            </div>
             <div class="nav-section">
                 <div class="nav-section-header">MONITORING</div>
                 <a href="<?php echo $farmerBasePath; ?>batch_tracking.php" class="nav-link <?php echo $current_page == 'batch_tracking.php' ? 'active' : ''; ?>"> 
                     <i class="fas fa-truck"></i> <span>Batch Tracking</span> 
                 </a>
                 <a href="<?php echo $farmerBasePath; ?>access_requests.php" class="nav-link <?php echo $current_page == 'access_requests.php' ? 'active' : ''; ?>"> 
                     <i class="fas fa-user-check"></i> <span>Access Requests</span> 
                 </a>
             </div>
             <div class="nav-section">
                 <div class="nav-section-header">ANALYTICS</div>
                 <a href="<?php echo $farmerBasePath; ?>farm_analytics.php" class="nav-link <?php echo $current_page == 'farm_analytics.php' ? 'active' : ''; ?>"> <i class="fas fa-chart-line"></i> <span>Farm Analytics</span> </a>
                 <a href="<?php echo $farmerBasePath; ?>reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"> <i class="fas fa-file-alt"></i> <span>Reports</span> </a>
                 <a href="<?php echo $farmerBasePath; ?>export_data.php" class="nav-link <?php echo $current_page == 'export_data.php' ? 'active' : ''; ?>"> <i class="fas fa-download"></i> <span>Export Data</span> </a>
             </div>
             <div class="nav-section">
                 <div class="nav-section-header">NAVIGATION</div>
             </div>

        <?php elseif ($user_role === 'RETAILER'): ?>
            <!-- Retailer Navigation (Corrected Paths) -->
            <div class="nav-section">
                <div class="nav-section-header">BROWSE & PURCHASE</div>
                <!-- When in retailer subdir, link directly; otherwise, prefix with retailer/ -->
                <a href="<?php echo $retailerBasePath; ?>dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"> <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span> </a>
                <a href="<?php echo $retailerBasePath; ?>browse_batches.php" class="nav-link <?php echo $current_page == 'browse_batches.php' ? 'active' : ''; ?>"> <i class="fas fa-search"></i> <span>Browse Batches</span> </a>
                <a href="<?php echo $retailerBasePath; ?>my_purchases.php" class="nav-link <?php echo $current_page == 'my_purchases.php' ? 'active' : ''; ?>"> <i class="fas fa-shopping-cart"></i> <span>My Purchases</span> </a>
             </div>
             <div class="nav-section">
                 <div class="nav-section-header">FARMS & REQUESTS</div>
                 <a href="<?php echo $retailerBasePath; ?>farms_connected.php" class="nav-link <?php echo $current_page == 'farms_connected.php' ? 'active' : ''; ?>"> <i class="fas fa-link"></i> <span>Connected Farms</span> </a>
                 <a href="<?php echo $retailerBasePath; ?>access_requests.php" class="nav-link <?php echo $current_page == 'access_requests.php' ? 'active' : ''; ?>"> <i class="fas fa-clock"></i> <span>My Access Requests</span> </a>
             </div>
             <div class="nav-section">
                 <div class="nav-section-header">NAVIGATION</div>
             </div>

        <?php else: ?>
            <!-- Optional: Navigation for other roles or default -->
             <div class="nav-section">
                 <div class="nav-section-header">GENERAL</div>
                 <a href="<?php echo $rootPath; ?>dashboard.php" class="nav-link active"> <i class="fas fa-home"></i> <span>Dashboard</span> </a>
             </div>
        <?php endif; ?>

        <!-- Common Account Section -->
        <div class="nav-section">
            <div class="nav-section-header">ACCOUNT</div>
            <a href="<?php echo $rootPath; ?>profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"> <i class="fas fa-user-circle"></i> <span>Profile</span> </a>
            <a href="<?php echo $rootPath; ?>notifications.php" class="nav-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>"> 
                <i class="fas fa-bell"></i> <span>Notifications</span> 
                <span id="sidebar-notification-count" class="ms-auto">
                    <?php if ($unread_notifications_count > 0): ?>
                        <span class="badge rounded-pill bg-danger"><?php echo $unread_notifications_count; ?></span>
                    <?php endif; ?>
                </span>
            </a>
            <a href="<?php echo $rootPath; ?>logout.php" class="nav-link text-danger"> <i class="fas fa-sign-out-alt"></i> <span>Logout</span> </a>
        </div>
    </nav>
</aside> 