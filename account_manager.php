<?php
session_start();
require_once 'db_connection.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

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

// Register new user
if (isset($_POST['register_user'])) {
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? '';
    $secret_question = $_POST['secret_question'] ?? '';
    $secret_answer = $_POST['secret_answer'] ?? '';

    if (!empty($new_username) && !empty($new_password) && !empty($confirm_password) && !empty($role) && !empty($secret_question) && !empty($secret_answer)) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $new_username]);

            if ($stmt->rowCount() > 0) {
                $error = "Username already taken.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, secret_question, secret_answer) VALUES (:username, :password, :role, :secret_question, :secret_answer)");
                $stmt->execute([
                    'username' => $new_username,
                    'password' => $hashed_password,
                    'role' => $role,
                    'secret_question' => $secret_question,
                    'secret_answer' => $secret_answer
                ]);
                $success = "User registered successfully!";
            }
        }
    } else {
        $error = "All fields are required!";
    }
}

// Delete user
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    if ($delete_id != $_SESSION['username']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE username = :username");
        $stmt->execute(['username' => $delete_id]);
        header("Location: account_manager.php");
        exit();
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Update role
if (isset($_POST['update_role'])) {
    $stmt = $conn->prepare("UPDATE users SET role = :role WHERE username = :username");
    $stmt->execute(['role' => $_POST['role'], 'username' => $_POST['username']]);
    $success = "User role updated.";
}

// Update password
if (isset($_POST['update_password'])) {
    if (!empty($_POST['new_password'])) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
        $stmt->execute(['password' => $hashed, 'username' => $_POST['username']]);
        $success = "Password updated.";
    } else {
        $error = "Password cannot be empty.";
    }
}

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
            background: #f1f4f8;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background: #3498db;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2980b9;
        }
        .top-bar a {
            background: white;
            color: #3498db;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .tabs {
            display: flex;
            justify-content: flex-start;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .tab-button {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px 5px 0 0;
            padding: 12px 20px;
            margin-right: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .tab-button:hover {
            background: #e6e6e6;
        }
        .tab-button.active {
            background: #ffffff;
            color: #3498db;
            border-bottom: 2px solid white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #ffffff;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 16px;
        }
        form {
            display: inline-block;
            margin: 0 10px;
        }
        input, select {
            padding: 8px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 100%;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .message {
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error { background: #e74c3c; color: white; }
        .success { background: #2ecc71; color: white; }
        #userSearch {
            padding: 10px;
            width: 300px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .delete-link {
            padding: 6px 12px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        .delete-link:hover {
            background-color: #c82333;
        }
        #userSearch {
            padding: 10px;
            width: 300px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
    </style>
</head>
<body>

<div class="top-bar">
    <div><strong>Account Manager</strong></div>
    <a href="?logout=true">Logout</a>
</div>

<div class="container">
    <?php if (isset($error)) echo "<div class='message error'>$error</div>"; ?>
    <?php if (isset($success)) echo "<div class='message success'>$success</div>"; ?>

    <div class="tabs">
        <button class="tab-button active" onclick="switchTab('manage')">Manage Users</button>
        <button class="tab-button" onclick="switchTab('register')">Register New User</button>
    </div>

    <div id="manage" class="tab-content active">
        <div style="margin-bottom: 20px; text-align: right;">
            <input type="text" id="userSearch" placeholder="Search by username or role...">
        </div>

        <table id="userTable">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Update Role</th>
                    <th>Change Password</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                        <td>
                            <form method="post" style="display: flex; gap: 10px; align-items: center; justify-content: center;">
                                <input type="hidden" name="username" value="<?= $user['username'] ?>">
                                <select name="role" style="width: auto; margin: 0;">
                                    <option value="doctor" <?= $user['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                                    <option value="nurse" <?= $user['role'] === 'nurse' ? 'selected' : '' ?>>Nurse</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <input type="submit" name="update_role" value="Update" style="width: auto; margin: 0;">
                            </form>
                        </td>
                        <td>
                            <form method="post" style="display: flex; gap: 10px; align-items: center; justify-content: center;">
                                <input type="hidden" name="username" value="<?= $user['username'] ?>">
                                <input type="password" name="new_password" placeholder="New Password" style="width: auto; margin: 0;">
                                <input type="submit" name="update_password" value="Update" style="width: auto; margin: 0;">
                            </form>
                        </td>
                        <td>
                            <?php if ($user['username'] !== $_SESSION['username']) : ?>
                                <a href="?delete_id=<?= urlencode($user['username']) ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php else: ?>
                                (You)
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="register" class="tab-content">
        <form method="post">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="new_username" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label>Secret Question:</label>
                <select name="secret_question" required>
                    <option value="Where did you grow up?">Where did you grow up?</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What was your first pet's name?">What was your first pet's name?</option>
                    <option value="What is the name of your elementary school?">What is the name of your elementary school?</option>
                    <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                </select>
            </div>
            <div class="form-group">
                <label>Secret Answer:</label>
                <input type="text" name="secret_answer" required>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role" required>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <input type="submit" name="register_user" value="Register User">
        </form>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelector(`.tab-button[onclick="switchTab('${tabId}')"]`).classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    // Live search filter
    document.getElementById('userSearch').addEventListener('input', function () {
        const search = this.value.toLowerCase();
        const rows = document.querySelectorAll('#userTable tbody tr');

        rows.forEach(row => {
            const username = row.children[0].textContent.toLowerCase();
            const role = row.children[1].textContent.toLowerCase();
            row.style.display = username.includes(search) || role.includes(search) ? '' : 'none';
        });
    });
</script>

</body>
</html>
