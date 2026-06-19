<?php
$host = '127.0.0.1';
$port = '5432';
$db   = 'db_caseman_mon';
$user = 'appsmon';
$pass = '2026@rsui!!!';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected successfully to database: $db\n\n";
    
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in the database:\n";
    foreach ($tables as $table) {
        echo "- {$table}\n";
        
        // Print columns
        $colStmt = $pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = :table ORDER BY ordinal_position");
        $colStmt->execute(['table' => $table]);
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  * {$col['column_name']} ({$col['data_type']})\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
