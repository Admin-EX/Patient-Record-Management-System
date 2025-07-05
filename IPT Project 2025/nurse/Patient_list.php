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

function loadPatientsFromDB($conn, $search = '', $filter = 'all') {
    $patients = [];
    $sql = "SELECT * FROM patients";
    $conditions = [];
    $params = [];
    
    // Add search condition if provided
    if (!empty($search)) {
        $conditions[] = "(name LIKE :search OR diagnosis LIKE :search OR cellphone LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Add diagnosis filter condition if specified
    if ($filter === 'with_diagnosis') {
        $conditions[] = "(diagnosis IS NOT NULL AND diagnosis != '')";
    } elseif ($filter === 'without_diagnosis') {
        $conditions[] = "(diagnosis IS NULL OR diagnosis = '')";
    }
    
    // Combine conditions if any exist
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
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

// Function to display patients
function displayPatients($patients, $search = null) {
    if (empty($patients)) {
        echo "<p>No patients found matching your criteria.</p>";
        return;
    }
    
    echo "<h2>Patients Information:</h2>";
    echo "<div class='filter-container'>";
    echo "<div class='search-box'>";
    echo "<input type='text' id='search' placeholder='Search by name, diagnosis, or cellphone...' onkeyup='searchPatients()'>";
    echo "</div>";
    echo "<div class='filter-buttons'>";
    echo "<a href='?filter=all' class='filter-btn " . ((!isset($_GET['filter']) || $_GET['filter'] === 'all') ? 'active' : '') . "'>All Patients</a>";
    echo "<a href='?filter=with_diagnosis' class='filter-btn " . ((isset($_GET['filter']) && $_GET['filter'] === 'with_diagnosis') ? 'active' : '') . "'>With Diagnosis</a>";
    echo "<a href='?filter=without_diagnosis' class='filter-btn " . ((isset($_GET['filter']) && $_GET['filter'] === 'without_diagnosis') ? 'active' : '') . "'>Without Diagnosis</a>";
    echo "</div>";
    echo "</div>";
    
    echo "<ul id='patientList'>";
    
    foreach ($patients as $patient) {
        if (isset($patient['name'], $patient['age'], $patient['gender'], $patient['cellphone'], $patient['address'], $patient['diagnosis'], $patient['date'])) {
            if (is_string($patient['name']) && ($search === null || stripos($patient['name'], $search) !== false)) {
                echo "<li>";
                echo "Name: <strong>{$patient['name']}</strong><br>";
                echo "Age: {$patient['age']}<br>";
                echo "Gender: {$patient['gender']}<br>";
                echo "Cellphone: {$patient['cellphone']}<br>";
                echo "Address: {$patient['address']}<br>";
                echo "Date: {$patient['date']}<br>";
                echo isset($patient['bloodtype']) ? "Blood Type: {$patient['bloodtype']}<br>" : "Blood Type: N/A<br>";
                echo "Diagnosis: " . (!empty($patient['diagnosis']) ? $patient['diagnosis'] : '<span class="no-diagnosis">No diagnosis</span>') . "<br>";
                echo "</li>";
            }
        }
    }
    echo "</ul>";
}

// Handle filters and search
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = '';
if (isset($_GET['search'])) {
    if (is_array($_GET['search'])) {
        $search = '';
    } else {
        $search = trim($_GET['search']);
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$patients = loadPatientsFromDB($conn, $search, $filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information</title>
    <link rel="stylesheet" type="text/css" href="assets/css/main_style.css">
    <style>
        .filter-container {
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .search-box {
            margin-bottom: 10px;
        }
        
        #search {
            padding: 8px;
            width: 100%;
            max-width: 500px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 15px;
            background-color: #f8f8f8;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            background-color: #e8e8e8;
        }
        
        .filter-btn.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        #patientList {
            list-style: none;
            padding: 0;
        }
        
        #patientList li {
            background-color: #f9f9f9;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .no-diagnosis {
            color: #999;
            font-style: italic;
        }
        
        .patient-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        
    </style>
</head>
<body>
    
<?php include 'navbar.php'; ?>

<div class="container">
    <?php
        // Count patients in each category
        $allPatients = loadPatientsFromDB($conn);
        $withDiagnosis = loadPatientsFromDB($conn, '', 'with_diagnosis');
        $withoutDiagnosis = loadPatientsFromDB($conn, '', 'without_diagnosis');
    ?>
    
    <div class="patient-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo count($allPatients); ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo count($withDiagnosis); ?></div>
            <div class="stat-label">With Diagnosis</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo count($withoutDiagnosis); ?></div>
            <div class="stat-label">Without Diagnosis</div>
        </div>
    </div>

    <?php if (isset($_SESSION['notification'])): ?>
        <div class="alert"><?php echo $_SESSION['notification']; ?></div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <?php displayPatients($patients, $search); ?>



<script>
    function searchPatients() {
        var input = document.getElementById('search');
        var filter = input.value.toUpperCase();
        var ul = document.getElementById("patientList");
        var li = ul.getElementsByTagName('li');
        for (var i = 0; i < li.length; i++) {
            var txtValue = li[i].textContent || li[i].innerText;
            li[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
</script>

<script src="Index.js"></script>
</body>
</html>