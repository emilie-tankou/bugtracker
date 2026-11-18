/**
 * Dashboard behaviour: status update, ticket deletion, stat cards animation.
 * The CSRF token is read from <body data-csrf="..."> and sent with every request.
 */
const csrfToken = document.body.dataset.csrf;

function updateStatus(ticketId, status) {
    if (!ticketId || status === '') {
        alert('Invalid parameters');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('ticket_id', ticketId);
    formData.append('status', status);
    formData.append('csrf_token', csrfToken);

    fetch('api.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
        }
        // Reload to show updated data (and reset the dropdown on error)
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the ticket');
        location.reload();
    });
}

function deleteTicket(ticketId) {
    if (!ticketId) {
        alert('Invalid ticket ID');
        return;
    }
    if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_ticket');
    formData.append('ticket_id', ticketId);
    formData.append('csrf_token', csrfToken);

    fetch('api.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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

document.addEventListener('DOMContentLoaded', function() {
    // Fade-in animation on the stat cards
    document.querySelectorAll('.stat-card').forEach((card, index) => {
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