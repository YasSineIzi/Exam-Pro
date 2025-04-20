<?php
session_start();
require_once '../db.php'; // Ensure the correct path to the config file

// Define the DSN and options for PDO
$dsn = 'mysql:host=localhost;dbname=exampro'; // Change this to your DSN
$username = 'root'; // Change this to your database username
$password = ''; // Change this to your database password
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Define the PDO variable for database connection
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
$exam = null;
$questions = [];

try {
    // Vérifier que l'examen appartient bien au formateur connecté
    $stmt = $pdo->prepare("
        SELECT * FROM exams 
        WHERE id = :exam_id AND user_id = :user_id
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

    // Récupérer les questions de l'examen
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE exam_id = :exam_id 
        ORDER BY id ASC
    ");
    $stmt->execute([':exam_id' => $examId]);
    $questions = $stmt->fetchAll();

    // Pour les questions QCM, récupérer leurs options
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mettre à jour les informations de l'examen
        $stmt = $pdo->prepare("
            UPDATE exams 
            SET title = :title, 
                description = :description, 
                duration = :duration 
            WHERE id = :exam_id AND user_id = :user_id
        ");

        $stmt->execute([
            ':title' => $_POST['examTitle'],
            ':description' => $_POST['examDescription'],
            ':duration' => $_POST['examDuration'],
            ':exam_id' => $examId,
            ':user_id' => $_SESSION['user_id']
        ]);

        // Mettre à jour les questions
        foreach ($_POST['questions'] as $questionId => $questionData) {
            $stmt = $pdo->prepare("
                UPDATE questions 
                SET question_title = :title,
                    points = :points 
                WHERE id = :question_id AND exam_id = :exam_id
            ");

            $stmt->execute([
                ':title' => $questionData['title'],
                ':points' => $questionData['points'],
                ':question_id' => $questionId,
                ':exam_id' => $examId
            ]);
        }

        $_SESSION['success'] = "Examen mis à jour avec succès.";
        header('Location: lesExamCreé.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Examen - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        
        .sidebar.collapsed~.container .main-content {
            margin-left: var(--collapsed-width);
        }

        .container {
            padding: 2rem;
        }
        /* Custom Styles */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: margin-left 0.3s;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

        .question-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }

        h1, h3 {
            color: #2c3e50;
            font-weight: 600;
        }

        h3 {
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
        }

        .form-control:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
        }

        .btn-outline-primary {
            border-width: 2px;
        }

        .btn-primary {
            padding: 0.625rem 2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .alert {
            border-radius: 8px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
        }
    </style>
</head>

<body>

    <main class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Modifier l'examen</h1>
                <a href="lesExamCreé.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <?php if ($exam): ?>
                <form method="POST">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Informations de l'examen</h3>
                            <div class="mb-3">
                                <label for="examTitle" class="form-label">Titre de l'examen</label>
                                <input type="text" class="form-control" id="examTitle" name="examTitle"
                                    value="<?= htmlspecialchars($exam['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="examDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="examDescription" name="examDescription" rows="3"
                                    required><?= htmlspecialchars($exam['description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="examDuration" class="form-label">Durée (minutes)</label>
                                <input type="number" class="form-control" id="examDuration" name="examDuration"
                                    value="<?= htmlspecialchars($exam['duration']) ?>" min="15" required>
                            </div>
                        </div>
                    </div>

                    <h3 class="mb-4">Questions</h3>
                    <?php foreach ($questions as $question): ?>
                        <div class="question-card">
                            <div class="mb-3">
                                <label for="questionTitle_<?= $question['id'] ?>" class="form-label">
                                    Question <?= htmlspecialchars($question['question_title']) ?>
                                </label>
                                <input type="text" class="form-control" id="questionTitle_<?= $question['id'] ?>"
                                    name="questions[<?= $question['id'] ?>][title]"
                                    value="<?= htmlspecialchars($question['question_title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="questionPoints_<?= $question['id'] ?>" class="form-label">Points</label>
                                <input type="number" class="form-control" id="questionPoints_<?= $question['id'] ?>"
                                    name="questions[<?= $question['id'] ?>][points]"
                                    value="<?= htmlspecialchars($question['points']) ?>" min="1" required>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Examen non trouvé.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>