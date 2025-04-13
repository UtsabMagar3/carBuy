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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['name']);
    
    if (empty($categoryName)) {
        $errorNotification = "Category name is required.";
    } else {
        // Check if category already exists
        $categoryCheck = $datapageConnection->prepare('SELECT id FROM categories WHERE name = :name');
        $categoryCheck->execute(['name' => $categoryName]);
        
        if ($categoryCheck->rowCount() > 0) {
            $errorNotification = "Category already exists.";
        } else {
            $categoryInsert = $datapageConnection->prepare('INSERT INTO categories (name) VALUES (:name)');
            
            if ($categoryInsert->execute(['name' => $categoryName])) {
                $_SESSION['success'] = "Category added successfully.";
                header('Location: adminCategories.php');
                exit();
            } else {
                $errorNotification = "Failed to add category.";
            }
        }
    }
}

$pageTitle = 'Add Category - Admin Dashboard';
?>
<?php 

require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div>
            <h1>Add New Category</h1>
        </div>
        
        <?php if ($errorNotification): ?>
            <div><?= htmlspecialchars($errorNotification) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label for="name">Category Name:</label>
            <input type="text" id="name" name="name" required 
                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            
            <div>
                <button type="submit">Add Category</button>
                <a href="adminCategories.php">Cancel</a>
            </div>
        </form>
    </main>
    
    <?php require __DIR__ . '/../includes/footer.php'; ?>
