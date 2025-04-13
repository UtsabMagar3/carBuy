<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

$redirectPage = isset($_GET['from']) ? $_GET['from'] : 'index';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=addAuction.php');
    exit();
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = filter_var($_POST['category'], FILTER_VALIDATE_INT);
    $endDate = $_POST['endDate'];
    $userId = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($title) || empty($description) || empty($categoryId) || empty($endDate)) {
        $errorMessage = 'All fields are required.';
    } elseif (strtotime($endDate) <= time()) {
        $errorMessage = 'End date must be in the future.';
    } else {
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errorMessage = 'Invalid file type. Only JPG, PNG and GIF allowed.';
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
                    $imagePath = null;
                }
            }
        }

        if (empty($errorMessage)) {
            try {
                $insertAuction = $datapageConnection->prepare('
                    INSERT INTO auctions (title, description, categoryId, userId, endDate, image) 
                    VALUES (:title, :description, :categoryId, :userId, :endDate, :image)
                ');
                
                $values = [
                    'title' => $title,
                    'description' => $description,
                    'categoryId' => $categoryId,
                    'userId' => $userId,
                    'endDate' => $endDate,
                    'image' => $imagePath
                ];

                if ($insertAuction->execute($values)) {
                    $_SESSION['success'] = 'Auction added successfully.';
                    if ($redirectPage === 'myauctions') {
                        header('Location: myAuctions.php');
                    } else {
                        header('Location: /index.php');
                    }
                    exit();
                } else {
                    $errorMessage = 'Failed to add auction.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories
$stmt = $datapageConnection->query('SELECT id, name FROM categories ORDER BY name');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/header.php';
?>

<main class="add-auction-page">
    <div class="dashboard-header">
        <h1>Add New Auction</h1>
        <?php 
        $backUrl = isset($_GET['from']) && $_GET['from'] === 'index' ? '/index.php' : '/user/myAuctions.php';
        $backText = 'Back';
        ?>
        <a href="<?= $backUrl ?>" class="back-button"><?= $backText ?></a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="addAuction.php<?= isset($_GET['from']) ? '?from=' . htmlspecialchars($_GET['from']) : '' ?>" 
          method="POST" 
          enctype="multipart/form-data" 
          class="auction-form">
        <div class="form-grid">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                       placeholder="Enter auction title"
                       required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                            <?= isset($_POST['category']) && $_POST['category'] == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Enter detailed description of the car"
                          required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <div class="form-group">
                <label for="endDate">End Date</label>
                <input type="datetime-local" id="endDate" name="endDate" 
                       min="<?= date('Y-m-d\TH:i') ?>" 
                       value="<?= isset($_POST['endDate']) ? htmlspecialchars($_POST['endDate']) : '' ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="image">Car Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Max file size: 5MB. Allowed types: JPG, PNG, GIF</small>
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="submit-button">Add Auction</button>
            <?php 
            // Set cancel button URL based on where user came from
            $cancelUrl = isset($_GET['from']) && $_GET['from'] === 'index' ? '/index.php' : '/user/myAuctions.php';
            ?>
            <a href="<?= $cancelUrl ?>" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
