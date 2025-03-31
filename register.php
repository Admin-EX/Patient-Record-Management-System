<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'user_auth';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_username) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $new_username]);

            if ($stmt->rowCount() > 0) {
                $error = "Username already taken. Please choose another one.";
            } else {
                // Hash password before storing
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $stmt->execute(['username' => $new_username, 'password' => $hashed_password]);

                // Redirect to login page
                header("Location: login.php?success=1");
                exit();
            }
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="login_style.css">
</head>
<body>
<div class="container">
    <h1>Register</h1>
    <?php if (isset($error)) : ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="new_username">Username:</label>
        <input type="text" id="new_username" name="new_username" required>

        <label for="new_password">Password:</label>
        <input type="password" id="new_password" name="new_password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
