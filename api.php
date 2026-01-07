<?php
require_once 'config.php';
requireAuth();

header('Content-Type: application/json');

$db = getDbConnection();
$response = ['success' => false, 'message' => ''];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_status') {
        $ticket_id = $_POST['ticket_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        // Validate inputs
        if (empty($ticket_id) || $status === '') {
            $response['message'] = 'Invalid parameters';
            echo json_encode($response);
            exit;
        }
        
        // Check if ticket exists
        $stmt = $db->prepare("SELECT id FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        if (!$stmt->fetch()) {
            $response['message'] = 'Ticket not found';
            echo json_encode($response);
            exit;
        }
        
        // Update resolved_at if status is closed
        $resolved_at = null;
        if ($status == 2) {
            $resolved_at = date('Y-m-d H:i:s');
        }
        
        // Update status
        $stmt = $db->prepare("UPDATE tickets SET status = ?, resolved_at = ? WHERE id = ?");
        
        if ($stmt->execute([$status, $resolved_at, $ticket_id])) {
            $response['success'] = true;
            $response['message'] = 'Status updated successfully';
        } else {
            $response['message'] = 'Failed to update status';
        }
    }
    
    elseif ($_POST['action'] === 'delete_ticket') {
        $ticket_id = $_POST['ticket_id'] ?? 0;
        
        if (empty($ticket_id)) {
            $response['message'] = 'Invalid ticket ID';
            echo json_encode($response);
            exit;
        }
        
        // Check if ticket exists
        $stmt = $db->prepare("SELECT id FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        if (!$stmt->fetch()) {
            $response['message'] = 'Ticket not found';
            echo json_encode($response);
            exit;
        }
        
        // Delete ticket
        $stmt = $db->prepare("DELETE FROM tickets WHERE id = ?");
        
        if ($stmt->execute([$ticket_id])) {
            $response['success'] = true;
            $response['message'] = 'Ticket deleted successfully';
        } else {
            $response['message'] = 'Failed to delete ticket';
        }
    }
    
    else {
        $response['message'] = 'Unknown action';
    }
}

echo json_encode($response);