<?php
session_start();
require 'db_connection.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$step = 1; // Step 1: Enter username, Step 2: Answer security question, Step 3: Reset password
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Find user by username
    if (isset($_POST['find_user'])) {
        $submitted_username = $_POST['username'] ?? '';
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $submitted_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['reset_username'] = $user['username'];
            $_SESSION['reset_question'] = $user['secret_question'];
            $step = 2;
        } else {
            $error = "Username not found.";
        }
    }
    
    // Step 2: Verify security question answer
    if (isset($_POST['verify_answer'])) {
        $submitted_answer = $_POST['answer'] ?? '';
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $_SESSION['reset_username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && strtolower($submitted_answer) === strtolower($user['secret_answer'])) {
            $step = 3;
        } else {
            $error = "Incorrect answer to security question.";
        }
    }
    
    // Step 3: Reset password
    if (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
            $stmt->execute([
                'password' => $hashed_password,
                'username' => $_SESSION['reset_username']
            ]);
            
            $success = "Password reset successfully. You can now login with your new password.";
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_question']);
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" type="text/css" href="assets/css/login_style.css">
    <style>
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .error {
            color: #e74c3c;
            background-color: #fceaea;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #2ecc71;
            background-color: #e8f8f5;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .countdown {
            font-weight: bold;
            color: #3498db;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Password Recovery</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
        <p>Redirecting to login page in <span id="countdown" class="countdown">3</span> seconds...</p>
        <script>
            // Countdown timer and redirect
            let seconds = 3;
            const countdownElement = document.getElementById('countdown');
            
            const countdownTimer = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = 'login.php';
                }
            }, 1000);
        </script>
    <?php else: ?>
        
        <?php if ($step === 1): ?>
            <!-- Step 1: Enter username -->
            <form method="post">
                <div class="form-group">
                    <label for="username">Enter your username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <button type="submit" name="find_user">Continue</button>
            </form>
            
        <?php elseif ($step === 2): ?>
            <!-- Step 2: Answer security question -->
            <form method="post">
                <div class="form-group">
                    <label>Security Question:</label>
                    <p><strong><?php echo htmlspecialchars($_SESSION['reset_question']); ?></strong></p>
                </div>
                <div class="form-group">
                    <label for="answer">Your Answer:</label>
                    <input type="text" id="answer" name="answer" required>
                </div>
                <button type="submit" name="verify_answer">Verify Answer</button>
            </form>
            
        <?php elseif ($step === 3): ?>
            <!-- Step 3: Reset password -->
            <form method="post">
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <p><a href="login.php">Back to Login</a></p>
    <?php endif; ?>
</div>
</body>
</html>