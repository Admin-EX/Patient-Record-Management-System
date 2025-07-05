<?php
session_start();
require 'db_connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: login.php");
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
            // Check if 'name' is a string and then check if it matches the search
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
                echo "<a href='javascript:void(0)' onclick='openEditModal({$patient['id']})' class='edit-link'>Edit</a> | ";
                echo "<a href='?delete={$patient['id']}" . (isset($_GET['filter']) ? "&filter={$_GET['filter']}" : "") . "' class='delete-link' onclick='return confirm(\"Are you sure you want to delete this patient?\")'>Delete</a>";
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
    // Check if 'search' is an array and handle it
    if (is_array($_GET['search'])) {
        $search = ''; // If it's an array, reset search to empty
    } else {
        // Sanitize and trim the input
        $search = trim($_GET['search']);
    }
}

if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$id]);

        $patients = loadPatientsFromDB($conn, $search, $filter);
        savePatientsToCSV($patients);

        $_SESSION['notification'] = "Patient deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['filter']) ? "?filter={$_GET['filter']}" : ""));
        exit();
    } catch (PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_submit'])) {
    try {
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE patients SET name = ?, age = ?, gender = ?, cellphone = ?, address = ?, bloodtype = ?, diagnosis = ?, date = ? WHERE id = ?");
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

        $patients = loadPatientsFromDB($conn, $search, $filter);
        savePatientsToCSV($patients);

        $_SESSION['notification'] = "Patient updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF'] . (isset($_POST['filter']) ? "?filter={$_POST['filter']}" : ""));
        exit();
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
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
        
        .edit-link, .delete-link {
            display: inline-block;
            padding: 0px 10px;
            margin-top: 8px;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .edit-link {
            background-color: #2196F3;
            color: white;
        }
        
        .delete-link {
            background-color: #f44336;
            color: white;
        }
        
        .alert {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            background-color: #45a049;
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

    <!-- Edit Patient Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Patient Information</h2>
            <form id="editForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" id="edit_id" name="edit_id" value="">
                <?php if (isset($_GET['filter'])): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($_GET['filter']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cellphone">Cellphone:</label>
                    <input type="text" id="cellphone" name="cellphone" required>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="bloodtype">Blood Type:</label>
                    <input type="text" id="bloodtype" name="bloodtype" required>
                </div>
                <div class="form-group">
                    <label for="diagnosis">Diagnosis:</label>
                    <textarea id="diagnosis" name="diagnosis"></textarea>
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <input type="submit" name="edit_submit" value="Save Changes" class="btn-submit">
                </div>
            </form>
        </div>
    </div>
    
    <?php
    // If edit parameter is present, prepare to show modal
    if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $edit_patient = $stmt->fetch(PDO::FETCH_ASSOC);
    endif;
    ?>
</div>

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
    
    // Modal functions
    var modal = document.getElementById("editModal");
    var span = document.getElementsByClassName("close")[0];
    
    // Function to open the modal and load patient data
    function openEditModal(patientId) {
        // AJAX request to get patient data
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "get_patient.php?id=" + patientId, true);
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var patient = JSON.parse(this.responseText);
                
                // Fill the form with patient data
                document.getElementById("edit_id").value = patient.id;
                document.getElementById("name").value = patient.name;
                document.getElementById("age").value = patient.age;
                document.getElementById("gender").value = patient.gender;
                document.getElementById("cellphone").value = patient.cellphone;
                document.getElementById("address").value = patient.address;
                document.getElementById("bloodtype").value = patient.bloodtype;
                document.getElementById("diagnosis").value = patient.diagnosis;
                document.getElementById("date").value = patient.date;
                
                // Show the modal
                modal.style.display = "block";
            }
        };
        xhr.send();
    }
    
    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
    // Check if we need to open the modal on page load (from PHP)
    <?php if (isset($edit_patient)): ?>
    window.onload = function() {
        // Fill the form with patient data
        document.getElementById("edit_id").value = <?php echo json_encode($edit_patient['id']); ?>;
        document.getElementById("name").value = <?php echo json_encode($edit_patient['name']); ?>;
        document.getElementById("age").value = <?php echo json_encode($edit_patient['age']); ?>;
        document.getElementById("gender").value = <?php echo json_encode($edit_patient['gender']); ?>;
        document.getElementById("cellphone").value = <?php echo json_encode($edit_patient['cellphone']); ?>;
        document.getElementById("address").value = <?php echo json_encode($edit_patient['address']); ?>;
        document.getElementById("bloodtype").value = <?php echo json_encode($edit_patient['bloodtype']); ?>;
        document.getElementById("diagnosis").value = <?php echo json_encode($edit_patient['diagnosis']); ?>;
        document.getElementById("date").value = <?php echo json_encode($edit_patient['date']); ?>;
        
        // Show the modal
        modal.style.display = "block";
    };
    <?php endif; ?>
</script>

<script src="Index.js"></script>
</body>
</html>