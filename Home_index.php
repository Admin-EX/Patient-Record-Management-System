<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost"; // Change if necessary
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = 'user_auth'; // Change to your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Initialize patients array
$_SESSION['patients'] = [];

// Fetch patient data from the database
$sql = "SELECT * FROM patients"; // Ensure table name is correct
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $_SESSION['patients'][] = $row;
    }
}

// Function to display patients
function displayPatients($search = null) {
    if (!isset($_SESSION['patients']) || empty($_SESSION['patients'])) {
        echo "<p>No patient information available.</p>";
        return;
    }

    echo "<h2>Patients Information:</h2>";
    echo "<input type='text' id='search' placeholder='Search by name...' onkeyup='searchPatients()'>";
    echo "<ul id='patientList'>";

    foreach ($_SESSION['patients'] as $patient) {
        if (isset($patient['name'], $patient['age'], $patient['gender'], $patient['cellphone'], $patient['address'], $patient['diagnosis'], $patient['date'])) {
            if ($search === null || stripos($patient['name'], $search) !== false) {
                echo "<li>";
                echo "Name: <strong>{$patient['name']}</strong><br>";
                echo "Age: {$patient['age']}, Gender: {$patient['gender']}<br>";
                echo "Cellphone: {$patient['cellphone']}<br>";
                echo "Address: {$patient['address']}<br>";
                echo "Date: {$patient['date']}<br>";
                echo "Blood Type: {$patient['bloodtype']}<br>";
                echo "Diagnosis: {$patient['diagnosis']}<br>";
                echo "</li>";
            }
        }
    }
    echo "</ul>";
}

// Logout functionality
if (isset($_POST['logout'])) {
    unset($_SESSION['authenticated']);
    session_destroy();
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information</title>
    <link rel="stylesheet" type="text/css" href="main_style.css">
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
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="dashboard">
        <h2>Dashboard</h2>
        <ul>
            <li>Total Patients: <?php echo count($_SESSION['patients']); ?></li>
        </ul>
    </div>
    <?php displayPatients(); ?>
</div>
</body>
</html>
