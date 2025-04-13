<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is super admin
if (!isset($_SESSION['is_admin']) || !isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    $_SESSION['error'] = 'You must be a super admin to manage administrators.';
    header('Location: /user/login.php');
    exit();
}

// Get session messages
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages
unset($_SESSION['error'], $_SESSION['success']);

// Handle admin deletion
if (isset($_POST['delete_admin']) && isset($_POST['admin_id'])) {
    $adminId = filter_var($_POST['admin_id'], FILTER_VALIDATE_INT);
    if ($adminId) {
        // Prevent deletion of super admin and self-deletion
        $deleteQuery = $datapageConnection->prepare('
            DELETE FROM admins 
            WHERE id = :id 
            AND super_admin = FALSE 
            AND id != :current_admin_id
        ');
        
        if ($deleteQuery->execute([
            'id' => $adminId,
            'current_admin_id' => $_SESSION['admin_id']
        ])) {
            $successMessage = 'Administrator removed successfully.';
        } else {
            $errorMessage = 'Could not remove administrator.';
        }
    }
}

// Fetch all admins
$adminsQuery = $datapageConnection->prepare('
    SELECT id, name, email, created_at, super_admin 
    FROM admins 
    ORDER BY created_at DESC
');
$adminsQuery->execute();
$admins = $adminsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Administrators - Carbuy</title>
    <link rel="stylesheet" href="/css/carbuy.css">  <!-- Updated to absolute path -->
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Manage Administrators</h1>
            <div class="admin-actions">
                <a href="adminDashboard.php" class="back-button">Back to Dashboard</a>
                <a href="addAdmin.php" class="button">Add New Administrator</a>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="4" class="no-records">No administrators found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo date('d M Y', strtotime($admin['created_at'])); ?></td>
                        <td class="actions">
                            <a href="editAdmin.php?id=<?php echo $admin['id']; ?>" 
                               class="edit-button">Edit</a>
                               
                            <?php if (!$admin['super_admin']): ?> <!-- Only show delete for non-super admins -->
                                <form method="POST" class="delete-form" 
                                      onsubmit="return confirm('Are you sure you want to remove this administrator?');">
                                    <input type="hidden" name="admin_id" 
                                           value="<?php echo $admin['id']; ?>">
                                    <button type="submit" name="delete_admin" 
                                            class="delete-button">Remove</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
    
    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>