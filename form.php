<?php
require_once 'config.php';
requireAuth();

$db = getDbConnection();
$user = getCurrentUser();

$errors = [];
$ticket = null;
$is_edit = false;

// Check if editing existing ticket
if (isset($_GET['id'])) {
    $is_edit = true;
    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        header('Location: dashboard.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    if ($priority === '') {
        $errors[] = "Priority is required";
    }
    
    if ($status === '') {
        $errors[] = "Status is required";
    }
    
    if (empty($assigned_to)) {
        $assigned_to = null;
    }
    
    // Process resolved_at date
    $resolved_at = null;
    if ($status == 2) { // Closed status
        $resolved_at = date('Y-m-d H:i:s');
    }
    
    if (empty($errors)) {
        if ($is_edit) {
            // Update existing ticket
            $sql = "UPDATE tickets SET 
                    title = ?, 
                    category_id = ?, 
                    priority = ?, 
                    status = ?, 
                    assigned_to = ?,
                    resolved_at = ?
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $category_id, $priority, $status, $assigned_to, $resolved_at, $ticket['id']]);
        } else {
            // Create new ticket
            $sql = "INSERT INTO tickets (title, category_id, priority, status, created_by, assigned_to, resolved_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $category_id, $priority, $status, $user['id'], $assigned_to, $resolved_at]);
        }
        
        if ($result) {
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = "Failed to save ticket";
        }
    }
}

// Get categories and users
$categories = $db->query("SELECT * FROM categories ORDER BY title")->fetchAll();
$users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'New'; ?> Ticket - <?php echo SITE_NAME; ?></title>
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
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="form-container">
                <h1><?php echo $is_edit ? 'Edit Ticket' : 'Create New Ticket'; ?></h1>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="ticket-form">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="<?php echo escape($ticket['title'] ?? $_POST['title'] ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($ticket['category_id'] ?? $_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($category['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority *</label>
                            <select id="priority" name="priority" required>
                                <option value="">Select priority</option>
                                <option value="0" <?php echo ($ticket['priority'] ?? $_POST['priority'] ?? '') === '0' ? 'selected' : ''; ?>>Low</option>
                                <option value="1" <?php echo ($ticket['priority'] ?? $_POST['priority'] ?? '1') == '1' ? 'selected' : ''; ?>>Standard</option>
                                <option value="2" <?php echo ($ticket['priority'] ?? $_POST['priority'] ?? '') == '2' ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="">Select status</option>
                                <option value="0" <?php echo ($ticket['status'] ?? $_POST['status'] ?? '0') === '0' || ($ticket['status'] ?? $_POST['status'] ?? '0') == '0' ? 'selected' : ''; ?>>Open</option>
                                <option value="1" <?php echo ($ticket['status'] ?? $_POST['status'] ?? '') == '1' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="2" <?php echo ($ticket['status'] ?? $_POST['status'] ?? '') == '2' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="assigned_to">Assigned To</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" 
                                        <?php echo ($ticket['assigned_to'] ?? $_POST['assigned_to'] ?? '') == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($u['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">Save Ticket</button>
                        <a href="dashboard.php" class="btn btn-secondary btn-large">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>