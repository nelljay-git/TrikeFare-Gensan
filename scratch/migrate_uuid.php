<?php
require_once 'api/db.php';
try {
    $pdo->exec("ALTER TABLE user_rides ADD COLUMN IF NOT EXISTS ride_uuid VARCHAR(36) UNIQUE AFTER user_id");
    echo "Migration successful: ride_uuid added.";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
