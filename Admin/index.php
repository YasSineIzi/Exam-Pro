<?php
// Inclure la connexion à la base de données
include '../db.php';

// Initialiser les variables
$nombreFormateurs = 0;
$nombreEtudiants = 0;

try {
    // Compter le nombre de formateurs
    $resultFormateurs = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'");
    if ($resultFormateurs) {
        $row = $resultFormateurs->fetch_assoc();
        $nombreFormateurs = $row['total'];
    }

    // Compter le nombre d'étudiants
    $resultEtudiants = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
    if ($resultEtudiants) {
        $row = $resultEtudiants->fetch_assoc();
        $nombreEtudiants = $row['total'];
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Gestion des Étudiants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/styleindex.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require "navbar.php"?>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 offset-md-3 offset-lg-2">
                <!-- Header -->
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Gestion des Étudiants</h3>
                    <div class="d-flex align-items-center text-primary">
                    <a href="profil.php" class="nav-link">  <i class="fas fa-user profile-icon"></i></a>
                       

                    </div>
                </header>

                <!-- User Management -->
                <div class="container py-5">
                    <!-- User Stats Cards -->
                    <div class="row">
                        <!-- Formateur Count -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Nombre de Formateurs</h5>
                                    <h1 class="display-4"><?= $nombreFormateurs ?></h1>
                                    <i class="fa-solid fa-user-graduate fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Etudiant Count -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Nombre des Étudiants</h5>
                                    <h1 class="display-4"><?= $nombreEtudiants ?></h1>
                                    <i class="fa-solid fa-user fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Users List Table -->
                </div>
            </main>
        </div>
    </div>

    <footer class="text-center py-3">
        <p class="mb-0">&copy; 2024 ExamPro. Tous droits réservés.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>