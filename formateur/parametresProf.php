<?php
session_start();
require_once '../db.php';

// Définir les informations de connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=exampro';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Créer la connexion PDO
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Récupérer les informations de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}

// Traiter la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valider et récupérer les données du formulaire
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        // Vérifier le mot de passe actuel
        if ($currentPassword === $user['password']) {
            // Préparer la requête de mise à jour
            $updateFields = ['name' => $username, 'email' => $email];
            $sql = "UPDATE users SET name = :name, email = :email";

            // Ajouter le nouveau mot de passe s'il est fourni
            if (!empty($newPassword)) {
                if ($newPassword === $confirmPassword) {
                    $sql .= ", password = :password";
                    $updateFields['password'] = $newPassword;
                } else {
                    throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
                }
            }

            $sql .= " WHERE id = :user_id";
            $updateFields['user_id'] = $user_id;

            // Exécuter la mise à jour
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateFields);

            // Mettre à jour les données de session
            $_SESSION['name'] = $username;

            $message = "Profil mis à jour avec succès!";

            // Recharger les informations utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch();
        } else {
            throw new Exception("Le mot de passe actuel est incorrect.");
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
$message = '';
$messageType = ''; // Will be either 'success' or 'error'
$passwordChanged = false;

// Traiter la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valider et récupérer les données du formulaire
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        // Vérifier le mot de passe actuel
        if ($currentPassword === $user['password']) {
            // Préparer la requête de mise à jour
            $updateFields = ['name' => $username, 'email' => $email];
            $sql = "UPDATE users SET name = :name, email = :email";

            // Vérifier si l'utilisateur a tenté de changer le mot de passe
            if (!empty($newPassword) || !empty($confirmPassword)) {
                if ($newPassword === $confirmPassword) {
                    $sql .= ", password = :password";
                    $updateFields['password'] = $newPassword;
                    $passwordChanged = true;
                } else {
                    throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
                }
            }

            $sql .= " WHERE id = :user_id";
            $updateFields['user_id'] = $user_id;

            // Exécuter la mise à jour
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateFields);

            // Mettre à jour les données de session
            $_SESSION['name'] = $username;

            // Définir le message approprié
            if ($passwordChanged) {
                $message = "Profil mis à jour avec succès! Votre mot de passe a été modifié.";
            } else {
                $message = "Profil mis à jour avec succès! Le mot de passe n'a pas été modifié.";
            }
            $messageType = 'success';

            // Recharger les informations utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch();
        } else {
            throw new Exception("Le mot de passe actuel est incorrect.");
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Le reste de votre HTML reste inchangé, mais nous allons mettre à jour les valeurs des champs
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Compte - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="./style/parametresProf.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <main class="main-content">
            <div class="card">
                <form id="settingsForm" method="POST" action="">
                    <h2><i class="fas fa-user"></i> Informations personnelles</h2>
                    <div class="form-group">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" class="form-control"
                            value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <h2><i class="fas fa-lock"></i> Modifier le mot de passe</h2>
                    <div class="form-group">
                        <label for="currentPassword" class="form-label">Mot de passe actuel</label>
                        <div class="password-field">
                            <input type="password" id="currentPassword" name="currentPassword" class="form-control"
                                required>
                            <button type="button" class="password-toggle" data-target="currentPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="newPassword" class="form-label">Nouveau mot de passe</label>
                        <div class="password-field">
                            <input type="password" id="newPassword" name="newPassword" class="form-control">
                            <button type="button" class="password-toggle" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirmer le nouveau mot de passe</label>
                        <div class="password-field">
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
                            <button type="button" class="password-toggle" data-target="confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <h2><i class="fas fa-bell"></i> Préférences de notification</h2>
                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" id="emailNotifications" checked>
                            <span class="slider"></span>
                        </label>
                        <label for="emailNotifications" style="margin-left: 10px;">Notifications par email</label>
                        <p style="margin-top: 0; color: var(--text-light);">Recevoir les mises à jour par email</p>
                    </div>
                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" id="smsNotifications">
                            <span class="slider"></span>
                        </label>
                        <label for="smsNotifications" style="margin-left: 10px;">Notifications par SMS</label>
                        <p style="margin-top: 0; color: var(--text-light);">Recevoir les alertes par SMS</p>
                    </div>

                    <?php if ($message): ?>
                        <div
                            class="alert <?= strpos($message, 'succès') !== false ? 'alert-success' : 'alert-danger' ?> mt-3">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-right mt-4">
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>

            </div>
        </main>
    </div>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    </body>

</html>