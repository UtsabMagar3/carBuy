<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $categoryId = (int)$_POST['id'];
    
    // Check if category has any auctions
    $checkAuctions = $datapageConnection->prepare('
        SELECT COUNT(*) FROM auctions WHERE categoryId = :id
    ');
    $checkAuctions->execute(['id' => $categoryId]);
    
    if ($checkAuctions->fetchColumn() > 0) {
        $_SESSION['error'] = "Cannot delete category: There are auctions using this category.";
    } else {
        $stmt = $datapageConnection->prepare('DELETE FROM categories WHERE id = :id');
        if ($stmt->execute(['id' => $categoryId])) {
            $_SESSION['success'] = "Category deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete category.";
        }
    }
}

// Redirect back to categories list
header('Location: adminCategories.php');
exit();