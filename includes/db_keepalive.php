<?php
// includes/db_keepalive.php
// Keep database connection alive for long-running scripts

function keepDatabaseAlive($pdo) {
    try {
        // Simple query to keep connection active
        $pdo->query("SELECT 1")->fetch();
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 2006 || strpos($e->getMessage(), 'gone away') !== false) {
            // Connection lost, need to reconnect
            return false;
        }
        throw $e;
    }
}

// Use this in long-running scripts or before critical operations
function ensureConnection($pdo) {
    if (!keepDatabaseAlive($pdo)) {
        // Force recreation of connection
        global $dsn, $DB_USER, $DB_PASS, $options;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            error_log("Database reconnected successfully");
        } catch (Exception $e) {
            error_log("Failed to reconnect: " . $e->getMessage());
            throw new Exception("Database connection lost");
        }
    }
    return $pdo;
}
?>