<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /user/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_auction'])) {
    $auctionIdentifier = filter_var($_POST['auction_id'], FILTER_VALIDATE_INT);
    if ($auctionIdentifier) {
        $deleteAuctionQuery = $datapageConnection->prepare('DELETE FROM auctions WHERE id = :id');
        if ($deleteAuctionQuery->execute(['id' => $auctionIdentifier])) {
            $_SESSION['successNotification'] = 'Auction deleted successfully.';
        } else {
            $_SESSION['errorNotification'] = 'Could not delete auction.';
        }
        header('Location: manageAuctions.php');
        exit();
    }
}

// Get session messages
$errorNotification = isset($_SESSION['errorNotification']) ? $_SESSION['errorNotification'] : '';
$successNotification = isset($_SESSION['successNotification']) ? $_SESSION['successNotification'] : '';

// Clear session messages
unset($_SESSION['errorNotification'], $_SESSION['successNotification']);

// Fetch all auctions with related data
$auctionsQuery = $datapageConnection->query('
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
$auctionsList = $auctionsQuery->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Auctions - Admin Dashboard';
?>

<?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Manage Auctions</h1>
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
                <?php foreach ($auctionsList as $auctionItem): ?>
                <tr>
                    <td><?= htmlspecialchars($auctionItem['title']) ?></td>
                    <td><?= htmlspecialchars($auctionItem['category_name']) ?></td>
                    <td><?= htmlspecialchars($auctionItem['user_name']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($auctionItem['endDate'])) ?></td>
                    <td>
                        <?= strtotime($auctionItem['endDate']) > time() ? 
                            '<span class="status-active">Active</span>' : 
                            '<span class="status-ended">Ended</span>' 
                        ?>
                    </td>
                    <td>
                        <?= $auctionItem['highest_bid'] ? 
                            '£' . number_format($auctionItem['highest_bid'], 2) : 
                            'No bids' 
                        ?>
                    </td>
                    <td><?= $auctionItem['bid_count'] ?></td>
                    <td>
                        <form method="POST" class="delete-form" 
                              onsubmit="return confirm('Are you sure you want to delete this auction?');">
                            <input type="hidden" name="auction_id" value="<?= $auctionItem['id'] ?>">
                            <button type="submit" name="delete_auction" class="delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
