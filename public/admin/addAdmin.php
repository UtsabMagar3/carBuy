<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

// Check if user is super admin
if (!isset($_SESSION['is_admin']) || !isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    $_SESSION['error'] = 'You must be logged in as a super admin to add administrators.';
    header('Location: ../user/login.php');
    exit();
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $username = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($username) || empty($email) || empty($password)) {
            $errorMessage = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $errorMessage = "Passwords do not match.";
        } else {
            $checkEmail = $datapageConnection->prepare('SELECT email FROM admins WHERE email = :email');
            $checkEmail->execute(['email' => $email]);

            if ($checkEmail->rowCount() > 0) {
                $errorMessage = "Email already in use. Please use a different email.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertdata = $datapageConnection->prepare('
                    INSERT INTO admins (
                        name, 
                        email, 
                        password, 
                        super_admin,
                        created_at
                    ) VALUES (
                        :username, 
                        :email, 
                        :password, 
                        0,
                        CURRENT_TIMESTAMP
                    )
                ');

                $values = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $passwordHash
                ];

                if ($insertdata->execute($values)) {
                    $_SESSION['success'] = "Administrator added successfully.";
                    header('Location: manageAdmins.php');
                    exit();
                } else {
                    $errorMessage = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Administrator - Carbuy</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    
    <main>
        <div class="admin-register-container">
            <h2>Add New Administrator</h2>
            
            <?php if ($errorMessage): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                       required>
                
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                
                <div class="form-buttons">
                    <button type="submit" class="submit-button">Add Administrator</button>
                    <a href="manageAdmins.php" class="cancel-button">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>