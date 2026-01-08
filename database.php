<?php
/**
 * Automatic database setup script
 * This will create all tables and insert initial data
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Setup</title>";
echo "<style>
    body { font-family: 'Arial', sans-serif; margin: 40px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #48e5c2; padding-bottom: 10px; }
    .success { color: #48e5c2; font-weight: bold; padding: 10px; background: #e8f8f5; margin: 10px 0; border-radius: 5px; }
    .error { color: #ff4444; font-weight: bold; padding: 10px; background: #ffebee; margin: 10px 0; border-radius: 5px; }
    .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #48e5c2; }
    .btn { display: inline-block; padding: 12px 24px; background: #48e5c2; color: #333; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; }
    .btn:hover { background: #3cd4b1; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🛠️ Database Setup</h1>";

try {
    $db = getDbConnection();
    echo "<p class='success'>✓ Connected to database</p>";
    
    // Drop existing tables
    echo "<h2>Dropping existing tables...</h2>";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DROP TABLE IF EXISTS tickets");
    echo "<p>✓ Dropped tickets table</p>";
    $db->exec("DROP TABLE IF EXISTS categories");
    echo "<p>✓ Dropped categories table</p>";
    $db->exec("DROP TABLE IF EXISTS users");
    echo "<p>✓ Dropped users table</p>";
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create users table
    echo "<h2>Creating tables...</h2>";
    $db->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p class='success'>✓ Created users table</p>";
    
    // Create categories table
    $db->exec("
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p class='success'>✓ Created categories table</p>";
    
    // Create tickets table
    $db->exec("
        CREATE TABLE tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category_id INT NOT NULL,
            priority TINYINT NOT NULL DEFAULT 1 COMMENT '0: low, 1: standard, 2: high',
            status TINYINT NOT NULL DEFAULT 0 COMMENT '0: open, 1: in progress, 2: closed',
            created_by INT NOT NULL,
            assigned_to INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p class='success'>✓ Created tickets table</p>";
    
    // Insert default user
    echo "<h2>Inserting default data...</h2>";
    $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
    $db->exec("
        INSERT INTO users (name, email, password) VALUES 
        ('Admin User', 'admin@bugtracker.com', '$hashed_password')
    ");
    echo "<p class='success'>✓ Inserted default user (admin@bugtracker.com / 123456)</p>";
    
    // Insert categories
    $db->exec("
        INSERT INTO categories (title) VALUES 
        ('Front-end'),
        ('Back-end'),
        ('Infrastructure')
    ");
    echo "<p class='success'>✓ Inserted categories (Front-end, Back-end, Infrastructure)</p>";
    
    // Insert sample tickets
    $db->exec("
        INSERT INTO tickets (title, category_id, priority, status, created_by, assigned_to, created_at, resolved_at) VALUES
        ('Login button not working on mobile', 1, 2, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
        ('API endpoint returns 500 error', 2, 2, 1, 1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),
        ('Server deployment failing', 3, 1, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
        ('CSS layout broken in Safari', 1, 1, 2, 1, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
        ('Database connection timeout', 2, 2, 1, 1, 1, DATE_SUB(NOW(), INTERVAL 6 DAY), NULL),
        ('Navigation menu disappears on scroll', 1, 0, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
        ('Memory leak in background process', 2, 1, 1, 1, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), NULL),
        ('SSL certificate expired', 3, 2, 2, 1, 1, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
        ('Form validation not displaying errors', 1, 1, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
        ('Backup script not running', 3, 0, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), NULL)
    ");
    echo "<p class='success'>✓ Inserted 10 sample tickets</p>";
    
    // Success message
    echo "<div class='info'>";
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p>Your database has been successfully set up with:</p>";
    echo "<ul>";
    echo "<li>1 user account</li>";
    echo "<li>3 categories</li>";
    echo "<li>10 sample tickets</li>";
    echo "</ul>";
    echo "<p><strong>Default Login:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>admin@bugtracker.com</code></li>";
    echo "<li>Password: <code>123456</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<a href='check_database.php' class='btn'>Verify Database</a>";
    echo "<a href='index.php' class='btn'>Go to Application</a>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<div class='info'>";
    echo "<p>If you see this error, please:</p>";
    echo "<ol>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check that the database '" . DB_NAME . "' exists</li>";
    echo "<li>Verify your credentials in config.php</li>";
    echo "</ol>";
    echo "<h3>Create database manually:</h3>";
    echo "<pre>mysql -u root -p
CREATE DATABASE bugtracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;</pre>";
    echo "</div>";
}

echo "</div></body></html>";