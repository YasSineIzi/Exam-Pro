<?php
session_start(); // Assurez-vous que la session est démarrée

// Inclure la connexion à la base de données
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$exams = [];

try {
    // Query to fetch published exams
    $stmt = $conn->prepare("
        SELECT e.*, 
        (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.id AND r.student_id = ?) AS taken
        FROM exams e 
        WHERE e.published = 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = $result->fetch_all(MYSQLI_ASSOC);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/exams.css">
    <script src="script.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'navbar.php'; ?>

        <!-- Main Content -->
        <main class="content">
            <div class="main-content">
                <header class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <h1>Examens</h1>
                    <a href="profil.php" class="nav-link">
                        <i class="fas fa-user profile-icon"></i>
                    </a>
                </header>

                <!-- Exam content goes here -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title">Liste des examens</h2>
                        <p class="card-text">Ici, vous trouverez la liste de vos examens à venir et passés.</p>
                        <?php if (!empty($exams)): ?>
                            <ul class="list-group animated-items">
                                <?php foreach ($exams as $exam): ?>
                                    <li class="list-group-item">
                                        <h5 class="mb-1"><?= htmlspecialchars($exam['title']) ?></h5>
                                        <p class="mb-1"><?= htmlspecialchars($exam['description']) ?></p>
                                        <small class="text-muted">Durée: <?= htmlspecialchars($exam['duration']) ?> minutes</small>
                                        <?php if ($exam['taken'] == 0): ?>
                                            <a href="takeExam.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary mt-2">Passer l'examen</a>
                                        <?php else: ?>
                                            <span class="text-success mt-2">Examen déjà passé</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-warning">Aucun examen publié pour le moment.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
