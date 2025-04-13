<?php
session_start();
require __DIR__ . '/../includes/connectionpage.php';

$errorMessage = '';

if(isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password) {
        $errorMessage = "Passwords do not match.";
    } else {
        $checkEmail = $datapageConnection->prepare('SELECT email FROM users WHERE email = :email');
        $checkEmail->execute(['email' => $email]);

        if($checkEmail->rowCount() > 0) {
            $errorMessage = "Email already in use. Please use a different email.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertdata = $datapageConnection->prepare('INSERT INTO users(name, email, password) VALUES(:username, :email, :password)');

            $values = [
                'username' => $username,
                'email' => $email,
                'password' => $passwordHash
            ];

            if($insertdata->execute($values)) {
                // Get the newly created user's ID
                $userId = $datapageConnection->lastInsertId();
                
                // Set all required session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $username;
                $_SESSION['is_admin'] = false;

                header("Location: ../index.php");
                exit();
            } else {
                $errorMessage = "Registration failed. Please try again.";
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
    <title>Register - Carbuy</title>
    <link rel="stylesheet" href="/css/carbuy.css">
</head>
<body class="register-body">
    <div class="register-container">
        <h3>Register to 
            <span class="C">C</span>
            <span class="a">a</span>
            <span class="r">r</span>
            <span class="b">b</span>
            <span class="u">u</span>
            <span class="y">y</span>
        </h3>
        
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        
        
        <form action="" method="POST">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>