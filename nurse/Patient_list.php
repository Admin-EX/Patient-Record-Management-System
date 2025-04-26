<?php
session_start();
require '../db_connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: ../login.php");
    exit();
}

function loadPatientsFromDB($conn, $search = '') {
    $patients = [];
    $sql = "SELECT * FROM patients";
    
    if (!empty($search)) {
        $sql .= " WHERE name LIKE :search OR diagnosis LIKE :search OR cellphone LIKE :search";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $conn->query($sql);
    }
    
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
       
        echo "</li>";
    }
    echo "</ul>";
}

// Handle Search
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}



if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$patients = loadPatientsFromDB($conn, $search);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/main_style.css">
    <style>
        .search-container {
            margin: 20px 0;
        }
        .search-input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="search-container">
        <form method="get" action="">
            <input type="text" name="search" class="search-input" placeholder="Search patients..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-button">Search</button>
            <?php if (!empty($search)): ?>
                <a href="patient_list.php" style="margin-left: 10px;">Clear Search</a>
            <?php endif; ?>
        </form>
    </div>

    <ul>
        <li>Total Patients: <?php echo count($patients); ?></li>
    </ul>

    <?php if (isset($_SESSION['notification'])): ?>
        <div class="alert"><?php echo $_SESSION['notification']; ?></div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <?php displayPatients($patients); ?>

</div>
<script src="Index.js"></script>
</body>
</html>
