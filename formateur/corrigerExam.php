<?php
session_start();
require_once '../db.php';
// Récupérer l'ID de l'examen et l'ID de l'étudiant depuis l'URL
if (!isset($_GET['exam_id']) || !isset($_GET['student_id'])) {
    die("ID de l'examen ou de l'étudiant manquant.");
}
$exam_id = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);
$student_id = filter_var($_GET['student_id'], FILTER_VALIDATE_INT);

if ($exam_id === false || $student_id === false) {
    die("ID de l'examen ou de l'étudiant invalide.");
}

try {
    $stmt = $conn->prepare("SELECT name FROM exams WHERE id = ?");
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

try {
    $stmt = $conn->prepare("
    SELECT 
        q.id AS question_id, 
        q.question_title, 
        q.type, 
        q.points AS max_points,
        sa.id AS answer_id,
        sa.answer_text,
        (
            SELECT GROUP_CONCAT(option_id) 
            FROM student_answers 
            WHERE question_id = q.id AND student_id = ?
        ) as selected_options,
        (
            SELECT GROUP_CONCAT(is_correct) 
            FROM student_answers 
            WHERE question_id = q.id AND student_id = ?
        ) as is_correct,
        COALESCE(
            (SELECT points_attributed 
            FROM student_answers 
            WHERE question_id = q.id AND student_id = ? 
            LIMIT 1), 0
        ) AS points_attributed
    FROM questions q
    LEFT JOIN (
        SELECT * FROM student_answers WHERE student_id = ?
    ) sa ON q.id = sa.question_id
    WHERE q.exam_id = ?
    GROUP BY q.id
    ORDER BY q.id ASC
");



    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param("iiiii", $student_id, $student_id, $student_id, $student_id, $exam_id);
    if (!$stmt->execute()) {
        throw new Exception("Erreur d'exécution de la requête : " . $stmt->error);
    }

    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Fonction pour récupérer les options d'une question
function getQuestionOptions($conn, $question_id)
{
    $stmt = $conn->prepare("
        SELECT id, option_text, is_correct 
        FROM question_options 
        WHERE question_id = ?
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corriger l'examen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/corrigerExam.css">
</head>

<body>
    <div class="container mt-5">
        <h1>Corriger l'examen : <?= htmlspecialchars($exam['name']) ?></h1>
        <h2>Réponses de l'étudiant (ID : <?= htmlspecialchars($student_id) ?>)</h2>

        <form action="save_correction.php" method="post" id="correction-form">
            <input type="hidden" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">

            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $question): ?>
                    <div class="card mb-3 question-card">
                        <div class="card-header">
                            <div>
                                <!-- <?php if ($question['type'] === 'mcq'): ?>
                                    <span class="question-type">QCM</span>
                                <?php elseif ($question['type'] === 'short'): ?>
                                    <span class="question-type">Réponse courte</span>
                                <?php elseif ($question['type'] === 'open'): ?>
                                    <span class="question-type">Réponse ouverte</span>
                                <?php endif; ?> -->
                                <h5 class="card-title d-inline-block"><?= htmlspecialchars($question['question_title']) ?></h5>
                            </div>
                            <div class="note-input">

                                <label for="points_<?= $question['answer_id'] ?>">Note</label>
                                <?php
                                if ($question['type'] === 'mcq') {
                                    $options = getQuestionOptions($conn, $question['question_id']);

                                    $selected_ids = [];

                                    if (!empty($question['selected_options'])) {
                                        $selected_ids = array_map('intval', explode(',', $question['selected_options']));
                                    }

                                    // Identifier les bonnes et mauvaises réponses
                                    $correct_ids = array_map(
                                        fn($opt) => $opt['id'],
                                        array_filter($options, fn($opt) => $opt['is_correct'] == 1)
                                    );
                                    $wrong_ids = array_map(
                                        fn($opt) => $opt['id'],
                                        array_filter($options, fn($opt) => $opt['is_correct'] == 0)
                                    );

                                    // Réponses correctes et incorrectes choisies
                                    $correct_selected = array_intersect($selected_ids, $correct_ids);
                                    $incorrect_selected = array_intersect($selected_ids, $wrong_ids);

                                    // Calcul du score par bonne réponse (note maximale divisée par le nombre de bonnes réponses)
                                    $points_per_correct = count($correct_ids) > 0 ? $question['max_points'] / count($correct_ids) : 0;

                                    // Pénalité par mauvaise réponse
                                    $penalty_per_wrong = 0.25;

                                    // Calcul du score en fonction des réponses correctes et incorrectes choisies
                                    $correct_score = count($correct_selected) * $points_per_correct;
                                    $penalty_score = count($incorrect_selected) * $penalty_per_wrong;

                                    // Calcul final du score
                                    $calculated_score = $correct_score - $penalty_score;

                                    // Assurer que le score ne soit pas négatif
                                    if ($calculated_score < 0) {
                                        $calculated_score = 0;
                                    }
                                }

                                ?>



                                <input type="number" id="points_<?= $question['answer_id'] ?>"
                                    name="points_attributed[<?= $question['answer_id'] ?>]"
                                    value="<?= $question['type'] === 'mcq' ? round($calculated_score, 2) : $question['points_attributed'] ?>"
                                    min="0" max="<?= $question['max_points'] ?>" step="0.5" class="form-control" required>
                                <span class="ms-1">/ <?= htmlspecialchars($question['max_points']) ?></span>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if ($question['type'] === 'mcq'): ?>
                                <span class="section-title">Options disponibles</span>
                                <div class="options-list">
                                    <?php
                                    $selected_options = !empty($question['selected_options']) ?
                                        explode(',', $question['selected_options']) : [];

                                    // Récupérer toutes les options de la question
                                    $options = getQuestionOptions($conn, $question['question_id']);
                                    ?>
                                    <ul>
                                        <?php foreach ($options as $option): ?>
                                            <li class="<?php
                                            if (in_array($option['id'], $selected_options)) {
                                                echo $option['is_correct'] ? 'correct-answer student-choice' : 'incorrect-answer student-choice';
                                            } elseif ($option['is_correct']) {
                                                echo 'correct-answer';
                                            }
                                            ?>">
                                                <?= htmlspecialchars($option['option_text']) ?>
                                                <?php if (in_array($option['id'], $selected_options)): ?>
                                                    <span>(Choisi par l'étudiant)</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php elseif ($question['type'] === 'open' || $question['type'] === 'short'): ?>
                                <span class="section-title">Réponse de l'étudiant</span>
                                <div class="answer-container">
                                    <?= nl2br(htmlspecialchars($question['answer_text'] ?? '')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning">Aucune question trouvée pour cet examen.</div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="students_who_took_exam.php" class="btn btn-secondary">Retour à l'accueil</a>
                <button type="submit" class="btn btn-primary">Enregistrer la correction</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation du formulaire
        document.getElementById('correction-form').addEventListener('submit', function (e) {
            const inputs = this.querySelectorAll('input[type="number"]');
            let isValid = true;

            inputs.forEach(input => {
                const value = parseFloat(input.value);
                const max = parseFloat(input.max);

                if (isNaN(value) || value < 0 || value > max) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Veuillez vérifier les notes attribuées. Elles doivent être comprises entre 0 et le maximum autorisé.');
            }
        });
    </script>
</body>

</html>