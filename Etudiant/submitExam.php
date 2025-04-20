<?php
session_start();
require_once '../db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Définir les informations de connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=exampro';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = $_SESSION['user_id'];
        $exam_id = $_POST['exam_id'];

        // Supprimer les anciennes réponses si elles existent
        $stmt = $pdo->prepare("DELETE FROM student_answers WHERE student_id = ? AND exam_id = ?");
        $stmt->execute([$student_id, $exam_id]);

        // Récupérer les questions de l'examen
        $stmt = $pdo->prepare("SELECT id, type FROM questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll();

        foreach ($questions as $question) {
            $question_id = $question['id'];
            $answer_key = "question_" . $question_id;

            if ($question['type'] === 'mcq') {
                // Traitement des réponses QCM (multiples)
                if (isset($_POST[$answer_key]) && is_array($_POST[$answer_key])) {
                    foreach ($_POST[$answer_key] as $option_id) {
                        // Vérifier si l'option est valide pour cette question
                        $stmt = $pdo->prepare("
                    SELECT is_correct 
                    FROM question_options 
                    WHERE id = ? AND question_id = ?
                ");
                        $stmt->execute([$option_id, $question_id]);
                        $option = $stmt->fetch();

                        if ($option) {
                            // Insérer la réponse
                            $stmt = $pdo->prepare("
                    INSERT INTO student_answers 
                    (student_id, exam_id, question_id, option_id, is_correct) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                            $stmt->execute([
                                $student_id,
                                $exam_id,
                                $question_id,
                                $option_id,
                                $option['is_correct']
                            ]);
                        }
                    }
                }
            } else {
                // Traitement des réponses textuelles
                if (isset($_POST[$answer_key])) {
                    $stmt = $pdo->prepare("
                    INSERT INTO student_answers 
                    (student_id, exam_id, question_id, answer_text) 
                    VALUES (?, ?, ?, ?)
                ");
                    $stmt->execute([
                        $student_id,
                        $exam_id,
                        $question_id,
                        $_POST[$answer_key]
                    ]);
                }
            }
        }

        // Créer une entrée dans la table results
        $stmt = $pdo->prepare("
            INSERT INTO results (student_id, exam_id, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$student_id, $exam_id]);

        $_SESSION['success'] = "Examen soumis avec succès!";
        header('Location: PageResultats.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la soumission de l'examen: " . $e->getMessage();
        header('Location: Examens.php');
        exit;
    }
} else {
    header('Location: Examens.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumission de l'examen - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .processing-card {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: white;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .processing-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .processing-step {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            animation: fadeIn 0.3s ease-out forwards;
            opacity: 0;
        }

        .processing-step.success {
            border-left-color: #198754;
            background: #d1e7dd;
        }

        .processing-step.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .step-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
            margin-right: 0.5rem;
            background: #e9ecef;
        }

        .success .step-icon {
            background: #198754;
            color: white;
        }

        .error .step-icon {
            background: #dc3545;
            color: white;
        }

        .processing-progress {
            height: 4px;
            margin: 2rem 0;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #0d6efd, #0099ff);
            width: 0;
            transition: width 0.3s ease;
            animation: progressAnimation 2s ease-out forwards;
        }

        @keyframes progressAnimation {
            from {
                width: 0;
            }

            to {
                width: 100%;
            }
        }

        .debug-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #666;
            max-height: 200px;
            overflow-y: auto;
        }

        .debug-info pre {
            margin: 0;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="processing-card">
            <h1 class="processing-title">
                <i class="fas fa-cog fa-spin me-2"></i>
                Traitement de l'examen
            </h1>

            <div class="processing-progress">
                <div class="progress-bar"></div>
            </div>

            <div class="processing-steps">
                <?php
                // Enable error reporting
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                error_reporting(E_ALL);
                ini_set('display_errors', 1);

                try {
                    // Verify user is logged in
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception("Erreur: Vous devez être connecté pour soumettre un examen.");
                    }
                    echo '<div class="processing-step success" style="animation-delay: 0.2s">
                            <i class="fas fa-check-circle step-icon"></i>
                            Authentification vérifiée
                          </div>';

                    // Get student ID
                    $student_id = $_SESSION['user_id'];
                    echo '<div class="processing-step success" style="animation-delay: 0.4s">
                            <i class="fas fa-user step-icon"></i>
                            Identifiant étudiant récupéré: ' . $student_id . '
                          </div>';

                    // Verify exam exists
                    if (!isset($_POST['exam_id'])) {
                        throw new Exception("ID de l'examen manquant.");
                    }
                    $exam_id = $_POST['exam_id'];

                    // Start transaction
                    $conn->begin_transaction();
                    echo '<div class="processing-step success" style="animation-delay: 0.6s">
                            <i class="fas fa-database step-icon"></i>
                            Transaction démarrée
                          </div>';

                    // Verify exam exists and is published
                    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND published = 1");
                    $stmt->bind_param("i", $exam_id);
                    $stmt->execute();
                    $exam = $stmt->get_result()->fetch_assoc();

                    if (!$exam) {
                        throw new Exception("Examen non trouvé ou non publié.");
                    }
                    echo '<div class="processing-step success" style="animation-delay: 0.8s">
                            <i class="fas fa-file-alt step-icon"></i>
                            Examen validé
                          </div>';

                    // Delete existing answers
                    $stmt = $conn->prepare("DELETE FROM student_answers WHERE student_id = ? AND exam_id = ?");
                    $stmt->bind_param("ii", $student_id, $exam_id);
                    $stmt->execute();
                    echo '<div class="processing-step success" style="animation-delay: 1s">
                            <i class="fas fa-trash-alt step-icon"></i>
                            Anciennes réponses effacées
                          </div>';

                    // Process submitted answers
                    $answersProcessed = 0;
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'question_') === 0) {
                            $question_id = str_replace('question_', '', $key);

                            // Get question type
                            $stmt = $conn->prepare("SELECT type FROM questions WHERE id = ? AND exam_id = ?");
                            $stmt->bind_param("ii", $question_id, $exam_id);
                            $stmt->execute();
                            $question = $stmt->get_result()->fetch_assoc();

                            if (!$question) {
                                throw new Exception("Question non trouvée: " . $question_id);
                            }

                            if ($question['type'] === 'mcq') {
                                // Process MCQ answer
                                $option_id = $value;
                                $stmt = $conn->prepare("
                                    SELECT is_correct 
                                    FROM question_options 
                                    WHERE id = ? AND question_id = ?
                                ");
                                $stmt->bind_param("ii", $option_id, $question_id);
                                $stmt->execute();
                                $option = $stmt->get_result()->fetch_assoc();

                                if (!$option) {
                                    throw new Exception("Option invalide pour la question: " . $question_id);
                                }

                                $is_correct = $option['is_correct'];

                                $stmt = $conn->prepare("
                                    INSERT INTO student_answers 
                                    (student_id, exam_id, question_id, option_id, is_correct) 
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->bind_param("iiiii", $student_id, $exam_id, $question_id, $option_id, $is_correct);
                            } else {
                                // Process text answer
                                $answer_text = $value;
                                $stmt = $conn->prepare("
                                    INSERT INTO student_answers 
                                    (student_id, exam_id, question_id, answer_text) 
                                    VALUES (?, ?, ?, ?)
                                ");
                                $stmt->bind_param("iiis", $student_id, $exam_id, $question_id, $answer_text);
                            }

                            $stmt->execute();
                            $answersProcessed++;
                        }
                    }

                    echo '<div class="processing-step success" style="animation-delay: 1.2s">
                            <i class="fas fa-save step-icon"></i>
                            ' . $answersProcessed . ' réponses enregistrées
                          </div>';

                    // Commit transaction
                    $conn->commit();
                    echo '<div class="processing-step success" style="animation-delay: 1.4s">
                            <i class="fas fa-check-double step-icon"></i>
                            Transaction validée
                          </div>';

                    // Redirect after 2 seconds
                    echo '<div class="processing-step" style="animation-delay: 1.6s">
                            <i class="fas fa-sync fa-spin step-icon"></i>
                            Redirection vers la page de confirmation...
                          </div>';

                    echo "<script>
                            setTimeout(function() {
                                window.location.href = 'confirmation.php?exam_id=" . $exam_id . "';
                            }, 2000);
                          </script>";

                } catch (Exception $e) {
                    if (isset($conn)) {
                        $conn->rollback();
                    }
                    echo '<div class="processing-step error">
                            <i class="fas fa-times-circle step-icon"></i>
                            Erreur: ' . htmlspecialchars($e->getMessage()) . '
                          </div>';
                }
                ?>
            </div>

            <?php if (isset($_POST)): ?>
                <div class="debug-info">
                    <h6><i class="fas fa-bug me-2"></i>Informations de débogage:</h6>
                    <pre><?php print_r($_POST); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>