<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['authenticated']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    if ($delete_id != $_SESSION['username']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE username = :username");
        $stmt->execute(['username' => $delete_id]);
        header("Location: account_manager.php"); // Refresh after delete
        exit();
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $username = $_POST['username'];
    $role = $_POST['role'];
    
    $stmt = $conn->prepare("UPDATE users SET role = :role WHERE username = :username");
    $stmt->execute(['role' => $role, 'username' => $username]);
    
    $success = "User role updated successfully!";
}

// Handle password update
if (isset($_POST['update_password'])) {
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
        $stmt->execute(['password' => $hashed_password, 'username' => $username]);
        
        $success = "Password updated successfully!";
    } else {
        $error = "Password cannot be empty.";
    }
}

// Fetch users
$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Manager</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background: #3498db;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .logout-btn {
            background: white;
            color: #3498db;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s, color 0.3s;
        }
        .logout-btn:hover {
            background: #2980b9;
            color: white;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background: #3498db;
            color: white;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .error, .success {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .error {
            background: #e74c3c;
            color: white;
        }
        .success {
            background: #2ecc71;
            color: white;
        }
        form {
            display: inline-block;
        }
        select, input[type="password"], input[type="submit"] {
            padding: 6px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background: #2ecc71;
            border: none;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }
        input[type="submit"]:hover {
            background: #27ae60;
        }
        a {
            text-decoration: none;
            color: #e74c3c;
            font-weight: bold;
            transition: color 0.3s;
        }
        a:hover {
            color: #c0392b;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div><strong>Account Manager</strong></div>
        <a class="logout-btn" href="logout.php">Logout</a>
    </div>

    <div class="container">

        <?php if (isset($error)) : ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)) : ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Update Role</th>
                    <th>Change Password</th>
                    <th>Delete Account</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <select name="role">
                                    <option value="doctor" <?php echo $user['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="nurse" <?php echo $user['role'] === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select><br>
                                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                                <input type="submit" name="update_role" value="Update Role">
                            </form>
                        </td>
                        <td>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <input type="password" name="new_password" placeholder="New Password" required><br>
                                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                                <input type="submit" name="update_password" value="Change Password">
                            </form>
                        </td>
                        <td>
                            <?php if ($user['username'] !== $_SESSION['username']) : ?>
                                <a href="?delete_id=<?php echo urlencode($user['username']); ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            <?php else: ?>
                                <span style="color: gray;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</body>
</html>
