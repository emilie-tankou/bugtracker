DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data
-- Default user: admin@bugtracker.com / 123456
INSERT INTO users (name, email, password) VALUES 
('Admin User', 'admin@bugtracker.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Password hash for '123456'

-- Insert categories
INSERT INTO categories (title) VALUES 
('Front-end'),
('Back-end'),
('Infrastructure');

-- Insert sample tickets
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
('Backup script not running', 3, 0, 0, 1, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), NULL);