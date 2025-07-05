<?php

session_start();
require 'db_connection.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Redirect to login page if the user is not authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: login.php");
    exit();
}

// Initialize patients array if it doesn't exist in session
if (!isset($_SESSION['patients'])) {
    $_SESSION['patients'] = [];
}

// Check if form is submitted for adding a new patient
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_submit'])) {
    $name = htmlspecialchars($_POST["name"]);
    $age = htmlspecialchars($_POST["age"]);
    $gender = htmlspecialchars($_POST["gender"]);
    $cellphone = htmlspecialchars($_POST["cellphone"]);
    $address = htmlspecialchars($_POST["address"]);
    $bloodtype = htmlspecialchars($_POST["bloodtype"]);
    $diagnosis = htmlspecialchars($_POST["diagnosis"]);
    $date = htmlspecialchars($_POST["date"]);

    if (!empty($name) && !empty($age) && !empty($gender) && !empty($cellphone) && !empty($address) && !empty($diagnosis) && !empty($bloodtype)) {
        $_SESSION['patients'][] = [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'cellphone' => $cellphone,
            'address' => $address,
            'bloodtype' => $bloodtype,
            'diagnosis' => $diagnosis,
            'date' => $date
        ];

        // Save to CSV
        savePatientsToCSV($_SESSION['patients']);

        // Save to database
        savePatientsToDB($pdo, $name, $age, $gender, $cellphone, $address, $bloodtype, $diagnosis, $date);

        echo "<div class=\"alert success\"><span class=\"closebtn\" onclick=\"this.parentElement.style.display='none';\">&times;</span>Successfully saved</div>";
    }
}

// Function to save patient data to CSV
function savePatientsToCSV($patients) {
    $filename = 'patients.csv';
    $file = fopen($filename, 'w');
    fputcsv($file, ['Name', 'Age', 'Gender', 'Cellphone', 'Address', 'Blood Type', 'Diagnosis', 'Date']);
    foreach ($patients as $patient) {
        fputcsv($file, $patient);
    }
    fclose($file);
}

// Function to save patient data to MySQL database
function savePatientsToDB($pdo, $name, $age, $gender, $cellphone, $address, $bloodtype, $diagnosis, $date) {
    $sql = "INSERT INTO patients (name, age, gender, cellphone, address, bloodtype, diagnosis, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $age, $gender, $cellphone, $address, $bloodtype, $diagnosis, $date]);
}

// Logout functionality
if (isset($_POST['logout'])) {
    unset($_SESSION['authenticated']);
    header("Location: login.php");
    exit();
}
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
<!-- NAV -->
<?php include 'navbar.php'; ?>

<div class="container">
    <h1>Patient Information Form</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="age">Age:</label>
        <input type="number" id="age" name="age" required>

        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select>

        <label for="cellphone">Cellphone:</label>
        <input type="text" id="cellphone" name="cellphone" required>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" required>

<div class="date-bloodtype-container">
    <div>
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>
    </div>
    <div>
        <label for="bloodtype">Blood Type:</label>
        <select id="bloodtype" name="bloodtype" required>
            <option value="">Select Blood Type</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
        </select>
    </div>
</div>


        <label for="diagnosis">Diagnosis:</label>
        <textarea id="diagnosis" name="diagnosis" required></textarea>

        <input type="submit" name="add_submit" value="Submit">
    </form>
</div>

<!-- Link to external JavaScript file -->
<script src="Index.js"></script>
</body>
</html>
