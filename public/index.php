<?php
session_start();
require __DIR__ . '/includes/connectionpage.php';
require __DIR__ . '/includes/header.php';

// First, delete ended auctions from database
try {
    $deleteEndedAuctions = $datapageConnection->prepare('
        DELETE FROM auctions 
        WHERE endDate <= NOW()
    ');
    $deleteEndedAuctions->execute();
} catch (PDOException $e) {
    error_log("Error deleting ended auctions: " . $e->getMessage());
}

// Get the current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Fetch 10 auctions with offset
$stmt = $datapageConnection->prepare('
    SELECT 
        a.id, a.title, a.description, a.endDate, a.image,
        c.name as category_name,
        u.name as seller_name,
        (SELECT MAX(bidAmount) FROM bids WHERE auctionId = a.id) as current_bid
    FROM auctions a 
    JOIN categories c ON a.categoryId = c.id
    JOIN users u ON a.userId = u.id
    WHERE a.endDate > NOW() /* Only show active auctions */
    ORDER BY a.endDate ASC
    LIMIT :limit OFFSET :offset
');

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of auctions
$totalStmt = $datapageConnection->query('
    SELECT COUNT(*) 
    FROM auctions 
    WHERE endDate > NOW()
');
$totalAuctions = $totalStmt->fetchColumn();
?>

<div class="banner-container">
    <img src="banners/1.jpg" alt="Banner" />
</div>

<main>
    <div class="listing-header">
        <h1>Soon to end auctions</h1>
        <div class="header-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/user/addAuction.php?from=index" class="add-auction-button">Add Auction</a>
            <?php else: ?>
                <a href="/user/login.php" class="add-auction-button">Login to Add Auction</a>
            <?php endif; ?>
        </div>
    </div>
    
    <ul class="carList" id="auctionsList">
        <?php if (empty($auctions)): ?>
            <li>No active auctions found.</li>
        <?php else: ?>
            <?php foreach ($auctions as $auction): ?>
                <li>
                    <img src="<?= $auction['image'] ?? 'car.png' ?>" alt="Car Image">
                    
                    <article>
                        <h2><?= htmlspecialchars($auction['title']) ?></h2>
                        <h3><?= htmlspecialchars($auction['category_name']) ?></h3>
                        <p><?= htmlspecialchars(substr($auction['description'], 0, 200)) ?>...</p>
                        
                        <div class="auction-info">
                            <p class="price">
                                Current bid: <?= $auction['current_bid'] ? 
                                    '£' . number_format($auction['current_bid'], 2) : 
                                    'No bids yet' 
                                ?>
                            </p>
                            <p class="seller">
                                Seller: <?= htmlspecialchars($auction['seller_name']) ?>
                            </p>
                            <p class="end-date">
                                Ends: <?= date('d M Y H:i', strtotime($auction['endDate'])) ?>
                            </p>
                            <a href="/auction/auction.php?id=<?= $auction['id'] ?>" class="more auctionLink">More &gt;&gt;</a>
                        </div>
                    </article>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <?php if ($totalAuctions > $perPage * $page): ?>
        <div class="see-more-container">
            <button id="seeMoreBtn" class="see-more-button" data-page="<?= $page ?>">See More</button>
        </div>
    <?php endif; ?>
</main>

<script>
document.getElementById('seeMoreBtn')?.addEventListener('click', function() {
    const button = this;
    let nextPage = parseInt(button.dataset.page) + 1;
    
    fetch(`/index.php?page=${nextPage}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newAuctions = doc.querySelectorAll('#auctionsList li');
            
            newAuctions.forEach(auction => {
                document.getElementById('auctionsList').appendChild(auction.cloneNode(true));
            });
            
            button.dataset.page = nextPage;
            
            // Hide button if no more auctions
            if (<?= $totalAuctions ?> <= nextPage * <?= $perPage ?>) {
                button.style.display = 'none';
            }
        });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>