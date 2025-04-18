<?php
require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel {
    public function __construct() {
        parent::__construct('notifications');
    }

    /**
     * Create a new notification.
     * 
     * @param int $userId The ID of the user receiving the notification.
     * @param string $type A category for the notification (e.g., 'PURCHASE_REQUEST', 'TRANSACTION_APPROVED', 'TRANSACTION_REJECTED').
     * @param string $message The notification message content.
     * @param int|null $relatedId Optional ID of a related entity (e.g., transaction ID, batch ID).
     * @return bool True on success, false on failure.
     */
    public function createNotification(int $userId, string $type, string $message, ?int $relatedId = null): bool 
    {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'related_id' => $relatedId,
            'is_read' => 0, // Default to unread
            // created_at is handled by DB default
        ];
        return $this->create($data);
    }

    /**
     * Get notifications for a specific user, optionally filtered by read status.
     * 
     * @param int $userId The user's ID.
     * @param bool|null $isRead Filter by read status (null = all, true = read, false = unread).
     * @param string $orderBy SQL ORDER BY clause (e.g., 'created_at DESC').
     * @param int $limit Max number of notifications to return.
     * @return array List of notifications.
     */
    public function getNotificationsForUser(int $userId, ?bool $isRead = null, string $orderBy = 'created_at DESC', int $limit = 50): array 
    {
        $conditions = ['user_id' => $userId];
        if ($isRead !== null) {
            $conditions['is_read'] = (int)$isRead;
        }
        return $this->findAll($conditions, $orderBy, $limit);
    }

    /**
     * Count unread notifications for a specific user.
     *
     * @param int $userId The user's ID.
     * @return int The count of unread notifications.
     */
    public function getUnreadCount(int $userId): int 
    {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = ? AND is_read = 0";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting unread notifications for user {$userId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a specific notification as read.
     *
     * @param int $notificationId The ID of the notification to mark as read.
     * @param int $userId The ID of the user (for security, ensure notification belongs to user).
     * @return bool True on success, false on failure.
     */
    public function markAsRead(int $notificationId, int $userId): bool 
    {
        // Ensure the notification belongs to the user before marking as read
        $sql = "UPDATE {$this->table_name} SET is_read = 1, updated_at = NOW() WHERE id = ? AND user_id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log("Error marking notification {$notificationId} as read for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all unread notifications for a user as read.
     *
     * @param int $userId The ID of the user.
     * @return bool True if any rows were updated, false otherwise or on error.
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE {$this->table_name} SET is_read = 1, updated_at = NOW() WHERE user_id = ? AND is_read = 0";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
}
?> 