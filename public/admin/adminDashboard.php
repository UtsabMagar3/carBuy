<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

// Fetch summary data - removed bid count query
$categoryCount = $datapageConnection->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$userCount = $datapageConnection->query('SELECT COUNT(*) FROM users')->fetchColumn();
$auctionCount = $datapageConnection->query('SELECT COUNT(*) FROM auctions')->fetchColumn();
// Removed bid count query
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Carbuy</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main class="admin-dashboard">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-profile">
                <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            </div>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Categories</h3>
                <p><?= $categoryCount ?></p>
                <a href="adminCategories.php" class="button">Manage Categories</a>
            </div>
            
            <div class="stat-card">
                <h3>Users</h3>
                <p><?= $userCount ?></p>
                <a href="manageUsers.php" class="button">Manage Users</a>
            </div>
            
            <div class="stat-card">
                <h3>Active Auctions</h3>
                <p><?= $auctionCount ?></p>
                <a href="manageAuctions.php" class="button">Manage Auctions</a>
            </div>
            
            <!-- Removed the entire bid statistics card -->
        </div>

        <?php if ($_SESSION['is_super_admin']): ?>
        <div class="admin-actions">
            <h2>Administrative Actions</h2>
            <a href="manageAdmins.php" class="button">Manage Administrators</a>
        </div>
        <?php endif; ?>

        <div class="account-actions">
            <div class="account-buttons">
                <form method="POST" action="/admin/switchAdmin.php" class="account-form">
                    <button type="submit" class="switch-account-button">Switch Account</button>
                </form>
                
                <?php if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']): ?>
                    <form method="POST" action="/admin/deleteAdminAccount.php" class="account-form"
                          onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        <input type="hidden" name="admin_id" value="<?= $_SESSION['admin_id'] ?>">
                        <button type="submit" class="delete-account-button">Delete Account</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>