-- =========================================================
-- CODE REDEMPTION SYSTEM — DATABASE MIGRATION
-- Run this in phpMyAdmin or MySQL CLI
-- Database: trikefare_db
-- =========================================================

USE trikefare_db;

-- Step 1: Add role column to existing users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- Step 2: Create the codes table
CREATE TABLE IF NOT EXISTS codes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(64) NOT NULL UNIQUE,
    status      ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
    is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
    redeemed_by INT NULL DEFAULT NULL,
    redeemed_at TIMESTAMP NULL DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_redeemed (is_redeemed)
);

-- =========================================================
-- HOW TO SET UP YOUR ADMIN ACCOUNT:
-- Replace 'your@email.com' with your actual registered email.
-- Run this AFTER you have registered your account:
-- =========================================================
-- UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
-- =========================================================
