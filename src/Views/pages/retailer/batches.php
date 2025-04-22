<?php
require_once '../config/database.php';
require_once '../includes/auth_middleware.php';

// Ensure user is authenticated and has retailer role
AuthMiddleware::hasRole('retailer');

$db = new Database();
$userId = AuthMiddleware::getUserId();

// Handle batch access request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id'])) {
    $batchId = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
    
    if ($batchId) {
        // Check if request already exists
        $query = "SELECT id FROM batch_access WHERE batch_id = ? AND retailer_id = ?";
        $existing = $db->query($query, [$batchId, $userId])->fetch();
        
        if (!$existing) {
            $query = "INSERT INTO batch_access (batch_id, retailer_id, access_status) VALUES (?, ?, 'pending')";
            try {
                $db->query($query, [$batchId, $userId]);
                $_SESSION['success'] = 'Access request submitted successfully.';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Failed to submit access request.';
            }
        } else {
            $_SESSION['error'] = 'You have already requested access to this batch.';
        }
    }
    header('Location: batches.php');
    exit;
}

// Get search parameters
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$quality = filter_input(INPUT_GET, 'quality', FILTER_SANITIZE_STRING) ?? '';
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? '';

// Build query conditions
$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(wb.batch_number LIKE ? OR f.farm_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($quality) {
    $conditions[] = "wb.quality_grade = ?";
    $params[] = $quality;
}

if ($status) {
    $conditions[] = "wb.status = ?";
    $params[] = $status;
}

// Base query
$query = "SELECT wb.*, f.farm_name, ba.access_status,
          DATE_FORMAT(wb.created_at, '%Y-%m-%d') as batch_date
          FROM wool_batches wb
          JOIN farms f ON wb.farm_id = f.id
          LEFT JOIN batch_access ba ON wb.id = ba.batch_id AND ba.retailer_id = ?";

// Add conditions if any
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY wb.created_at DESC";

// Add retailer_id as first parameter
array_unshift($params, $userId);

// Get batches
$batches = $db->query($query, $params)->fetchAll(PDO::FETCH_ASSOC);

// Get user information
$query = "SELECT name, email FROM users WHERE id = ?";
$user = $db->query($query, [$userId])->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wool Batches - Woolify</title>
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
                <h1 class="page-title">Wool Batches</h1>
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

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by batch number or farm">
                        </div>
                        <div class="col-md-3">
                            <label for="quality" class="form-label">Quality Grade</label>
                            <select class="form-select" id="quality" name="quality">
                                <option value="">All Grades</option>
                                <option value="A" <?php echo $quality === 'A' ? 'selected' : ''; ?>>Grade A</option>
                                <option value="B" <?php echo $quality === 'B' ? 'selected' : ''; ?>>Grade B</option>
                                <option value="C" <?php echo $quality === 'C' ? 'selected' : ''; ?>>Grade C</option>
                                <option value="D" <?php echo $quality === 'D' ? 'selected' : ''; ?>>Grade D</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="at_farm" <?php echo $status === 'at_farm' ? 'selected' : ''; ?>>At Farm</option>
                                <option value="in_processing" <?php echo $status === 'in_processing' ? 'selected' : ''; ?>>In Processing</option>
                                <option value="processed" <?php echo $status === 'processed' ? 'selected' : ''; ?>>Processed</option>
                                <option value="in_transit" <?php echo $status === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Batches Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Batch Number</th>
                                    <th>Farm</th>
                                    <th>Weight (kg)</th>
                                    <th>Quality</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Access</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batches as $batch): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                                    <td><?php echo htmlspecialchars($batch['farm_name']); ?></td>
                                    <td><?php echo number_format($batch['weight_kg'], 1); ?></td>
                                    <td>
                                        <span class="quality-badge quality-<?php echo strtolower($batch['quality_grade']); ?>">
                                            Grade <?php echo $batch['quality_grade']; ?>
                                        </span>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td><?php echo $batch['batch_date']; ?></td>
                                    <td>
                                        <?php if ($batch['access_status']): ?>
                                            <span class="badge badge-<?php echo $batch['access_status'] === 'approved' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($batch['access_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">No Access</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$batch['access_status']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Request Access
                                                </button>
                                            </form>
                                        <?php elseif ($batch['access_status'] === 'approved'): ?>
                                            <a href="view_batch.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-info">
                                                View Details
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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