<?php
session_start(); // Assurez-vous que la session est démarrée

// Inclure la connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=exampro'; // Change this to your DSN
$username = 'root'; // Change this to your database username
$password = ''; // Change this to your database password
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$exams = [];

try {
    // Query to fetch published exams and check if student has already taken them
    $stmt = $pdo->prepare("
        SELECT e.*, 
            CASE WHEN sa.student_id IS NOT NULL THEN 1 ELSE 0 END as has_taken
        FROM exams e
        LEFT JOIN student_answers sa ON e.id = sa.exam_id 
            AND sa.student_id = ?
        WHERE e.published = 1
        GROUP BY e.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens - ExamPro</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/Examens.css">
    <script src="script.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include 'navbar.php'; ?>

        <main class="main-content" id="mainContent">
            <header class="content-header">
                    <h1>Examens</h1>
                </header>

            <!-- Exam Content -->
            <div class="exam-card">
                    <div class="card-body">
                    <div class="card-header">
                        <h2>Liste des examens</h2>
                        <p class="subheader">Examens à venir et historiques</p>
                    </div>

                        <?php if (!empty($exams)): ?>
                        <div class="exam-list">
                                <?php foreach ($exams as $exam): ?>
                                <div class="exam-item <?= $exam['has_taken'] ? 'completed' : '' ?>">
                                    <?php if ($exam['has_taken']): ?>
                                        <div class="completion-badge">
                                            <i class="fas fa-check-double"></i>
                                            Examen Réussi!
                                        </div>
                                    <?php endif; ?>

                                    <div class="exam-content">
                                        <div class="exam-meta">
                                            <span
                                                class="exam-status <?= $exam['has_taken'] ? 'status-complete' : 'status-pending' ?>">
                                                <i
                                                    class="fas <?= $exam['has_taken'] ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
                                                <?= $exam['has_taken'] ? 'Terminé' : 'Disponible' ?>
                                            </span>
                                            <span class="exam-duration">
                                                <i class="fas fa-clock"></i>
                                                <?= htmlspecialchars($exam['duration']) ?> minutes
                                            </span>
                                        </div>
                                        <h3 class="exam-title"><?= htmlspecialchars($exam['title']) ?></h3>
                                        <p class="exam-description"><?= htmlspecialchars($exam['description']) ?></p>
                                    </div>
                                    <div class="exam-actions">
                                        <?php if ($exam['has_taken']): ?>
                                            <a href="PageResultats.php?exam_id=<?= $exam['id'] ?>" class="btn-secondary">
                                                <i class="fas fa-chart-bar"></i>
                                                Voir Résultats
                                            </a>
                        <?php else: ?>
                                            <a href="takeExam.php?exam_id=<?= $exam['id'] ?>" class="btn-primary">
                                                <i class="fas fa-pencil-alt"></i>
                                                Commencer
                                            </a>
                        <?php endif; ?>
                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>Aucun examen disponible actuellement</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="scripts.js"></script>
</body>

</html>