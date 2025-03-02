<?php
$db_host = 'localhost';
$db_name = 'domainacc_tasks';
$db_user = 'domainacc_tasks';
$db_pass = 'admin123';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Start transaction
    $pdo->beginTransaction();

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id VARCHAR(36) PRIMARY KEY,
            ro_number VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('urgent', 'important', 'no-rush', 'done') NOT NULL,
            expanded BOOLEAN DEFAULT FALSE,
            has_issue BOOLEAN DEFAULT FALSE,
            in_progress BOOLEAN DEFAULT FALSE,
            completed BOOLEAN DEFAULT FALSE,
            position INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS notes (
            id VARCHAR(36) PRIMARY KEY,
            task_id VARCHAR(36) NOT NULL,
            content TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS photos (
            id VARCHAR(36) PRIMARY KEY,
            task_id VARCHAR(36) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            thumbnail_path VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert demo tasks
    $demoTasks = [
        [
            'id' => '1',
            'ro_number' => 'RO-001',
            'title' => 'BMW X5 - Engine Repair',
            'description' => 'Check and repair engine knocking sound. Customer reported issues during high-speed driving.',
            'priority' => 'urgent',
            'expanded' => false,
            'has_issue' => false,
            'in_progress' => false,
            'completed' => false,
            'position' => 1
        ],
        [
            'id' => '2',
            'ro_number' => 'RO-002',
            'title' => 'Honda Civic - Brake Service',
            'description' => 'Replace brake pads and check rotors. Regular maintenance service.',
            'priority' => 'important',
            'expanded' => false,
            'has_issue' => false,
            'in_progress' => true,
            'completed' => false,
            'position' => 2
        ],
        [
            'id' => '3',
            'ro_number' => 'RO-003',
            'title' => 'Tesla Model 3 - Software Update',
            'description' => 'Perform latest software update and system diagnostic.',
            'priority' => 'no-rush',
            'expanded' => false,
            'has_issue' => false,
            'in_progress' => false,
            'completed' => false,
            'position' => 3
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            id, ro_number, title, description, priority,
            expanded, has_issue, in_progress, completed, position
        ) VALUES (
            :id, :ro_number, :title, :description, :priority,
            :expanded, :has_issue, :in_progress, :completed, :position
        )
    ");

    foreach ($demoTasks as $task) {
        $stmt->execute($task);
    }

    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Installation completed successfully!',
        'details' => [
            'tables_created' => ['tasks', 'notes', 'photos'],
            'demo_tasks_added' => count($demoTasks),
            'uploads_directory' => 'Created and configured'
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo)) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Installation failed: ' . $e->getMessage()
    ]);
}