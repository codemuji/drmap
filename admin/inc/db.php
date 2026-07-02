<?php
// Database helper (PDO)
// Update these values with your hosting provider's database credentials
defined('DB_HOST') || define('DB_HOST', 'localhost'); // Usually 'localhost' on shared hosting
defined('DB_NAME') || define('DB_NAME', 'DrMap'); // Your database name from hosting provider
defined('DB_USER') || define('DB_USER', 'root'); // Your database username from hosting provider
defined('DB_PASS') || define('DB_PASS', ''); // Your database password from hosting provider

function getPDO()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // Log error to file and show user-friendly message
            error_log("Database connection error: " . $e->getMessage());
            
            // Show detailed error (disable in production)
            echo "<!DOCTYPE html><html><head><title>Database Error</title></head><body>";
            echo "<h2>Database Connection Error</h2>";
            echo "<p>Could not connect to database. Please check:</p>";
            echo "<ul>";
            echo "<li>Database credentials are correct</li>";
            echo "<li>Database server is running</li>";
            echo "<li>Database name exists</li>";
            echo "<li>User has proper permissions</li>";
            echo "</ul>";
            echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "</body></html>";
            exit;
        }
    }
    return $pdo;
}

?>
