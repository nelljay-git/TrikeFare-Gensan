<?php
require_once 'db.php';

try {
    // 1. Create/Update Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        streak_count INT DEFAULT 0,
        last_commute_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add email and password_hash if they don't exist (migration for existing 'users' table from previous step)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE AFTER username");
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email");
        $pdo->exec("ALTER TABLE users ADD COLUMN streak_count INT DEFAULT 0 AFTER password_hash");
        $pdo->exec("ALTER TABLE users ADD COLUMN last_commute_date DATE DEFAULT NULL AFTER streak_count");
    } catch (PDOException $e) {
        // Likely already exists
    }

    // 2. Create Rides Table for sync
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_rides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        origin VARCHAR(255),
        destination VARCHAR(255),
        fare DECIMAL(10, 2),
        distance_km DECIMAL(8, 3),
        duration INT,
        path_json LONGTEXT,
        ride_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo "Auth system tables initialized successfully.";
} catch (PDOException $e) {
    die("Error initializing tables: " . $e->getMessage());
}
