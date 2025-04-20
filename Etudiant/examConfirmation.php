<?php
// examConfirmation.php

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-success">
            <h4 class="alert-heading">Examen soumis avec succès !</h4>
            <p>Vos réponses ont été enregistrées. Vous pouvez consulter vos résultats plus tard.</p>
        </div>
        <a href="index.php" class="btn btn-primary">Retour au tableau de bord</a>
    </div>
</body>
</html>