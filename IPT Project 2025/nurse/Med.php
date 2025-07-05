<?php
session_start();
require_once '../db_connection.php';
require_once 'navbar.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../login.php");
    exit();
}


// Handle delete medicine
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: Med.php');
    exit;
}

// Handle update medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medicine'])) {
    // Check if this is an AJAX request for pieces update only
    if (isset($_POST['id']) && isset($_POST['pieces']) && count($_POST) == 2) {
        $id = intval($_POST['id']);
        $pieces = intval($_POST['pieces']);
        
        if ($pieces >= 0) {
            $stmt = $conn->prepare("UPDATE medicines SET pieces = ? WHERE id = ?");
            $stmt->execute([$pieces, $id]);
            exit; // Exit for AJAX requests
        }
    } else {
        // This is a full form update
        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $expiration = $_POST['expiration'] ?? '';
        $pieces = $_POST['pieces'] ?? '';

        if (!empty($name) && !empty($expiration) && !empty($pieces)) {
            $stmt = $conn->prepare("UPDATE medicines SET name = ?, notes = ?, expiration = ?, pieces = ? WHERE id = ?");
            $stmt->execute([$name, $notes, $expiration, $pieces, $id]);
            header('Location: Med.php');
            exit;
        } else {
            $error = "Please fill all required fields.";
        }
    }
}

// Handle new medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $name = $_POST['name'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $expiration = $_POST['expiration'] ?? '';
    $pieces = $_POST['pieces'] ?? '';

    if (!empty($name) && !empty($expiration) && !empty($pieces)) {
        $stmt = $conn->prepare("INSERT INTO medicines (name, notes, expiration, pieces) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $notes, $expiration, $pieces]);
        header('Location: Med.php');
        exit;
    } else {
        $error = "Please fill all required fields.";
    }
}

// Fetch medicines  
$stmt = $conn->query("SELECT * FROM medicines ORDER BY id DESC");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<style>
/* Existing container styling */
.container {
    margin-left: 31%; 
    padding: 20px;
    max-width: 50%;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Heading styles */
h1 {
    text-align: center;
    margin-bottom: 20px;
}

/* General form styling */
form {
    margin-bottom: 20px;
}

/* Label styling */
label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

/* General input styles */
input[type="text"],
input[type="number"],
select,
textarea,
input[type="date"] { /* Styling date input */
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

/* "Add Medicine" button styling */
button[type="submit"] {
    padding: 10px 20px;
    background-color: #4caf50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color: #37813b;
}

/* Table styling for medicine list */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Add this to the style section*/
th, td {
    padding: 12px 15px;
    text-align: center; /* Center align all table content */
    border: 1px solid #ddd;
}

.pieces-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.pieces-btn {
    padding: 2px 8px;
    font-size: 14px;
    color: white;
    background-color: #4caf50;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.pieces-btn.minus {
    background-color: #dc3545;
}

.pieces-btn:hover {
    opacity: 0.9;
}

.pieces-value {
    font-size: 14px;
    min-width: 30px;
    text-align: center;
}
thead {
    background-color: #4caf50;
    color: white;
}

/* Hover effect for table rows */
tbody tr:hover {
    background-color: #f1f1f1;
}

/* Action buttons styling (edit, delete) */
.action-buttons {
    display: flex;
    gap: 10px;
}

.edit-btn, .delete-btn {
    padding: 6px 12px;
    font-size: 14px;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.edit-btn {
    background-color: #007bff;
}

.edit-btn:hover {
    background-color: #0056b3;
}

.delete-btn {
    background-color: #dc3545;
}

.delete-btn:hover {
    background-color: #c82333;
}

/* Search Box Styling */
.search-box {
    margin-bottom: 0px;
    text-align: left;
}

.search-box input {
    width: 50%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
    margin-top: 10px;
}
</style>
</head>
<body>

<div class="container">
    <h2>Medicine Manager</h2>

    <?php if (isset($error)) : ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Form to add or update medicine -->
    <form method="POST">
        <?php if (isset($_GET['edit'])) : 
            $edit_id = intval($_GET['edit']);
            $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ?");
            $stmt->execute([$edit_id]);
            $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($medicine) :
        ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($medicine['id']) ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($medicine['name']) ?>" required>
            <input type="text" name="notes" value="<?= htmlspecialchars($medicine['notes']) ?>">
            <input type="date" name="expiration" value="<?= htmlspecialchars($medicine['expiration']) ?>" required>
            <input type="number" name="pieces" value="<?= htmlspecialchars($medicine['pieces']) ?>" required min="1">
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="update_medicine">Update Medicine</button>
                <a href="Med.php" class="cancel-btn" style="padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; cursor: pointer;">Cancel</a>
            </div>
        <?php else : ?>
            <p class="error">Medicine not found.</p>
        <?php endif; else : ?>
            <input type="text" name="name" placeholder="Medicine Name" required>
            <input type="text" name="notes" placeholder="Notes (optional)">
            <input type="date" name="expiration" required>
            <input type="number" name="pieces" placeholder="Pieces" required min="1">
            <button type="submit" name="add_medicine">Add Medicine</button>
        <?php endif; ?>
    </form>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" onkeyup="searchMedicine()" placeholder="Search for medicines...">
    </div>

    <!-- Medicine List Table -->
    <table id="medicineTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Notes</th>
                <th>Expiration</th>
                <th>Pieces (box)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($medicines) : ?>
                <?php foreach ($medicines as $medicine) : ?>
                    <tr class="medicineRow">
                        <td><?= htmlspecialchars($medicine['id']) ?></td>
                        <td><?= htmlspecialchars($medicine['name']) ?></td>
                        <td><?= htmlspecialchars($medicine['notes']) ?></td>
                        <td><?= htmlspecialchars($medicine['expiration']) ?></td>
                        <td>
                            <div class="pieces-control">
                                <button class="pieces-btn minus" onclick="updatePieces(<?= $medicine['id'] ?>, -1)">-</button>
                                <span class="pieces-value"><?= htmlspecialchars($medicine['pieces']) ?></span>
                                <button class="pieces-btn" onclick="updatePieces(<?= $medicine['id'] ?>, 1)">+</button>
                            </div>
                        </td>
                        <td class="action-buttons">
                            <a href="Med.php?edit=<?= htmlspecialchars($medicine['id']) ?>" class="edit-btn">Edit</a>
                            <a href="Med.php?delete=<?= htmlspecialchars($medicine['id']) ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this medicine?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6">No medicines found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// JavaScript for active search
function searchMedicine() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("medicineTable");
    const rows = table.getElementsByClassName("medicineRow");

    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        let found = false;

        for (let j = 0; j < cells.length - 1; j++) { // Exclude the action buttons column
            if (cells[j].innerText.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }

        if (found) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

function updatePieces(id, change) {
    const row = event.target.closest('tr');
    const piecesSpan = row.querySelector('.pieces-value');
    const currentPieces = parseInt(piecesSpan.textContent);
    const newPieces = currentPieces + change;
    
    if (newPieces >= 0) {
        // Send AJAX request to update the database
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'Med.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                piecesSpan.textContent = newPieces;
            }
        };
        xhr.send('update_medicine&id=' + id + '&pieces=' + newPieces);
    }
}
</script>

</body>
</html>
