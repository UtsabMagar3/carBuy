<?php
session_start();
require '../includes/connectionpage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit();
}

// Fetch reviews for the current user
$currentUserId = $_SESSION['user_id'];
$userReviews = $datapageConnection->query("
    SELECT r.*, u.name as reviewerName 
    FROM reviews r
    JOIN users u ON r.reviewerId = u.id
    WHERE r.reviewedUserId = $currentUserId
    ORDER BY r.reviewDate DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Reviews - Carbuy';
require '../includes/header.php';
?>

<main class="reviews-page">
    <div class="page-header">
        <h1>My Reviews</h1>
        <a href="/user/userProfile.php" class="back-button">Back to Profile</a>
    </div>

    <?php if (empty($userReviews)): ?>
        <div class="no-reviews">
            <p>You haven't received any reviews yet.</p>
        </div>
    <?php else: ?>
        <div class="reviews-list">
            <?php foreach ($userReviews as $reviewItem): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="reviewer-name"><?= $reviewItem['reviewerName'] ?></span>
                        <span class="review-date">
                            <?= date('M j, Y', strtotime($reviewItem['reviewDate'])) ?>
                        </span>
                    </div>
                    <div class="review-content">
                        <p><?= nl2br($reviewItem['reviewText']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require '../includes/footer.php'; ?>