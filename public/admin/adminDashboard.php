<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

// Fetch summary data
$totalCategories = $datapageConnection->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalUsers = $datapageConnection->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalAuctions = $datapageConnection->query('SELECT COUNT(*) FROM auctions')->fetchColumn();

$pageTitle = 'Admin Dashboard - Carbuy';
?>


<?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div>
            <h1>Admin Dashboard</h1>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            </div>
        </div>
        
        <div>
            <div>
                <h3>Categories</h3>
                <p><?= $totalCategories ?></p>
                <a href="adminCategories.php">Manage Categories</a>
            </div>
            
            <div>
                <h3>Users</h3>
                <p><?= $totalUsers ?></p>
                <a href="manageUsers.php">Manage Users</a>
            </div>
            
            <div>
                <h3>Active Auctions</h3>
                <p><?= $totalAuctions ?></p>
                <a href="manageAuctions.php">Manage Auctions</a>
            </div>
        </div>

        <?php if ($_SESSION['is_super_admin']): ?>
        <div>
            <h2>Administrative Actions</h2>
            <a href="manageAdmins.php">Manage Administrators</a>
        </div>
        <?php endif; ?>

        <div>
            <div>
                <form method="POST" action="/admin/switchAdmin.php">
                    <button type="submit">Switch Account</button>
                </form>
                
                <?php if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']): ?>
                    <form method="POST" action="/admin/deleteAdminAccount.php"
                          onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        <input type="hidden" name="admin_id" value="<?= $_SESSION['admin_id'] ?>">
                        <button type="submit">Delete Account</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
