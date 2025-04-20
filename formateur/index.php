<?php
session_start();
require_once '../db.php';

try {
    // Récupérer le nombre d'examens créés par ce formateur
    $stmt = $conn->prepare("
        SELECT COUNT(*) as exam_count 
        FROM exams 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $examCount = $stmt->get_result()->fetch_assoc()['exam_count'];

    // Récupérer le nombre total de questions créées par ce formateur
    $stmt = $conn->prepare("
        SELECT COUNT(q.id) as question_count
        FROM questions q
        JOIN exams e ON q.exam_id = e.id
        WHERE e.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $questionCount = $stmt->get_result()->fetch_assoc()['question_count'];

    // Récupérer le nombre d'examens en attente (non publiés)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending_count 
        FROM exams 
        WHERE user_id = ? AND published = 0
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $pendingCount = $stmt->get_result()->fetch_assoc()['pending_count'];

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erreur : ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/index.css">
    <style> </style>
</head>






<body>
    <?php include 'sidebar.php'; ?>

    <div class="container-fluid">
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
        <header class="main-header">
    <div class="header-container">
        <div class="header-brand">
            <!-- <img src="logo-exampro.png" alt="ExamPro Logo" class="header-logo"> -->
            <h1 class="header-title">Bienvenue sur ExamPro</h1>
        </div>
        
        <div class="header-profile">
            <a href="parametresProf.php" class="profile-link">
                <div class="profile-content">
                    <div class="profile-info">
                        <span class="profile-role">Formateur</span>
                        <span class="profile-name"></span>
                    </div>
                    <div class="profile-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</header>

<style></style>

            <div class="dashboard-content">
                <p class="dashboard-subtitle">Gérez vos examens et vos questions de manière simple et efficace.</p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $examCount ?></div>
                        <div class="stat-label">Examens Créés</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $pendingCount ?></div>
                        <div class="stat-label">Examens en attente</div>
                    </div>
                </div>

                <div class="actions-grid">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3 class="action-title">Créer exam</h3>
                        <p class="action-description">Ajoutez et gérez vos questions pour les examens.</p>
                        <a href="creerExam.html" class="btn">Créer</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="action-title">Examens</h3>
                        <p class="action-description">Visualisez et modifiez vos examens existants.</p>
                        <a href="lesExamCree.html" class="btn">Voir</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Remove the sidebar toggle functionality as it's no longer needed
    </script>
</body>

</html>