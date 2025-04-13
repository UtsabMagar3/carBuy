<?php
session_start();
require '../includes/connectionpage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to view your profile.';
    header('Location: /user/login.php');
    exit();
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        $deleteQuery = $datapageConnection->prepare('DELETE FROM users WHERE id = :id');
        if ($deleteQuery->execute(['id' => $_SESSION['user_id']])) {
            // Store success message in session
            $_SESSION['success'] = 'Your account has been successfully deleted.';
            // Clear all session data except success message
            $successMessage = $_SESSION['success'];
            session_unset();
            $_SESSION['success'] = $successMessage;
            // Write and close session
            session_write_close();
            header('Location: /index.php');
            exit();
        }
    }
}

// Get user details from database
$stmt = $datapageConnection->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

require '../includes/header.php';
?>

<main>
    <div class="page-header">
        <h1>My Profile</h1>
        <a href="/index.php" class="back-button">Back to Home</a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <section class="user-info">
        <h2>Account Information</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['user_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user_email']) ?></p>
    </section>

    <section class="account-actions">
        <div class="profile-links">
            <a href="/user/myReviews.php" class="profile-button">My Reviews</a>
        </div>
        <div class="action-buttons">
            <form method="POST" action="/user/switchAccount.php">
                <button type="submit" class="switch-account-button">Switch Account</button>
            </form>
            <form method="POST" action="/user/logout.php">
                <button type="submit" class="logout-button">Logout</button>
            </form>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                <input type="hidden" name="confirm_delete" value="yes">
                <button type="submit" name="delete_account" class="delete-button">Delete Account</button>
            </form>
        </div>
    </section>
</main>

<?php require '../includes/footer.php'; ?>