<?php
require_once 'config.php';
requireAuth();

$db = getDbConnection();
$user = getCurrentUser();

$errors = [];
$ticket = null;
$is_edit = false;

// Check if editing an existing ticket
if (isset($_GET['id'])) {
    $is_edit = true;
    $ticket_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($ticket_id !== false) {
        $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
    }

    if (!$ticket) {
        header('Location: dashboard.php');
        exit;
    }
}

// Reference data for the selects (also used to validate what the browser sends)
$categories = $db->query("SELECT * FROM categories ORDER BY title")->fetchAll();
$users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
$category_ids = array_column($categories, 'id');
$user_ids = array_column($users, 'id');

// Values shown in the form: what was just posted, otherwise the saved ticket, otherwise defaults
$form = [
    'title'       => $_POST['title']       ?? $ticket['title']       ?? '',
    'category_id' => $_POST['category_id'] ?? $ticket['category_id'] ?? '',
    'priority'    => $_POST['priority']    ?? $ticket['priority']    ?? '1',
    'status'      => $_POST['status']      ?? $ticket['status']      ?? '0',
    'assigned_to' => $_POST['assigned_to'] ?? $ticket['assigned_to'] ?? '',
];

/** True when two form values are the same (compared as strings: "0" must match 0). */
function isSelected($current, $value) {
    return (string) $current === (string) $value;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isCsrfValid()) {
        $errors[] = "Your session has expired, please try again";
    }

    $title = trim($form['title']);
    if ($title === '') {
        $errors[] = "Title is required";
    } elseif (mb_strlen($title) > 255) {
        $errors[] = "Title must be 255 characters or fewer";
    }

    $category_id = filter_var($form['category_id'], FILTER_VALIDATE_INT);
    if ($category_id === false || !in_array($category_id, $category_ids)) {
        $errors[] = "Please choose a valid category";
    }

    $priority = filter_var($form['priority'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2]]);
    if ($priority === false) {
        $errors[] = "Please choose a valid priority";
    }

    $status = filter_var($form['status'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2]]);
    if ($status === false) {
        $errors[] = "Please choose a valid status";
    }

    $assigned_to = null;
    if ($form['assigned_to'] !== '') {
        $assigned_to = filter_var($form['assigned_to'], FILTER_VALIDATE_INT);
        if ($assigned_to === false || !in_array($assigned_to, $user_ids)) {
            $errors[] = "Please choose a valid assignee";
        }
    }

    if (empty($errors)) {
        // A closed ticket keeps its original resolution date; any other status clears it
        $resolved_at = null;
        if ($status === 2) {
            $resolved_at = ($is_edit && !empty($ticket['resolved_at'])) ? $ticket['resolved_at'] : date('Y-m-d H:i:s');
        }

        if ($is_edit) {
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
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            maxlength="255"
                            value="<?php echo escape($form['title']); ?>"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id']; ?>"
                                        <?php echo isSelected($form['category_id'], $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo escape($category['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority *</label>
                            <select id="priority" name="priority" required>
                                <option value="0" <?php echo isSelected($form['priority'], 0) ? 'selected' : ''; ?>>Low</option>
                                <option value="1" <?php echo isSelected($form['priority'], 1) ? 'selected' : ''; ?>>Standard</option>
                                <option value="2" <?php echo isSelected($form['priority'], 2) ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="0" <?php echo isSelected($form['status'], 0) ? 'selected' : ''; ?>>Open</option>
                                <option value="1" <?php echo isSelected($form['status'], 1) ? 'selected' : ''; ?>>In Progress</option>
                                <option value="2" <?php echo isSelected($form['status'], 2) ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="assigned_to">Assigned To</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo (int) $u['id']; ?>"
                                        <?php echo isSelected($form['assigned_to'], $u['id']) ? 'selected' : ''; ?>>
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