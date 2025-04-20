<?php
session_start();
require_once '../db.php';

// Vérifier si l'ID de l'examen est présent
if (!isset($_GET['exam_id'])) {
    header('Location: ./');
    exit;
}

$exam_id = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);
if ($exam_id === false) {
    header('Location: ./');
    exit;
}

try {
    // Récupérer les informations de l'examen
    $stmt = $conn->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();

    if (!$exam) {
        header('Location: ./');
        exit;
    }
} catch (Exception $e) {
    header('Location: ./');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen Soumis - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/confirmation.css">
</head>
<body>
    <div class="container">
        <div class="confirmation-card">
            <div class="text-center">
                <i class="fas fa-check-circle icon-success"></i>
                <h1 class="confirmation-title">Examen soumis avec succès !</h1>
                <p class="confirmation-text">
                    Votre réponse pour l'examen "<?= htmlspecialchars($exam['title']) ?>" a été enregistrée avec succès.
                </p>
                <div class="alert alert-custom" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Vos résultats seront disponibles une fois que l'enseignant aura terminé la correction de l'examen.
                </div>
                <div class="action-buttons">
                    <a href="Examens.php" class="btn btn-primary btn-action">
                        <i class="fas fa-list me-2"></i>
                        Liste des examens
                    </a>
                    <a href="./" class="btn btn-secondary btn-action">
                        <i class="fas fa-home me-2"></i>
                        Accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 