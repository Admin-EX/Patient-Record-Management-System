// script.js
function searchPatients() {
    var input, filter, ul, li, txtValue;
    input = document.getElementById('search');
    filter = input.value.toUpperCase();
    ul = document.getElementById("patientList");
    li = ul.getElementsByTagName('li');
    for (var i = 0; i < li.length; i++) {
        txtValue = li[i].textContent || li[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
}

function fadeOutAlert() {
    var alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(function() {
            alertBox.style.opacity = '0';
            setTimeout(function() {
                alertBox.style.display = 'none';
            }, 700); // Adjust the duration of fade-out animation
        }, 2500); // Adjust the duration before the message starts fading out (in milliseconds)
    }
}

// Call the function when the document is loaded
document.addEventListener('DOMContentLoaded', function() {
    fadeOutAlert();
});
const modal = document.getElementById("medicineModal");
    const openModal = document.getElementById("newMedicineBtn");
    const closeModal = document.getElementById("closeModal");

    // Open modal on button click
    openModal.onclick = function () {
        modal.style.display = "block";
    };

    // Close modal on close button click
    closeModal.onclick = function () {
        modal.style.display = "none";
    };

    // Close modal on clicking outside of the modal
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    // Handle form submission via AJAX
    document.getElementById("medicineForm").onsubmit = function (event) {
        event.preventDefault();

        const name = document.getElementById("medicineName").value;
        const pieces = document.getElementById("medicinePieces").value;
        const notes = document.getElementById("medicineNotes").value;
        const expiration = document.getElementById("medicineExpiration").value;

        const formData = new FormData();
        formData.append("medicineName", name);
        formData.append("medicinePieces", pieces);
        formData.append("medicineNotes", notes);
        formData.append("medicineExpiration", expiration);

        // Send the data to the server using AJAX
        fetch('Med.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the modal
                modal.style.display = "none";

                // Reset the form
                document.getElementById("medicineForm").reset();

                // Reload the page to show updated list
                location.reload();
            } else {
                alert('Error saving medicine');
            }
        });
    };
// Handle edit button clicks
document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function () {
            const medicineRow = this.closest('tr');
            const medicineName = medicineRow.querySelector('td').innerText;
            const medicineNotes = medicineRow.querySelector('td:nth-child(2)').innerText;
            const medicineExpiration = medicineRow.querySelector('td:nth-child(3)').innerText;
            const medicinePieces = medicineRow.querySelector('td:nth-child(4)').innerText;

            // Populate the edit modal with the selected medicine's data
            document.getElementById("originalMedicineName").value = medicineName;
            document.getElementById("editMedicineName").value = medicineName;
            document.getElementById("editMedicineNotes").value = medicineNotes;
            document.getElementById("editMedicineExpiration").value = medicineExpiration;
            document.getElementById("editMedicinePieces").value = medicinePieces;

            // Open the edit modal
            editModal.style.display = "block";
        });
    });

    // Handle form submission for editing medicine
    const editModal = document.getElementById("editMedicineModal");
    const closeEditModal = document.getElementById("closeEditModal");

    document.querySelectorAll('.editBtn').forEach(button => {
    button.addEventListener('click', function () {
        const medicineRow = this.closest('tr');
        const medicineName = medicineRow.querySelector('td').innerText;
        const medicineNotes = medicineRow.querySelector('td:nth-child(2)').innerText;
        const medicineExpiration = medicineRow.querySelector('td:nth-child(3)').innerText;
        const medicinePieces = medicineRow.querySelector('td:nth-child(4)').innerText;

        document.getElementById("originalMedicineName").value = medicineName;
        document.getElementById("editMedicineName").value = medicineName;
        document.getElementById("editMedicineNotes").value = medicineNotes;
        document.getElementById("editMedicineExpiration").value = medicineExpiration;
        document.getElementById("editMedicinePieces").value = medicinePieces;

        editModal.style.display = "block";
    });
});

closeEditModal.onclick = function () {
    editModal.style.display = "none";
};

window.onclick = function (event) {
    if (event.target == editModal) {
        editModal.style.display = "none";
    }
};

document.getElementById("editMedicineForm").onsubmit = function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('Med.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            editModal.style.display = "none";
            location.reload();
        } else {
            alert('Error updating medicine');
        }
    });
};

    // Handle delete button clicks
    document.querySelectorAll('.deleteBtn').forEach(button => {
        button.addEventListener('click', function () {
            const medicineRow = this.closest('tr');
            const medicineName = medicineRow.querySelector('td').innerText;

            if (confirm('Are you sure you want to delete this medicine?')) {
                const formData = new FormData();
                formData.append("deleteMedicine", medicineName);

                fetch('Med.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the medicine row from the table
                        medicineRow.remove();
                    } else {
                        alert('Error deleting medicine');
                    }
                });
            }
        });
    });

    // Search functionality
    document.getElementById("searchInput").addEventListener("input", function () {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll(".medicine-table tbody tr");

        rows.forEach(row => {
            const medicineName = row.querySelector("td").innerText.toLowerCase();
            if (medicineName.includes(searchTerm)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });