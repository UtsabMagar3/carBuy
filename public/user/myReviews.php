<?php
session_start();
require '../includes/connectionpage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit();
}

// Fetch reviews for the current user
$userId = $_SESSION['user_id'];
$reviews = $datapageConnection->query("
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r
    JOIN users u ON r.reviewerId = u.id
    WHERE r.reviewedUserId = $userId
    ORDER BY r.reviewDate DESC
")->fetchAll(PDO::FETCH_ASSOC);

require '../includes/header.php';
?>

<main class="reviews-page">
    <div class="page-header">
        <h1>My Reviews</h1>
        <a href="/user/userProfile.php" class="back-button">Back to Profile</a>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="no-reviews">
            <p>You haven't received any reviews yet.</p>
        </div>
    <?php else: ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="reviewer-name"><?= $review['reviewer_name'] ?></span>
                        <span class="review-date">
                            <?= date('M j, Y', strtotime($review['reviewDate'])) ?>
                        </span>
                    </div>
                    <div class="review-content">
                        <p><?= nl2br($review['reviewText']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require '../includes/footer.php'; ?>