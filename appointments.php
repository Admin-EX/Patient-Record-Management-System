<?php
session_start();
require_once 'db_connection.php'; // Database configuration file

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

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
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_name, phone_number, time, date, notes) 
                              VALUES (:patient_name, :phone_number, :time, :date, :notes)");
        
        $stmt->execute([
            ':patient_name' => $_POST['patient_name'],
            ':phone_number' => $_POST['phone_number'],
            ':time' => $_POST['time'],
            ':date' => $_POST['date'],
            ':notes' => $_POST['notes']
        ]);
        
        $_SESSION['success_message'] = "Appointment added successfully!";
        header("Location: appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding appointment: " . $e->getMessage();
    }
}

// Handle deleting an appointment
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);
        
        $_SESSION['success_message'] = "Appointment deleted successfully!";
        header("Location: appointments.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting appointment: " . $e->getMessage();
    }
}

// Retrieve all appointments from database
try {
    $stmt = $pdo->query("SELECT * FROM appointments ORDER BY date, time");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    $_SESSION['error_message'] = "Error retrieving appointments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Inventory</title>
    <link rel="stylesheet" type="text/css" href="assets/css/appointment_modal.css">
    <link rel="stylesheet" type="text/css" href="assets/css/main_style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <div class="container">
    <div class="total-appointments">
        <h1>Appointment Inventory</h1>
        
        <div class="total-appointments1">
            Total Appointments: <?php echo count($appointments); ?>
        </div>
        </div>
        
        <!-- Display success/error messages -->
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

        <button class="add-btn" onclick="openModal()">Add New Appointment</button>

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

        <!-- Add Appointment Modal -->
        <div id="appointmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add New Appointment</h3>
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                </div>
                <form method="POST" class="modal-form">
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
                    
                    <input type="text" name="notes" placeholder="Notes">
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
                    <th>Notes</th>
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
                        <td>
                            <a href="#" class="delete-btn" onclick="showDeleteModal(<?php echo $appointment['id']; ?>); return false;">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Search functionality
        function searchAppointments() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('appointmentsTable'); 
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Start from index 1 to skip table header
                const td = rows[i].getElementsByTagName('td')[0]; // First column (Patient Name)
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    rows[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }
        }

        let deleteIndex = null;

        function openModal() {
            document.getElementById('appointmentModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('appointmentModal').style.display = 'none';
        }

        function showDeleteModal(index) {
            deleteIndex = index;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteIndex = null;
        }

        function confirmDelete() {
            if (deleteIndex !== null) {
                window.location.href = '?delete=' + deleteIndex;
            }
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            var appointmentModal = document.getElementById('appointmentModal');
            var deleteModal = document.getElementById('deleteModal');
            
            if (event.target == appointmentModal) {
                appointmentModal.style.display = 'none';
            }
            
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
                deleteIndex = null;
            }
        }
    </script>
</body>
</html>