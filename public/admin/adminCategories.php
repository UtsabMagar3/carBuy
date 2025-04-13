<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

// Fetch all categories
$categoryQuery = $datapageConnection->query('SELECT * FROM categories ORDER BY name');
$categoriesList = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Get session messages
$errorNotification = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$successNotification = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages after retrieving them
unset($_SESSION['error']);
unset($_SESSION['success']);

$pageTitle = 'Categories Management - Admin Dashboard';
?>


<?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div>
            <h1>Categories Management</h1>
            <div>
                <a href="adminDashboard.php">Back to Dashboard</a>
                <a href="addCategory.php">Add New Category</a>
            </div>
        </div>

        <?php if ($errorNotification): ?>
            <div><?= htmlspecialchars($errorNotification) ?></div>
        <?php endif; ?>
        
        <?php if ($successNotification): ?>
            <div><?= htmlspecialchars($successNotification) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoriesList as $category): ?>
                <tr>
                    <td><?= htmlspecialchars($category['name']) ?></td>
                    <td>
                        <a href="editCategory.php?id=<?= $category['id'] ?>">Edit</a>
                        <form action="deleteCategory.php" method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                            <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
