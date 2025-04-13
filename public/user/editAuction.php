<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to edit your auction.';
    header('Location: /user/login.php');
    exit();
}

// Initialize variables
$errorMessage = '';
$successMessage = '';
$auction = null;

// Get auction ID from query string
$auctionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$auctionId) {
    $_SESSION['error'] = 'Invalid auction ID.';
    header('Location: myAuctions.php');
    exit();
}

// Get the referrer - where the user came from
$referrer = isset($_GET['from']) ? $_GET['from'] : 'myauctions';

// Fetch auction details
$stmt = $datapageConnection->prepare('SELECT * FROM auctions WHERE id = :id AND userId = :userId');
$stmt->execute(['id' => $auctionId, 'userId' => $_SESSION['user_id']]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    $_SESSION['error'] = 'Auction not found or you do not have permission to edit it.';
    header('Location: myAuctions.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $categoryId = $_POST['category'];
    $endDate = $_POST['endDate'];

    // Handle image upload
    $imagePath = $auction['image']; // Keep the existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errorMessage = 'Invalid file type. Only JPG, PNG, and GIF allowed.';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errorMessage = 'File too large. Maximum size is 5MB.';
        } else {
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $imagePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $errorMessage = 'Failed to upload image.';
                $imagePath = $auction['image']; // Revert to the existing image
            }
        }
    }

    if (empty($title) || empty($description) || empty($categoryId) || empty($endDate)) {
        $errorMessage = 'All fields are required.';
    } else {
        $updateAuction = $datapageConnection->prepare(
            'UPDATE auctions 
             SET title = :title, description = :description, categoryId = :categoryId, endDate = :endDate, image = :image 
             WHERE id = :id AND userId = :userId'
        );
        $values = [
            'title' => $title,
            'description' => $description,
            'categoryId' => $categoryId,
            'endDate' => $endDate,
            'image' => $imagePath,
            'id' => $auctionId,
            'userId' => $_SESSION['user_id']
        ];

        if ($updateAuction->execute($values)) {
            $_SESSION['success'] = 'Auction updated successfully.';
            
            // Redirect to auction page
            header("Location: /auction/auction.php?id={$auctionId}");
            exit();
        } else {
            $errorMessage = 'Failed to update auction.';
        }
    }
}

// Fetch categories
$categories = [
    ['id' => 1, 'name' => 'Estate'],
    ['id' => 2, 'name' => 'Electric'],
    ['id' => 3, 'name' => 'Coupe'],
    ['id' => 4, 'name' => 'Saloon'],
    ['id' => 5, 'name' => '4x4'],
    ['id' => 6, 'name' => 'Sports'],
    ['id' => 7, 'name' => 'Hybrid'],
];

require __DIR__ . '/../includes/header.php';
?>

<main class="edit-auction-page">
    <div class="page-header">
        <h1>Edit Auction</h1>
    </div>

    <?php if ($errorMessage): ?>
        <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="editAuction.php?id=<?= $auctionId ?>&from=<?= $referrer ?>" method="POST" enctype="multipart/form-data">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($auction['title']) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" required><?= htmlspecialchars($auction['description']) ?></textarea>

        <label for="category">Category</label>
        <select id="category" name="category" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= $category['id'] == $auction['categoryId'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="endDate">End Date</label>
        <input type="datetime-local" id="endDate" name="endDate" value="<?= date('Y-m-d\TH:i', strtotime($auction['endDate'])) ?>" required>

        <label for="image">Car Image</label>
        <input type="file" id="image" name="image" accept="image/*">
        <small>Max file size: 5MB. Allowed types: JPG, PNG, GIF</small>
        <?php if ($auction['image']): ?>
            <p>Current Image: <img src="<?= htmlspecialchars($auction['image']) ?>" alt="Auction Image" style="max-width: 200px;"></p>
        <?php endif; ?>

        <div class="form-buttons">
            <button type="submit">Update Auction</button>
            <a href="/auction/auction.php?id=<?= $auctionId ?>" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>