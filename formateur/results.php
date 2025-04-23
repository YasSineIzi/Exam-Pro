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

// Récupérer la liste des examens du formateur
try {
    $stmt = $pdo->prepare("SELECT id, title FROM exams WHERE user_id = :user_id ORDER BY title");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer la liste des groupes
try {
    $stmt = $pdo->query("SELECT Id_c, Nom_c FROM class ORDER BY Nom_c");
    $groups = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des groupes : " . $e->getMessage());
}

// Récupérer les résultats si un examen est sélectionné
if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as student_name, u.class,
                   e.title as exam_title,
                   (SELECT COUNT(*) FROM questions WHERE exam_id = r.exam_id) as total_questions
            FROM results r
            JOIN users u ON r.student_id = u.id
            JOIN exams e ON r.exam_id = e.id
            WHERE r.exam_id = :exam_id
            ORDER BY u.class, u.name
        ");
        $stmt->execute([':exam_id' => $exam_id]);
        $results = $stmt->fetchAll();

        if (!empty($results)) {
            $totalStudents = count($results);
            $scores = array_column($results, 'score');
            $averageScore = number_format(array_sum($scores) / $totalStudents, 2);
            $highestScore = max($scores);
            $lowestScore = min($scores);
        }
    } catch (Exception $e) {
        die("Erreur : " . $e->getMessage());
    }
}
?>

<!-- HTML RESTE INCHANGÉ SAUF LA PARTIE DU FORMULAIRE GROUPE & AFFICHAGE ETUDIANTS -->
<!-- ... Dans la section .card-body, ajoute le formulaire suivant au-dessus de l'examen -->


<?php
if (isset($_GET['group_id']) && !empty($_GET['group_id'])) {
    $group_id = $_GET['group_id'];
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE class = :group_id AND role = 'student'");
    $stmt->execute(['group_id' => $group_id]);
    $students = $stmt->fetchAll();

    if ($students):
        foreach ($students as $student):
            echo "<h3>Étudiant : " . htmlspecialchars($student['name']) . "</h3>";

            $stmt = $pdo->prepare("SELECT e.id, e.title, r.score FROM results r JOIN exams e ON e.id = r.exam_id WHERE r.student_id = :student_id");
            $stmt->execute(['student_id' => $student['id']]);
            $exams = $stmt->fetchAll();

            if ($exams):
                echo "<ul>";
                foreach ($exams as $exam):
                    echo "<li>";
                    echo htmlspecialchars($exam['title']) . " - Note : " . number_format($exam['score'], 2) . "/20 ";
                    echo "<a href='corriger_exam.php?exam_id=" . $exam['id'] . "&student_id=" . $student['id'] . "' class='score-badge score-medium'>Corriger</a>";
                    echo "</li>";
                endforeach;
                echo "</ul>";
            else:
                echo "<p>Aucun examen passé.</p>";
            endif;

        endforeach;
   
    endif;
}
?>

<!-- Le reste du HTML reste inchangé -->

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats des Examens - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/results.css">
    <style></style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <div class="main-content">
            <header>
                <h1>Résultats des Examens</h1>
            </header>

            <div class="card mb-4">
                <div class="card-body">
                <div class="select-container">
    <form method="GET" class="exam-form">
        <div class="select-wrapper">
        <div class="select-container">
   
</div>

<div class="select-container">
    <form method="GET" class="exam-form">
        <div class="select-wrapper">
            <select name="exam_id" class="custom-select" onchange="this.form.submit()">
                <option value="">Sélectionner un examen</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?= $exam['id'] ?>" 
                            <?= (isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($exam['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="select-icon">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </form>
</div>

            
            <div class="select-icon">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </form>
</div>

                    <?php if (isset($results) && !empty($results)): ?>
                        <div class="stats-grid mb-4">
                            <div class="stat-card">
                                <div class="stat-value"><?= $totalStudents ?></div>
                                <div class="stat-label">Étudiants</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $averageScore ?></div>
                                <div class="stat-label">Moyenne</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $highestScore ?></div>
                                <div class="stat-label">Note max</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $lowestScore ?></div>
                                <div class="stat-label">Note min</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Score</th>
                                        <!-- <th>Statut</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($result['student_name']) ?></td>
                                            <td><?= htmlspecialchars($result['class']) ?></td>
                                            <td>
                                                <span class="score-badge <?= $result['score'] >= 10 ? 'score-high' : 'score-low' ?>">
                                                    <?= number_format($result['score'], 2) ?>/20
                                                </span>
                                            </td>
                                           
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (isset($_GET['exam_id'])): ?>
                        <div class="alert alert-info">
                            Aucun résultat disponible pour cet examen.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>