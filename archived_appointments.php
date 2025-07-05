<?php
session_start();
require_once 'db_connection.php'; // Database configuration and connection

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

// Handle permanent deletion of archived appointment
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM archived_appointments WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);

        $_SESSION['success_message'] = "Archived appointment permanently deleted!";
        header("Location: archived_appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting archived appointment: " . $e->getMessage();
    }
}

// Handle restoring an archived appointment
if (isset($_GET['restore'])) {
    try {
        // First, get the archived appointment details
        $getStmt = $conn->prepare("SELECT * FROM archived_appointments WHERE id = :id");
        $getStmt->execute([':id' => $_GET['restore']]);
        $archivedAppointment = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archivedAppointment) {
            // Insert back into active appointments table
            $restoreStmt = $conn->prepare("INSERT INTO appointments 
                                      (patient_name, phone_number, time, date, notes) 
                                      VALUES (:patient_name, :phone_number, :time, :date, :notes)");
            $restoreStmt->execute([
                ':patient_name' => $archivedAppointment['patient_name'],
                ':phone_number' => $archivedAppointment['phone_number'],
                ':time' => $archivedAppointment['time'],
                ':date' => $archivedAppointment['date'],
                ':notes' => $archivedAppointment['notes']
            ]);
            
            // Now delete from the archived appointments
            $deleteStmt = $conn->prepare("DELETE FROM archived_appointments WHERE id = :id");
            $deleteStmt->execute([':id' => $_GET['restore']]);
            
            $_SESSION['success_message'] = "Appointment restored successfully!";
        } else {
            $_SESSION['error_message'] = "Archived appointment not found!";
        }
        
        header("Location: archived_appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error restoring appointment: " . $e->getMessage();
    }
}

// Retrieve all archived appointments
try {
    $stmt = $conn->query("SELECT * FROM archived_appointments ORDER BY archived_at DESC");
    $archivedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving archived appointments: " . $e->getMessage();
    $archivedAppointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Appointments</title>
    <link rel="stylesheet" href="assets/css/appointment_modal.css">
    <link rel="stylesheet" href="assets/css/main_style.css">
    <style>
        .action-buttons a {
            margin-right: 8px;
            display: inline-block;
            min-width: 70px;
            text-align: center;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .restore-btn {
            background-color: #28a745;
            color: white;
        }
        .restore-btn:hover {
            background-color: #218838;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .back-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="total-appointments">
        <h1>Archived Appointments</h1>
        <div class="total-appointments1">
            Total Archived: <?php echo count($archivedAppointments); ?>
        </div>
    </div>
    
    <a href="appointments.php" class="back-link">← Back to Active Appointments</a>

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
                <h3>Confirm Permanent Deletion</h3>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to permanently delete this archived appointment? This action cannot be undone.</p>
            <div class="delete-modal-buttons">
                <button class="delete-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-modal-confirm" onclick="confirmDelete()">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="modal-header">
                <h3>Confirm Restore</h3>
                <span class="close-btn" onclick="closeRestoreModal()">&times;</span>
            </div>
            <p>Are you sure you want to restore this appointment to the active list?</p>
            <div class="delete-modal-buttons">
                <button class="delete-modal-cancel" onclick="closeRestoreModal()">Cancel</button>
                <button class="delete-modal-confirm" style="background-color: #28a745;" onclick="confirmRestore()">Restore</button>
            </div>
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
                <th>Archived</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($archivedAppointments as $appointment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['phone_number']); ?></td>
                    <td><?php echo htmlspecialchars(convertTo12HourFormat($appointment['time'])); ?></td>
                    <td><?php echo htmlspecialchars(formatDate($appointment['date'])); ?></td>
                    <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                    <td class="archived-at"><?php echo date('M d, Y h:i A', strtotime($appointment['archived_at'])); ?></td>
                    <td class="action-buttons">
                        <a href="#" class="restore-btn" onclick="showRestoreModal(<?php echo $appointment['id']; ?>); return false;">Restore</a>
                        <a href="#" class="delete-btn" onclick="showDeleteModal(<?php echo $appointment['id']; ?>); return false;">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    let deleteId = null;
    let restoreId = null;

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
    
    function showRestoreModal(id) {
        restoreId = id;
        document.getElementById('restoreModal').style.display = 'block';
    }

    function closeRestoreModal() {
        document.getElementById('restoreModal').style.display = 'none';
        restoreId = null;
    }

    function confirmRestore() {
        if (restoreId !== null) {
            window.location.href = '?restore=' + restoreId;
        }
    }

    window.onclick = function(event) {
        const deleteModal = document.getElementById('deleteModal');
        const restoreModal = document.getElementById('restoreModal');
        
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
        
        if (event.target == restoreModal) {
            closeRestoreModal();
        }
    }
</script>
<script src="Index.js"></script>
</body>
</html>