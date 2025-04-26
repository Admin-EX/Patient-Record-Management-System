<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: login.php");
    exit();
}

function loadPatientsFromDB($pdo) {
    $patients = [];
    $stmt = $pdo->query("SELECT * FROM patients");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $patients[] = $row;
    }
    return $patients;
}

function savePatientsToCSV($patients) {
    $filename = 'patients.csv';
    $file = fopen($filename, 'w');
    fputcsv($file, ['ID', 'Name', 'Age', 'Gender', 'Cellphone', 'Address', 'Blood Type', 'Diagnosis', 'Date']);
    foreach ($patients as $patient) {
        fputcsv($file, $patient);
    }
    fclose($file);
}

function displayPatients($patients) {
    echo "<h2>Patients Information:</h2>";
    echo "<ul>";
    foreach ($patients as $patient) {
        echo "<li>";
        echo "Name: <strong>{$patient['name']}</strong><br>";
        echo "Age: {$patient['age']}<br>";
        echo "Gender: {$patient['gender']}<br>";
        echo "Cellphone: {$patient['cellphone']}<br>";
        echo "Address: {$patient['address']}<br>";
        echo "Date: {$patient['date']}<br>";
        echo isset($patient['bloodtype']) ? "Blood Type: {$patient['bloodtype']}<br>" : "Blood Type: N/A<br>";
        echo "Diagnosis: {$patient['diagnosis']}<br>";
        echo "<a href='?edit={$patient['id']}' class='edit-link'>Edit</a> | <a href='?delete={$patient['id']}' class='delete-link'>Delete</a>";
        echo "</li>";
    }
    echo "</ul>";
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->execute([$id]);

    $patients = loadPatientsFromDB($pdo);
    savePatientsToCSV($patients);

    $_SESSION['notification'] = "Patient deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_submit'])) {
    $id = $_POST['edit_id'];
    $stmt = $pdo->prepare("UPDATE patients SET name = ?, age = ?, gender = ?, cellphone = ?, address = ?, bloodtype = ?, diagnosis = ?, date = ? WHERE id = ?");
    $stmt->execute([
        $_POST['name'],
        $_POST['age'],
        $_POST['gender'],
        $_POST['cellphone'],
        $_POST['address'],
        $_POST['bloodtype'],
        $_POST['diagnosis'],
        $_POST['date'],
        $id
    ]);

    $patients = loadPatientsFromDB($pdo);
    savePatientsToCSV($patients);

    $_SESSION['notification'] = "Patient updated successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$patients = loadPatientsFromDB($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information</title>
    <link rel="stylesheet" type="text/css" href="assets/css/main_style.css">
</head>
<body>
    
<?php include 'navbar.php'; ?>

<div class="container">
    <ul>
        <li>Total Patients: <?php echo count($patients); ?></li>
    </ul>

    <?php if (isset($_SESSION['notification'])): ?>
        <div class="alert"><?php echo $_SESSION['notification']; ?></div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <?php displayPatients($patients); ?>

    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $edit_patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($edit_patient): ?>
            <h2>Edit Patient Information:</h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" name="edit_id" value="<?php echo $id; ?>">
                <label>Name:</label> <input type="text" name="name" value="<?php echo $edit_patient['name']; ?>" required><br>
                <label>Age:</label> <input type="number" name="age" value="<?php echo $edit_patient['age']; ?>" required><br>
                <label>Gender:</label> 
                <select name="gender" required>
                    <option value="male" <?php if ($edit_patient['gender'] === 'male') echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if ($edit_patient['gender'] === 'female') echo 'selected'; ?>>Female</option>
                    <option value="other" <?php if ($edit_patient['gender'] === 'other') echo 'selected'; ?>>Other</option>
                </select><br>
                <label>Cellphone:</label> <input type="text" name="cellphone" value="<?php echo $edit_patient['cellphone']; ?>" required><br>
                <label>Address:</label> <input type="text" name="address" value="<?php echo $edit_patient['address']; ?>" required><br>
                <label>Blood Type:</label> <input type="text" name="bloodtype" value="<?php echo $edit_patient['bloodtype']; ?>" required><br>
                <label>Diagnosis:</label> <textarea name="diagnosis" required><?php echo $edit_patient['diagnosis']; ?></textarea><br>
                <label>Date:</label> <input type="date" name="date" value="<?php echo $edit_patient['date']; ?>" required><br>
                <input type="submit" name="edit_submit" value="Save Changes">
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="Index.js"></script>
</body>
</html>