<?php
session_start();
require_once 'db_connection.php';
require_once 'navbar.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
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

    <div class="search-box">
        <input type="text" id="searchInput" onkeyup="searchMedicine()" placeholder="Search for medicines...">
    </div>

    <!-- Medicine List Table -->
    <table id="medicineTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Notes</th>
                <th>Expiration</th>
                <th>Pieces (box)</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($medicines) : ?>
                <?php $counter = 1; ?>
                <?php foreach ($medicines as $medicine) : ?>
                    <tr class="medicineRow">
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($medicine['name']) ?></td>
                        <td><?= htmlspecialchars($medicine['notes']) ?></td>
                        <td><?= htmlspecialchars($medicine['expiration']) ?></td>
                        <td>
                            <span class="pieces-value"><?= htmlspecialchars($medicine['pieces']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="5">No medicines found.</td></tr>
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
