function updateStatus(ticketId, status) {
    if (!ticketId || status === '') {
        alert('Invalid parameters');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('ticket_id', ticketId);
    formData.append('status', status);
    
    // Send AJAX request
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated data
            location.reload();
        } else {
            alert('Error: ' + data.message);
            // Reload to reset the dropdown
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the ticket');
        location.reload();
    });
}

/**
 * Delete a ticket with confirmation
 * @param {number} ticketId - The ID of the ticket to delete
 */
function deleteTicket(ticketId) {
    if (!ticketId) {
        alert('Invalid ticket ID');
        return;
    }
    
    // Ask for confirmation
    if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'delete_ticket');
    formData.append('ticket_id', ticketId);
    
    // Send AJAX request
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated list
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the ticket');
    });
}

/**
 * Initialize dashboard functionality when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('BugTracker Dashboard loaded');
    
    // Add animation to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});