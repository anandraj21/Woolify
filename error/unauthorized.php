<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/auth.css" rel="stylesheet">
    <style>
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: var(--danger-color);
            margin-bottom: 1rem;
            line-height: 1;
        }
        
        .error-message {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 2rem;
        }
        
        .error-description {
            color: var(--muted-color);
            margin-bottom: 2rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="error-container">
                            <div class="error-code">403</div>
                            <h1 class="error-message">Access Denied</h1>
                            <p class="error-description">
                                Sorry, you don't have permission to access this page. 
                                Please make sure you're logged in with the correct account.
                            </p>
                            <?php if (isset($_SESSION['user_role'])): ?>
                                <a href="/Woolify/<?php echo $_SESSION['user_role']; ?>/dashboard.php" class="btn btn-primary back-button">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            <?php else: ?>
                                <a href="/Woolify/auth/login.php" class="btn btn-primary back-button">
                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 