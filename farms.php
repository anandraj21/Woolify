<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();

// Get all farms for the user with statistics
$query = "SELECT 
            f.*,
            COUNT(wb.id) as batch_count,
            COALESCE(SUM(wb.weight_kg), 0) as total_weight,
            COALESCE(AVG(CASE 
                WHEN quality_grade = 'A' THEN 5 
                WHEN quality_grade = 'B' THEN 4 
                WHEN quality_grade = 'C' THEN 3 
                WHEN quality_grade = 'D' THEN 2 
                WHEN quality_grade = 'E' THEN 1 
            END), 0) as avg_quality,
            'active' as status
          FROM farms f
          LEFT JOIN wool_batches wb ON f.id = wb.farm_id
          WHERE f.user_id = ?
          GROUP BY f.id
          ORDER BY f.farm_name";

$farms = $db->query($query, [$userId])->fetchAll(PDO::FETCH_ASSOC);

// Function to convert numeric quality to letter grade
function getQualityGrade($avgQuality) {
    if (!$avgQuality) return 'N/A';
    if ($avgQuality >= 4.5) return 'A';
    if ($avgQuality >= 3.5) return 'B';
    if ($avgQuality >= 2.5) return 'C';
    if ($avgQuality >= 1.5) return 'D';
    return 'E';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Farms - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
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
                    <a class="nav-link active" href="farms.php">
                        <i class="fas fa-warehouse"></i> View All Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <div class="container">
                <div class="row mb-4">
                    <div class="col">
                        <h1>All Farms</h1>
                    </div>
                    <div class="col text-end">
                        <a href="add_farm.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Farm
                        </a>
                    </div>
                </div>

                <?php if (empty($farms)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> No farms found. Add your first farm to get started!
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Location</th>
                                        <th>Contact</th>
                                        <th>Registration</th>
                                        <th>Total Batches</th>
                                        <th>Total Weight (kg)</th>
                                        <th>Avg. Quality</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($farms as $farm): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($farm['farm_name']); ?></td>
                                        <td><?php echo htmlspecialchars($farm['location']); ?></td>
                                        <td>
                                            <?php if ($farm['contact_number'] || $farm['email']): ?>
                                                <?php if ($farm['contact_number']): ?>
                                                    <div><?php echo htmlspecialchars($farm['contact_number']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($farm['email']): ?>
                                                    <div class="text-muted"><?php echo htmlspecialchars($farm['email']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No contact info</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $farm['registration_number'] ? htmlspecialchars($farm['registration_number']) : '<span class="text-muted">Not registered</span>'; ?>
                                        </td>
                                        <td><?php echo number_format($farm['batch_count']); ?></td>
                                        <td><?php echo number_format($farm['total_weight'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php $quality = getQualityGrade($farm['avg_quality']); ?>
                                            <?php if ($quality !== 'N/A'): ?>
                                                <span class="quality-badge quality-<?php echo strtolower($quality); ?>">
                                                    <?php echo $quality; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $statusClass = $farm['status'] === 'active' ? 'success' : 'danger';
                                            $statusText = ucfirst($farm['status']);
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="farm_details.php?id=<?php echo $farm['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_farm.php?id=<?php echo $farm['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit Farm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 