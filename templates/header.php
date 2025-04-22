<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
// We need auth here if we check login status, but pages including this already do
// require_once __DIR__ . '/auth.php'; 

// Use the $pageTitle variable set by the including page, or provide a default
$title = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Woolify' : 'Woolify';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <!-- Favicon example (replace with your actual favicon) -->
    <!-- <link rel="icon" href="/favicon.ico" type="image/x-icon"> -->
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Dashboard CSS (adjust path based on where header is included from) -->
    <?php 
    // Simple path adjustment: if included from root, use css/, if from farmer/, use ../css/
    $cssPath = (basename(dirname($_SERVER['PHP_SELF'])) === 'farmer' || basename(dirname($_SERVER['PHP_SELF'])) === 'retailer') ? '../css/' : 'css/';
    ?>
    <link href="<?php echo $cssPath; ?>dashboard.css" rel="stylesheet">
    <!-- Chart.js (Include here if needed on multiple pages, or only on pages with charts) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script> 
</head>
<body class="bg-light">
<?php 
//<!-- Body tag is opened here, closed in footer.php --> 
?> 