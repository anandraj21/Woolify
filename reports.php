<?php
session_start();
require_once 'config/database.php';
require_once 'batch_operations.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();

// Get statistics
$totalBatches = getTotalBatches($userId);
$processingCount = getProcessingCount($userId);
$averageQuality = getAverageQuality($userId);
$totalWeight = getTotalWeight($userId);
$qualityDistribution = getQualityDistribution($userId);
$weightOverTime = getWeightOverTime($userId, 12);

// Format quality distribution for chart
$qualityLabels = [];
$qualityData = [];
foreach ($qualityDistribution as $quality) {
    $qualityLabels[] = "Grade " . $quality['quality_grade'];
    $qualityData[] = (int)$quality['count'];
}

// Format weight over time for chart
$weightLabels = [];
$weightData = [];
foreach ($weightOverTime as $weight) {
    $weightLabels[] = $weight['month'];
    $weightData[] = (float)$weight['total_weight'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.png" alt="Woolify" class="img-fluid" style="max-width: 120px;">
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_batch.php">
                        <i class="fas fa-plus-circle"></i> Add New Batch
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="batches.php">
                        <i class="fas fa-list"></i> View All Batches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="farms.php">
                        <i class="fas fa-warehouse"></i> View All Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <div class="container">
                <div class="row mb-4">
                    <div class="col">
                        <h1>Reports & Analytics</h1>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Batches</h5>
                                <h2 class="card-text"><?php echo $totalBatches; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">In Processing</h5>
                                <h2 class="card-text"><?php echo $processingCount; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Average Quality</h5>
                                <h2 class="card-text">
                                    <span class="quality-badge quality-<?php echo strtolower($averageQuality); ?>">
                                        <?php echo $averageQuality; ?>
                                    </span>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Weight</h5>
                                <h2 class="card-text"><?php echo number_format($totalWeight, 2); ?> kg</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quality Distribution</h5>
                                <canvas id="qualityChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Weight Over Time</h5>
                                <canvas id="weightChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quality Distribution Chart
        new Chart(document.getElementById('qualityChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($qualityLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($qualityData); ?>,
                    backgroundColor: [
                        '#55efc4',
                        '#81ecec',
                        '#ffeaa7',
                        '#fab1a0',
                        '#ff7675'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Weight Over Time Chart
        new Chart(document.getElementById('weightChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weightLabels); ?>,
                datasets: [{
                    label: 'Total Weight (kg)',
                    data: <?php echo json_encode($weightData); ?>,
                    borderColor: '#4a90e2',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Weight (kg)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 