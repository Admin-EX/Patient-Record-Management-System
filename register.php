<?php
session_start();
require_once 'db_connection.php';

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
    $role = $_POST['role'] ?? ''; // 'doctor' or 'nurse'

    if (!empty($new_username) && !empty($new_password) && !empty($confirm_password) && !empty($role)) {
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

                // Insert new user with role
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
                $stmt->execute([
                    'username' => $new_username, 
                    'password' => $hashed_password,
                    'role' => $role
                ]);

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
    <link rel="stylesheet" type="text/css" href="assets/css/login_style.css">
    <style>
        .role-selection {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        .role-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .role-btn:hover {
            border-color: #3498db;
        }
        .role-btn.selected {
            border-color: #3498db;
            background-color: #3498db;
            color: white;
        }
        .role-input {
            display: none;
        }
    </style>
    <script>
        function selectRole(role) {
            // Update visual selection
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Update hidden input value
            document.getElementById('selected_role').value = role;
        }
    </script>
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

        <label>Account Type:</label>
        <div class="role-selection">
            <div class="role-btn" onclick="selectRole('doctor')">
                Doctor
            </div>
            <div class="role-btn" onclick="selectRole('nurse')">
                Nurse
            </div>
        </div>
        <input type="hidden" id="selected_role" name="role" required>

        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>