<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // Needed to get farmer ID
require_once __DIR__ . '/../includes/helpers.php'; // For helper functions

$auth->requireRole('FARMER');
$pageTitle = "Farm Analytics";

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    die("Error: Farmer record not found.");
}
$farmerId = $farmerData['id'];

// Fetch Analytics Data
$woolBatchModel = new WoolBatch();
$productionTrends = [];
$qualityMetrics = [];
$overallStats = null; // Using the stats method we created for quality_control
$fetchError = '';

try {
    // Use existing methods (adjust months if needed)
    $productionTrends = $woolBatchModel->getProductionTrends($farmerId, 6);
    $qualityMetrics = $woolBatchModel->getQualityMetrics($farmerId);
    $overallStats = $woolBatchModel->getOverallQualityStats($farmerId);
} catch (Exception $e) {
    error_log("Error fetching analytics data for farmer {$farmerId}: " . $e->getMessage());
    $fetchError = "Could not retrieve analytics data. Please try again later.";
}

// Prepare data for potential charts (optional)
$trendLabels = json_encode(array_column($productionTrends, 'month'));
$trendWeightData = json_encode(array_column($productionTrends, 'total_weight'));
$trendCountData = json_encode(array_column($productionTrends, 'batch_count'));

include __DIR__ . '/../includes/header.php'; 
?>
<!-- Optional: Add Chart.js library if planning to use charts -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/../includes/topnav.php'; ?>
        <div class="dashboard-content">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Insights into your farm's wool production and quality.</p>

            <?php if ($fetchError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $fetchError; ?></div>
            <?php elseif (!$overallStats || $overallStats['total_batches'] === 0): ?>
                 <div class="alert alert-info" role="alert">
                    No wool batch data available to generate analytics. 
                    <a href="add_batch.php" class="alert-link">Add batches</a> to see analytics here.
                </div>
            <?php else: ?>
                <!-- Overall Performance Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                         <div class="stat-card h-100">
                            <div class="stat-icon bg-success text-white"><i class="fas fa-weight-hanging"></i></div>
                            <div class="stat-info">
                                <div class="stat-label">Total Weight Produced</div>
                                <!-- Calculate total weight from overall stats or trends -->
                                <?php 
                                    $totalWeight = 0;
                                    foreach($productionTrends as $trend) { $totalWeight += $trend['total_weight']; }
                                ?>
                                <div class="stat-value"><?php echo number_format($totalWeight, 1); ?> kg</div>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-4">
                         <div class="stat-card h-100">
                            <div class="stat-icon bg-primary text-white"><i class="fas fa-box"></i></div>
                            <div class="stat-info">
                                <div class="stat-label">Total Batches</div>
                                <div class="stat-value"><?php echo $overallStats['total_batches']; ?></div>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-4">
                         <div class="stat-card h-100">
                            <div class="stat-icon bg-info text-white"><i class="fas fa-ruler-combined"></i></div>
                            <div class="stat-info">
                                <div class="stat-label">Avg. Micron</div>
                                <div class="stat-value"><?php echo $overallStats['avg_micron'] !== null ? number_format($overallStats['avg_micron'], 1) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Production Trends Section -->
                <div class="chart-section mb-4">
                    <div class="section-header">
                        <h5 class="section-title">Production Trends (Last 6 Months)</h5>
                    </div>
                    <!-- Chart Placeholder -->
                    <!-- <div class="chart-container mb-3"> <canvas id="productionTrendChart"></canvas> </div> -->
                    
                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Month</th><th>Total Weight (kg)</th><th>Batches Created</th></tr></thead>
                            <tbody>
                                <?php if (empty($productionTrends)): ?>
                                    <tr><td colspan="3" class="text-muted text-center">No production data for the selected period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($productionTrends as $trend): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trend['month']); ?></td>
                                            <td><?php echo number_format((float)$trend['total_weight'], 1); ?></td>
                                            <td><?php echo htmlspecialchars($trend['batch_count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quality Metrics Section -->
                 <div class="table-section mb-4">
                     <div class="section-header">
                        <h5 class="section-title">Quality Metrics by Grade</h5>
                    </div>
                     <div class="table-responsive">
                         <table class="table table-sm table-striped">
                             <thead><tr><th>Grade</th><th>Batch Count</th><th>Avg. Micron</th></tr></thead>
                             <tbody>
                                <?php if (empty($qualityMetrics)): ?>
                                     <tr><td colspan="3" class="text-muted text-center">No quality data available.</td></tr>
                                 <?php else: ?>
                                     <?php foreach ($qualityMetrics as $metric): ?>
                                        <tr>
                                            <td><span class="badge bg-<?php echo getQualityClass($metric['grade']); ?>"><?php echo getQualityLabel($metric['grade']); ?></span></td>
                                            <td><?php echo htmlspecialchars($metric['batch_count']); ?></td>
                                            <td><?php echo $metric['avg_micron'] !== null ? number_format((float)$metric['avg_micron'], 1) : 'N/A'; ?></td>
                                        </tr>
                                     <?php endforeach; ?>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<?php 
/* 
// Example Chart.js Implementation (Add this in a <script> tag before footer include)

if (!empty($productionTrends)) {
    echo "<script>
      const ctx = document.getElementById('productionTrendChart');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: {$trendLabels},
          datasets: [{
            label: 'Total Weight (kg)',
            data: {$trendWeightData},
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
          },
          {
            label: 'Batches Created',
            data: {$trendCountData},
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1,
            yAxisID: 'y1' // Optional: Use a second y-axis for batch count
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              type: 'linear',
              display: true,
              position: 'left',
              title: { display: true, text: 'Weight (kg)' }
            },
            y1: { // Optional second axis
              beginAtZero: true,
              type: 'linear',
              display: true,
              position: 'right',
              title: { display: true, text: 'Batch Count' },
              grid: { drawOnChartArea: false } // only want the grid lines for one axis
            }
          }
        }
      });
    </script>";
}

*/

include __DIR__ . '/../includes/footer.php'; 
?> 