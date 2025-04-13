<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is super admin
if (!isset($_SESSION['is_admin']) || !isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    $_SESSION['errorNotification'] = 'You need super administrator privileges to manage administrators.';
    header('Location: /user/login.php');
    exit();
}

// Get session messages
$errorNotification = isset($_SESSION['errorNotification']) ? $_SESSION['errorNotification'] : '';
$successNotification = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages
unset($_SESSION['errorNotification'], $_SESSION['success']);

// Handle admin deletion
if (isset($_POST['delete_admin']) && isset($_POST['admin_id'])) {
    $administratorId = filter_var($_POST['admin_id'], FILTER_VALIDATE_INT);
    if ($administratorId) {
        // Prevent deletion of super admin and self-deletion
        $deleteAdminQuery = $datapageConnection->prepare('
            DELETE FROM admins 
            WHERE id = :id 
            AND super_admin = FALSE 
            AND id != :current_admin_id
        ');
        
        if ($deleteAdminQuery->execute([
            'id' => $administratorId,
            'current_admin_id' => $_SESSION['admin_id']
        ])) {
            $successNotification = 'Administrator removed successfully.';
        } else {
            $errorNotification = 'Could not remove administrator.';
        }
    }
}

// Fetch all admins
$administratorsQuery = $datapageConnection->prepare('
    SELECT id, name, email, created_at, super_admin 
    FROM admins 
    ORDER BY created_at DESC
');
$administratorsQuery->execute();
$administratorsList = $administratorsQuery->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Administrators - Carbuy';
?>


<?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div>
            <h1>Manage Administrators</h1>
            <div>
                <a href="adminDashboard.php">Back to Dashboard</a>
                <a href="addAdmin.php">Add New Administrator</a>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($administratorsList)): ?>
                    <tr>
                        <td colspan="4">No administrators found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($administratorsList as $administrator): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($administrator['name']); ?></td>
                        <td><?php echo htmlspecialchars($administrator['email']); ?></td>
                        <td><?php echo date('d M Y', strtotime($administrator['created_at'])); ?></td>
                        <td>
                            <a href="editAdmin.php?id=<?php echo $administrator['id']; ?>">Edit</a>
                               
                            <?php if (!$administrator['super_admin']): ?> <!-- Only show delete for non-super admins -->
                                <form method="POST" 
                                      onsubmit="return confirm('Are you sure you want to remove this administrator?');">
                                    <input type="hidden" name="admin_id" 
                                           value="<?php echo $administrator['id']; ?>">
                                    <button type="submit" name="delete_admin">Remove</button>
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
