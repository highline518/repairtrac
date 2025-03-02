
<?php
require_once 'db.php';

try {
    // Check if the table already exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'sms_recipients'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create sms_recipients table
        $pdo->exec("
            CREATE TABLE sms_recipients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                notify_new_tasks BOOLEAN DEFAULT TRUE,
                notify_status_changes BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        echo "SMS recipients table created successfully!\n";
    } else {
        echo "SMS recipients table already exists.\n";
    }
    
    echo "SMS setup completed successfully!\n";
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
