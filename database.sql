-- Drop and recreate database
DROP DATABASE IF EXISTS gymnastics_academy;
CREATE DATABASE gymnastics_academy;
USE gymnastics_academy;

-- Create gymnasts table
CREATE TABLE IF NOT EXISTS gymnasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    membership_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_no VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    training_program ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    enrollment_date DATE NOT NULL,
    progress_status ENUM('Active', 'On Hold', 'Completed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_membership (membership_id),
    INDEX idx_status (progress_status),
    INDEX idx_program (training_program)
);

-- Create users table with admin fields
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'gymnast') NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    contact_no VARCHAR(20),
    gymnast_id INT NULL,
    admin_position VARCHAR(50),
    department VARCHAR(50),
    employee_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gymnast_id) REFERENCES gymnasts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_gymnast_id (gymnast_id)
);

-- Create deleted_records_log table for tracking deletions
CREATE TABLE IF NOT EXISTS deleted_records_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gymnast_id INT NOT NULL,
    membership_id VARCHAR(20),
    full_name VARCHAR(100),
    email VARCHAR(100),
    contact_no VARCHAR(20),
    date_of_birth DATE,
    training_program VARCHAR(50),
    enrollment_date DATE,
    progress_status VARCHAR(50),
    deleted_by VARCHAR(50),
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_deleted_by (deleted_by),
    INDEX idx_deleted_at (deleted_at)
);

-- Create update_log table for tracking changes
CREATE TABLE IF NOT EXISTS update_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gymnast_id INT NOT NULL,
    field_name VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    updated_by VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gymnast_id (gymnast_id),
    INDEX idx_updated_at (updated_at)
);

-- Create trigger to log updates
DELIMITER $$
CREATE TRIGGER log_gymnast_update
AFTER UPDATE ON gymnasts
FOR EACH ROW
BEGIN
    IF OLD.full_name != NEW.full_name THEN
        INSERT INTO update_log (gymnast_id, field_name, old_value, new_value, updated_by) 
        VALUES (NEW.id, 'full_name', OLD.full_name, NEW.full_name, @updated_by);
    END IF;
    
    IF OLD.email != NEW.email THEN
        INSERT INTO update_log (gymnast_id, field_name, old_value, new_value, updated_by) 
        VALUES (NEW.id, 'email', OLD.email, NEW.email, @updated_by);
    END IF;
    
    IF OLD.contact_no != NEW.contact_no THEN
        INSERT INTO update_log (gymnast_id, field_name, old_value, new_value, updated_by) 
        VALUES (NEW.id, 'contact_no', OLD.contact_no, NEW.contact_no, @updated_by);
    END IF;
    
    IF OLD.training_program != NEW.training_program THEN
        INSERT INTO update_log (gymnast_id, field_name, old_value, new_value, updated_by) 
        VALUES (NEW.id, 'training_program', OLD.training_program, NEW.training_program, @updated_by);
    END IF;
    
    IF OLD.progress_status != NEW.progress_status THEN
        INSERT INTO update_log (gymnast_id, field_name, old_value, new_value, updated_by) 
        VALUES (NEW.id, 'progress_status', OLD.progress_status, NEW.progress_status, @updated_by);
    END IF;
END$$

-- Create trigger to log deletions
CREATE TRIGGER log_gymnast_deletion
BEFORE DELETE ON gymnasts
FOR EACH ROW
BEGIN
    INSERT INTO deleted_records_log (
        gymnast_id, membership_id, full_name, email, contact_no, 
        date_of_birth, training_program, enrollment_date, progress_status, deleted_by
    ) VALUES (
        OLD.id, OLD.membership_id, OLD.full_name, OLD.email, OLD.contact_no,
        OLD.date_of_birth, OLD.training_program, OLD.enrollment_date, OLD.progress_status, @deleted_by
    );
END$$

DELIMITER ;

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, password, role, email, full_name, contact_no, admin_position, department, employee_id) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@gymnastics.com', 'System Administrator', '1234567890', 'Director', 'Management', 'ADMIN001');

-- Insert sample gymnast user (password: gymnast123)
INSERT INTO users (username, password, role, email, full_name, contact_no) VALUES 
('gymnast1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gymnast', 'sarah.j@example.com', 'Sarah Johnson', '1234567890');

-- Insert sample gymnast data
INSERT INTO gymnasts (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status) VALUES
('GYM2024001', 'Sarah Johnson', 'sarah.j@example.com', '1234567890', '2010-05-15', 'Beginner', '2024-01-15', 'Active'),
('GYM2024002', 'Mike Williams', 'mike.w@example.com', '1234567891', '2009-08-22', 'Intermediate', '2024-01-20', 'Active'),
('GYM2024003', 'Emma Davis', 'emma.d@example.com', '1234567892', '2011-03-10', 'Advanced', '2024-02-01', 'On Hold'),
('GYM2024004', 'James Wilson', 'james.w@example.com', '1234567893', '2008-11-30', 'Intermediate', '2024-02-10', 'Active'),
('GYM2024005', 'Lisa Brown', 'lisa.b@example.com', '1234567894', '2012-01-20', 'Beginner', '2024-02-15', 'Active');

-- Update gymnast_id in users table
UPDATE users SET gymnast_id = 1 WHERE username = 'gymnast1';

-- Verify the data
SELECT '=== GYMNASTS TABLE ===' as '';
SELECT * FROM gymnasts;

SELECT '=== USERS TABLE ===' as '';
SELECT id, username, role, email, full_name, gymnast_id FROM users;

SELECT '=== TRIGGERS ===' as '';
SHOW TRIGGERS;