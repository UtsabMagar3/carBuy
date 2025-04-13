<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

$errorMessage = '';
$successMessage = '';

// Get category ID from URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $errorMessage = "Category name is required.";
    } else {
        // Check if category name already exists (excluding current category)
        $checkCategory = $datapageConnection->prepare('
            SELECT id FROM categories 
            WHERE name = :name AND id != :id
        ');
        $checkCategory->execute([
            'name' => $name,
            'id' => $categoryId
        ]);
        
        if ($checkCategory->rowCount() > 0) {
            $errorMessage = "Category name already exists.";
        } else {
            $stmt = $datapageConnection->prepare('
                UPDATE categories 
                SET name = :name 
                WHERE id = :id
            ');
            
            if ($stmt->execute(['name' => $name, 'id' => $categoryId])) {
                $successMessage = "Category updated successfully.";
            } else {
                $errorMessage = "Failed to update category.";
            }
        }
    }
}

// Fetch category details
$stmt = $datapageConnection->prepare('SELECT * FROM categories WHERE id = :id');
$stmt->execute(['id' => $categoryId]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

// If category doesn't exist, redirect to categories list
if (!$category) {
    header('Location: adminCategories.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <h1>Edit Category</h1>
        
        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="admin-form">
            <label for="name">Category Name:</label>
            <input type="text" id="name" name="name" required>

            
            <div class="form-buttons">
                <button type="submit">Update Category</button>
                <a href="adminCategories.php" class="button">Cancel</a>
            </div>
        </form>
    </main>
    
    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

