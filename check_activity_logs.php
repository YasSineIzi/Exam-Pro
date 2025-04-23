<?php
require_once 'db.php';
$sql = "SELECT * FROM exam_activity_logs";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Number of records: " . $result->num_rows . "\n\n";
    echo "Activity logs:\n";
    echo "------------------------------------\n";
    
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"] . "\n";
        echo "User ID: " . $row["user_id"] . "\n";
        echo "Exam ID: " . $row["exam_id"] . "\n";
        echo "Activity Type: " . $row["activity_type"] . "\n";
        echo "Details: " . $row["details"] . "\n";
        echo "Created at: " . $row["created_at"] . "\n";
        echo "------------------------------------\n";
    }
} else {
    echo "No activity logs found";
}
?> 