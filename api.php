<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/**
 * Send a JSON answer with the right HTTP status code, then stop.
 */
function jsonResponse($httpCode, $success, $message) {
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// 401 in JSON (not a redirect to the login page): the caller is JavaScript
if (!isAuthenticated()) {
    jsonResponse(401, false, 'Authentication required');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, false, 'Method not allowed');
}
if (!isCsrfValid()) {
    jsonResponse(403, false, 'Invalid CSRF token');
}

$db = getDbConnection();
$action = $_POST['action'] ?? '';

$ticketId = filter_var($_POST['ticket_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($ticketId === false || $ticketId === null) {
    jsonResponse(400, false, 'Invalid ticket ID');
}

$stmt = $db->prepare("SELECT id FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
if (!$stmt->fetch()) {
    jsonResponse(404, false, 'Ticket not found');
}

if ($action === 'update_status') {
    // Only the three known statuses are accepted: 0 open, 1 in progress, 2 closed
    $status = filter_var($_POST['status'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2]]);
    if ($status === false || $status === null) {
        jsonResponse(400, false, 'Invalid status');
    }

    // Closing keeps the original resolution date if there is one; reopening clears it
    $stmt = $db->prepare(
        "UPDATE tickets
         SET status = ?, resolved_at = CASE WHEN ? = 2 THEN COALESCE(resolved_at, NOW()) ELSE NULL END
         WHERE id = ?"
    );
    $stmt->execute([$status, $status, $ticketId]);
    jsonResponse(200, true, 'Status updated successfully');
}

if ($action === 'delete_ticket') {
    $stmt = $db->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    jsonResponse(200, true, 'Ticket deleted successfully');
}

jsonResponse(400, false, 'Unknown action');