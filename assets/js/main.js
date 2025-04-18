// Utility function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Function to show notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Function to confirm actions
function confirmAction(message) {
    return window.confirm(message);
}

// Function to update batch status
async function updateBatchStatus(batchId, newStatus) {
    try {
        const response = await fetch('api/update_batch_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                batch_id: batchId,
                status: newStatus
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('Status updated successfully');
            return true;
        } else {
            showNotification(data.message || 'Failed to update status', 'error');
            return false;
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
        return false;
    }
}

// Function to handle form submissions
function handleFormSubmit(formElement, successCallback) {
    formElement.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(formElement);
        try {
            const response = await fetch(formElement.action, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                showNotification(data.message || 'Success!');
                if (successCallback) successCallback(data);
            } else {
                showNotification(data.message || 'An error occurred', 'error');
            }
        } catch (error) {
            showNotification('An error occurred', 'error');
        }
    });
}

// Initialize interactive elements
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all forms with data-ajax="true"
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        handleFormSubmit(form);
    });
    
    // Initialize date formatting
    document.querySelectorAll('[data-format-date]').forEach(element => {
        const dateStr = element.textContent;
        if (dateStr) {
            element.textContent = formatDate(dateStr);
        }
    });
    
    // Initialize status update buttons
    document.querySelectorAll('[data-update-status]').forEach(button => {
        button.addEventListener('click', async () => {
            const batchId = button.dataset.batchId;
            const newStatus = button.dataset.newStatus;
            
            if (confirmAction('Are you sure you want to update the status?')) {
                const success = await updateBatchStatus(batchId, newStatus);
                if (success) {
                    location.reload();
                }
            }
        });
    });
}); 