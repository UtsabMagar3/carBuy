<?php
session_start();
require __DIR__ . '/includes/connectionpage.php';

// Pagination settings
$itemsPerPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$categoryName = isset($_GET['name']) ? $_GET['name'] : 'All Categories';

// Fetch all categories
$categoriesQuery = $datapageConnection->query('
    SELECT 
        c.id,
        c.name,
        COUNT(a.id) as auction_count
    FROM categories c
    LEFT JOIN auctions a ON c.id = a.categoryId 
        AND a.endDate > NOW()
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
');
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

// If specific category is selected, fetch its auctions
if ($categoryId) {
    // Get total count for pagination
    $countQuery = $datapageConnection->prepare('
        SELECT COUNT(*) FROM auctions 
        WHERE categoryId = :categoryId AND endDate > NOW()
    ');
    $countQuery->execute(['categoryId' => $categoryId]);
    $totalItems = $countQuery->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Fetch auctions for this category
    $auctionsQuery = $datapageConnection->prepare('
        SELECT 
            a.id, a.title, a.description, a.endDate, a.image,
            c.name as category_name,
            u.name as seller_name,
            (SELECT MAX(bidAmount) FROM bids WHERE auctionId = a.id) as current_bid,
            (SELECT COUNT(*) FROM bids WHERE auctionId = a.id) as bid_count
        FROM auctions a 
        JOIN categories c ON a.categoryId = c.id
        JOIN users u ON a.userId = u.id
        WHERE a.categoryId = :categoryId 
        AND a.endDate > NOW()
        ORDER BY a.endDate ASC
        LIMIT :offset, :limit
    ');
    
    $auctionsQuery->bindValue(':categoryId', $categoryId, PDO::PARAM_INT);
    $auctionsQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
    $auctionsQuery->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $auctionsQuery->execute();
    $auctions = $auctionsQuery->fetchAll(PDO::FETCH_ASSOC);
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="page-header">
        <h1><?= htmlspecialchars($categoryId ? $categoryName : 'All Categories') ?></h1>
        <a href="/index.php" class="back-button">Back to Home</a>
    </div>

    <?php if (!$categoryId): ?>
        <!-- Show all categories grid -->
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="/categories.php?id=<?= $category['id'] ?>&name=<?= urlencode($category['name']) ?>" 
                   class="category-card">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                    <p>
                        <?= $category['auction_count'] ?> active auction<?= $category['auction_count'] !== 1 ? 's' : '' ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Show auctions for selected category -->
        <div class="category-auctions">
            <?php if (empty($auctions)): ?>
                <p class="no-auctions">No active auctions in this category.</p>
            <?php else: ?>
                <ul class="carList">
                    <?php foreach ($auctions as $auction): ?>
                        <li>
                            <?php 
                            // Improved image handling with fallback
                            $imagePath = '/car.png'; // Default fallback image in public directory
                            
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?id=<?= $categoryId ?>&name=<?= urlencode($categoryName) ?>&page=<?= $i ?>" 
                               class="<?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>