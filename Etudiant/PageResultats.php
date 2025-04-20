<?php
session_start();

// Database connection
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

// Fetch student results
try {
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as exam_title, c.name as course_name, r.created_at as exam_date,
        CASE 
            WHEN r.score >= 10 THEN 'pass'
            WHEN r.score < 10 THEN 'fail'
            ELSE 'pending'
        END as calculated_status
        FROM results r
        JOIN exams e ON r.exam_id = e.id
        LEFT JOIN cours c ON e.course_id = c.id
        WHERE r.student_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll();

    // Calculate statistics
    $totalExams = count($results);
    $totalScore = 0;
    $passedExams = 0;
    $failedExams = 0;
    $pendingExams = 0;
    $writtenQuestions = 0;

    // Count written questions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as question_count 
        FROM student_answers 
        WHERE student_id = ? AND answer_text IS NOT NULL
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $questionCount = $stmt->fetch();
    $writtenQuestions = $questionCount['question_count'];

    // Calculate exam statistics
    foreach ($results as $result) {
        if ($result['score'] !== null) {
            $totalScore += $result['score'];
            if ($result['score'] >= 10) {
                $passedExams++;
            } else {
                $failedExams++;
            }
        } else {
            $pendingExams++;
        }
    }

    $averageScore = $totalExams > 0 ? $totalScore / $totalExams : 0;

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - ExamPro</title>
    
    <!-- Shared Styles -->
    <link rel="stylesheet" href="styles.css">
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/PageResultats.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'navbar.php'; ?>

        <main class="main-content" id="mainContent">
            

            <!-- Results Section -->
            <div class="result-card">
                <div class="card-body">
                    <?php if (!empty($results)): ?>
                        <div class="table-container">
                            <table class="result-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Examen</th>
                                        <th>Cours</th>
                                        <th>Date</th>
                                        <th>Score</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $index => $result): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($result['exam_title']) ?></td>
                                            <td><?= htmlspecialchars($result['course_name'] ?? 'N/A') ?></td>
                                            <td><?= date('d/m/Y', strtotime($result['exam_date'])) ?></td>
                                            <td><?= number_format($result['score'], 2) ?>/20</td>
                                            <td>
                                                <?php
                                                $statusClass = 'status-pending';
                                                $statusText = 'En attente';

                                                if ($result['score'] !== null) {
                                                    if ($result['score'] >= 10) {
                                                        $statusClass = 'status-success';
                                                        $statusText = 'Réussi';
                                                    } else {
                                                        $statusClass = 'status-failed';
                                                        $statusText = 'Échoué';
                                                    }
                                                }
                                                ?>
                                                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert-message">
                            <i class="fas fa-info-circle"></i>
                            Vous n'avez pas encore passé d'examens.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Analysis -->
            <div class="performance-card">
                <div class="card-body">
                    <h4><i class="fas fa-chart-line"></i> Analyse des performances</h4>
                    <div class="performance-stats">
                        <div class="stat-item">
                            <strong>Moyenne générale</strong>
                            <?= number_format($averageScore, 2) ?>/20
                        </div>
                        <div class="stat-item">
                            <strong>Questions répondues</strong>
                            <?= $writtenQuestions ?>
                        </div>
                        <div class="stat-item">
                            <strong>Examens réussis</strong>
                            <?= $passedExams ?>
                        </div>
                        <div class="stat-item">
                            <strong>Examens échoués</strong>
                            <?= $failedExams ?>
                        </div>
                        <div class="stat-item">
                            <strong>Examens en attente</strong>
                            <?= $pendingExams ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="scripts.js"></script>
</body>
</html>