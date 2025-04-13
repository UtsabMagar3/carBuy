<?php
session_start();
require '../includes/connectionpage.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Tell the user they need to log in
    $_SESSION['errorMessage'] = 'You need to log in to place a bid on this auction.';
    header('Location: /user/login.php');
    exit();
}

// Only run this code if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the auction ID and bid amount from the form
    $auctionIdentifier = $_POST['auction_id'];
    $bidAmount = $_POST['bid_amount'];
    $userIdentifier = $_SESSION['user_id'];

    // Make sure the auction ID and bid amount are numbers
    if (!is_numeric($auctionIdentifier) || !is_numeric($bidAmount)) {
        $_SESSION['errorMessage'] = 'Invalid auction identifier or bid amount format.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Turn them into numbers to be safe
    $auctionIdentifier = (int)$auctionIdentifier;
    $bidAmount = (float)$bidAmount;

    // Check if the bid amount is positive
    if ($bidAmount <= 0) {
        // Tell the user their bid is too low
        $_SESSION['errorMessage'] = 'Your bid amount must be greater than zero.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Look up the auction in the database
    $auctionQuery = $datapageConnection->prepare('
        SELECT userId, (SELECT MAX(bidAmount) FROM bids WHERE auctionId = ?) AS highest_bid
        FROM auctions
        WHERE id = ?
    ');
    $auctionQuery->execute([$auctionIdentifier, $auctionIdentifier]);
    $auctionData = $auctionQuery->fetch();

    // Check if we found the auction
    if (!$auctionData) {
        // Tell the user the auction doesn't exist
        $_SESSION['errorMessage'] = 'The requested auction is not available.';
        header('Location: /index.php');
        exit();
    }

    // Get the highest bid, or use 0 if there's none
    $highestExistingBid = $auctionData['highest_bid'];
    if ($highestExistingBid == null) {
        $highestExistingBid = 0;
    }

    // Make sure the bid is higher than the current highest
    if ($bidAmount <= $highestExistingBid) {
        // Tell the user their bid needs to be higher
        $_SESSION['errorMessage'] = 'Your bid must be higher than the current highest bid.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Check if the user owns the auction
    if ($userIdentifier == $auctionData['userId']) {
        // Tell the user they can't bid on their own auction
        $_SESSION['errorMessage'] = 'You cannot bid on your own auction listing.';
        header("Location: /auction/auction.php?id=$auctionIdentifier");
        exit();
    }

    // Save the new bid to the database
    $bidInsertQuery = $datapageConnection->prepare('
        INSERT INTO bids (auctionId, userId, bidAmount, bidTime)
        VALUES (?, ?, ?, NOW())
    ');
    $bidInsertQuery->execute([$auctionIdentifier, $userIdentifier, $bidAmount]);

    // Check if the bid was saved
    if ($bidInsertQuery->rowCount() > 0) {
        // Tell the user their bid worked
        $_SESSION['successMessage'] = 'Your bid has been successfully placed!';
    } else {
        // Tell the user something went wrong
        $_SESSION['errorMessage'] = 'Sorry, we encountered an error while processing your bid.';
    }

    // Go back to the auction page
    header("Location: /auction/auction.php?id=$auctionIdentifier");
    exit();
}
?>