<?php
require_once '../api/db.php';

$queries = [
    "ALTER TABLE posts CHANGE upvotes likes INT DEFAULT 0",
    "ALTER TABLE posts ADD COLUMN dislikes INT DEFAULT 0",
    "ALTER TABLE comments ADD COLUMN likes INT DEFAULT 0",
    "ALTER TABLE comments ADD COLUMN dislikes INT DEFAULT 0",
    "ALTER TABLE replies ADD COLUMN likes INT DEFAULT 0",
    "ALTER TABLE replies ADD COLUMN dislikes INT DEFAULT 0",
    "DROP TABLE IF EXISTS reactions",
    "CREATE TABLE reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_type ENUM('post', 'comment', 'reply') NOT NULL,
        target_id INT NOT NULL,
        reaction_type ENUM('like', 'dislike') NOT NULL,
        user_identifier VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_reaction (target_type, target_id, user_identifier)
    )"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Executed: $sql\n";
    } catch (PDOException $e) {
        echo "Error on: $sql - " . $e->getMessage() . "\n";
    }
}
?>
