<?php
session_start();
require '../includes/connectionpage.php';

// Get the auction ID from the URL
$auctionId = (int)($_GET['id'] ?? 0);

// Redirect if the ID is invalid
if ($auctionId <= 0) {
    $_SESSION['error'] = 'Oops, that auction ID doesn’t look right.';
    header('Location: /index.php');
    exit();
}

// Fetch auction details with category and seller info
$auctionQuery = $datapageConnection->prepare('
    SELECT 
        a.*, 
        c.name AS category_name,
        u.name AS seller_name,
        (SELECT MAX(bidAmount) FROM bids WHERE auctionId = a.id) AS highest_bid,
        (SELECT COUNT(*) FROM bids WHERE auctionId = a.id) AS total_bids
    FROM auctions a
    JOIN categories c ON a.categoryId = c.id
    JOIN users u ON a.userId = u.id
    WHERE a.id = :id
');
$auctionQuery->execute(['id' => $auctionId]);
$auction = $auctionQuery->fetch(PDO::FETCH_ASSOC);


if (!$auction) {
    $_SESSION['error'] = 'Sorry, we couldn’t find that auction.';
    header('Location: /index.php');
    exit();
}

// Fetch reviews for the seller
$reviewsQuery = $datapageConnection->prepare('
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r
    JOIN users u ON r.reviewerId = u.id
    WHERE r.reviewedUserId = :userId
    ORDER BY r.reviewDate DESC
');
$reviewsQuery->execute(['userId' => $auction['userId']]);
$reviews = $reviewsQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch bid history
$bidHistoryQuery = $datapageConnection->prepare('
    SELECT 
        b.bidAmount,
        b.bidTime,
        u.name as bidder_name
    FROM bids b
    JOIN users u ON b.userId = u.id
    WHERE b.auctionId = :auctionId
    ORDER BY b.bidTime DESC
');
$bidHistoryQuery->execute(['auctionId' => $auctionId]);
$bidHistory = $bidHistoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Calculate time for remaining display
$endTime = strtotime($auction['endDate']);

// Format time remaining for display
function formatTimeRemaining($endTime) {
    $seconds = $endTime - time();
    if ($seconds <= 0) return 'Auction ended';
    
    $days = floor($seconds / (24 * 60 * 60));
    $hours = floor(($seconds % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($seconds % (60 * 60)) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = "$days day" . ($days > 1 ? 's' : '');
    if ($hours > 0) $parts[] = "$hours hour" . ($hours > 1 ? 's' : '');
    if ($minutes > 0) $parts[] = "$minutes minute" . ($minutes > 1 ? 's' : '');
    
    return implode(', ', $parts) . ' remaining';
}
?>


<?php require '../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <a href="/index.php" class="back-button">Back to Home</a>
        </div>

        <!-- Show success or error messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <article class="car">
            <!-- Show the auction image -->
                <div class="auction-image-container">
                <?php 
                // Improved image handling with fixed dimensions
                $imagePath = '/car.png'; // Default fallback image
                
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
            </div>
            
            <section class="details">
                <h2><?= htmlspecialchars($auction['title']) ?></h2>
                <h3><?= htmlspecialchars($auction['category_name']) ?></h3>
                <p>Seller: <span><?= htmlspecialchars($auction['seller_name']) ?></span></p>
                <p class="price">
                    Current bid: <?= $auction['highest_bid'] ? 
                        '£' . number_format($auction['highest_bid'], 2) : 
                        'No bids yet' ?>
                </p>
                
                <!-- Show auction timing -->
                <div class="auction-time">
                    <p class="end-date">End Date: <?= date('d M Y', strtotime($auction['endDate'])) ?></p>
                    <p class="end-time">End Time: <?= date('H:i', strtotime($auction['endDate'])) ?></p>
                    <p class="time-remaining">
                        <?= formatTimeRemaining($endTime) ?>
                    </p>
                </div>
                
                <!-- Handle bidding -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_id'] == $auction['userId']): ?>
                        <!-- Show options for the auction owner -->
                        <div class="owner-controls">
                            <p class="own-auction">This is your auction</p>
                            <div class="auction-buttons">
                                <a href="/user/editAuction.php?id=<?= $auction['id'] ?>&redirect=myauctions" 
                                   class="edit-button">Edit Auction</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Show bid form for other users -->
                        <form action="placeBid.php" method="POST" class="bid">
                            <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">
                            <div class="bid-input-group">
                                <input type="number" 
                                       name="bid_amount" 
                                       step="0.01" 
                                       min="<?= $auction['highest_bid'] ? $auction['highest_bid'] + 1 : 1 ?>" 
                                       placeholder="Enter bid amount" 
                                       required>
                                <button type="submit">Place Bid</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/user/login.php?redirect=/auction/auction.php?id=<?= $auction['id'] ?>" 
                       class="login-to-bid">Login to bid</a>
                <?php endif; ?>
            </section>
            
            <!-- Show auction description -->
            <section class="description">
                <h2>Description</h2>
                <div class="description-content">
                    <p><?= nl2br(htmlspecialchars($auction['description'])) ?></p>
                </div>
            </section>
            
            <!-- Show seller reviews -->
            <section class="reviews">
                <h2>Reviews of <?= htmlspecialchars($auction['seller_name']) ?></h2>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet</p>
                <?php else: ?>
                    <ul class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <li class="review-item">
                                <div class="review-header">
                                    <strong><?= htmlspecialchars($review['reviewer_name']) ?> said: </strong>
                                    <span class="review-date"><?= date('d M Y', strtotime($review['reviewDate'])) ?></span>
                                </div>
                                <p class="review-text"><?= nl2br(htmlspecialchars($review['reviewText'])) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- Show review form for non-owners -->
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $auction['userId']): ?>
                    <form action="reviews.php" method="POST" class="review-form">
                        <input type="hidden" name="user_id" value="<?= $auction['userId'] ?>">
                        <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">
                        <label for="reviewtext">Add your review</label>
                        <textarea id="reviewtext" name="reviewtext" required></textarea>
                        <button type="submit">Add Review</button>
                    </form>
                <?php endif; ?>
            </section>
            
            <!-- Show bid history -->
            <section class="bid-history">
                <h2>Bid History</h2>
                <?php if (empty($bidHistory)): ?>
                    <p>No bids yet</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($bidHistory as $bid): ?>
                            <li class="bid-entry">
                                <span class="bidder"><?= htmlspecialchars($bid['bidder_name']) ?></span>
                                <span class="amount">£<?= number_format($bid['bidAmount'], 2) ?></span>
                                <span class="date"><?= date('d M Y H:i', strtotime($bid['bidTime'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </article>
    </main>
<?php require '../includes/footer.php'; ?>
