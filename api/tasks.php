<?php
require_once 'db.php';
require_once 'sms.php';
header('Content-Type: application/json');

// Enable CORS for the frontend domain
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Get all tasks
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query('SELECT * FROM tasks ORDER BY position ASC');
        $tasks = $stmt->fetchAll();
        
        // Fetch notes and photos for each task
        foreach ($tasks as &$task) {
            $stmt = $pdo->prepare('SELECT * FROM notes WHERE task_id = ?');
            $stmt->execute([$task['id']]);
            $notes = $stmt->fetchAll();
            
            $stmt = $pdo->prepare('SELECT * FROM photos WHERE task_id = ?');
            $stmt->execute([$task['id']]);
            $photos = $stmt->fetchAll();
            
            // Format task for frontend
            $task = [
                'id' => $task['id'],
                'roNumber' => $task['ro_number'],
                'title' => $task['title'],
                'description' => $task['description'],
                'priority' => $task['priority'],
                'expanded' => (bool)$task['expanded'],
                'hasIssue' => (bool)$task['has_issue'],
                'inProgress' => (bool)$task['in_progress'],
                'completed' => (bool)$task['completed'],
                'position' => (int)$task['position'],
                'notes' => array_map(function($note) {
                    return [
                        'id' => $note['id'],
                        'content' => $note['content'],
                        'timestamp' => $note['timestamp']
                    ];
                }, $notes),
                'photos' => array_map(function($photo) {
                    return [
                        'id' => $photo['id'],
                        'url' => $photo['file_path'],
                        'thumbnail' => $photo['thumbnail_path']
                    ];
                }, $photos)
            ];
        }
        
        echo json_encode($tasks);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch tasks']);
    }
}

// Create new task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update positions of existing tasks if this is position 1
        if (isset($data['position']) && $data['position'] === 1) {
            $pdo->exec('UPDATE tasks SET position = position + 1');
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO tasks (
                id, ro_number, title, description, priority, 
                expanded, has_issue, in_progress, completed, position
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $taskId = generateUUID();
        $position = isset($data['position']) ? $data['position'] : 1;
        
        $stmt->execute([
            $taskId,
            $data['roNumber'],
            $data['title'],
            $data['description'],
            $data['priority'],
            $data['expanded'] ?? false,
            $data['hasIssue'] ?? false,
            $data['inProgress'] ?? false,
            $data['completed'] ?? false,
            $position
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $newTask = [
            'id' => $taskId,
            'roNumber' => $data['roNumber'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'expanded' => $data['expanded'] ?? false,
            'hasIssue' => $data['hasIssue'] ?? false,
            'inProgress' => $data['inProgress'] ?? false,
            'completed' => $data['completed'] ?? false,
            'position' => $position,
            'notes' => [],
            'photos' => []
        ];
        
        // Send SMS notification for new task
        notifyTaskCreation($newTask);
        
        echo json_encode($newTask);
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create task: ' . $e->getMessage()]);
    }
}

// Update task
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if we're updating multiple tasks (batch update)
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare('
                UPDATE tasks SET 
                    position = ?
                WHERE id = ?
            ');
            
            foreach ($data['tasks'] as $index => $task) {
                $stmt->execute([
                    $index + 1,
                    $task['id']
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Single task update
        if (!isset($data['id'])) {
            throw new Exception('Task ID is required');
        }
        
        $taskId = $data['id'];
        
        $stmt = $pdo->prepare('
            UPDATE tasks SET 
                ro_number = ?,
                title = ?,
                description = ?,
                priority = ?,
                expanded = ?,
                has_issue = ?,
                in_progress = ?,
                completed = ?,
                position = ?
            WHERE id = ?
        ');
        
        $stmt->execute([
            $data['roNumber'],
            $data['title'],
            $data['description'],
            $data['priority'],
            $data['expanded'] ? 1 : 0,
            $data['hasIssue'] ? 1 : 0,
            $data['inProgress'] ? 1 : 0,
            $data['completed'] ? 1 : 0,
            $data['position'],
            $taskId
        ]);
        
        // Get the old task data for comparison (if SMS notifications are needed)
        $oldTask = null;
        if (isset($data['hasIssue']) || isset($data['inProgress']) || isset($data['completed'])) {
            $stmt = $pdo->prepare('SELECT title, ro_number as roNumber, has_issue as hasIssue, in_progress as inProgress, completed FROM tasks WHERE id = ?');
            $stmt->execute([$taskId]);
            $oldTask = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Check if status has changed and send notifications
        if ($oldTask) {
            // Check for issue status change
            if (isset($data['hasIssue']) && $data['hasIssue'] != $oldTask['hasIssue']) {
                notifyTaskStatusChange(
                    ['title' => $data['title'], 'roNumber' => $data['roNumber']], 
                    'Issue Status',
                    $oldTask['hasIssue'] ? 'Has Issue' : 'No Issue',
                    $data['hasIssue'] ? 'Has Issue' : 'No Issue'
                );
            }
            
            // Check for in progress status change
            if (isset($data['inProgress']) && $data['inProgress'] != $oldTask['inProgress']) {
                notifyTaskStatusChange(
                    ['title' => $data['title'], 'roNumber' => $data['roNumber']], 
                    'Progress Status',
                    $oldTask['inProgress'] ? 'In Progress' : 'Not Started',
                    $data['inProgress'] ? 'In Progress' : 'Paused'
                );
            }
            
            // Check for completion status change
            if (isset($data['completed']) && $data['completed'] != $oldTask['completed']) {
                notifyTaskStatusChange(
                    ['title' => $data['title'], 'roNumber' => $data['roNumber']], 
                    'Completion Status',
                    $oldTask['completed'] ? 'Completed' : 'Incomplete',
                    $data['completed'] ? 'Completed' : 'Reopened'
                );
            }
        }
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update task: ' . $e->getMessage()]);
    }
}

// Delete task
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $taskId = $_GET['id'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get task position before deleting
        $stmt = $pdo->prepare('SELECT position FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            throw new Exception('Task not found');
        }
        
        $position = $task['position'];
        
        // Delete associated notes
        $stmt = $pdo->prepare('DELETE FROM notes WHERE task_id = ?');
        $stmt->execute([$taskId]);
        
        // Delete associated photos and their files
        $stmt = $pdo->prepare('SELECT file_path, thumbnail_path FROM photos WHERE task_id = ?');
        $stmt->execute([$taskId]);
        $photos = $stmt->fetchAll();
        
        foreach ($photos as $photo) {
            $fullPath = __DIR__ . '/..' . $photo['file_path'];
            $thumbPath = __DIR__ . '/..' . $photo['thumbnail_path'];
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        
        $stmt = $pdo->prepare('DELETE FROM photos WHERE task_id = ?');
        $stmt->execute([$taskId]);
        
        // Delete the task
        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        
        // Update positions of remaining tasks
        $stmt = $pdo->prepare('UPDATE tasks SET position = position - 1 WHERE position > ?');
        $stmt->execute([$position]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete task: ' . $e->getMessage()]);
    }
}