<?php
session_start();
require_once '../db.php';

// Vérifier si l'utilisateur est connecté et a le rôle approprié (admin ou teacher)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
//     die("Accès refusé. Vous devez être un administrateur ou un enseignant pour accéder à cette page.");
// }

// Récupérer l'ID de l'examen et l'ID de l'étudiant depuis l'URL
if (!isset($_GET['exam_id']) || !isset($_GET['student_id'])) {
    die("ID de l'examen ou de l'étudiant manquant.");
}
$exam_id = $_GET['exam_id'];
$student_id = $_GET['student_id'];

// Récupérer les informations de l'examen
try {
    $stmt = $conn->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();

    if (!$exam) {
        die("Examen non trouvé.");
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer les questions et les réponses corrigées de l'étudiant
$questions = [];
try {
    $stmt = $conn->prepare("
        SELECT q.id AS question_id, q.question_title, q.type, q.points, sa.id AS answer_id, sa.answer_text, sa.option_id, sa.is_correct, sa.points_attributed
        FROM questions q
        LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_id = ?
        WHERE q.exam_id = ?
    ");
    $stmt->bind_param("ii", $student_id, $exam_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer le résultat global de l'étudiant
try {
    $stmt = $conn->prepare("SELECT score, status FROM results WHERE exam_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $exam_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam_result = $result->fetch_assoc();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de l'examen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .correct-answer {
            color: green;
            font-weight: bold;
        }
        .incorrect-answer {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Résultats de l'examen : <?= htmlspecialchars($exam['title']) ?></h1>
        <h2>Réponses de l'étudiant (ID : <?= htmlspecialchars($student_id) ?>)</h2>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">La correction a été enregistrée avec succès.</div>
        <?php endif; ?>

        <!-- Afficher le résultat global -->
        <?php if ($exam_result): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Résultat global</h5>
                    <p class="card-text">
                        <strong>Score total :</strong> <?= htmlspecialchars($exam_result['score']) ?><br>
                        <strong>Statut :</strong> <span class="<?= $exam_result['status'] === 'pass' ? 'correct-answer' : 'incorrect-answer' ?>"><?= htmlspecialchars($exam_result['status']) ?></span>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Aucun résultat global trouvé pour cet étudiant.</div>
        <?php endif; ?>

        <!-- Afficher les questions et réponses -->
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $question): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Question : <?= htmlspecialchars($question['question_title']) ?></h5>
                        <p class="card-text">
                            <strong>Points :</strong> <?= htmlspecialchars($question['points']) ?><br>
                            <?php if ($question['type'] === 'mcq'): ?>
                                <?php
                                // Récupérer l'option choisie par l'étudiant
                                $stmt = $conn->prepare("SELECT option_text FROM question_options WHERE id = ?");
                                $stmt->bind_param("i", $question['option_id']);
                                $stmt->execute();
                                $chosen_option = $stmt->get_result()->fetch_assoc();

                                // Récupérer les bonnes réponses pour cette question
                                $stmt = $conn->prepare("SELECT option_text FROM question_options WHERE question_id = ? AND is_correct = 1");
                                $stmt->bind_param("i", $question['question_id']);
                                $stmt->execute();
                                $correct_options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                $correct_option_texts = array_column($correct_options, 'option_text');
                                ?>

                                <strong>Option choisie :</strong>
                                <span class="<?= $question['is_correct'] ? 'correct-answer' : 'incorrect-answer' ?>">
                                    <?= htmlspecialchars($chosen_option['option_text'] ?? 'Aucune réponse') ?>
                                </span><br>

                                <strong>Bonne(s) réponse(s) :</strong>
                                <?= implode(', ', $correct_option_texts) ?><br>
                            <?php elseif ($question['type'] === 'open' || $question['type'] === 'short'): ?>
                                <strong>Réponse :</strong> <?= htmlspecialchars($question['answer_text'] ?? 'Aucune réponse') ?><br>
                                <strong>Points attribués :</strong> <?= htmlspecialchars($question['points_attributed'] ?? 0) ?>/<?= htmlspecialchars($question['points']) ?><br>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">Aucune question trouvée pour cet examen.</div>
        <?php endif; ?>

        <a href="index.php?exam_id=<?= $exam_id ?>&student_id=<?= $student_id ?>" class="btn btn-primary">Retour à l'accueil</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>