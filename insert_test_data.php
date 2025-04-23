<?php
require_once 'db.php';
$sql = "INSERT INTO exam_activity_logs (user_id, exam_id, activity_type, details, created_at) 
        VALUES 
        (61, 56, 'copy_attempt', 'Student tried to copy text', NOW()),
        (61, 56, 'tab_switch', 'Student switched tabs 3 times', NOW()),
        (61, 56, 'print_screen_attempt', 'Student tried to take screenshot', NOW())";

if ($conn->query($sql) === TRUE) {
    echo "Test data inserted successfully";
} else {
    echo "Error: " . $conn->error;
}
?> 