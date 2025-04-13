<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check for admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if ($userId) {
        $deleteQuery = $datapageConnection->prepare('DELETE FROM users WHERE id = :id');
        if ($deleteQuery->execute(['id' => $userId])) {
            $_SESSION['success'] = 'User deleted successfully.';
        } else {
            $_SESSION['error'] = 'Could not delete user.';
        }
        header('Location: manageUsers.php');
        exit();
    }
}

// Get session messages
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages
unset($_SESSION['error'], $_SESSION['success']);

// Fetch users with their statistics
$stmt = $datapageConnection->query('
    SELECT u.*, 
           COUNT(DISTINCT a.id) as auction_count,
           COUNT(DISTINCT b.id) as bid_count,
           COUNT(DISTINCT r.id) as review_count
    FROM users u
    LEFT JOIN auctions a ON u.id = a.userId
    LEFT JOIN bids b ON u.id = b.userId
    LEFT JOIN reviews r ON u.id = r.reviewerId
    GROUP BY u.id
    ORDER BY u.name
');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <a href="adminDashboard.php" class="back-button">Back to Dashboard</a>
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
                    <th>Auctions</th>
                    <th>Bids</th>
                    <th>Reviews</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['auction_count'] ?></td>
                    <td><?= $user['bid_count'] ?></td>
                    <td><?= $user['review_count'] ?></td>
                    <td>
                        <form method="POST" class="delete-form" 
                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" class="delete-button">Delete</button>
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