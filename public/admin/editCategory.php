<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

$errorNotification = '';
$successNotification = '';

// Get category ID from URL
$categoryIdentifier = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['categoryName']);
    
    if (empty($categoryName)) {
        $errorNotification = "Category name is required.";
    } else {
        // Check if category name already exists (excluding current category)
        $categoryExistsCheck = $datapageConnection->prepare('
            SELECT id FROM categories 
            WHERE name = :name AND id != :id
        ');
        $categoryExistsCheck->execute([
            'name' => $categoryName,
            'id' => $categoryIdentifier
        ]);
        
        if ($categoryExistsCheck->rowCount() > 0) {
            $errorNotification = "This category name already exists. Please choose a different name.";
        } else {
            $categoryUpdateQuery = $datapageConnection->prepare('
                UPDATE categories 
                SET name = :name 
                WHERE id = :id
            ');
            
            if ($categoryUpdateQuery->execute(['name' => $categoryName, 'id' => $categoryIdentifier])) {
                $_SESSION['success'] = "The category has been updated successfully.";
                header('Location: adminCategories.php');
                exit();
            } else {
                $errorNotification = "We couldn't update the category due to a system error.";
            }
        }
    }
}

// Fetch category details
$categoryInformation = $datapageConnection->prepare('SELECT * FROM categories WHERE id = :id');
$categoryInformation->execute(['id' => $categoryIdentifier]);
$categoryData = $categoryInformation->fetch(PDO::FETCH_ASSOC);

// If category doesn't exist, redirect to categories list
if (!$categoryData) {
    header('Location: adminCategories.php');
    exit();
}

$pageTitle = 'Edit Category - Admin Dashboard';
?>

<?php require __DIR__ . '/../includes/header.php'; ?>
    
<main>
    <div>
        <h1>Edit Category</h1>
    </div>
    
    <?php if ($errorNotification): ?>
        <div><?= htmlspecialchars($errorNotification) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label for="categoryName">Category Name:</label>
            <input type="text" id="categoryName" name="categoryName" required
                   value="<?= htmlspecialchars($categoryData['name']) ?>">
        </div>
        
        <div>
            <button type="submit">Update Category</button>
            <a href="adminCategories.php">Cancel</a>
        </div>
    </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

