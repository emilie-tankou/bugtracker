<?php
require_once 'config.php';
requireAuth();

$db = getDbConnection();
$user = getCurrentUser();

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$query = "SELECT t.*, 
          c.title as category_name,
          u1.name as creator_name,
          u2.name as assignee_name
          FROM tickets t
          LEFT JOIN categories c ON t.category_id = c.id
          LEFT JOIN users u1 ON t.created_by = u1.id
          LEFT JOIN users u2 ON t.assigned_to = u2.id";

$params = [];

switch ($filter) {
    case 'my_tickets':
        $query .= " WHERE t.assigned_to = ?";
        $params[] = $user['id'];
        break;
    case 'frontend':
        $query .= " WHERE c.title = 'Front-end'";
        break;
    case 'backend':
        $query .= " WHERE c.title = 'Back-end'";
        break;
    case 'infrastructure':
        $query .= " WHERE c.title = 'Infrastructure'";
        break;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as closed
    FROM tickets";
$stats = $db->query($stats_query)->fetch();

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY title")->fetchAll();

// Get users for assignment
$users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

// Status labels
$status_labels = ['Open', 'In Progress', 'Closed'];
$priority_labels = ['Low', 'Standard', 'High'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>BugTracker</h2>
            </div>
            <div class="nav-menu">
                <span class="nav-user">Welcome, <?php echo escape($user['name']); ?></span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-card stat-open">
                    <div class="stat-value"><?php echo $stats['open']; ?></div>
                    <div class="stat-label">Open</div>
                </div>
                <div class="stat-card stat-progress">
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card stat-closed">
                    <div class="stat-value"><?php echo $stats['closed']; ?></div>
                    <div class="stat-label">Closed</div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <select id="filter" class="filter-select" onchange="location.href='?filter='+this.value">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Tickets</option>
                        <option value="my_tickets" <?php echo $filter === 'my_tickets' ? 'selected' : ''; ?>>My Tickets</option>
                        <option value="frontend" <?php echo $filter === 'frontend' ? 'selected' : ''; ?>>Category: Front-end</option>
                        <option value="backend" <?php echo $filter === 'backend' ? 'selected' : ''; ?>>Category: Back-end</option>
                        <option value="infrastructure" <?php echo $filter === 'infrastructure' ? 'selected' : ''; ?>>Category: Infrastructure</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <a href="form.php" class="btn btn-primary">New Ticket</a>
                </div>
            </div>

            <!-- Tickets Table -->
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <p>No tickets found. Create your first ticket!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Created</th>
                                <th>Creator</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Resolved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td class="ticket-title"><?php echo escape($ticket['title']); ?></td>
                                    <td><span class="badge badge-category"><?php echo escape($ticket['category_name']); ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($ticket['created_at'])); ?></td>
                                    <td><?php echo escape($ticket['creator_name']); ?></td>
                                    <td>
                                        <select class="status-select status-<?php echo $ticket['status']; ?>" 
                                                onchange="updateStatus(<?php echo $ticket['id']; ?>, this.value)">
                                            <?php foreach ($status_labels as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo $ticket['status'] == $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><span class="badge badge-priority-<?php echo $ticket['priority']; ?>"><?php echo $priority_labels[$ticket['priority']]; ?></span></td>
                                    <td><?php echo $ticket['assignee_name'] ? escape($ticket['assignee_name']) : '-'; ?></td>
                                    <td><?php echo $ticket['resolved_at'] ? date('Y-m-d', strtotime($ticket['resolved_at'])) : '-'; ?></td>
                                    <td class="actions">
                                        <a href="form.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <button onclick="deleteTicket(<?php echo $ticket['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/dashboard.js"></script>
</body>
</html>