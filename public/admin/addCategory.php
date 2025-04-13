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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $errorMessage = "Category name is required.";
    } else {
        // Check if category already exists
        $checkCategory = $datapageConnection->prepare('SELECT id FROM categories WHERE name = :name');
        $checkCategory->execute(['name' => $name]);
        
        if ($checkCategory->rowCount() > 0) {
            $errorMessage = "Category already exists.";
        } else {
            $stmt = $datapageConnection->prepare('INSERT INTO categories (name) VALUES (:name)');
            
            if ($stmt->execute(['name' => $name])) {
                $_SESSION['success'] = "Category added successfully.";
                header('Location: adminCategories.php');
                exit();
            } else {
                $errorMessage = "Failed to add category.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Add New Category</h1>
        </div>
        
        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="admin-form">
            <label for="name">Category Name:</label>
            <input type="text" id="name" name="name" required 
                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            
            <div class="form-buttons">
                <button type="submit">Add Category</button>
                <a href="adminCategories.php" class="button">Cancel</a>
            </div>
        </form>
    </main>
    
    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>