CREATE DATABASE IF NOT EXISTS trikefare_db;
USE trikefare_db;

CREATE TABLE IF NOT EXISTS community_fares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    fare DECIMAL(10, 2) NOT NULL,
    transport_type VARCHAR(50) NOT NULL,
    time_tag VARCHAR(50) NOT NULL,
    rating INT DEFAULT 0,
    distance_km DECIMAL(8, 3) DEFAULT NULL,
    origin_lat DECIMAL(10, 7) DEFAULT NULL,
    origin_lng DECIMAL(10, 7) DEFAULT NULL,
    dest_lat DECIMAL(10, 7) DEFAULT NULL,
    dest_lng DECIMAL(10, 7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_route (origin(100), destination(100)),
    INDEX idx_transport (transport_type),
    INDEX idx_distance (distance_km),
    INDEX idx_coords (origin_lat, origin_lng, dest_lat, dest_lng)
);

-- Migration for existing tables: run these if the table already exists
-- ALTER TABLE community_fares ADD COLUMN distance_km DECIMAL(8, 3) DEFAULT NULL AFTER rating;
-- ALTER TABLE community_fares ADD COLUMN origin_lat DECIMAL(10, 7) DEFAULT NULL AFTER distance_km;
-- ALTER TABLE community_fares ADD COLUMN origin_lng DECIMAL(10, 7) DEFAULT NULL AFTER origin_lat;
-- ALTER TABLE community_fares ADD COLUMN dest_lat DECIMAL(10, 7) DEFAULT NULL AFTER origin_lng;
-- ALTER TABLE community_fares ADD COLUMN dest_lng DECIMAL(10, 7) DEFAULT NULL AFTER dest_lat;
-- ALTER TABLE community_fares ADD COLUMN note VARCHAR(100) DEFAULT NULL AFTER dest_lng;
-- ALTER TABLE community_fares ADD INDEX idx_distance (distance_km);
-- ALTER TABLE community_fares ADD INDEX idx_coords (origin_lat, origin_lng, dest_lat, dest_lng);

CREATE TABLE IF NOT EXISTS fare_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fare_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    vote_type ENUM('upvote','downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_vote (fare_id, username),
    FOREIGN KEY (fare_id) REFERENCES community_fares(id) ON DELETE CASCADE
);

-- =========================================================
-- API SECURITY & RATE LIMITING
-- =========================================================

-- Create table for storing API keys (for server-to-server)
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create table for rate limiting API keys and Sessions
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL, -- Either api_key or session_token
    endpoint VARCHAR(100) NOT NULL,
    request_count INT DEFAULT 1,
    reset_time INT NOT NULL,
    UNIQUE KEY unique_id_endpoint (identifier, endpoint)
);

-- =========================================================
-- APP UPDATES (CHANGELOG)
-- =========================================================

CREATE TABLE IF NOT EXISTS app_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    release_date DATE NOT NULL,
    update_type VARCHAR(50) NOT NULL, -- 'Feature', 'Bug Fix', 'UI Improvement'
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
