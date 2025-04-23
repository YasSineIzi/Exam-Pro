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
$student_id = $_SESSION['user_id'];

try {
    // First, get the student's class_id
    $stmt = $pdo->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    $student_class_id = $student['class_id'];
    
    // Query to fetch published exams that are either:
    // 1. Assigned to the student's class, OR
    // 2. Not assigned to any specific class (class_id is NULL)
    // Also check if student has already taken them
    $stmt = $pdo->prepare("
        SELECT e.*, 
            CASE WHEN sa.student_id IS NOT NULL THEN 1 ELSE 0 END as has_taken,
            c.Nom_c as class_name
        FROM exams e
        LEFT JOIN student_answers sa ON e.id = sa.exam_id 
            AND sa.student_id = ?
        LEFT JOIN class c ON e.class_id = c.Id_c
        WHERE e.published = 1
        AND (e.class_id = ? OR e.class_id IS NULL)
        GROUP BY e.id
    ");
    $stmt->execute([$student_id, $student_class_id]);
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
    <style>
        .exam-class {
            display: inline-flex;
            align-items: center;
            margin-left: 1rem;
            padding: 0.25rem 0.75rem;
            background-color: #E8F5E9;
            color: #2E7D32;
            border-radius: 9999px;
            font-size: 0.875rem;
        }
        
        .exam-class i {
            margin-right: 0.35rem;
        }
    </style>
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
                                            <?php if (!empty($exam['class_name'])): ?>
                                            <span class="exam-class">
                                                <i class="fas fa-users"></i>
                                                <?= htmlspecialchars($exam['class_name']) ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="exam-class">
                                                <i class="fas fa-globe"></i>
                                                Tous les groupes
                                            </span>
                                            <?php endif; ?>
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