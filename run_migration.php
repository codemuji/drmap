<?php
// Script to run SQL migrations
require_once __DIR__ . '/admin/inc/db.php';

try {
    $pdo = getPDO();
    echo "✓ Connected to database successfully.\n";
    
    $sqlFile = __DIR__ . '/create_upgrades_schema.sql';
    if (!file_exists($sqlFile)) {
        die("Error: create_upgrades_schema.sql not found.\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute SQL query
    // PDO exec handles multiple queries separated by semicolons if local config allows
    // To be safe, we split by semicolon (naive split but safe for our seeds)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $index => $query) {
        if (empty($query)) continue;
        try {
            $pdo->exec($query);
        } catch (PDOException $qe) {
            echo "Warning at query " . ($index + 1) . ": " . $qe->getMessage() . "\n";
        }
    }
    
    echo "✓ Database migration completed successfully.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please make sure your MySQL database server is running in XAMPP.\n";
}
?>
