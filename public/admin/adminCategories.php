<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

// Fetch all categories
$stmt = $datapageConnection->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get session messages
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages after retrieving them
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Categories Management - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Categories Management</h1>
            <div class="admin-actions">
                <a href="adminDashboard.php" class="back-button">Back to Dashboard</a>
                <a href="addCategory.php" class="button">Add New Category</a>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <table class="categories-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= htmlspecialchars($category['name']) ?></td>
                    <td>
                        <a href="editCategory.php?id=<?= $category['id'] ?>" class="edit-button">Edit</a>
                        <form action="deleteCategory.php" method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                            <button type="submit" onclick="return confirm('Are you sure?')" class="delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>