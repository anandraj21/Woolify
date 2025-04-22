<?php
require_once '../config/database.php';
require_once '../includes/auth_middleware.php';

// Ensure user is authenticated and has retailer role
AuthMiddleware::hasRole('retailer');

$db = new Database();
$userId = AuthMiddleware::getUserId();

// Get batch ID from URL
$batchId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$batchId) {
    $_SESSION['error'] = 'Invalid batch ID.';
    header('Location: batches.php');
    exit;
}

// Check if retailer has approved access to this batch
$query = "SELECT access_status FROM batch_access 
          WHERE batch_id = ? AND retailer_id = ? AND access_status = 'approved'";
$access = $db->query($query, [$batchId, $userId])->fetch();

if (!$access) {
    $_SESSION['error'] = 'You do not have access to view this batch.';
    header('Location: batches.php');
    exit;
}

// Get batch details with related information
$query = "SELECT wb.*, f.farm_name, f.location as farm_location,
          pr.facility_name, pr.start_date as processing_start,
          pr.end_date as processing_end, pr.notes as processing_notes,
          qc.micron_count, qc.strength_score, qc.color_grade,
          tr.carrier_name, tr.tracking_number,
          tr.pickup_date, tr.estimated_delivery,
          DATE_FORMAT(wb.created_at, '%Y-%m-%d') as batch_date
          FROM wool_batches wb
          JOIN farms f ON wb.farm_id = f.id
          LEFT JOIN processing_records pr ON wb.id = pr.batch_id
          LEFT JOIN quality_checks qc ON wb.id = qc.batch_id
          LEFT JOIN transportation_records tr ON wb.id = tr.batch_id
          WHERE wb.id = ?";

$batch = $db->query($query, [$batchId])->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    $_SESSION['error'] = 'Batch not found.';
    header('Location: batches.php');
    exit;
}

// Get batch tracking history
$query = "SELECT status, notes, DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i') as event_time
          FROM batch_tracking_history
          WHERE batch_id = ?
          ORDER BY timestamp DESC";
$history = $db->query($query, [$batchId])->fetchAll(PDO::FETCH_ASSOC);

// Get user information
$query = "SELECT name, email FROM users WHERE id = ?";
$user = $db->query($query, [$userId])->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Batch - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../img/logo.png" alt="Woolify" height="40">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="batches.php" class="nav-link active">
                        <i class="fas fa-box"></i>
                        <span>Wool Batches</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="requests.php" class="nav-link">
                        <i class="fas fa-clock"></i>
                        <span>Access Requests</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div>
                    <a href="batches.php" class="btn btn-link">
                        <i class="fas fa-arrow-left"></i> Back to Batches
                    </a>
                    <h1 class="page-title">Batch Details: <?php echo htmlspecialchars($batch['batch_number']); ?></h1>
                </div>
                <div class="user-dropdown">
                    <button class="user-button">
                        <img src="../img/avatar-placeholder.png" alt="User" class="user-avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role">Retailer</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Batch Overview -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Batch Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Batch Number:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo htmlspecialchars($batch['batch_number']); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Weight:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo number_format($batch['weight_kg'], 1); ?> kg
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Quality Grade:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <span class="quality-badge quality-<?php echo strtolower($batch['quality_grade']); ?>">
                                        Grade <?php echo $batch['quality_grade']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Status:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php
                                    $status_class = match($batch['status']) {
                                        'processed' => 'success',
                                        'in_processing' => 'warning',
                                        'in_transit' => 'info',
                                        'delivered' => 'primary',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $batch['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Created Date:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo $batch['batch_date']; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong>Notes:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo htmlspecialchars($batch['notes'] ?? 'No notes available'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Farm Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Farm Name:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo htmlspecialchars($batch['farm_name']); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong>Location:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <?php echo htmlspecialchars($batch['farm_location']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quality Check Information -->
            <?php if ($batch['micron_count']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quality Check Results</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="quality-metric">
                                <div class="metric-label">Micron Count</div>
                                <div class="metric-value"><?php echo number_format($batch['micron_count'], 1); ?> μm</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quality-metric">
                                <div class="metric-label">Strength Score</div>
                                <div class="metric-value"><?php echo number_format($batch['strength_score'], 1); ?> N/ktex</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quality-metric">
                                <div class="metric-label">Color Grade</div>
                                <div class="metric-value"><?php echo htmlspecialchars($batch['color_grade']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Processing Information -->
            <?php if ($batch['facility_name']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Processing Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Facility:</strong>
                            <?php echo htmlspecialchars($batch['facility_name']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Start Date:</strong>
                            <?php echo $batch['processing_start']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>End Date:</strong>
                            <?php echo $batch['processing_end'] ?? 'In Progress'; ?>
                        </div>
                    </div>
                    <?php if ($batch['processing_notes']): ?>
                    <div class="row">
                        <div class="col">
                            <strong>Processing Notes:</strong>
                            <p class="mb-0"><?php echo htmlspecialchars($batch['processing_notes']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Transportation Information -->
            <?php if ($batch['carrier_name']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transportation Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Carrier:</strong>
                            <?php echo htmlspecialchars($batch['carrier_name']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Tracking Number:</strong>
                            <?php echo htmlspecialchars($batch['tracking_number']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Pickup Date:</strong>
                            <?php echo $batch['pickup_date']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Est. Delivery:</strong>
                            <?php echo $batch['estimated_delivery']; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tracking History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tracking History</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($history as $event): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    <?php echo ucwords(str_replace('_', ' ', $event['status'])); ?>
                                </h6>
                                <p class="timeline-date"><?php echo $event['event_time']; ?></p>
                                <?php if ($event['notes']): ?>
                                <p class="timeline-text"><?php echo htmlspecialchars($event['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.querySelector('.sidebar-toggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html> 