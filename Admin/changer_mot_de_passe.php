<?php
session_start();
include '../db.php';

$error = '';
$success = '';

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $ancien_mot_de_passe = trim($_POST['ancien_mot_de_passe']);
    $nouveau_mot_de_passe = trim($_POST['nouveau_mot_de_passe']);
    $confirmer_mot_de_passe = trim($_POST['confirmer_mot_de_passe']);

    // Vérifiez que tous les champs sont remplis
    if (!empty($ancien_mot_de_passe) && !empty($nouveau_mot_de_passe) && !empty($confirmer_mot_de_passe)) {
        // Récupérez le mot de passe actuel depuis la base de données
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($db_password);

        if ($stmt->num_rows > 0) {
            $stmt->fetch();

            // Vérifiez que l'ancien mot de passe est correct
            if ($ancien_mot_de_passe === $db_password) {
                // Vérifiez que les nouveaux mots de passe correspondent
                if ($nouveau_mot_de_passe === $confirmer_mot_de_passe) {
                    // Mettez à jour le mot de passe en base de données
                    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $nouveau_mot_de_passe, $user_id);

                    if ($update_stmt->execute()) {
                        $success = "Mot de passe changé avec succès.";
                    } else {
                        $error = "Erreur lors de la mise à jour du mot de passe.";
                    }

                    $update_stmt->close();
                } else {
                    $error = "Les nouveaux mots de passe ne correspondent pas.";
                }
            } else {
                $error = "L'ancien mot de passe est incorrect.";
            }
        } else {
            $error = "Utilisateur introuvable.";
        }

        $stmt->close();
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Changer le mot de passe</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="ancien_mot_de_passe" class="form-label">Ancien mot de passe</label>
                <input type="password" class="form-control" id="ancien_mot_de_passe" name="ancien_mot_de_passe" required>
            </div>
            <div class="mb-3">
                <label for="nouveau_mot_de_passe" class="form-label">Nouveau mot de passe</label>
                <input type="password" class="form-control" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required>
            </div>
            <div class="mb-3">
                <label for="confirmer_mot_de_passe" class="form-label">Confirmer le nouveau mot de passe</label>
                <input type="password" class="form-control" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" required>
            </div>
            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
        </form>
    </div>
</body>
</html>
