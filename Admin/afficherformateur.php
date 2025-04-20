<?php
// Inclure la connexion à la base de données
include '../db.php';  // Vérifiez que le chemin est correct

$formateurs = [];

// Requête pour récupérer les formateurs
$sql = "SELECT * FROM users WHERE role = 'teacher'";
$result = $conn->query($sql);  // Utilisation de MySQLi

if ($result->num_rows > 0) {
    // Récupérer les formateurs
    while ($row = $result->fetch_assoc()) {
        $formateurs[] = $row;
    }
} else {
    echo "Aucun formateur trouvé.";
}

// Suppression d'un formateur
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $formateurId = $_GET['delete'];

    // Requête pour supprimer le formateur
    $sql_delete = "DELETE FROM users WHERE id = ? AND role = 'teacher'";
    $stmt = $conn->prepare($sql_delete);  // Utilisation de MySQLi avec prepared statements

    if ($stmt) {
        $stmt->bind_param("i", $formateurId);  // Lier l'id du formateur
        $stmt->execute();  // Exécuter la requête
        $stmt->close();

        // Redirection après suppression
        header("Location: Gestionutilisateurs.php");
        exit();
    } else {
        echo "Erreur lors de la suppression : " . $conn->error;
    }
}
?>

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
    <link rel="stylesheet" href="style/afficherformateur.css">
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
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Gestion des Formateurs</h3>
                    
                </header>

                <!-- Section: Liste des formateurs -->
                <section class="container py-4">
                    <h4 class="mb-4 text-primary">Afficher les formateurs</h4>

                    <?php if (!empty($formateurs)): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Mot de passe</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formateurs as $formateur): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($formateur['id']) ?></td>
                                        <td><?= htmlspecialchars($formateur['name']) ?></td>
                                        <td><?= htmlspecialchars($formateur['email']) ?></td>
                                        <td><?= htmlspecialchars($formateur['password']) ?></td>
                                        <td>
                                            <!-- Modifier le formateur -->
                                            <a href="modifier_formateur.php?id=<?= $formateur['id'] ?>"
                                                class="btn btn-warning btn-sm">Modifier</a>
                                            <!-- Supprimer le formateur -->
                                            <a href="?delete=<?= $formateur['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce formateur ?')">Supprimer</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">Aucun formateur trouvé.</div>
                    <?php endif; ?>
                </section>
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