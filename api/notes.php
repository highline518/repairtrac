<?php
require_once 'db.php';
header('Content-Type: application/json');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Function to generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Add note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare('
            INSERT INTO notes (id, task_id, content, timestamp)
            VALUES (?, ?, ?, NOW())
        ');
        
        $noteId = generateUUID();
        $stmt->execute([
            $noteId,
            $data['taskId'],
            $data['content']
        ]);
        
        echo json_encode([
            'success' => true,
            'note' => [
                'id' => $noteId,
                'content' => $data['content'],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add note']);
    }
}

// Delete note
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $noteId = $_GET['id'];
        
        $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
        $stmt->execute([$noteId]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete note']);
    }
}