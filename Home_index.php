<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Philippines

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}
require_once 'db_connection.php'; // Database configuration file

// Create connection using the variables from db_config.php
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Initialize patients array
$_SESSION['patients'] = [];

// Fetch patient data from the database
$sql = "SELECT * FROM patients";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $_SESSION['patients'][] = $row;
    }
}

// Fetch appointments data from database
$appointments = [];
$sql = "SELECT * FROM appointments ORDER BY date, time";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Get today's appointments
$today = date('Y-m-d');
$todaysAppointments = array_filter($appointments, function($appt) use ($today) {
    return $appt['date'] == $today;
});

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
    <link rel="stylesheet" type="text/css" href="assets/css/main_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <style>
        .dashboard-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex: 1;
        }
        #calendar {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .today-appointments {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .appointment-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .appointment-item:last-child {
            border-bottom: none;
        }
        .appointment-time {
            font-weight: bold;
            color: #3498db;
        }
    </style>
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
        
        $(document).ready(function() {
            var appointments = <?php echo json_encode($appointments); ?>;
            var calendarEvents = [];
            
            appointments.forEach(function(appointment) {
                calendarEvents.push({
                    title: appointment.patient_name + ' - ' + appointment.notes,
                    start: appointment.date + 'T' + appointment.time,
                    allDay: false
                });
            });
            
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                defaultView: 'month',
                events: calendarEvents,
                eventClick: function(calEvent, jsEvent, view) {
                    alert('Appointment: ' + calEvent.title + '\nTime: ' + moment(calEvent.start).format('MMMM Do YYYY, h:mm a'));
                }
            });
        });
    </script>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <div class="stats-card">
            <h3>Total Patients</h3>
            <p><?php echo count($_SESSION['patients']); ?></p>
        </div>
        <div class="stats-card">
            <h3>Upcoming Appointments</h3>
            <p><?php echo count($appointments); ?></p>
        </div>
    </div>

    <div class="today-appointments">
        <h3>Today's Appointments (<?php echo date('F j, Y'); ?>)</h3>
        <?php if (count($todaysAppointments) > 0): ?>
            <?php foreach ($todaysAppointments as $appt): ?>
                <div class="appointment-item">
                    <span class="appointment-time">
                        <?php echo date('g:i A', strtotime($appt['time'])); ?>
                    </span>
                    - <?php echo htmlspecialchars($appt['patient_name']); ?>
                    <div style="font-size: 0.9em; color: #666;">
                        <?php echo htmlspecialchars($appt['notes']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No appointments scheduled for today.</p>
        <?php endif; ?>
    </div>
    
    <div id="calendar"></div>
    
    <?php displayPatients(); ?>
</div>
</body>
</html>