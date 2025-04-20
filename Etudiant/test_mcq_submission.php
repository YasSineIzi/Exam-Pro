<?php
session_start();
require_once '../db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test data
$_SESSION['user_id'] = 1; // Replace with actual student ID
$exam_id = 1; // Replace with actual exam ID
$question_id = 1; // Replace with actual question ID
$option_id = 1; // Replace with actual option ID

try {
    // Start transaction
    $conn->begin_transaction();

    echo "<h2>Testing MCQ Submission</h2>";
    echo "<pre>";

    // 1. Verify student exists
    echo "1. Checking student ID: " . $_SESSION['user_id'] . "\n";

    // 2. Verify exam exists
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
    echo "2. Exam check: " . ($exam ? "Found" : "Not found") . "\n";

    // 3. Verify question exists and is MCQ
    $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ? AND exam_id = ? AND type = 'mcq'");
    $stmt->bind_param("ii", $question_id, $exam_id);
    $stmt->execute();
    $question = $stmt->get_result()->fetch_assoc();
    echo "3. Question check: " . ($question ? "Found MCQ" : "Not found or not MCQ") . "\n";

    // 4. Verify option exists and belongs to question
    $stmt = $conn->prepare("SELECT * FROM question_options WHERE id = ? AND question_id = ?");
    $stmt->bind_param("ii", $option_id, $question_id);
    $stmt->execute();
    $option = $stmt->get_result()->fetch_assoc();
    echo "4. Option check: " . ($option ? "Found" : "Not found") . "\n";

    if ($option) {
        // 5. Try to insert the answer
        $stmt = $conn->prepare("
            INSERT INTO student_answers 
            (student_id, exam_id, question_id, option_id, is_correct) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $is_correct = $option['is_correct'];
        $stmt->bind_param("iiiii", $_SESSION['user_id'], $exam_id, $question_id, $option_id, $is_correct);
        
        $result = $stmt->execute();
        echo "5. Insert result: " . ($result ? "Success" : "Failed") . "\n";
        if (!$result) {
            echo "Error: " . $stmt->error . "\n";
        }
        
        // 6. Verify the insertion
        $stmt = $conn->prepare("
            SELECT * FROM student_answers 
            WHERE student_id = ? AND exam_id = ? AND question_id = ?
        ");
        $stmt->bind_param("iii", $_SESSION['user_id'], $exam_id, $question_id);
        $stmt->execute();
        $answer = $stmt->get_result()->fetch_assoc();
        echo "6. Verification: " . ($answer ? "Answer found in database" : "Answer not found in database") . "\n";
        if ($answer) {
            echo "\nStored answer details:\n";
            print_r($answer);
        }
    }

    // Commit the transaction
    $conn->commit();
    echo "\nTransaction committed successfully\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?> 