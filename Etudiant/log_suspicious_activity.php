<?php
session_start();
require_once '../db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'Not authorized']));
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate data
if (!$data || !isset($data['type']) || !isset($data['examId'])) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid data']));
}

try {
    // Add user ID if not provided
    if (!isset($data['userId'])) {
        $data['userId'] = $_SESSION['user_id'];
    }
    
    // Validate that the user is authorized to take this exam
    $stmt = $pdo->prepare("
        SELECT e.id FROM exams e
        LEFT JOIN users u ON u.id = ?
        WHERE e.id = ? 
        AND (e.class_id = u.class_id OR e.class_id IS NULL)
    ");
    $stmt->execute([$data['userId'], $data['examId']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit(json_encode(['status' => 'error', 'message' => 'Not authorized for this exam']));
    }
    
    // Create log entry in database
    $stmt = $pdo->prepare("
        INSERT INTO exam_activity_logs 
        (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $data['userId'],
        $data['examId'],
        $data['type'],
        json_encode($data),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Return success
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    // Log the error but don't expose details to client
    error_log('Error logging suspicious activity: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
?> 