<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // First check admin credentials
    $adminQuery = $datapageConnection->prepare('
        SELECT id, name, password, super_admin 
        FROM admins 
        WHERE email = :email
    ');
    $adminQuery->execute(['email' => $email]);
    $admin = $adminQuery->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Set admin session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['is_admin'] = true;
        $_SESSION['is_super_admin'] = $admin['super_admin'];

        // Change redirect to admin dashboard
        $redirectTo = isset($_GET['redirect']) ? $_GET['redirect'] : '../admin/adminDashboard.php';
        header("Location: $redirectTo");
        exit();
    } else {
        // If not admin, check regular user credentials
        $userQuery = $datapageConnection->prepare('
            SELECT id, name, password 
            FROM users 
            WHERE email = :email
        ');
        $userQuery->execute(['email' => $email]);
        $user = $userQuery->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set user session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = false;

            // Redirect to home or intended page
            $redirectTo = isset($_GET['redirect']) ? $_GET['redirect'] : '../index.php';
            header("Location: $redirectTo");
            exit();
        } else {
            $errorMessage = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Carbuy</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h3>Login to 
            <span class="C">C</span>
            <span class="a">a</span>
            <span class="r">r</span>
            <span class="b">b</span>
            <span class="u">u</span>
            <span class="y">y</span>
        </h3>
        
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" 
              method="POST">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>