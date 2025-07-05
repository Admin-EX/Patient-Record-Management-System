/**
 * Medicine Inventory Management System
 * JavaScript for handling UI interactions and AJAX requests
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fade out notifications
    fadeOutAlert();
    
    // Initialize modals
    initializeModals();
    
    // Setup form submissions
    setupForms();
    
    // Setup edit/delete buttons
    setupActionButtons();
    
    // Setup search functionality
    setupSearch();
});

/**
 * Fade out alert notifications
 */
function fadeOutAlert() {
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(function() {
            alertBox.style.opacity = '0';
            setTimeout(function() {
                alertBox.style.display = 'none';
            }, 700);
        }, 2500);
    }
}

/**
 * Initialize modal dialogs
 */
function initializeModals() {
    // Add medicine modal
    const addModal = document.getElementById('medicineModal');
    const newMedicineBtn = document.getElementById('newMedicineBtn');
    const closeModal = document.getElementById('closeModal');
    
    // Edit medicine modal
    const editModal = document.getElementById('editMedicineModal');
    const closeEditModal = document.getElementById('closeEditModal');
    
    // Open add medicine modal
    if (newMedicineBtn) {
        newMedicineBtn.addEventListener('click', function() {
            if (addModal) addModal.style.display = 'block';
        });
    }
    
    // Close modals on X button click
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            if (addModal) addModal.style.display = 'none';
        });
    }
    
    if (closeEditModal) {
        closeEditModal.addEventListener('click', function() {
            if (editModal) editModal.style.display = 'none';
        });
    }
    
    // Close modals on outside click
    window.addEventListener('click', function(event) {
        if (event.target === addModal) {
            addModal.style.display = 'none';
        }
        
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
    });
}

/**
 * Setup form submissions
 */
function setupForms() {
    // Add medicine form
    const addForm = document.getElementById('medicineForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(addForm);
            
            // Send AJAX request
            submitFormData(formData, function(success) {
                if (success) {
                    // Close modal and reset form
                    document.getElementById('medicineModal').style.display = 'none';
                    addForm.reset();
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 100);
                }
            });
        });
    }
    
    // Edit medicine form
    const editForm = document.getElementById('editMedicineForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(editForm);
            
            // Send AJAX request
            submitFormData(formData, function(success) {
                if (success) {
                    // Close modal
                    document.getElementById('editMedicineModal').style.display = 'none';
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 100);
                }
            });
        });
    }
}

/**
 * Submit form data via AJAX
 * @param {FormData} formData - The form data to submit
 * @param {Function} callback - Callback function on success
 */
function submitFormData(formData, callback) {
    // Create and configure the XHR object
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Med.php', true);
    
    // Handle the response
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    callback(true);
                } else {
                    alert('Error: ' + (response.error || 'Unknown error occurred'));
                    callback(false);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Error: Could not process server response');
                callback(false);
            }
        } else {
            alert('Error: Server returned status code ' + xhr.status);
            callback(false);
        }
    };
    
    // Handle network errors
    xhr.onerror = function() {
        alert('Network error occurred. Please check your connection.');
        callback(false);
    };
    
    // Send the request
    xhr.send(formData);
}

/**
 * Setup edit and delete buttons
 */
function setupActionButtons() {
    // Edit buttons
    const editButtons = document.querySelectorAll('.editBtn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Get medicine data from the table
            const row = this.closest('tr');
            const cells = row.getElementsByTagName('td');
            
            // Use data attributes for safer data retrieval
            const medicineName = this.getAttribute('data-name');
            
            // Populate the edit form
            document.getElementById('originalMedicineName').value = medicineName;
            document.getElementById('editMedicineName').value = cells[0].textContent.trim();
            document.getElementById('editMedicineNotes').value = cells[1].textContent.trim();
            
            // Format date for the input field
            let expirationDate = cells[2].textContent.trim();
            // If date is not in YYYY-MM-DD format, you might need to parse it
            document.getElementById('editMedicineExpiration').value = expirationDate;
            
            document.getElementById('editMedicinePieces').value = cells[3].textContent.trim();
            
            // Show the edit modal
            document.getElementById('editMedicineModal').style.display = 'block';
        });
    });
    
    // Delete buttons
    const deleteButtons = document.querySelectorAll('.deleteBtn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Use data attribute for safer data retrieval
            const medicineName = this.getAttribute('data-name');
            
            if (confirm('Are you sure you want to delete this medicine?')) {
                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('medicineName', medicineName);
                
                // Send AJAX request
                submitFormData(formData, function(success) {
                    if (success) {
                        // Reload the page after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 100);
                    }
                });
            }
        });
    });
}

/**
 * Setup search functionality
 */
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.medicine-table tbody tr');
            
            rows.forEach(function(row) {
                const medicineName = row.querySelector('td').textContent.toLowerCase();
                if (medicineName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}