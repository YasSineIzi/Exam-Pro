<?php
// Start the session to handle user login
session_start();

// Include database connection
$dsn = 'mysql:host=localhost;dbname=exampro';
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
);

try {
    $pdo = new PDO($dsn, 'root', '', $options);  // Adjust username/password as needed
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Fetch the POST data from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);

// Exam data
$examTitle = $data['examTitle'];
$examDescription = $data['examDescription'];
$examDuration = $data['examDuration'];
$courseId = $data['courseId'];
$userId = $_SESSION['user_id'];  // Get the logged-in user ID

// Start a transaction to ensure atomicity
try {
    $pdo->beginTransaction();

    // Insert exam data into the 'exams' table
    $stmt = $pdo->prepare("INSERT INTO exams (name, course_id, duration, title, description, user_id, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'draft')");
    $stmt->execute([$examTitle, $courseId, $examDuration, $examTitle, $examDescription, $userId]);

    // Get the last inserted exam ID
    $examId = $pdo->lastInsertId();

    // Insert each question for this exam
    if (isset($data['questions']) && is_array($data['questions'])) {
        foreach ($data['questions'] as $question) {
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, type) 
                                    VALUES (?, ?, ?)");
            $stmt->execute([$examId, $question['title'], $question['type']]);

            // Get the last inserted question ID
            $questionId = $pdo->lastInsertId();

            // Save question details based on type
            if ($question['type'] === 'mcq' && isset($question['details']['options'])) {
                foreach ($question['details']['options'] as $option) {
                    $stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) 
                                            VALUES (?, ?, ?)");
                    $stmt->execute([$questionId, $option['option'], $option['correct']]);
                }
            } elseif ($question['type'] === 'short' && isset($question['details']['answer'])) {
                // Save the short answer (if any)
                // (No separate table for short answers in your schema, adjust if needed)
            } elseif ($question['type'] === 'open' && isset($question['details']['correctionGuide'])) {
                // Save the open question correction guide (if needed)
                // (No separate table for open answers in your schema, adjust if needed)
            }
        }
    }

    // Commit the transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Exam and questions saved successfully']);

} catch (Exception $e) {
    // Rollback the transaction in case of error
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
