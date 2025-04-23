<?php
// Script to verify and fix the activity logging system

// Include the database connection
require_once 'db.php';

// Check if the exam_activity_logs table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'exam_activity_logs'")->num_rows > 0;

echo "<h1>Activity Logging System Check</h1>";

if (!$table_exists) {
    echo "<p>The exam_activity_logs table does not exist. Creating it now...</p>";

    // Create the table
    $sql = "CREATE TABLE exam_activity_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        exam_id INT(11) NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        details TEXT,
        ip_address VARCHAR(50),
        user_agent TEXT,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_exam_idx (user_id, exam_id),
        KEY activity_type_idx (activity_type)
    )";

    if ($conn->query($sql)) {
        echo "<p style='color:green'>Table created successfully!</p>";
    } else {
        echo "<p style='color:red'>Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>The exam_activity_logs table exists. Checking structure...</p>";

    // Check if all required columns exist
    $result = $conn->query("DESCRIBE exam_activity_logs");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    $missing_columns = [];
    $required_columns = [
        'id',
        'user_id',
        'exam_id',
        'activity_type',
        'details',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    foreach ($required_columns as $column) {
        if (!isset($columns[$column])) {
            $missing_columns[] = $column;
        }
    }

    if (empty($missing_columns)) {
        echo "<p style='color:green'>All required columns exist in the table.</p>";
    } else {
        echo "<p style='color:orange'>Missing columns: " . implode(", ", $missing_columns) . "</p>";

        // Add missing columns
        foreach ($missing_columns as $column) {
            $sql = "";
            switch ($column) {
                case 'id':
                    $sql = "ALTER TABLE exam_activity_logs ADD id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
                    break;
                case 'user_id':
                    $sql = "ALTER TABLE exam_activity_logs ADD user_id INT(11) NOT NULL";
                    break;
                case 'exam_id':
                    $sql = "ALTER TABLE exam_activity_logs ADD exam_id INT(11) NOT NULL";
                    break;
                case 'activity_type':
                    $sql = "ALTER TABLE exam_activity_logs ADD activity_type VARCHAR(50) NOT NULL";
                    break;
                case 'details':
                    $sql = "ALTER TABLE exam_activity_logs ADD details TEXT";
                    break;
                case 'ip_address':
                    $sql = "ALTER TABLE exam_activity_logs ADD ip_address VARCHAR(50)";
                    break;
                case 'user_agent':
                    $sql = "ALTER TABLE exam_activity_logs ADD user_agent TEXT";
                    break;
                case 'created_at':
                    $sql = "ALTER TABLE exam_activity_logs ADD created_at DATETIME NOT NULL";
                    break;
            }

            if ($sql && $conn->query($sql)) {
                echo "<p style='color:green'>Added column $column successfully!</p>";
            } else if ($sql) {
                echo "<p style='color:red'>Error adding column $column: " . $conn->error . "</p>";
            }
        }
    }

    // Check if the required indexes exist
    $result = $conn->query("SHOW INDEX FROM exam_activity_logs");
    $indexes = [];
    while ($row = $result->fetch_assoc()) {
        $indexes[$row['Key_name']][] = $row['Column_name'];
    }

    $missing_indexes = [];
    $required_indexes = [
        'user_exam_idx' => ['user_id', 'exam_id'],
        'activity_type_idx' => ['activity_type']
    ];

    foreach ($required_indexes as $idx_name => $columns) {
        if (!isset($indexes[$idx_name])) {
            $missing_indexes[$idx_name] = $columns;
        }
    }

    if (empty($missing_indexes)) {
        echo "<p style='color:green'>All required indexes exist in the table.</p>";
    } else {
        echo "<p style='color:orange'>Missing indexes: " . implode(", ", array_keys($missing_indexes)) . "</p>";

        // Add missing indexes
        foreach ($missing_indexes as $idx_name => $columns) {
            $sql = "ALTER TABLE exam_activity_logs ADD INDEX " . $idx_name . " (" . implode(", ", $columns) . ")";

            if ($conn->query($sql)) {
                echo "<p style='color:green'>Added index $idx_name successfully!</p>";
            } else {
                echo "<p style='color:red'>Error adding index $idx_name: " . $conn->error . "</p>";
            }
        }
    }
}

// Check the log_suspicious_activity.php file
$log_file_path = 'Etudiant/log_suspicious_activity.php';
if (file_exists($log_file_path)) {
    echo "<p style='color:green'>The log_suspicious_activity.php file exists.</p>";

    // Check if it contains the expected functionality
    $file_contents = file_get_contents($log_file_path);
    if (strpos($file_contents, 'INSERT INTO exam_activity_logs') !== false) {
        echo "<p style='color:green'>The log file contains the INSERT functionality.</p>";
    } else {
        echo "<p style='color:red'>The log file does not contain the INSERT functionality!</p>";
    }
} else {
    echo "<p style='color:red'>The log_suspicious_activity.php file does not exist!</p>";

    // Create the file with proper functionality
    $log_file_content = '<?php
session_start();
require_once "../db.php";

// Verify user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit(json_encode(["status" => "error", "message" => "Not authorized"]));
}

// Get JSON data from request
$data = json_decode(file_get_contents("php://input"), true);

// Validate data
if (!$data || !isset($data["type"]) || !isset($data["examId"])) {
    http_response_code(400);
    exit(json_encode(["status" => "error", "message" => "Invalid data"]));
}

try {
    // Add user ID if not provided
    if (!isset($data["userId"])) {
        $data["userId"] = $_SESSION["user_id"];
    }
    
    // Create log entry in database
    $stmt = $conn->prepare("
        INSERT INTO exam_activity_logs 
        (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $details = json_encode($data);
    $stmt->bind_param("iissss", 
        $data["userId"],
        $data["examId"],
        $data["type"],
        $details,
        $_SERVER["REMOTE_ADDR"],
        $_SERVER["HTTP_USER_AGENT"]
    );
    
    $stmt->execute();
    
    // Return success
    http_response_code(200);
    echo json_encode(["status" => "success"]);
    
} catch (Exception $e) {
    // Log the error but don\'t expose details to client
    error_log("Error logging suspicious activity: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error"]);
}
?>';

    // Create the directory if it doesn't exist
    if (!is_dir('Etudiant')) {
        mkdir('Etudiant', 0755, true);
    }

    if (file_put_contents($log_file_path, $log_file_content)) {
        echo "<p style='color:green'>Created the log_suspicious_activity.php file successfully!</p>";
    } else {
        echo "<p style='color:red'>Failed to create the log_suspicious_activity.php file!</p>";
    }
}

// Insert a test activity to verify the system is working
echo "<h2>Testing Activity Logging</h2>";

// Only run this if there's at least one exam and one user
$result = $conn->query("SELECT id FROM exams LIMIT 1");
$exam = $result->fetch_assoc();

$result = $conn->query("SELECT id FROM users WHERE role = 'student' LIMIT 1");
$student = $result->fetch_assoc();

if ($exam && $student) {
    echo "<p>Found exam ID: {$exam['id']} and student ID: {$student['id']}</p>";

    // Insert test activity
    $stmt = $conn->prepare("
        INSERT INTO exam_activity_logs 
        (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $activity_type = 'copy_attempt';
    $details = json_encode(['test' => true, 'source' => 'fix_activity_logging.php']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt->bind_param(
        "iissss",
        $student['id'],
        $exam['id'],
        $activity_type,
        $details,
        $ip,
        $user_agent
    );

    if ($stmt->execute()) {
        echo "<p style='color:green'>Test activity logged successfully!</p>";

        // Verify it was inserted
        $result = $conn->query("
            SELECT * FROM exam_activity_logs 
            WHERE user_id = {$student['id']} 
            AND exam_id = {$exam['id']} 
            AND activity_type = 'copy_attempt'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        if ($result->num_rows > 0) {
            $log = $result->fetch_assoc();
            echo "<p style='color:green'>Verified the test activity was logged (ID: {$log['id']}, time: {$log['created_at']}).</p>";
        } else {
            echo "<p style='color:red'>Failed to verify the test activity!</p>";
        }
    } else {
        echo "<p style='color:red'>Failed to log test activity: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red'>No exam or student found in the database. Cannot test activity logging.</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The activity logging system has been checked and fixed. Here are the key points:</p>";
echo "<ul>";
echo "<li>Table exists: " . ($table_exists ? "Yes" : "No, but created now") . "</li>";

// Count activities 
$result = $conn->query("SELECT COUNT(*) as count FROM exam_activity_logs");
$count = $result->fetch_assoc()['count'];
echo "<li>Total activities logged: $count</li>";

// Count copy attempts
$result = $conn->query("SELECT COUNT(*) as count FROM exam_activity_logs WHERE activity_type = 'copy_attempt'");
$copy_count = $result->fetch_assoc()['count'];
echo "<li>Copy attempts logged: $copy_count</li>";

// Count exit attempts
$result = $conn->query("SELECT COUNT(*) as count FROM exam_activity_logs WHERE activity_type = 'exit_fullscreen' OR activity_type = 'attempted_page_exit'");
$exit_count = $result->fetch_assoc()['count'];
echo "<li>Exit attempts logged: $exit_count</li>";

echo "</ul>";

echo "<p>If you're having issues with activity logging, please check the following:</p>";
echo "<ol>";
echo "<li>Make sure anti_cheating.js is loaded properly in the exam page</li>";
echo "<li>Verify that the AntiCheatingSystem is initialized with the correct parameters</li>";
echo "<li>Check network requests in the browser's developer tools to ensure activities are being sent to the server</li>";
echo "<li>Make sure the log_suspicious_activity.php file is accessible and has the correct permissions</li>";
echo "</ol>";

echo "<p><a href='Etudiant/test_activity_logging.php' target='_blank'>Click here to open the Activity Testing Tool</a></p>";
?>