-- ============================================
-- SFI QUEUING SYSTEM - Database Installation
-- Database: sfi_queuing_db
-- ============================================

CREATE DATABASE IF NOT EXISTS sfi_queuing_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sfi_queuing_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('admin','teller') DEFAULT 'teller',
    assigned_counter INT DEFAULT 1,
    status ENUM('active','inactive') DEFAULT 'active',
    force_password_change TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- LOAN TYPES TABLE
-- ============================================
CREATE TABLE loan_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    prefix VARCHAR(5) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT '',
    category ENUM('transaction','product') NOT NULL DEFAULT 'transaction',
    status ENUM('active','inactive') DEFAULT 'active',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- COUNTERS TABLE
-- ============================================
CREATE TABLE counters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    counter_number INT NOT NULL UNIQUE,
    status ENUM('active','inactive') DEFAULT 'active',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- QUEUE TICKETS TABLE
-- ============================================
CREATE TABLE queue_tickets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(20) NOT NULL,
    queue_date DATE NOT NULL,
    client_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    loan_type_id INT NOT NULL,
    prefix VARCHAR(5) NOT NULL,
    status ENUM('waiting','serving','completed','no_show','cancelled','transferred') DEFAULT 'waiting',
    counter_assigned INT DEFAULT NULL,
    served_by INT DEFAULT NULL,
    called_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id),
    INDEX idx_queue_date(queue_date),
    INDEX idx_status(status),
    INDEX idx_prefix(prefix),
    INDEX idx_created_at(created_at),
    INDEX idx_ticket_date_status(queue_date, status),
    INDEX idx_counter_assigned(counter_assigned)
) ENGINE=InnoDB;

-- ============================================
-- CLIENTS TABLE (Excel/CSV import target)
-- ============================================
CREATE TABLE clients (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    loan_type_id INT DEFAULT NULL,
    loan_status ENUM('pending','approved','released','active','closed') DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT '',
    raw_data LONGTEXT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id) ON DELETE SET NULL,
    INDEX idx_full_name(full_name),
    INDEX idx_created_at(created_at),
    INDEX idx_is_archived(is_archived)
) ENGINE=InnoDB;

-- ============================================
-- OTP CODES TABLE (Website "Check Your Loan" SMS verification)
-- ============================================
CREATE TABLE otp_codes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    contact_number VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose VARCHAR(50) DEFAULT 'loan_lookup',
    expires_at DATETIME NOT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact(contact_number),
    INDEX idx_expires(expires_at)
) ENGINE=InnoDB;

-- ============================================
-- ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE activity_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) DEFAULT 'system',
    action VARCHAR(100) NOT NULL,
    description VARCHAR(500) DEFAULT '',
    ip_address VARCHAR(45) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id(user_id),
    INDEX idx_action(action),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB;

-- ============================================
-- SETTINGS TABLE (Key-Value)
-- ============================================
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA: LOAN TYPES
-- ============================================
INSERT INTO loan_types (name, prefix, description, category) VALUES
    ('Payment',            'PY', 'Payment transactions',           'transaction'),
    ('Release',            'RL', 'Loan release transactions',      'transaction'),
    ('Customer Services',  'CS', 'Customer service and inquiries', 'transaction');

-- ============================================
-- SEED DATA: COUNTERS
-- ============================================
INSERT INTO counters (name, counter_number) VALUES
    ('Counter 1', 1),
    ('Counter 2', 2),
    ('Counter 3', 3),
    ('Counter 4', 4);

-- ============================================
-- SEED DATA: USERS
-- Passwords are hashed with password_hash()
-- DEV-ONLY CREDENTIALS:
--   admin   / admin123
--   teller1 / teller123
--   teller2 / teller123
--   teller3 / teller123
-- ============================================
INSERT INTO users (username, password_hash, full_name, role, assigned_counter, force_password_change) VALUES
    ('admin',   '$2y$10$YourHashWillBeReplacedByPHPScript', 'System Administrator', 'admin',  1, 1),
    ('teller1', '$2y$10$YourHashWillBeReplacedByPHPScript', 'Maria Santos',       'teller', 1, 1),
    ('teller2', '$2y$10$YourHashWillBeReplacedByPHPScript', 'Jose Reyes',         'teller', 2, 1),
    ('teller3', '$2y$10$YourHashWillBeReplacedByPHPScript', 'Ana Cruz',           'teller', 3, 1);

-- ============================================
-- SEED DATA: DEFAULT SETTINGS
-- ============================================
INSERT INTO settings (setting_key, setting_value) VALUES
    ('company_name',        'SFI Queuing System'),
    ('display_title',       'Smart Loan Queue Management System'),
    ('announcement_message','Welcome to SFI. Please proceed to the kiosk to get your queue number.'),
    ('enable_audio',        '1'),
    ('announcement_voice',  'female'),
    ('announcement_speed',  '0.9'),
    ('auto_reset_queue',    '1'),
    ('kiosk_reset_time',    '6'),
    ('session_timeout',     '30'),
    ('max_login_attempts',  '5'),
    ('login_lockout_time',  '15');
