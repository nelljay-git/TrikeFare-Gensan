<?php
require_once '../api/db.php';

$tables = ['posts', 'comments', 'replies', 'reactions'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESC $table");
        while ($row = $stmt->fetch()) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
