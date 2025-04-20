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

// Récupérez les informations actuelles de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

// Traitement des mises à jour du profil ou du mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);

        if (!empty($new_name) && !empty($new_email)) {
            $update_sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $new_name, $new_email, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['user_name'] = $new_name;
                $_SESSION['user_email'] = $new_email;
                $success = "Profil mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour du profil.";
            }
            $update_stmt->close();
        } else {
            $error = "Tous les champs doivent être remplis.";
        }
    } elseif (isset($_POST['change_password'])) {
        $ancien_mot_de_passe = trim($_POST['ancien_mot_de_passe']);
        $nouveau_mot_de_passe = trim($_POST['nouveau_mot_de_passe']);
        $confirmer_mot_de_passe = trim($_POST['confirmer_mot_de_passe']);

        if (!empty($ancien_mot_de_passe) && !empty($nouveau_mot_de_passe) && !empty($confirmer_mot_de_passe)) {
            $password_sql = "SELECT password FROM users WHERE id = ?";
            $password_stmt = $conn->prepare($password_sql);
            $password_stmt->bind_param("i", $user_id);
            $password_stmt->execute();
            $password_stmt->store_result();
            $password_stmt->bind_result($db_password);
            $password_stmt->fetch();

            if ($ancien_mot_de_passe === $db_password) {
                if ($nouveau_mot_de_passe === $confirmer_mot_de_passe) {
                    $update_password_sql = "UPDATE users SET password = ? WHERE id = ?";
                    $update_password_stmt = $conn->prepare($update_password_sql);
                    $update_password_stmt->bind_param("si", $nouveau_mot_de_passe, $user_id);

                    if ($update_password_stmt->execute()) {
                        $success = "Mot de passe changé avec succès.";
                    } else {
                        $error = "Erreur lors du changement de mot de passe.";
                    }
                    $update_password_stmt->close();
                } else {
                    $error = "Les nouveaux mots de passe ne correspondent pas.";
                }
            } else {
                $error = "L'ancien mot de passe est incorrect.";
            }
            $password_stmt->close();
        } else {
            $error = "Veuillez remplir tous les champs pour changer le mot de passe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Gestion de Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/styleindex.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require "navbar.php" ?>


            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 offset-md-3 offset-lg-2">
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Gestion de Profil</h3>
                    <div class="d-flex align-items-center">
                        <a href="profil.php" class="nav-link">Mon Profil</a>
                    </div>
                </header>

                <!-- Profile and Password Management -->
                <div class="container mt-4">
                    <h2>Profil et Mot de Passe</h2>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="mb-4">
                        <h4>Mettre à jour le profil</h4>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($email); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour</button>
                    </form>

                    <form method="POST" action="">
                        <h4>Changer le mot de passe</h4>
                        <div class="mb-3">
                            <label for="ancien_mot_de_passe" class="form-label">Ancien mot de passe</label>
                            <input type="password" class="form-control" id="ancien_mot_de_passe"
                                name="ancien_mot_de_passe" required>
                        </div>
                        <div class="mb-3">
                            <label for="nouveau_mot_de_passe" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="nouveau_mot_de_passe"
                                name="nouveau_mot_de_passe" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmer_mot_de_passe" class="form-label">Confirmer le nouveau mot de
                                passe</label>
                            <input type="password" class="form-control" id="confirmer_mot_de_passe"
                                name="confirmer_mot_de_passe" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Changer le mot de
                            passe</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>