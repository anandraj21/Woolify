<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // Needed to get farmer ID
require_once __DIR__ . '/../includes/helpers.php'; // For helper functions like getQualityLabel

$auth->requireRole('FARMER');
$pageTitle = "Wool Quality Overview";

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    die("Error: Farmer record not found."); // Or handle more gracefully
}
$farmerId = $farmerData['id'];

// Fetch quality stats
$woolBatchModel = new WoolBatch();
$stats = null;
$fetchError = '';

try {
    $stats = $woolBatchModel->getOverallQualityStats($farmerId);
} catch (Exception $e) {
    error_log("Error fetching quality stats for farmer {$farmerId}: " . $e->getMessage());
    $fetchError = "Could not retrieve quality statistics. Please try again later.";
}

include __DIR__ . '/../includes/header.php'; 
?>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">An overview of the quality metrics for the wool batches you've registered.</p>

            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php elseif ($stats === null || $stats['total_batches'] === 0): ?>
                 <div class="alert alert-info" role="alert">
                    No wool batches found or no quality data available yet. 
                    <a href="add_batch.php" class="alert-link">Add a new batch</a> to see statistics here.
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Stat Card: Total Batches -->
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="stat-card h-100">
                            <div class="stat-icon bg-primary text-white">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Total Batches Recorded</div>
                                <div class="stat-value"><?php echo $stats['total_batches']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Card: Average Micron -->
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="stat-card h-100">
                             <div class="stat-icon bg-info text-white">
                                <i class="fas fa-ruler-combined"></i> <!-- Or similar micron icon -->
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Average Micron</div>
                                <div class="stat-value"><?php echo $stats['avg_micron'] !== null ? number_format($stats['avg_micron'], 1) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Grade Distribution</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless text-center">
                                <thead>
                                    <tr>
                                        <?php foreach ($stats['grade_counts'] as $grade => $count): ?>
                                            <th>
                                                <span class="badge fs-6 bg-<?php echo getQualityClass($grade); ?>">
                                                    <?php echo getQualityLabel($grade); ?> (<?php echo $grade; ?>)
                                                </span>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php foreach ($stats['grade_counts'] as $grade => $count): ?>
                                            <td class="fs-4 fw-bold"> <?php echo $count; ?> </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <?php foreach ($stats['grade_counts'] as $grade => $count): ?>
                                             <td class="text-muted"> Batches </td>
                                         <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <!-- Optional: Placeholder for a Chart -->
                         <!-- <canvas id="gradeDistributionChart"></canvas> -->
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<?php 
// Optional: Add Chart.js integration here if desired later
// include __DIR__ . '/../includes/footer.php'; 
?> 