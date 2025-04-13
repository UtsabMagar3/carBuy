<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is super admin
if (!isset($_SESSION['is_admin']) || !isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    $_SESSION['errorNotification'] = 'You need super administrator privileges to edit administrator accounts.';
    header('Location: /user/login.php');
    exit();
}

$administratorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errorNotification = '';

// Fetch administrator details
$administratorQuery = $datapageConnection->prepare('SELECT * FROM admins WHERE id = :id');
$administratorQuery->execute(['id' => $administratorId]);
$administratorData = $administratorQuery->fetch(PDO::FETCH_ASSOC);

if (!$administratorData) {
    header('Location: manageAdmins.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $administratorName = trim($_POST['fullName']);
    $administratorEmail = trim($_POST['emailAddress']);
    
    if (empty($administratorName) || empty($administratorEmail)) {
        $errorNotification = "Please fill in all required fields to update the administrator account.";
    } else {
        // Check if email exists for other admins
        $emailCheckQuery = $datapageConnection->prepare('
            SELECT id FROM admins 
            WHERE email = :email AND id != :id
        ');
        $emailCheckQuery->execute([
            'email' => $administratorEmail,
            'id' => $administratorId
        ]);

        if ($emailCheckQuery->rowCount() > 0) {
            $errorNotification = "This email address is already being used by another administrator.";
        } else {
            $updateQuery = $datapageConnection->prepare('
                UPDATE admins 
                SET name = :name, 
                    email = :email
                WHERE id = :id
            ');
            
            if ($updateQuery->execute([
                'name' => $administratorName,
                'email' => $administratorEmail,
                'id' => $administratorId
            ])) {
                $_SESSION['successNotification'] = "The administrator information has been updated successfully.";
                header('Location: manageAdmins.php');
                exit();
            } else {
                $errorNotification = "We couldn't update the administrator information due to a system error.";
            }
        }
    }
}

$pageTitle = 'Edit Administrator - Admin Dashboard';
?>

<?php require __DIR__ . '/../includes/header.php'; ?>
    
<main>
    <div>
        <h1>Edit Administrator</h1>
    </div>

    <?php if ($errorNotification): ?>
        <div><?= htmlspecialchars($errorNotification) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="fullName">Full Name:</label>
            <input type="text" id="fullName" name="fullName" required 
                   value="<?= htmlspecialchars($administratorData['name']) ?>">
        </div>

        <div>
            <label for="emailAddress">Email Address:</label>
            <input type="email" id="emailAddress" name="emailAddress" required 
                   value="<?= htmlspecialchars($administratorData['email']) ?>">
        </div>

        <div>
            <button type="submit">Save Changes</button>
            <a href="manageAdmins.php">Cancel</a>
        </div>
    </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>