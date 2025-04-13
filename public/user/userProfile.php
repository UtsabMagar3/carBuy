<?php
session_start();
require '../includes/connectionpage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['errorMessage'] = 'Please log in to view your profile.';
    header('Location: /user/login.php');
    exit();
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteAccount'])) {
    if (isset($_POST['confirmDeletion']) && $_POST['confirmDeletion'] === 'yes') {
        $deleteUserQuery = $datapageConnection->prepare('DELETE FROM users WHERE id = :id');
        if ($deleteUserQuery->execute(['id' => $_SESSION['user_id']])) {
            // Store success message in session
            $_SESSION['successMessage'] = 'Your account has been successfully deleted.';
            // Clear all session data except success message
            $accountDeletionMessage = $_SESSION['successMessage'];
            session_unset();
            $_SESSION['successMessage'] = $accountDeletionMessage;
            // Write and close session
            session_write_close();
            header('Location: /index.php');
            exit();
        }
    }
}

// Get user details from database
$userDetailsQuery = $datapageConnection->prepare('SELECT * FROM users WHERE id = :id');
$userDetailsQuery->execute(['id' => $_SESSION['user_id']]);
$userData = $userDetailsQuery->fetch();

$pageTitle = 'My Profile';
require '../includes/header.php';
?>

<main>
    <div class="page-header">
        <h1>My Profile</h1>
        <a href="/index.php" class="back-button">Back to Home</a>
    </div>
    
    <?php if (isset($_SESSION['successMessage'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['successMessage']) ?></div>
        <?php unset($_SESSION['successMessage']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errorMessage'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['errorMessage']) ?></div>
        <?php unset($_SESSION['errorMessage']); ?>
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
                <input type="hidden" name="confirmDeletion" value="yes">
                <button type="submit" name="deleteAccount" class="delete-button">Delete Account</button>
            </form>
        </div>
    </section>
</main>

<?php require '../includes/footer.php'; ?>