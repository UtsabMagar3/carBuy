<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

if (!isset($_SESSION['admin_id']) || !isset($_POST['admin_id']) || $_SESSION['admin_id'] != $_POST['admin_id']) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: adminDashboard.php');
    exit();
}

// Don't allow super admin deletion
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']) {
    $_SESSION['error'] = 'Super admin accounts cannot be deleted.';
    header('Location: adminDashboard.php');
    exit();
}

try {
    $deleteQuery = $datapageConnection->prepare('DELETE FROM admins WHERE id = :id AND super_admin = FALSE');
    
    if ($deleteQuery->execute(['id' => $_SESSION['admin_id']])) {
        // Clear all session data
        session_unset();
        session_destroy();
        
        // Start new session for message
        session_start();
        $_SESSION['success'] = 'Your admin account has been successfully deleted.';
        
        // Redirect to index page
        header('Location: /index.php');
    } else {
        $_SESSION['error'] = 'Failed to delete account.';
        header('Location: adminDashboard.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error occurred.';
    header('Location: adminDashboard.php');
}
exit();