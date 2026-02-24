-- RAYS OF GRACE Junior School Database
-- Knowledge Changing Lives Forever

CREATE DATABASE IF NOT EXISTS raysofgrace_db;
USE raysofgrace_db;

-- ========================================
-- Users Table
-- ========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'parent', 'student', 'admin', 'teacher') DEFAULT 'user',
    family_id VARCHAR(50),
    family_code VARCHAR(50) UNIQUE,
    class VARCHAR(10) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_email (email),
    INDEX idx_family (family_id)
);

-- ========================================
-- Classes Table
-- ========================================
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) UNIQUE NOT NULL,
    display_order INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Insert classes
INSERT INTO classes (name, display_order) VALUES
('P1', 1), ('P2', 2), ('P3', 3), ('P4', 4), ('P5', 5), ('P6', 6), ('P7', 7);

-- ========================================
-- Subjects Table
-- ========================================
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    class_id INT NOT NULL,
    category ENUM('lower', 'upper') NOT NULL,
    display_order INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_subject (class_id, name)
);

-- Insert subjects for P1-P3
INSERT INTO subjects (name, class_id, category, display_order) 
SELECT 'Literacy 1A', id, 'lower', 1 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'Literacy 1B', id, 'lower', 2 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'Mathematics', id, 'lower', 3 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'Reading', id, 'lower', 4 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'Writing', id, 'lower', 5 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'English Language', id, 'lower', 6 FROM classes WHERE name IN ('P1', 'P2', 'P3')
UNION ALL
SELECT 'Religious Education', id, 'lower', 7 FROM classes WHERE name IN ('P1', 'P2', 'P3');

-- Insert subjects for P4-P7
INSERT INTO subjects (name, class_id, category, display_order) 
SELECT 'Kiswahili', id, 'upper', 1 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7')
UNION ALL
SELECT 'English Language', id, 'upper', 2 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7')
UNION ALL
SELECT 'Religious Education', id, 'upper', 3 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7')
UNION ALL
SELECT 'Mathematics', id, 'upper', 4 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7')
UNION ALL
SELECT 'Integrated Science', id, 'upper', 5 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7')
UNION ALL
SELECT 'Social Studies', id, 'upper', 6 FROM classes WHERE name IN ('P4', 'P5', 'P6', 'P7');

-- ========================================
-- Lessons Table
-- ========================================
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class VARCHAR(10) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    topic VARCHAR(200) NOT NULL,
    description TEXT,
    week INT NOT NULL,
    duration VARCHAR(20) DEFAULT '30 min',
    video_url VARCHAR(500),
    video_path VARCHAR(500),
    pdf_url VARCHAR(500),
    pdf_path VARCHAR(500),
    thumbnail VARCHAR(500),
    is_free BOOLEAN DEFAULT FALSE,
    display_order INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_by INT,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_class_subject (class, subject),
    INDEX idx_week (class, week)
);

-- ========================================
-- Quiz Questions Table
-- ========================================
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    explanation TEXT,
    points INT DEFAULT 1,
    display_order INT NOT NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson (lesson_id)
);

-- ========================================
-- Quiz Results Table
-- ========================================
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    score INT NOT NULL,
    total INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    answers TEXT,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_lesson (user_id, lesson_id)
);

-- ========================================
-- Progress Tracking Table
-- ========================================
CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress INT DEFAULT 0,
    last_accessed DATETIME,
    completed_at DATETIME,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_status (user_id, status)
);

-- ========================================
-- Downloads Table
-- ========================================
CREATE TABLE downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    file_type ENUM('video', 'pdf') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    downloaded_at DATETIME,
    expires_at DATETIME,
    last_accessed DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_lesson (user_id, lesson_id)
);

-- ========================================
-- Subscriptions Table
-- ========================================
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan ENUM('monthly', 'termly', 'yearly') NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    auto_renew BOOLEAN DEFAULT TRUE,
    transaction_id VARCHAR(50),
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- ========================================
-- Transactions Table
-- ========================================
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone VARCHAR(20),
    provider ENUM('mtn', 'airtel', 'card') NOT NULL,
    plan VARCHAR(20) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_data TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_user_status (user_id, status)
);

-- ========================================
-- Activity Log Table
-- ========================================
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_action (action)
);

-- ========================================
-- Password Resets Table
-- ========================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
);

-- ========================================
-- Notifications Table
-- ========================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('payment', 'reminder', 'achievement', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
);

-- ========================================
-- Sample Admin User (password: admin123)
-- ========================================
-- Note: You'll need to generate a proper hash
-- For now, we'll create a user and you can reset password later
INSERT INTO users (fullname, email, phone, password, role, status, created_at) VALUES
('School Administrator', 'admin@raysofgrace.ac.ug', '256700000000', 'temp_password_hash', 'admin', 'active', NOW());

-- ========================================
-- Sample Lessons (P.4 Science)
-- ========================================
INSERT INTO lessons (class, subject, topic, description, week, is_free, display_order, status, created_at) VALUES
('P4', 'Integrated Science', 'The Human Body - Skeleton', 'Learn about bones and their functions', 1, TRUE, 1, 'published', NOW()),
('P4', 'Integrated Science', 'The Human Body - Muscles', 'Understanding how muscles work', 1, FALSE, 2, 'published', NOW()),
('P4', 'Integrated Science', 'The Human Body - Digestive System', 'How we digest food', 2, FALSE, 3, 'published', NOW());

-- ========================================
-- Sample Quiz Questions
-- ========================================
INSERT INTO quiz_questions (lesson_id, question, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(1, 'How many bones are in the human body?', '206', '208', '200', '210', 'A', 1),
(1, 'What is the largest bone in the body?', 'Skull', 'Femur', 'Spine', 'Rib cage', 'B', 2);

-- ========================================
-- End of Database Schema
-- ========================================