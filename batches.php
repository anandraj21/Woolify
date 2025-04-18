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

// Get all batches for the user
$query = "SELECT 
            wb.batch_number,
            f.farm_name,
            wt.type_name as wool_type,
            wb.weight_kg,
            wb.quality_grade,
            wb.status,
            wb.created_at,
            wb.notes
          FROM wool_batches wb 
          JOIN farms f ON wb.farm_id = f.id 
          JOIN wool_types wt ON wb.wool_type_id = wt.id
          WHERE f.user_id = ?
          ORDER BY wb.created_at DESC";

$batches = $db->query($query, [$userId])->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Batches - Woolify</title>
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
                    <a class="nav-link active" href="batches.php">
                        <i class="fas fa-list"></i> View All Batches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="farms.php">
                        <i class="fas fa-warehouse"></i> View All Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <div class="container">
                <div class="row mb-4">
                    <div class="col">
                        <h1>All Batches</h1>
                    </div>
                    <div class="col text-end">
                        <a href="add_batch.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Batch
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Batch Number</th>
                                        <th>Farm Name</th>
                                        <th>Wool Type</th>
                                        <th>Weight (kg)</th>
                                        <th>Quality</th>
                                        <th>Status</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($batches as $batch): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['farm_name']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['wool_type']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['weight_kg']); ?></td>
                                        <td>
                                            <span class="quality-badge quality-<?php echo strtolower($batch['quality_grade']); ?>">
                                                <?php echo htmlspecialchars($batch['quality_grade']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($batch['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($batch['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($batch['created_at'])); ?></td>
                                        <td>
                                            <a href="batch_details.php?id=<?php echo $batch['batch_number']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="showNotes('<?php echo htmlspecialchars($batch['notes']); ?>')">
                                                <i class="fas fa-sticky-note"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Batch Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="notesContent"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showNotes(notes) {
            document.getElementById('notesContent').textContent = notes || 'No notes available';
            new bootstrap.Modal(document.getElementById('notesModal')).show();
        }
    </script>
</body>
</html> 