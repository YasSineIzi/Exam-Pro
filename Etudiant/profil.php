<?php
session_start();
include '../db.php';  // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Assuming user is logged in and user ID is stored in session
$user_id = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

// Update personal info
if (isset($_POST['update_info'])) {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];

    // Validate and update the personal information
    if (!empty($new_name) && !empty($new_email)) {
        $update_query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
        if ($stmt->execute()) {
            $success = "Informations personnelles mises à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour des informations personnelles.";
        }
        $stmt->close();
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];

    // Fetch current password from the database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify the current password
    if (password_verify($current_password, $hashed_password)) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Hash the new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_password_query);
            $stmt->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt->execute()) {
                $success = "Mot de passe modifié avec succès.";
            } else {
                $error = "Erreur lors de la modification du mot de passe.";
            }
            $stmt->close();
        } else {
            $error = "Les mots de passe ne correspondent pas.";
        }
    } else {
        $error = "Le mot de passe actuel est incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/profil.css">

</head>

<body>
    <div class="wrapper">
        <?php include 'navbar.php'; ?>

        <main class="main-content" id="mainContent">
            <header class="content-header">
                <h1>Profil Utilisateur</h1>

            </header>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert-message success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="profile-card">
                <div class="card-body">
                    <div class="profile-grid">
                        <!-- Profile Photo -->
                        <div class="profile-photo-section">
                            <img src="https://images.unsplash.com/photo-1633332755192-727a05c4013d?q=80&w=1480&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                                alt="Photo de profil" class="profile-image">
                            <button class="btn-secondary">
                                <i class="fas fa-camera"></i>
                                Changer la photo
                            </button>
                        </div>

                        <!-- Personal Info -->
                        <div class="profile-info-section">
                            <h2>Informations personnelles</h2>
                            <form method="POST" class="profile-form">
                                <div class="form-group">
                                    <label for="name">Nom complet</label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>"
                                        class="form-input" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="email">Adresse email</label>
                                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>"
                                        class="form-input" disabled>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="settings-card">
                <div class="card-body">
                    <h2>Modifier le mot de passe</h2>
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label for="currentPassword">Mot de passe actuel</label>
                            <input type="password" id="currentPassword" name="currentPassword" class="form-input"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">Nouveau mot de passe</label>
                            <input type="password" id="newPassword" name="newPassword" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirmer le mot de passe</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-input"
                                required>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-lock"></i>
                            Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="scripts.js"></script>
    <style></style>
</body>

</html>