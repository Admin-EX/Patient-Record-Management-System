
<?php
session_start();
require_once 'db_connection.php'; // Database configuration and connection

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to convert 24-hour time to 12-hour format
function convertTo12HourFormat($time24) {
    $time = new DateTime($time24);
    return $time->format('h:i A');
}

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y');
}

// Handle adding a new appointment
if (isset($_POST['add_appointment'])) {
    try {
        // Convert the requested time to minutes for comparison
        $requestedTime = strtotime($_POST['time']);
        $requestedDate = $_POST['date'];
        
        // Get all appointments for the selected date
        $checkStmt = $conn->prepare("SELECT time FROM appointments WHERE date = :date");
        $checkStmt->execute([':date' => $requestedDate]);
        $existingAppointments = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $timeConflict = false;
        $nextAvailableTime = null;
        
        // Check if any existing appointment is within 30 minutes of the requested time
        foreach ($existingAppointments as $existingTime) {
            $existingTimeInMinutes = strtotime($existingTime);
            $timeDifference = abs($existingTimeInMinutes - $requestedTime) / 60; // difference in minutes
            
            if ($timeDifference < 30) {
                $timeConflict = true;
                
                // Calculate next available time (30 minutes after the existing appointment)
                $suggestedTime = date('H:i', strtotime($existingTime) + 30 * 60);
                if (!$nextAvailableTime || strtotime($suggestedTime) < strtotime($nextAvailableTime)) {
                    $nextAvailableTime = $suggestedTime;
                }
            }
        }
        
        if ($timeConflict) {
            // Format the suggested time for display
            $formattedSuggestedTime = date('h:i A', strtotime($nextAvailableTime));
            $_SESSION['error_message'] = "Appointments must be at least 30 minutes apart. The next available time is {$formattedSuggestedTime}."; 
        } else {
            // Time slot is available, proceed with insertion
            $stmt = $conn->prepare("INSERT INTO appointments (patient_name, phone_number, time, date, notes) 
                               VALUES (:patient_name, :phone_number, :time, :date, :notes)");
            $stmt->execute([
                ':patient_name' => $_POST['patient_name'],
                ':phone_number' => $_POST['phone_number'],
                ':time' => $_POST['time'],
                ':date' => $_POST['date'],
                ':notes' => $_POST['notes']
            ]);

            $_SESSION['success_message'] = "Appointment added successfully!";
        }
        
        header("Location: appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding appointment: " . $e->getMessage();
    }
}

// Handle archiving an appointment instead of deleting
if (isset($_GET['archive'])) {
    try {
        // First, get the appointment details
        $getStmt = $conn->prepare("SELECT * FROM appointments WHERE id = :id");
        $getStmt->execute([':id' => $_GET['archive']]);
        $appointment = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appointment) {
            // Insert into archived_appointments table
            $archiveStmt = $conn->prepare("INSERT INTO archived_appointments 
                                      (patient_name, phone_number, time, date, notes, archived_by) 
                                      VALUES (:patient_name, :phone_number, :time, :date, :notes, :archived_by)");
            $archiveStmt->execute([
                ':patient_name' => $appointment['patient_name'],
                ':phone_number' => $appointment['phone_number'],
                ':time' => $appointment['time'],
                ':date' => $appointment['date'],
                ':notes' => $appointment['notes'],
                ':archived_by' => 'admin' // You can replace with actual user if you have user sessions
            ]);
            
            // Now delete from the active appointments
            $deleteStmt = $conn->prepare("DELETE FROM appointments WHERE id = :id");
            $deleteStmt->execute([':id' => $_GET['archive']]);
            
            $_SESSION['success_message'] = "Appointment archived successfully!";
        } else {
            $_SESSION['error_message'] = "Appointment not found!";
        }
        
        header("Location: appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error archiving appointment: " . $e->getMessage();
    }
}

// Handle deleting an appointment (keep this for permanent deletion if needed)
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);

        $_SESSION['success_message'] = "Appointment deleted successfully!";
        header("Location: appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting appointment: " . $e->getMessage();
    }
}

// Retrieve all appointments
try {
    $stmt = $conn->query("SELECT * FROM appointments ORDER BY date ASC, time ASC");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get count of archived appointments for the badge
    $archiveCountStmt = $conn->query("SELECT COUNT(*) FROM archived_appointments");
    $archivedCount = $archiveCountStmt->fetchColumn();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving appointments: " . $e->getMessage();
    $appointments = [];
    $archivedCount = 0;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Inventory</title>
    <link rel="stylesheet" href="assets/css/appointment_modal.css">
    <link rel="stylesheet" href="assets/css/main_style.css">
    <style>
        .action-buttons a {
            margin-right: 5px;
        }
        .archive-btn {
            background-color: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 12px;
        }
        .archive-btn:hover {
            background-color: #e68a00;
        }
        .archive-link {
            display: inline-block;
            margin-left: 15px;
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        .archive-link:hover {
            background-color: #5a6268;
        }
        .archive-badge {
            background-color: #ff9800;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="total-appointments">
        <h1>Appointment Inventory</h1>
        <div class="total-appointments1">
            Total Appointments: <?php echo count($appointments); ?>
            <a href="archived_appointments.php" class="archive-link">View Archived <span class="archive-badge"><?php echo $archivedCount; ?></span></a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by patient name..." onkeyup="searchAppointments()">
    </div>


    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to delete this appointment?</p>
            <div class="delete-modal-buttons">
                <button class="delete-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-modal-confirm" onclick="confirmDelete()">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="modal-header">
                <h3>Confirm Archive</h3>
                <span class="close-btn" onclick="closeArchiveModal()">&times;</span>
            </div>
            <p>Are you sure you want to archive this appointment?</p>
            <div class="delete-modal-buttons">
                <button class="delete-modal-cancel" onclick="closeArchiveModal()">Cancel</button>
                <button class="delete-modal-confirm" onclick="confirmArchive()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Appointment</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="appointmentForm">
                <input type="text" name="patient_name" placeholder="Patient Name" required>
                <input type="tel" name="phone_number" placeholder="Phone Number" required pattern="[0-9]{11}" title="Please enter an 11-digit phone number">
                
                <div class="time-date-row">
                    <div style="flex: 1;">
                        <label for="time">Time:</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>
                
                <input type="text" name="notes" placeholder="Notes (optional)">
                <button type="submit" name="add_appointment" class="modal-save-btn">Save Appointment</button>
            </form>
        </div>
    </div>

    <table id="appointmentsTable">
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Phone Number</th>
                <th>Time</th>
                <th>Date</th>
                <th>Diagnosis</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['phone_number']); ?></td>
                    <td><?php echo htmlspecialchars(convertTo12HourFormat($appointment['time'])); ?></td>
                    <td><?php echo htmlspecialchars(formatDate($appointment['date'])); ?></td>
                    <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                    <!-- Find this section in the table body -->
                    <td class="action-buttons">
                        <a href="#" class="archive-btn" onclick="showArchiveModal(<?php echo $appointment['id']; ?>); return false;">Archive</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    let deleteId = null;
    let archiveId = null;

    function searchAppointments() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('appointmentsTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const td = rows[i].getElementsByTagName('td')[0];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                rows[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
            }
        }
    }

    function openModal() {
        document.getElementById('appointmentModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('appointmentModal').style.display = 'none';
    }

    function showDeleteModal(id) {
        deleteId = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteId = null;
    }

    function confirmDelete() {
        if (deleteId !== null) {
            window.location.href = '?delete=' + deleteId;
        }
    }
    
    function showArchiveModal(id) {
        archiveId = id;
        document.getElementById('archiveModal').style.display = 'block';
    }

    function closeArchiveModal() {
        document.getElementById('archiveModal').style.display = 'none';
        archiveId = null;
    }

    function confirmArchive() {
        if (archiveId !== null) {
            window.location.href = '?archive=' + archiveId;
        }
    }

    window.onclick = function(event) {
        const appointmentModal = document.getElementById('appointmentModal');
        const deleteModal = document.getElementById('deleteModal');
        const archiveModal = document.getElementById('archiveModal');
        
        if (event.target == appointmentModal) {
            closeModal();
        }
        
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
        
        if (event.target == archiveModal) {
            closeArchiveModal();
        }
    }
</script>
<script src="Index.js"></script>
</body>
</html>
