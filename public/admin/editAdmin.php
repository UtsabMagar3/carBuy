<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is super admin
if (!isset($_SESSION['is_admin']) || !isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    $_SESSION['error'] = 'You must be a super admin to edit administrators.';
    header('Location: /user/login.php');
    exit();
}

$adminId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errorMessage = '';

// Fetch admin details
$stmt = $datapageConnection->prepare('SELECT * FROM admins WHERE id = :id');
$stmt->execute(['id' => $adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: manageAdmins.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $errorMessage = "All fields are required.";
    } else {
        // Check if email exists for other admins
        $checkEmail = $datapageConnection->prepare('
            SELECT id FROM admins 
            WHERE email = :email AND id != :id
        ');
        $checkEmail->execute([
            'email' => $email,
            'id' => $adminId
        ]);

        if ($checkEmail->rowCount() > 0) {
            $errorMessage = "Email already exists.";
        } else {
            $stmt = $datapageConnection->prepare('
                UPDATE admins 
                SET name = :name, 
                    email = :email
                WHERE id = :id
            ');
            
            if ($stmt->execute([
                'name' => $name,
                'email' => $email,
                'id' => $adminId
            ])) {
                $_SESSION['success'] = "Administrator updated successfully.";
                header('Location: manageAdmins.php');
                exit();
            } else {
                $errorMessage = "Failed to update administrator.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Administrator - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Edit Administrator</h1>
        </div>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($admin['name']) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($admin['email']) ?>">
            </div>

            <div class="form-buttons">
                <button type="submit" class="save-button">Save Changes</button>
                <a href="manageAdmins.php" class="cancel-button">Cancel</a>
            </div>
        </form>
    </main>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>