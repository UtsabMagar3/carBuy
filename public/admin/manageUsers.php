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
    $userIdentifier = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if ($userIdentifier) {
        $deleteUserQuery = $datapageConnection->prepare('DELETE FROM users WHERE id = :id');
        if ($deleteUserQuery->execute(['id' => $userIdentifier])) {
            $_SESSION['successNotification'] = 'User deleted successfully.';
        } else {
            $_SESSION['errorNotification'] = 'Could not delete user.';
        }
        header('Location: manageUsers.php');
        exit();
    }
}

// Get session messages
$errorNotification = isset($_SESSION['errorNotification']) ? $_SESSION['errorNotification'] : '';
$successNotification = isset($_SESSION['successNotification']) ? $_SESSION['successNotification'] : '';

// Clear session messages
unset($_SESSION['errorNotification'], $_SESSION['successNotification']);

// Fetch users with their statistics
$usersQuery = $datapageConnection->query('
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
$usersList = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Users - Admin Dashboard';
?>

<?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <a href="adminDashboard.php" class="back-button">Back to Dashboard</a>
        </div>

        <?php if ($errorNotification): ?>
            <div class="error-message"><?= htmlspecialchars($errorNotification) ?></div>
        <?php endif; ?>
        <?php if ($successNotification): ?>
            <div class="success-message"><?= htmlspecialchars($successNotification) ?></div>
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
                <?php foreach ($usersList as $userAccount): ?>
                <tr>
                    <td><?= htmlspecialchars($userAccount['name']) ?></td>
                    <td><?= htmlspecialchars($userAccount['email']) ?></td>
                    <td><?= $userAccount['auction_count'] ?></td>
                    <td><?= $userAccount['bid_count'] ?></td>
                    <td><?= $userAccount['review_count'] ?></td>
                    <td>
                        <form method="POST" class="delete-form" 
                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="user_id" value="<?= $userAccount['id'] ?>">
                            <button type="submit" name="delete_user" class="delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
