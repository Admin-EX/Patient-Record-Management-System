<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'user_auth';
$username = 'root'; // Default username for phpMyAdmin
$password = ''; // Default password for phpMyAdmin

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully!"; // Debugging line
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the submitted username and password
    $submitted_username = $_POST['username'] ?? '';
    $submitted_password = $_POST['password'] ?? '';

    // Fetch user from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $submitted_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($submitted_password, $user['password'])) {
        // If credentials are correct, set session variable to indicate user is authenticated
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user['username'];
        // Redirect user to Home_index.php
        header('Location: Home_index.php');
        exit(); // Ensure script stops execution after redirect
    } else {
        // If credentials are incorrect, display an error message
        $error = "Invalid username or password.";
    }
}

// If the user is already authenticated, redirect to Home_index.php
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: Home_index.php');
    exit(); // Ensure script stops execution after redirect
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="login_style.css">
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <?php if (isset($error)) : ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" value="Login">
    </form>
</div>
</body>
</html>