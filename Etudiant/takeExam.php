<?php
session_start();

// Inclure la connexion à la base de données
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

$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

try {
    // Get the student's class_id
    $stmt = $pdo->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    $student_class_id = $student['class_id'];

    // Vérifier si l'étudiant a déjà passé cet examen
    $stmt = $pdo->prepare("
        SELECT id FROM student_answers 
        WHERE exam_id = ? AND student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        // L'étudiant a déjà passé cet examen
        header('Location: Examens.php');
        exit;
    }

    // Récupérer les détails de l'examen, en vérifiant qu'il est assigné à la classe de l'étudiant ou à toutes les classes
    $stmt = $pdo->prepare("
        SELECT * FROM exams 
        WHERE id = ? 
        AND published = 1
        AND (class_id = ? OR class_id IS NULL)
    ");
    $stmt->execute([$exam_id, $student_class_id]);
    $exam = $stmt->fetch();

    // Si l'examen n'existe pas ou n'est pas publié, rediriger
    if (!$exam) {
        header('Location: Examens.php');
        exit;
    }

    // Récupérer les questions de l'examen
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll();

    // Récupérer les options pour chaque question de type QCM
    if (!empty($questions)) {
        foreach ($questions as &$question) {
            if ($question['type'] === 'mcq') {
                $stmt = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ?");
                $stmt->execute([$question['id']]);
                $question['options'] = $stmt->fetchAll();
            }
        }
    }
    unset($question);

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($exam['title']) ?> - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/takeExam.css">
    <!-- Anti-cheating notification styles -->
    <style>
        /* Notification for cheating prevention */
        .cheating-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .cheating-notice i {
            font-size: 24px;
            margin-right: 15px;
            color: #e0a800;
        }

        .cheating-notice ul {
            margin-bottom: 0;
        }

        .cheating-notice h5 {
            color: #e0a800;
            margin-bottom: 10px;
        }

        .arabic-proverb {
            font-size: 1.5rem;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            font-family: 'Traditional Arabic', 'Scheherazade New', serif;
        }

        .proverb-translation {
            text-align: center;
            font-style: italic;
            margin-bottom: 15px;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="progress-indicator">
        <div class="progress-bar" id="progress-bar"></div>
    </div>



    <div class="page-header">
        <div class="container">

            <h1 class="display-5 mb-2"><?= htmlspecialchars($exam['title']) ?></h1>
            <p class="lead mb-0"><?= htmlspecialchars($exam['description']) ?></p>
        </div>
    </div>
    <style>
        .page-header {
            background: linear-gradient(to right, #007bff, #6610f2);
            color: white;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://source.unsplash.com/1600x900/?exam,study') center/cover no-repeat;
            opacity: 0.2;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: auto;
        }

        h1.display-5 {
            font-weight: bold;
            font-size: 2.8rem;
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1s ease-in-out;
        }

        p.lead {
            font-size: 1.3rem;
            opacity: 0.9;
            animation: fadeInUp 1.5s ease-in-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .col-md-6 {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }



        h5 {
            font-size: 1.25rem;
            font-weight: bold;
            color: #007bff;
            display: flex;
            align-items: center;
        }

        h5 i {
            color: rgb(164, 7, 255);
            font-size: 1.5rem;
        }

        ul {
            padding-left: 20px;
        }

        ul li {
            font-size: 1rem;
            margin-bottom: 8px;
            position: relative;
            padding-left: 25px;
        }

        ul li::before {
            content: "✔";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
    </style>

    <div class="container mb-5">
        <div class="exam-info">
            <div class="row">
            <div class="exam-instructions bg-white p-4 rounded shadow-sm mb-4">
    <div class="row align-items-center">
        <!-- Instructions Section -->
        <div class="col-md-8">
            <h5 class="text-primary mb-3">
                <i class="fas fa-info-circle me-2"></i>Instructions de l'examen
            </h5>
            <ul class="list-unstyled ps-3">
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    Lire attentivement chaque question avant de répondre.
                </li>
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    Répondre à toutes les questions sans laisser de blancs.
                </li>
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    Vérifier vos réponses avant de soumettre.
                </li>
            </ul>
        </div>

        <!-- Timer Section -->
        <?php if (isset($exam['duration']) && $exam['duration'] > 0): ?>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <div class="timer-box bg-light border-start border-4 border-danger rounded p-3 d-inline-block shadow-sm">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-clock fa-lg text-danger me-2"></i>
                    <strong class="text-danger fs-5">
                        <span id="timer">--:--</span>
                    </strong>
                </div>
                <div class="text-muted small mt-1">Temps restant</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


                <div class="col-md-6">

                    <h5><i class="fas fa-clipboard-list me-2"></i>Détails de l'examen</h5>

                    <ul class="mb-0">
                        <?php if (isset($exam['duration']) && $exam['duration'] > 0): ?>
                            <li>Durée: <?= $exam['duration'] ?> minutes</li>
                        <?php endif; ?>
                        <li>Nombre de questions: <?= count($questions) ?></li>
                        <li>Total des points: <?= array_sum(array_column($questions, 'points')) ?></li>
                    </ul>
                </div>
            </div>

            <!-- Anti-cheating notice -->
            <div class="cheating-notice mt-3">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <h5><strong>Système anti-triche actif</strong></h5>
                    <p class="arabic-proverb"><span dir="rtl" lang="ar">من غشنا فليس منا</span></p>
                    <p class="proverb-translation">"Quiconque nous trompe n'est pas des nôtres" - Hadith</p>
                    <p>Pour garantir l'intégrité de l'examen, les activités suivantes sont surveillées et peuvent
                        entraîner la fin automatique de votre examen :</p>
                    <ul>
                        <li>Changer d'onglet ou quitter l'examen</li>
                        <li>Copier/coller du contenu</li>
                        <li>Utiliser les menus contextuels (clic droit)</li>
                        <li>Quitter le mode plein écran</li>
                        <li>Utiliser des raccourcis clavier de navigation (Alt+Tab)</li>
                    </ul>
                    <p class="mt-2 mb-0"><strong>Note :</strong> Après 5 avertissements, votre examen sera
                        automatiquement soumis.</p>
                </div>
            </div>
        </div>

        <form action="submitExam.php" method="post" id="examForm">
            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">

            <?php if (is_array($questions) && !empty($questions)): ?>
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question" data-question="<?= $index + 1 ?>">
                        <div class="question-header">
                            <div class="question-number">Question <?= $index + 1 ?> sur <?= count($questions) ?></div>
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="question-title"><?= htmlspecialchars($question['question_title']) ?></h4>
                                <span class="points-badge">
                                    <i class="fas fa-star me-1"></i>
                                    <?= htmlspecialchars($question['points']) ?> points
                                </span>
                            </div>
                        </div>

                        <?php if ($question['type'] === 'mcq' && is_array($question['options']) && !empty($question['options'])): ?>
                            <div class="mcq-options">
                                <?php foreach ($question['options'] as $option): ?>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                            id="option_<?= $question['id'] ?>_<?= $option['id'] ?>"
                                            name="question_<?= $question['id'] ?>[]" value="<?= htmlspecialchars($option['id']) ?>">
                                        <label class="form-check-label" for="option_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                            <?= htmlspecialchars($option['option_text']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($question['type'] === 'open'): ?>
                            <div class="form-group">
                                <textarea class="form-control" name="question_<?= $question['id'] ?>" rows="6"
                                    placeholder="Tapez votre réponse ici..." required></textarea>
                            </div>
                        <?php elseif ($question['type'] === 'short'): ?>
                            <div class="form-group">
                                <input type="text" class="form-control input-text" name="question_<?= $question['id'] ?>"
                                    placeholder="Votre réponse courte..." required>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Aucune question disponible pour cet examen.
                </div>
            <?php endif; ?>

            <div class="footer-actions">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="progress-text">
                            <span id="answered-count">0</span> sur <?= count($questions) ?> questions répondues
                        </div>
                        <div class="d-flex gap-2">
                            <a href="Examens.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Soumettre l'examen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Progress tracking
        function updateProgress() {
            const form = document.getElementById('examForm');
            const questions = form.querySelectorAll('.question');
            let answered = 0;

            questions.forEach(question => {
                const inputs = question.querySelectorAll('input[type="radio"], input[type="text"], textarea');
                const isAnswered = Array.from(inputs).some(input => input.value.trim() !== '');
                if (isAnswered) {
                    answered++;
                    question.classList.add('answered');
                }
            });

            const progress = (answered / questions.length) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('answered-count').textContent = answered;
        }

        // Timer functionality
        <?php if (isset($exam['duration']) && $exam['duration'] > 0): ?>
            function startTimer(duration) {
                let timer = duration * 60;
                const timerDisplay = document.getElementById('timer');

                const countdown = setInterval(() => {
                    const minutes = parseInt(timer / 60, 10);
                    const seconds = parseInt(timer % 60, 10);

                    timerDisplay.textContent = minutes.toString().padStart(2, '0') + ':' +
                        seconds.toString().padStart(2, '0');

                    if (--timer < 0) {
                        clearInterval(countdown);
                        document.getElementById('examForm').submit();
                    }
                }, 1000);
            }

            // Start the timer when the page loads
            window.onload = () => {
                startTimer(<?= $exam['duration'] ?>);
            };
        <?php endif; ?>

        // Form validation and progress tracking
        document.getElementById('examForm').addEventListener('change', updateProgress);
        document.getElementById('examForm').addEventListener('input', updateProgress);

        // Initial progress update
        updateProgress();
    </script>

    <!-- Anti-Cheating System -->
    <script src="anti_cheating.js"></script>
    <script>
        // Initialize the anti-cheating system
        document.addEventListener('DOMContentLoaded', function () {
            // Load security settings (in production, these would come from the server)
            const securitySettings = {
                examId: <?= $exam['id'] ?>,
                userId: <?= $_SESSION['user_id'] ?>,
                shuffleQuestions: false, // Set to true to shuffle questions
                shuffleOptions: false,   // Set to true to shuffle options
                preventCopyPaste: true,
                preventTabSwitching: true,
                preventRightClick: true,
                preventPrintScreen: true,
                fullscreenMode: true,
                logSuspiciousActivity: true,
                maxWarnings: 5
            };

            // Initialize the anti-cheating system
            const antiCheat = new AntiCheatingSystem(securitySettings);

            <?php if ($exam['duration'] > 0): ?>
                // Show full screen button with explanation
                const btnExplanation = document.createElement('div');
                btnExplanation.className = 'fullscreen-explanation';
                btnExplanation.innerHTML = '<p>Pour commencer l\'examen, veuillez activer le mode plein écran.</p>';
                document.querySelector('#fullscreen-btn').parentNode.insertBefore(btnExplanation, document.querySelector('#fullscreen-btn').nextSibling);
            <?php endif; ?>
        });
    </script>
</body>

</html>