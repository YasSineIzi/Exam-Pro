<?php
session_start();
require_once '../db.php';

// Définir les informations de connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=exampro';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Créer la connexion PDO
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de l'examen est fourni
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de l'examen non spécifié.";
    header('Location: lesExamCreé.php');
    exit;
}

$examId = (int) $_GET['id'];

try {
    // Récupérer les détails de l'examen
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as course_name 
        FROM exams e
        LEFT JOIN cours c ON e.course_id = c.id
        WHERE e.id = :exam_id AND e.user_id = :user_id
    ");
    $stmt->execute([
        ':exam_id' => $examId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $exam = $stmt->fetch();

    if (!$exam) {
        $_SESSION['error'] = "Examen non trouvé ou accès non autorisé.";
        header('Location: lesExamCreé.php');
        exit;
    }

    // Récupérer les questions
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE exam_id = :exam_id 
        ORDER BY id ASC
    ");
    $stmt->execute([':exam_id' => $examId]);
    $questions = $stmt->fetchAll();

    // Récupérer les options pour les questions QCM
    foreach ($questions as &$question) {
        if ($question['type'] === 'mcq') {
            $stmt = $pdo->prepare("
                SELECT * FROM question_options 
                WHERE question_id = :question_id
            ");
            $stmt->execute([':question_id' => $question['id']]);
            $question['options'] = $stmt->fetchAll();
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: lesExamCreé.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voir l'examen - <?= htmlspecialchars($exam['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .question-card {
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .correct-option {
            color: #198754;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= htmlspecialchars($exam['title']) ?></h1>
                <a href="lesExamCreé.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Détails de l'examen</h5>
                    <p><strong>Cours :</strong> <?= htmlspecialchars($exam['course_name'] ?? 'Non spécifié') ?></p>
                    <p><strong>Durée :</strong> <?= htmlspecialchars($exam['duration']) ?> minutes</p>
                    <p><strong>Description :</strong> <?= htmlspecialchars($exam['description']) ?></p>
                </div>
            </div>

            <h2 class="mb-4">Questions</h2>
            <?php foreach ($questions as $index => $question): ?>
                    <div class="card question-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                Question <?= $index + 1 ?> 
                                <span class="badge bg-primary"><?= $question['points'] ?> points</span>
                            </h5>
                            <p class="card-text"><?= htmlspecialchars($question['question_title']) ?></p>

                            <?php if ($question['type'] === 'mcq' && isset($question['options'])): ?>
                                    <div class="options-list mt-3">
                                        <?php foreach ($question['options'] as $option): ?>
                                                <div class="option <?= $option['is_correct'] ? 'correct-option' : '' ?>">
                                                    <?php if ($option['is_correct']): ?>
                                                            <i class="fas fa-check-circle"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($option['option_text']) ?>
                                                </div>
                                        <?php endforeach; ?>
                                    </div>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>