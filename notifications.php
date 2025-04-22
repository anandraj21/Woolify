<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/models/Notification.php';

// Ensure user is logged in
$auth->requireLogin();

$user = $auth->getUser();
$userId = $user['id'];

// Initialize notification model
$notificationModel = new Notification();

// Handle mark single notification as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    if ($notificationId) {
        try {
            $notificationModel->markAsRead($notificationId, $userId);
            // Redirect to prevent form resubmission on refresh
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $error = "Error marking notification as read: " . $e->getMessage();
        }
    }
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    try {
        $notificationModel->markAllAsRead($userId);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $error = "Error marking notifications as read: " . $e->getMessage();
    }
}

// Get notifications with proper error handling
try {
    $notifications = $notificationModel->getNotificationsForUser($userId);
} catch (Exception $e) {
    $error = "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}

// Get the base path for assets
$isSubDir = (strpos($_SERVER['PHP_SELF'], '/farmer/') !== false || strpos($_SERVER['PHP_SELF'], '/retailer/') !== false);
$rootPath = $isSubDir ? '../' : '';

$pageTitle = "Notifications";
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-container">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include __DIR__ . '/includes/topnav.php'; ?>
        
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?= h($pageTitle) ?></h2>
                <?php if (!empty($notifications)): ?>
                    <form method="post" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-secondary">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No notifications found.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?= $notification['is_read'] ? 'bg-light' : 'border-start border-4 border-primary' ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h5 class="mb-1">
                                    <i class="fas fa-<?= h(getNotificationIcon($notification['type'])) ?> me-2"></i>
                                    <?= h($notification['title']) ?>
                                </h5>
                                <small class="text-muted"><?= time_ago($notification['created_at']) ?></small>
                            </div>
                            <p class="mb-1"><?= h($notification['message']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Type: <?= h($notification['type']) ?> | 
                                    Status: <?= $notification['is_read'] ? 'Read' : '<span class="text-primary">Unread</span>' ?>
                                </small>
                                <?php if (!$notification['is_read']): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="notification_id" value="<?= h($notification['id']) ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-link text-decoration-none">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php 
// Helper function for notification icons
function getNotificationIcon($type) {
    return match(strtoupper($type)) {
        'PURCHASE_REQUEST' => 'shopping-cart',
        'TRANSACTION_APPROVED' => 'check-circle',
        'TRANSACTION_REJECTED' => 'times-circle',
        'BATCH_ADDED' => 'box',
        'SYSTEM' => 'info-circle',
        default => 'bell'
    };
}

// include __DIR__ . '/includes/footer.php'; 
?>