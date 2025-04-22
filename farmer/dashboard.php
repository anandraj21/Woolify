<?php
// Ensure session is started *only* if not already active
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
// Include necessary files
require_once __DIR__ . '/../includes/auth.php'; // Auth implicitly requires Database
require_once __DIR__ . '/../models/Farmer.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Notification.php';

// Authentication & Authorization
// $auth object is already initialized in auth.php
$auth->requireRole('FARMER'); // Redirects if not logged in or not a farmer

$user = $auth->getUser();
if (!$user) {
    // Should not happen if requireRole works, but good practice
    $auth->logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}
$userId = $user['id'];

// Instantiate models
$dbInstance = Database::getInstance(); // Get instance to pass if needed (though models get it themselves now)
$farmerModel = new Farmer($dbInstance); // Pass DB if constructor requires it (Update: BaseModel handles this)
$woolBatchModel = new WoolBatch($dbInstance);
$notificationModel = new Notification($dbInstance);

// --- Fetch Data using Models ---

// Get Farmer's specific data (including farmer ID needed for other queries)
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    // Handle case where farmer data is missing 
    // This might happen if registration didn't create the farmer record properly
    error_log("Farmer data not found for user ID: $userId");
    die('Error: Farmer details not found. Please contact support.'); 
}
$farmerId = $farmerData['id']; // The ID from the 'farmers' table

// Get Farmer's wool batches (ordered by creation date descending)
$allBatches = $woolBatchModel->getBatchesByFarmer($farmerId, null, 'created_at DESC');

// Separate batches by status for stats
$availableBatches = [];
$pendingBatches = [];
$soldBatches = [];
$totalWeight = 0;
$totalValue = 0; // Placeholder for value calculation if needed
$qualityCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'UNKNOWN' => 0];
$monthlyProduction = []; // [YYYY-MM => total_weight]
$recentWeight = 0; // Last 30 days
$thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
$sixMonthsAgoDate = date('Y-m', strtotime('-6 months')); // For chart start

foreach ($allBatches as $batch) {
    $status = strtoupper($batch['status'] ?? 'UNKNOWN');
    $grade = strtoupper($batch['grade'] ?? 'UNKNOWN');
    $batchWeight = (float)($batch['quantity'] ?? 0);
    $batchDate = $batch['created_at'] ?? date('Y-m-d H:i:s');
    $batchMonth = date('Y-m', strtotime($batchDate));

    $totalWeight += $batchWeight;
    
    // Categorize by status
    match ($status) {
        'AVAILABLE' => $availableBatches[] = $batch,
        'PENDING' => $pendingBatches[] = $batch,
        'SOLD' => $soldBatches[] = $batch,
        default => null, // Or handle other statuses if they exist
    };

    // Count grades for quality chart
    $qualityCounts[$grade] = ($qualityCounts[$grade] ?? 0) + 1;

    // Sum monthly production for trend chart (last 6 months)
    if ($batchMonth >= $sixMonthsAgoDate) {
         $monthlyProduction[$batchMonth] = ($monthlyProduction[$batchMonth] ?? 0) + $batchWeight;
    }
   
    // Calculate recent weight
    if ($batchDate >= $thirtyDaysAgo) {
        $recentWeight += $batchWeight;
    }
}

// Calculate Average Quality (Simple numeric score: A=3, B=2, C=1)
$qualityScoreSum = ($qualityCounts['A'] * 3) + ($qualityCounts['B'] * 2) + ($qualityCounts['C'] * 1);
$qualityScoreCount = $qualityCounts['A'] + $qualityCounts['B'] + $qualityCounts['C'];
$avgQualityValue = $qualityScoreCount > 0 ? ($qualityScoreSum / $qualityScoreCount) : 0;
$avgQualityGrade = match(true) {
    $avgQualityValue >= 2.5 => 'A',
    $avgQualityValue >= 1.5 => 'B',
    $avgQualityValue > 0 => 'C',
    default => 'N/A'
};

// Prepare data for Charts
// Production Trend Chart (Last 6 Months)
$productionLabels = [];
$productionValues = [];
// Ensure all months in the last 6 are present, even if 0 production
$currentMonth = date('Y-m');
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months", strtotime($currentMonth . '-01')));
    $productionLabels[] = date('M Y', strtotime($monthKey . '-01')); // Format: Jan 2024
    $productionValues[] = $monthlyProduction[$monthKey] ?? 0;
}

// Quality Distribution Chart
$qualityLabels = array_keys($qualityCounts);
$qualityValues = array_values($qualityCounts);


// Get notification count
$unreadNotifications = $notificationModel->getUnreadCount($userId);

// --- Populate Data Structures for View ---
$analytics = [
    'total_farms' => 1, // Assuming one farmer - enhance if multiple farms per user needed
    'total_batches' => count($allBatches),
    'processing_batches' => count($pendingBatches), 
    'completed_batches' => count($soldBatches), 
    'pending_requests' => 0, // Placeholder - needs specific logic/table
    'total_weight' => $totalWeight,
    'avg_quality' => $avgQualityGrade, // Display calculated grade
    'recent_weight' => $recentWeight,
    'active_farms' => 1, // Placeholder
];

// Farm Performance - simplified for one farm
$farmPerformance = [
    [
        'id' => $farmerId,
        'farm_name' => $farmerData['farm_name'],
        'location' => $farmerData['farm_address'], 
        'batch_count' => count($allBatches),
        'total_weight' => $totalWeight,
        'active_batches' => count($availableBatches) + count($pendingBatches), // Available + Pending
        'avg_quality' => $avgQualityGrade,
        'last_batch_date' => !empty($allBatches) ? date('d M Y', strtotime($allBatches[0]['created_at'])) : 'N/A', // Assumes DESC order
        'capacity_utilization' => 0 // Placeholder - needs max capacity data
    ]
];

// Recent Activity
$recentActivity = array_slice($allBatches, 0, 5); // Get the 5 most recent batches

// Helper functions (Keep as they are used in the view)
function getQualityLabel($grade) {
    return match(strtoupper($grade)) {
        'A' => 'Premium',
        'B' => 'High',
        'C' => 'Standard',
        default => 'Unknown'
    };
}

function getStatusClass($status) {
    return match(strtolower($status)) {
        'sold' => 'success',
        'pending' => 'warning', // Map PENDING to warning (processing)
        'available' => 'info',
        default => 'secondary'
    };
}

function getQualityClass($grade) {
    return match(strtoupper($grade)) {
        'A' => 'success',
        'B' => 'primary',
        'C' => 'info',
        default => 'secondary'
    };
}

function getProductionTrend($current, $previous) {
    // Basic trend calculation - replace with more robust logic later
    if ($previous == 0) {
        return ['up', 100]; // Or handle division by zero appropriately
    }
    $change = (($current - $previous) / abs($previous)) * 100;
    return [$change >= 0 ? 'up' : 'down', abs($change)];
}

function getCapacityClass($utilization) {
    // Basic capacity mapping - replace with more robust logic later
    return match(true) {
        $utilization >= 90 => 'danger',
        $utilization >= 75 => 'warning',
        $utilization >= 50 => 'info',
        default => 'success'
    };
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Management - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ensure correct path to dashboard.css -->
    <link href="../css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script> 
    <style>
        .woolify-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .woolify-brand img {
            height: 40px;
            width: auto;
        }

        .woolify-brand span {
            font-size: 24px;
            color: #5F975F;
            font-weight: 600;
            margin-left: 8px;
            font-family: 'Inter', sans-serif;
        }

        .woolify-brand:hover {
            text-decoration: none;
        }

        .woolify-brand:hover span {
            color: #4C794C;
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar shadow-sm">
            <div class="sidebar-header">
                <!-- Make logo clickable link to home page -->
                <a href="../index.php" class="woolify-brand">
                    <img src="../public/assets/images/logo.png" alt="Woolify">
                    <span>Woolify</span>
                </a>
                <button class="sidebar-toggle btn btn-link d-lg-none"> <!-- Show toggle on smaller screens -->
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-user">
                 <!-- Link avatar/name to profile page -->
                 <a href="../profile.php" class="d-flex align-items-center text-decoration-none text-dark">
                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '../img/avatar-placeholder.png'); ?>" alt="User" class="user-avatar">
                <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role text-muted">Farm Manager</div>
                </div>
                </a>
            </div>

            <nav class="sidebar-nav">
                <!-- Navigation Sections -->
                <div class="nav-section">
                    <div class="nav-section-header">FARM MANAGEMENT</div>
                    <a href="dashboard.php" class="nav-link active"> <i class="fas fa-home"></i> <span>Overview</span> </a>
                    <!-- Point other links to placeholder/actual pages -->
                    <a href="farms.php" class="nav-link"> <i class="fas fa-warehouse"></i> <span>My Farms</span> </a>
                    <a href="add_farm.php" class="nav-link"> <i class="fas fa-plus"></i> <span>Add New Farm</span> </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-header">PRODUCTION</div>
                    <a href="batches.php" class="nav-link">
                        <i class="fas fa-box"></i> <span>Wool Batches</span> 
                        <?php if ($analytics['total_batches'] > 0): ?>
                            <span class="badge rounded-pill bg-secondary ms-auto"><?php echo $analytics['total_batches']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="add_batch.php" class="nav-link"> <i class="fas fa-plus-circle"></i> <span>Create Batch</span> </a>
                    <a href="quality_control.php" class="nav-link"> <i class="fas fa-check-circle"></i> <span>Quality Control</span> </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-header">MONITORING</div>
                    <a href="batch_tracking.php" class="nav-link">
                         <i class="fas fa-truck"></i> <span>Batch Tracking</span> 
                        <?php if ($analytics['processing_batches'] > 0): ?>
                            <span class="badge rounded-pill bg-warning ms-auto"><?php echo $analytics['processing_batches']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="access_requests.php" class="nav-link">
                         <i class="fas fa-user-check"></i> <span>Access Requests</span> 
                        <?php if ($analytics['pending_requests'] > 0): ?>
                        <span class="badge rounded-pill bg-warning ms-auto"><?php echo $analytics['pending_requests']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-header">ANALYTICS</div>
                    <a href="farm_analytics.php" class="nav-link"> <i class="fas fa-chart-line"></i> <span>Farm Analytics</span> </a>
                    <!-- <a href="reports.php" class="nav-link"> <i class="fas fa-file-alt"></i> <span>Reports</span> </a> -->
                    <!-- <a href="export_data.php" class="nav-link"> <i class="fas fa-download"></i> <span>Export Data</span> </a> -->
                </div>

                <div class="nav-section">
                    <div class="nav-section-header">ACCOUNT</div>
                    <a href="../profile.php" class="nav-link"> <i class="fas fa-user-circle"></i> <span>Profile</span> </a>
                    <a href="../notifications.php" class="nav-link"> 
                        <i class="fas fa-bell"></i> <span>Notifications</span> 
                        <span id="sidebar-notification-count">
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-auto"><?php echo $unreadNotifications; ?></span>
                        <?php endif; ?>
                        </span>
                    </a>
                    <!-- Use root path for logout -->
                    <a href="../logout.php" class="nav-link text-danger"> <i class="fas fa-sign-out-alt"></i> <span>Logout</span> </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <nav class="top-nav shadow-sm">
                <div class="nav-left">
                     <button class="sidebar-toggle btn btn-link me-2 d-lg-none"> <!-- Button to toggle sidebar on small screens -->
                         <i class="fas fa-bars"></i>
                     </button>
                    <h1 class="page-title">Farm Management Overview</h1>
                </div>
                <div class="nav-right">
                    <div class="nav-item">
                        <a href="add_farm.php" class="btn btn-sm btn-outline-primary"> <i class="fas fa-plus me-1"></i> Add Farm </a>
                    </div>
                    <div class="nav-item">
                        <a href="add_batch.php" class="btn btn-sm btn-success"> <i class="fas fa-plus-circle me-1"></i> Create Batch </a>
                    </div>
                    <div class="nav-item position-relative">
                         <!-- Link to notifications page -->
                        <a href="../notifications.php" class="btn btn-icon position-relative" id="topnav-notification-icon">
                            <i class="fas fa-bell"></i>
                            <span id="topnav-notification-bubble">
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em; padding: 0.3em 0.5em;">
                                <?php echo $unreadNotifications; ?>
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
                            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><a class="dropdown-item" href="help.php">Help Center</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Quick Stats -->
                <div class="stats-grid mb-4">
                     <div class="stat-card"> <div class="stat-icon bg-primary bg-opacity-10"><i class="fas fa-warehouse text-primary"></i></div> <div class="stat-info"> <div class="stat-label">Farm Name</div> <div class="stat-value"><?php echo htmlspecialchars($farmerData['farm_name']); ?></div> <div class="stat-change"><?php echo htmlspecialchars($farmerData['farm_address']); ?></div> </div> </div>
                     <div class="stat-card"> <div class="stat-icon bg-success bg-opacity-10"><i class="fas fa-weight-hanging text-success"></i></div> <div class="stat-info"> <div class="stat-label">Total Production</div> <div class="stat-value"><?php echo number_format($analytics['total_weight'], 1); ?> kg</div> <div class="stat-change <?php echo $analytics['recent_weight'] >= 0 ? 'increase' : 'decrease'; ?>"> <i class="fas <?php echo $analytics['recent_weight'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> <?php echo number_format($analytics['recent_weight'], 1); ?> kg last 30 days</div> </div> </div>
                     <div class="stat-card"> <div class="stat-icon bg-warning bg-opacity-10"><i class="fas fa-hourglass-half text-warning"></i></div> <div class="stat-info"> <div class="stat-label">Pending Batches</div> <div class="stat-value"><?php echo number_format($analytics['processing_batches']); ?></div> <div class="stat-change"><?php echo number_format($analytics['total_batches']); ?> total batches</div> </div> </div>
                     <div class="stat-card"> <div class="stat-icon bg-info bg-opacity-10"><i class="fas fa-star text-info"></i></div> <div class="stat-info"> <div class="stat-label">Avg. Quality</div> <div class="stat-value"><?php echo $analytics['avg_quality']; ?></div> <div class="stat-change"><?php echo number_format($analytics['completed_batches']); ?> sold</div> </div> </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <div class="chart-section h-100">
                            <div class="section-header">
                                <h3 class="section-title mb-0">Production Trends (Last 6 Months)</h3>
                                <!-- Optional: Add date range selector -->
                            </div>
                            <div class="chart-container">
                                <canvas id="productionTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="chart-section h-100">
                             <div class="section-header">
                                <h3 class="section-title mb-0">Quality Distribution</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="qualityDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Table -->
                <div class="table-section">
                    <div class="section-header">
                         <h3 class="section-title mb-0">Recent Activity (Last 5 Batches)</h3>
                         <a href="batches.php" class="btn btn-sm btn-outline-secondary">View All Batches</a>
                    </div>
                        <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Created</th>
                                    <th>Quantity (kg)</th>
                                    <th>Micron</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                    <th>Price ($)</th> <!-- Adjust currency/label as needed -->
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($recentActivity)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No recent batches found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentActivity as $batch): ?>
                                    <tr>
                                            <td>#<?php echo htmlspecialchars($batch['id']); ?></td>
                                            <td><?php echo date('d M Y, H:i', strtotime($batch['created_at'])); ?></td>
                                            <td><?php echo number_format((float)$batch['quantity'], 1); ?></td>
                                            <td><?php echo htmlspecialchars($batch['micron'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-<?php echo getQualityClass($batch['grade']); ?>"><?php echo getQualityLabel($batch['grade']); ?></span></td>
                                            <td><span class="badge rounded-pill bg-<?php echo getStatusClass($batch['status']); ?>"><?php echo ucfirst(strtolower(htmlspecialchars($batch['status']))); ?></span></td>
                                            <td><?php echo number_format((float)($batch['price'] ?? 0), 2); ?></td>
                                            <td>
                                                <!-- Example Actions - Link to specific batch view/edit pages -->
                                                <a href="view_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                                <a href="edit_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Batch"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Farm Performance Table (If needed - currently simplified) -->
                <!-- 
                <div class="table-section mt-4"> ... Table for $farmPerformance ... </div>
                -->
            </div>
        </main>
    </div>

    <!-- Include Bootstrap JS (required for dropdowns, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Chart Initialization ---
            
            // Production Trend Chart (Line)
            const productionCtx = document.getElementById('productionTrendChart')?.getContext('2d');
            if (productionCtx) {
                new Chart(productionCtx, {
            type: 'line',
            data: {
                        labels: <?php echo json_encode($productionLabels); ?>,
                datasets: [{
                            label: 'Wool Production (kg)',
                            data: <?php echo json_encode($productionValues); ?>,
                            borderColor: 'rgba(13, 110, 253, 1)', // Bootstrap primary
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                            tension: 0.3 // Slightly curved lines
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Quantity (kg)' }
                            },
                            x: {
                                title: { display: true, text: 'Month' }
                            }
                        },
                        plugins: { legend: { display: false } } // Hide legend if only one dataset
                    }
                });
            } else {
                console.error('Canvas element for production chart not found');
            }

            // Quality Distribution Chart (Doughnut)
            const qualityCtx = document.getElementById('qualityDistributionChart')?.getContext('2d');
             if (qualityCtx) {
                // Filter out categories with 0 count for a cleaner chart
                const qualityData = <?php echo json_encode(['labels' => $qualityLabels, 'values' => $qualityValues]); ?>;
                const filteredLabels = [];
                const filteredValues = [];
                const backgroundColors = [];
                const borderColors = [];
                const colorMap = { // Define colors for grades
                    'A': 'rgba(25, 135, 84, 0.7)',  // Success green
                    'B': 'rgba(13, 110, 253, 0.7)',  // Primary blue
                    'C': 'rgba(13, 202, 240, 0.7)',  // Info cyan
                    'UNKNOWN': 'rgba(108, 117, 125, 0.7)' // Secondary grey
                };
                 const borderMap = { 
                    'A': 'rgba(25, 135, 84, 1)',
                    'B': 'rgba(13, 110, 253, 1)',
                    'C': 'rgba(13, 202, 240, 1)',
                    'UNKNOWN': 'rgba(108, 117, 125, 1)'
                };


                for (let i = 0; i < qualityData.labels.length; i++) {
                    if (qualityData.values[i] > 0) {
                        const grade = qualityData.labels[i].toUpperCase();
                        filteredLabels.push(qualityData.labels[i] + ' Grade');
                        filteredValues.push(qualityData.values[i]);
                        backgroundColors.push(colorMap[grade] || colorMap['UNKNOWN']);
                        borderColors.push(borderMap[grade] || borderMap['UNKNOWN']);
                    }
                }

                if (filteredLabels.length > 0) {
                    new Chart(qualityCtx, {
                        type: 'doughnut',
                        data: {
                            labels: filteredLabels,
                            datasets: [{
                                label: 'Batch Count',
                                data: filteredValues,
                                backgroundColor: backgroundColors,
                                borderColor: borderColors,
                                borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                                    position: 'bottom', // Position legend below chart
                                },
                                tooltip: {
                                    callbacks: { // Show count and percentage
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            const value = context.parsed;
                                            const sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = sum > 0 ? ((value / sum) * 100).toFixed(1) + '%' : '0%';
                                            label += `${value} (${percentage})`;
                                            return label;
                            }
                        }
                    }
                }
            }
        });
                 } else {
                     // Optional: Display a message if no quality data
                     qualityCtx.font = "16px Arial";
                     qualityCtx.fillStyle = "#6c757d";
                     qualityCtx.textAlign = "center";
                     qualityCtx.fillText("No quality data available", qualityCtx.canvas.width / 2, qualityCtx.canvas.height / 2);
                 }
            } else {
                 console.error('Canvas element for quality chart not found');
            }

            // --- Basic Notification Polling ---
            const notificationIconBubble = document.getElementById('topnav-notification-bubble');
            const sidebarNotificationCount = document.getElementById('sidebar-notification-count');
            
            function updateNotificationCount(count) {
                 const bubbleHtml = count > 0 ? 
                    `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em; padding: 0.3em 0.5em;">${count}<span class="visually-hidden">unread messages</span></span>` : '';
                 const sidebarHtml = count > 0 ? 
                    `<span class="badge rounded-pill bg-danger ms-auto">${count}</span>` : '';
                 
                 if (notificationIconBubble) notificationIconBubble.innerHTML = bubbleHtml;
                 if (sidebarNotificationCount) sidebarNotificationCount.innerHTML = sidebarHtml;
            }

            function checkNotifications() {
                 // Use fetch API to call a new endpoint that returns the unread count
                 fetch('../api/get_notification_count.php') // Assumes API endpoint exists at root level
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && typeof data.unread_count !== 'undefined') {
                            updateNotificationCount(data.unread_count);
                        } else {
                             console.error('Invalid notification count data received:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching notification count:', error);
                        // Optional: Stop polling if there's a persistent error
                        // clearInterval(notificationInterval); 
                    });
            }

            // Check notifications every 30 seconds (adjust interval as needed)
            const notificationInterval = setInterval(checkNotifications, 30000); 
            // Initial check on page load
            // checkNotifications(); // Uncomment if you want an immediate check

            // --- Sidebar Toggle (Optional - Basic Example) ---
            const sidebarToggleButtons = document.querySelectorAll('.sidebar-toggle');
            const dashboardContainer = document.querySelector('.dashboard-container');

            sidebarToggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    dashboardContainer.classList.toggle('sidebar-collapsed');
                    // Optional: Save preference in localStorage
                });
            });

        });
    </script>
</body>
</html> 