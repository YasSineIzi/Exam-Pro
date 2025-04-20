<?php
session_start();
require_once '../db.php';

// Vérifier si l'examen et l'étudiant sont spécifiés
if (!isset($_POST['exam_id']) || !isset($_POST['student_id'])) {
    die("ID de l'examen ou de l'étudiant manquant.");
}

$exam_id = $_POST['exam_id'];
$student_id = $_POST['student_id'];
$points_attributed = $_POST['points_attributed'];

// Enregistrer les corrections
$total_score = 0;
try {
    foreach ($points_attributed as $answer_id => $points) {
        $stmt = $conn->prepare("
            UPDATE student_answers 
            SET points_attributed = ? 
            WHERE id = ? AND student_id = ?
        ");
        $stmt->bind_param("iii", $points, $answer_id, $student_id);
        $stmt->execute();
        $total_score += $points;
    }

    // Mettre à jour le score total dans la table des résultats
    $stmt = $conn->prepare("
        INSERT INTO results (student_id, exam_id, score, status) 
        VALUES (?, ?, ?, 'pending')
        ON DUPLICATE KEY UPDATE score = VALUES(score)
    ");
    $stmt->bind_param("iid", $student_id, $exam_id, $total_score);
    $stmt->execute();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction Enregistrée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-success">
            <h4 class="alert-heading">Correction Enregistrée</h4>
            <p>La correction a été enregistrée avec succès.</p>
            <hr>
            <a href="results.php?exam_id=<?= htmlspecialchars($exam_id) ?>" class="btn btn-primary">Voir les résultats</a>
            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
