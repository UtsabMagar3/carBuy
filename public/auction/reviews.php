<?php
session_start();
require '../includes/connectionpage.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errorMessage'] = 'You need to log in to write a review for this seller.';
    header('Location: /user/login.php');
    exit();
}

// Only run this code if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the review text, user ID, and auction ID from the form
    $reviewContent = $_POST['reviewtext'];
    $reviewedUserId = $_POST['user_id'];
    $auctionIdentifier = $_POST['auction_id'];
    $reviewerIdentifier = $_SESSION['user_id'];

    // Make sure the review text isn't empty
    if ($reviewContent == '') {
        // Tell the user they forgot the review
        $_SESSION['errorMessage'] = 'Please write something in your review before submitting.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Make sure user ID and auction ID are numbers
    if (!is_numeric($reviewedUserId) || !is_numeric($auctionIdentifier)) {
        // Tell the user something's wrong
        $_SESSION['errorMessage'] = 'Invalid user identifier or auction identifier format.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Turn them into numbers to be safe
    $reviewedUserId = (int)$reviewedUserId;
    $auctionIdentifier = (int)$auctionIdentifier;

    // Check if the user is trying to review themselves
    if ($reviewerIdentifier == $reviewedUserId) {
        // Tell the user they can't do that
        $_SESSION['errorMessage'] = 'You cannot write a review for yourself.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Save the review to the database
    $reviewInsertQuery = $datapageConnection->prepare('
        INSERT INTO reviews (reviewerId, reviewedUserId, reviewText, reviewDate)
        VALUES (?, ?, ?, NOW())
    ');
    $reviewInsertQuery->execute([$reviewerIdentifier, $reviewedUserId, $reviewContent]);

    // Check if the review was saved
    if ($reviewInsertQuery->rowCount() > 0) {
        $_SESSION['successMessage'] = 'Your review has been successfully added. Thank you for your feedback!';
    } else {
        $_SESSION['errorMessage'] = 'Sorry, we encountered an error while saving your review.';
    }

    header("Location: /auction/auction.php?id=$auctionIdentifier");
    exit();
}
?>