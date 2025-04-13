<?php
session_start();
require __DIR__ . '/includes/connectionpage.php';

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = [];

// Only perform search if query is not empty
if (!empty($searchQuery)) {
    $stmt = $datapageConnection->prepare('
        SELECT 
            a.id, a.title, a.description, a.endDate, a.image,
            c.name as category_name,
            u.name as seller_name,
            (SELECT MAX(bidAmount) FROM bids WHERE auctionId = a.id) as current_bid,
            (SELECT COUNT(*) FROM bids WHERE auctionId = a.id) as bid_count
        FROM auctions a 
        JOIN categories c ON a.categoryId = c.id
        JOIN users u ON a.userId = u.id
        WHERE (a.title LIKE :search 
           OR a.description LIKE :search 
           OR c.name LIKE :search)
        AND a.endDate > NOW()
        ORDER BY a.endDate ASC
    ');
    
    $stmt->execute(['search' => '%' . $searchQuery . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // If search is empty, redirect back to home page
    header('Location: /index.php');
    exit();
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="search-results-header">
        <h1>Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h1>
        <a href="/index.php" class="back-button">Back to Home</a>
    </div>

    <?php if (empty($searchQuery)): ?>
        <p class="no-results">Please enter a search term.</p>
    <?php elseif (empty($results)): ?>
        <p class="no-results">No auctions found matching "<?= htmlspecialchars($searchQuery) ?>"</p>
    <?php else: ?>
        <ul class="carList">
            <?php foreach ($results as $auction): ?>
                <li>
                    <?php 
                    // Improved image handling with fallback - same as in categories.php
                    $imagePath = '/images/car.png'; // Default fallback image
                    
                    if (!empty($auction['image'])) {
                        // If image path starts with ../, convert it to web-friendly path
                        if (strpos($auction['image'], '../') === 0) {
                            $imagePath = str_replace('../', '/', $auction['image']);
                        } else {
                            $imagePath = $auction['image'];
                        }
                    }
                    ?>
                    <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($auction['title']) ?>" class="auction-image">
                    
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
                            <p class="bid-count">
                                (<?= $auction['bid_count'] ?> bid<?= $auction['bid_count'] != 1 ? 's' : '' ?>)
                            </p>
                            <p class="seller">
                                Seller: <?= htmlspecialchars($auction['seller_name']) ?>
                            </p>
                            <p class="end-date">
                                Ends: <?= date('d M Y H:i', strtotime($auction['endDate'])) ?>
                            </p>
                            <a href="/auction/auction.php?id=<?= $auction['id'] ?>" class="more">More &gt;&gt;</a>
                        </div>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>