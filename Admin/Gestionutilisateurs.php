<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - ExamPro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/Gestionutilisateurs.css">
</head>

<style>

</style>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require "navbar.php"?>


            <!-- Main Content -->
            <main class="col-md-9 col-lg-10">
                <!-- Header -->
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Gestion des Étudiants</h3>
                    
                </header>

                <!-- User Management -->
                <div class="container py-5">
                    <div class="d-flex justify-content-center">
                        <a href="afficheretudiant.php" class="btn btn-primary me-3">
                            <i class="fas fa-calendar-alt"></i> Afficher les Étudiants
                        </a>
                        <a href="afficherformateur.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Afficher les Formateurs
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="bg-light text-center py-3 mt-5">
        <p class="mb-0">&copy; 2024 ExamPro. Tous droits réservés.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>

</html>