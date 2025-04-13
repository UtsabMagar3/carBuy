<?php
session_start();
require '../includes/connectionpage.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Tell the user they need to log in
    $_SESSION['error'] = 'You need to log in to bid!';
    header('Location: /user/login.php');
    exit();
}

// Only run this code if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the auction ID and bid amount from the form
    $auction_id = $_POST['auction_id'];
    $bid_amount = $_POST['bid_amount'];
    $user_id = $_SESSION['user_id'];

    // Make sure the auction ID and bid amount are numbers
    if (!is_numeric($auction_id) || !is_numeric($bid_amount)) {
        $_SESSION['error'] = 'The auction ID or bid amount isn’t right.';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Turn them into numbers to be safe
    $auction_id = (int)$auction_id;
    $bid_amount = (float)$bid_amount;

    // Check if the bid amount is positive
    if ($bid_amount <= 0) {
        // Tell the user their bid is too low
        $_SESSION['error'] = 'Your bid must be more than zero.';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Look up the auction in the database
    $query = $datapageConnection->prepare('
        SELECT userId, (SELECT MAX(bidAmount) FROM bids WHERE auctionId = ?) AS highest_bid
        FROM auctions
        WHERE id = ?
    ');
    $query->execute([$auction_id, $auction_id]);
    $auction = $query->fetch();

    // Check if we found the auction
    if (!$auction) {
        // Tell the user the auction doesn’t exist
        $_SESSION['error'] = 'That auction isn’t available.';
        header('Location: /index.php');
        exit();
    }

    // Get the highest bid, or use 0 if there’s none
    $highest_bid = $auction['highest_bid'];
    if ($highest_bid == null) {
        $highest_bid = 0;
    }

    // Make sure the bid is higher than the current highest
    if ($bid_amount <= $highest_bid) {
        // Tell the user their bid needs to be higher
        $_SESSION['error'] = 'Your bid must be bigger than the current bid.';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Check if the user owns the auction
    if ($user_id == $auction['userId']) {
        // Tell the user they can’t bid on their own auction
        $_SESSION['error'] = 'You can’t bid on your own auction!';
        header("Location: /auction/auction.php?id=$auction_id");
        exit();
    }

    // Save the new bid to the database
    $bid_query = $datapageConnection->prepare('
        INSERT INTO bids (auctionId, userId, bidAmount, bidTime)
        VALUES (?, ?, ?, NOW())
    ');
    $bid_query->execute([$auction_id, $user_id, $bid_amount]);

    // Check if the bid was saved
    if ($bid_query->rowCount() > 0) {
        // Tell the user their bid worked
        $_SESSION['success'] = 'Your bid was placed!';
    } else {
        // Tell the user something went wrong
        $_SESSION['error'] = 'Sorry, we couldn’t save your bid.';
    }

    // Go back to the auction page
    header("Location: /auction/auction.php?id=$auction_id");
    exit();
}
?>