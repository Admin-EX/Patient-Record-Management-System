<?php
session_start();
require '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['medicineName'])) {
    $medicineName = $_POST['medicineName'];
    $medicinePieces = $_POST['medicinePieces'];
    $medicineNotes = $_POST['medicineNotes'];
    $medicineExpiration = $_POST['medicineExpiration'];

    $csvFile = 'medicines.csv';
    if (($handle = fopen($csvFile, 'a')) !== false) {
        fputcsv($handle, [$medicineName, $medicineNotes, $medicineExpiration, $medicinePieces]);
        fclose($handle);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO medicines (name, notes, expiration, pieces) VALUES (?, ?, ?, ?)");
        $stmt->execute([$medicineName, $medicineNotes, $medicineExpiration, $medicinePieces]);
        $_SESSION['notification'] = "Medicine added successfully!";
        $response = ['success' => true];
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['originalMedicineName'])) {
    $originalName = $_POST['originalMedicineName'];
    $newName = $_POST['editMedicineName'];
    $newPieces = $_POST['editMedicinePieces'];
    $newNotes = $_POST['editMedicineNotes'];
    $newExpiration = $_POST['editMedicineExpiration'];

    $csvFile = 'medicines.csv';
    $medicines = [];
    if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            if ($data[0] == $originalName) {
                $data = [$newName, $newNotes, $newExpiration, $newPieces];
            }
            $medicines[] = $data;
        }
        fclose($handle);
    }
    if (($handle = fopen($csvFile, 'w')) !== false) {
        foreach ($medicines as $medicine) {
            fputcsv($handle, $medicine);
        }
        fclose($handle);
    }

    try {
        $stmt = $pdo->prepare("UPDATE medicines SET name = ?, notes = ?, expiration = ?, pieces = ? WHERE name = ?");
        $stmt->execute([$newName, $newNotes, $newExpiration, $newPieces, $originalName]);
        $_SESSION['notification'] = "Medicine updated successfully!";
        $response = ['success' => true];
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteMedicine'])) {
    $medicineName = $_POST['deleteMedicine'];

    $csvFile = 'medicines.csv';
    $medicines = [];
    if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            if ($data[0] !== $medicineName) {
                $medicines[] = $data;
            }
        }
        fclose($handle);
    }
    if (($handle = fopen($csvFile, 'w')) !== false) {
        foreach ($medicines as $medicine) {
            fputcsv($handle, $medicine);
        }
        fclose($handle);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE name = ?");
        $stmt->execute([$medicineName]);
        $_SESSION['notification'] = "Medicine deleted successfully!";
        $response = ['success' => true];
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

$medicines = [];
$csvFile = 'medicines.csv';
if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        $medicines[] = $data;
    }
    fclose($handle);
}
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/main_style.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/med.css">
</head>
<body>

<?php include 'navbar.php'; ?>


<div class="container">
    <?php if (isset($_SESSION['notification'])): ?>
        <div class="alert"><?php echo $_SESSION['notification']; ?></div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <div class="dashboard">
        <h2>Medicine Inventory</h2>
        <ul>
            <li>Total Medicines: <?php echo count($medicines); ?></li>
        </ul>
    </div>
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by medicine name...">
    </div>
    <div class="add-medicine">
        <button id="newMedicineBtn">Add New Medicine</button>
    </div>
    <table class="medicine-table">
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Notes</th>
                <th>Expiration Date</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicines as $medicine): ?>
            <tr>
                <td><?php echo isset($medicine[0]) ? $medicine[0] : ''; ?></td>
                <td><?php echo isset($medicine[1]) ? $medicine[1] : ''; ?></td>
                <td><?php echo isset($medicine[2]) ? $medicine[2] : ''; ?></td>
                <td><?php echo isset($medicine[3]) ? $medicine[3] : ''; ?></td>
                <td>
                    <button class="editBtn">Edit</button>
                    <button class="deleteBtn">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="medicineModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Add New Medicine</h2>
        <form id="medicineForm">
            <label for="medicineName">Medicine Name:</label>
            <input type="text" id="medicineName" name="medicineName" required>
            <label for="medicineNotes">Notes:</label>
            <textarea id="medicineNotes" name="medicineNotes" rows="4"></textarea>
            <label for="medicineExpiration">Expiration Date:</label>
            <input type="date" id="medicineExpiration" name="medicineExpiration" required>
            <label for="medicinePieces">Stock:</label>
            <input type="number" id="medicinePieces" name="medicinePieces" required>
            <button type="submit">Save Medicine</button>
        </form>
    </div>
</div>
<div id="editMedicineModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Edit Medicine</h2>
        <form id="editMedicineForm">
            <input type="hidden" id="originalMedicineName" name="originalMedicineName">
            <label for="editMedicineName">Medicine Name:</label>
            <input type="text" id="editMedicineName" name="editMedicineName" required>
            <label for="editMedicineNotes">Notes:</label>
            <textarea id="editMedicineNotes" name="editMedicineNotes" rows="4"></textarea>
            <label for="editMedicineExpiration">Expiration Date:</label>
            <input type="date" id="editMedicineExpiration" name="editMedicineExpiration" required>
            <label for="editMedicinePieces">Stock:</label>
            <input type="number" id="editMedicinePieces" name="editMedicinePieces" required>
            <button type="submit">Update Medicine</button>
        </form>
    </div>
</div>
<script src="../Index.js"></script>
</body>
</html>