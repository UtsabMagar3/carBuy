<?php
session_start();
require '../includes/connectionpage.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You need to log in to write a review!';
    header('Location: /user/login.php');
    exit();
}

// Only run this code if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the review text, user ID, and auction ID from the form
    $review_text = $_POST['reviewtext'];
    $reviewed_user_id = $_POST['user_id'];
    $auction_id = $_POST['auction_id'];
    $reviewer_id = $_SESSION['user_id'];

    // Make sure the review text isn’t empty
    if ($review_text == '') {
        // Tell the user they forgot the review
        $_SESSION['error'] = 'Please write something in your review.';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Make sure user ID and auction ID are numbers
    if (!is_numeric($reviewed_user_id) || !is_numeric($auction_id)) {
        // Tell the user something’s wrong
        $_SESSION['error'] = 'The user ID or auction ID isn’t right.';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Turn them into numbers to be safe
    $reviewed_user_id = (int)$reviewed_user_id;
    $auction_id = (int)$auction_id;

    // Check if the user is trying to review themselves
    if ($reviewer_id == $reviewed_user_id) {
        // Tell the user they can’t do that
        $_SESSION['error'] = 'You can’t write a review for yourself!';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Save the review to the database
    $query = $datapageConnection->prepare('
        INSERT INTO reviews (reviewerId, reviewedUserId, reviewText, reviewDate)
        VALUES (?, ?, ?, NOW())
    ');
    $query->execute([$reviewer_id, $reviewed_user_id, $review_text]);

    // Check if the review was saved
    if ($query->rowCount() > 0) {
        $_SESSION['success'] = 'Your review was added!';
    } else {
        $_SESSION['error'] = 'Sorry, we couldn’t save your review.';
    }

    header("Location: /auction/auction.php?id=$auction_id");
    exit();
}
?>