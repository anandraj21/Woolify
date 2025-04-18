<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user']['id'];

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    try {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $db->query($sql, [$userId]);
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        $error = "Error marking notifications as read: " . $e->getMessage();
    }
}

// Get notifications
try {
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $db->query($sql, [$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}

$pageTitle = "Notifications";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notifications</h2>
        <?php if (!empty($notifications)): ?>
            <form method="post" style="display: inline;">
                <button type="submit" name="mark_all_read" class="btn btn-secondary">
                    Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">No notifications found.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item <?= $notification['is_read'] ? 'bg-light' : '' ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?= h($notification['title']) ?></h5>
                        <small class="text-muted"><?= time_ago($notification['created_at']) ?></small>
                    </div>
                    <p class="mb-1"><?= h($notification['message']) ?></p>
                    <small class="text-muted">
                        Type: <?= h($notification['type']) ?> | 
                        Status: <?= $notification['is_read'] ? 'Read' : 'Unread' ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>