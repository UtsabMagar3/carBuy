<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLoggedIn = false;
$isAdmin = false;

// Check if admin is logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $userLoggedIn = true;
    $isAdmin = true;
} 
// Check if regular user is logged in
elseif (isset($_SESSION['user_id'])) {
    $userLoggedIn = true;
}

// Fetch category data for navigation
require_once __DIR__ . '/connectionpage.php';
$navCategories = $datapageConnection->query('
    SELECT id, name FROM categories 
    ORDER BY name ASC 
    LIMIT 7
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Carbuy'; ?></title>
    <link rel="stylesheet" href="/css/carbuy.css" />
</head>

<body>
    <header>
        <h1>
            <a href="/index.php" style=" text-decoration: none;">
                <span class="C">C</span>
                <span class="a">a</span>
                <span class="r">r</span>
                <span class="b">b</span>
                <span class="u">u</span>
                <span class="y">y</span>
            </a>
        </h1>

        <form action="/search.php" method="GET">
            <input type="text" name="search" placeholder="Search for a car" />
            <input type="submit" name="submit" value="Search" />
        </form>

        <?php if ($userLoggedIn): ?>
            <div class="user-profile">
                <?php if ($isAdmin): ?>
                    <a href="/admin/adminDashboard.php" class="profile-link">Admin Dashboard</a>
                <?php else: ?>
                    <a href="/user/userProfile.php" class="profile-link">My Profile</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <a href="/user/login.php" class="login-button">Login</a>
        <?php endif; ?>
    </header>

    <nav>
        <ul>
            <?php foreach ($navCategories as $category): ?>
                <li><a class="categoryLink" href="/categories.php?id=<?= $category['id'] ?>&name=<?= urlencode($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></a></li>
            <?php endforeach; ?>
            <li><a class="categoryLink more-link" href="/categories.php">More</a></li>
        </ul>
    </nav>
</body>
</html>
