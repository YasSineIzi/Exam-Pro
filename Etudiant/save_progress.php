<?php
session_start();
require_once '../db.php';

// Security check for authenticated user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_VALIDATE_INT);
    $answers = $_POST['answers'] ?? [];

    if (!$examId || !$startTime) {
        echo json_encode(['success' => false, 'message' => 'Données d\'examen invalides.']);
        exit;
    }

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if progress already exists
        $stmt = $pdo->prepare("SELECT id FROM exam_progress WHERE user_id = :userId AND exam_id = :examId");
        $stmt->execute([
            ':userId' => $_SESSION['user_id'],
            ':examId' => $examId
        ]);
        $existingProgress = $stmt->fetch();

        if ($existingProgress) {
            // Update existing progress
            $progressId = $existingProgress['id'];
            $stmt = $pdo->prepare("UPDATE exam_progress SET answers = :answers, updated_at = NOW() WHERE id = :progressId");
            $stmt->execute([
                ':answers' => json_encode($answers),
                ':progressId' => $progressId
            ]);
        } else {
            // Insert new progress
            $stmt = $pdo->prepare("INSERT INTO exam_progress (user_id, exam_id, start_time, answers, created_at, updated_at) VALUES (:userId, :examId, :startTime, :answers, NOW(), NOW())");
            $stmt->execute([
                ':userId' => $_SESSION['user_id'],
                ':examId' => $examId,
                ':startTime' => $startTime,
                ':answers' => json_encode($answers)
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Progrès sauvegardé avec succès.']);
    } catch (PDOException $e) {
        error_log("Error saving progress: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la sauvegarde du progrès.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}
?>
