<?php
require_once '../api/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS app_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        release_date DATE NOT NULL,
        update_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table app_updates created successfully.\n";

    // Seed with initial update
    $stmt = $pdo->prepare("INSERT INTO app_updates (title, release_date, update_type, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        "Enhanced GPS & Smart Fare",
        "2026-04-22",
        "Feature",
        "Improved GPS recovery with exponential backoff, pre-ride dynamic route preview, and smart fare fallback for rides without destinations."
    ]);
    echo "Initial update seeded.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>