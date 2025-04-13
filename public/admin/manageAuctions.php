<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_auction'])) {
    $auctionId = filter_var($_POST['auction_id'], FILTER_VALIDATE_INT);
    if ($auctionId) {
        $deleteQuery = $datapageConnection->prepare('DELETE FROM auctions WHERE id = :id');
        if ($deleteQuery->execute(['id' => $auctionId])) {
            $_SESSION['success'] = 'Auction deleted successfully.';
        } else {
            $_SESSION['error'] = 'Could not delete auction.';
        }
        header('Location: manageAuctions.php');
        exit();
    }
}

// Get session messages
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear session messages
unset($_SESSION['error'], $_SESSION['success']);

// Fetch all auctions with related data
$stmt = $datapageConnection->query('
    SELECT a.*, 
           c.name as category_name,
           u.name as user_name,
           (SELECT COUNT(*) FROM bids WHERE auctionId = a.id) as bid_count,
           (SELECT MAX(bidAmount) FROM bids WHERE auctionId = a.id) as highest_bid
    FROM auctions a
    JOIN categories c ON a.categoryId = c.id
    JOIN users u ON a.userId = u.id
    ORDER BY a.endDate DESC
');
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Auctions - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Manage Auctions</h1>
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
                    <th>Title</th>
                    <th>Category</th>
                    <th>Seller</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Highest Bid</th>
                    <th>Total Bids</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auctions as $auction): ?>
                <tr>
                    <td><?= htmlspecialchars($auction['title']) ?></td>
                    <td><?= htmlspecialchars($auction['category_name']) ?></td>
                    <td><?= htmlspecialchars($auction['user_name']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($auction['endDate'])) ?></td>
                    <td>
                        <?= strtotime($auction['endDate']) > time() ? 
                            '<span class="status-active">Active</span>' : 
                            '<span class="status-ended">Ended</span>' 
                        ?>
                    </td>
                    <td>
                        <?= $auction['highest_bid'] ? 
                            '£' . number_format($auction['highest_bid'], 2) : 
                            'No bids' 
                        ?>
                    </td>
                    <td><?= $auction['bid_count'] ?></td>
                    <td>
                        <form method="POST" class="delete-form" 
                              onsubmit="return confirm('Are you sure you want to delete this auction?');">
                            <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">
                            <button type="submit" name="delete_auction" class="delete-button">Delete</button>
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