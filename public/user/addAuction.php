<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

$redirectPage = isset($_GET['from']) ? $_GET['from'] : 'index';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=addAuction.php');
    exit();
}

$errorNotification = '';
$successNotification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auctionTitle = trim($_POST['title']);
    $auctionDescription = trim($_POST['description']);
    $auctionCategoryId = filter_var($_POST['category'], FILTER_VALIDATE_INT);
    $auctionEndDate = $_POST['endDate'];
    $sellerIdentifier = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($auctionTitle) || empty($auctionDescription) || empty($auctionCategoryId) || empty($auctionEndDate)) {
        $errorNotification = 'All fields are required.';
    } elseif (strtotime($auctionEndDate) <= time()) {
        $errorNotification = 'End date must be in the future.';
    } else {
        // Handle image upload
        $auctionImagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowedFileTypes)) {
                $errorNotification = 'Invalid file type. Only JPG, PNG and GIF allowed.';
            } elseif ($_FILES['image']['size'] > $maxFileSize) {
                $errorNotification = 'File too large. Maximum size is 5MB.';
            } else {
                $uploadDirectory = '../uploads/';
                if (!file_exists($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }
                
                $imageFileName = uniqid() . '_' . basename($_FILES['image']['name']);
                $auctionImagePath = $uploadDirectory . $imageFileName;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $auctionImagePath)) {
                    $errorNotification = 'Failed to upload image.';
                    $auctionImagePath = null;
                }
            }
        }

        if (empty($errorNotification)) {
            try {
                $insertAuctionQuery = $datapageConnection->prepare('
                    INSERT INTO auctions (title, description, categoryId, userId, endDate, image) 
                    VALUES (:title, :description, :categoryId, :userId, :endDate, :image)
                ');
                
                $auctionValues = [
                    'title' => $auctionTitle,
                    'description' => $auctionDescription,
                    'categoryId' => $auctionCategoryId,
                    'userId' => $sellerIdentifier,
                    'endDate' => $auctionEndDate,
                    'image' => $auctionImagePath
                ];

                if ($insertAuctionQuery->execute($auctionValues)) {
                    $_SESSION['success'] = 'Auction added successfully.';
                    if ($redirectPage === 'myauctions') {
                        header('Location: myAuctions.php');
                    } else {
                        header('Location: /index.php');
                    }
                    exit();
                } else {
                    $errorNotification = 'Failed to add auction.';
                }
            } catch (PDOException $databaseException) {
                $errorNotification = 'Database error: ' . $databaseException->getMessage();
            }
        }
    }
}

// Fetch categories
$categoriesQuery = $datapageConnection->query('SELECT id, name FROM categories ORDER BY name');
$categoriesList = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Add Auction';
require __DIR__ . '/../includes/header.php';
?>

<main>
    <div>
        <h1>Add New Auction</h1>
        <?php 
        $backUrl = isset($_GET['from']) && $_GET['from'] === 'index' ? '/index.php' : '/user/myAuctions.php';
        $backText = 'Back';
        ?>
        <a href="<?= $backUrl ?>"><?= $backText ?></a>
    </div>

    <?php if ($errorNotification): ?>
        <div><?= htmlspecialchars($errorNotification) ?></div>
    <?php endif; ?>

    <form action="addAuction.php<?= isset($_GET['from']) ? '?from=' . htmlspecialchars($_GET['from']) : '' ?>" 
          method="POST" 
          enctype="multipart/form-data">
        <div>
            <div>
                <label for="title">Title</label>
                <input type="text" id="title" name="title" 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                       placeholder="Enter auction title"
                       required>
            </div>

            <div>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categoriesList as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                            <?= isset($_POST['category']) && $_POST['category'] == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Enter detailed description of the car"
                          required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <div>
                <label for="endDate">End Date</label>
                <input type="datetime-local" id="endDate" name="endDate" 
                       min="<?= date('Y-m-d\TH:i') ?>" 
                       value="<?= isset($_POST['endDate']) ? htmlspecialchars($_POST['endDate']) : '' ?>" 
                       required>
            </div>

            <div>
                <label for="image">Car Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Max file size: 5MB. Allowed types: JPG, PNG, GIF</small>
            </div>
        </div>

        <div>
            <button type="submit">Add Auction</button>
            <?php 
            // Set cancel button URL based on where user came from
            $cancelUrl = isset($_GET['from']) && $_GET['from'] === 'index' ? '/index.php' : '/user/myAuctions.php';
            ?>
            <a href="<?= $cancelUrl ?>">Cancel</a>
        </div>
    </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
