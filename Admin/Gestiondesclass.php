<?php
// Inclure la connexion à la base de données
include '../db.php';

// Initialiser le message et le tableau des classes
$message = '';
$classes = [];

try {
    // Ajouter une classe
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_c'])) {
        $nom_c = trim($_POST['nom_c']);
        if (!empty($nom_c)) {
            $stmt = $conn->prepare("INSERT INTO class (Nom_c) VALUES (?)");
            $stmt->bind_param("s", $nom_c);
            if ($stmt->execute()) {
                $message = "Classe ajoutée avec succès.";
            } else {
                $message = "Erreur lors de l'ajout de la classe.";
            }
            $stmt->close();
        } else {
            $message = "Le nom de la classe est requis.";
        }
    }

    // Supprimer une classe
    if (isset($_GET['delete'])) {
        $id_c = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM class WHERE Id_c = ?");
        $stmt->bind_param("i", $id_c);
        if ($stmt->execute()) {
            $message = "Classe supprimée avec succès.";
        } else {
            $message = "Erreur lors de la suppression de la classe.";
        }
        $stmt->close();
    }

    // Récupérer toutes les classes
    $result = $conn->query("SELECT * FROM class");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes - ExamPro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/Gestiondesclass.css">
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
                    <h3>Gestion des Classes</h3>
                    
                </header>
                <div class="container mt-5">
                    <h1 class="text-center mb-4">Gestion des Classes</h1>

                    <?php if (isset($message)): ?>
                        <div class="alert alert-info text-center">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire pour ajouter une classe -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="nom_c" name="nom_c"
                                placeholder="Nom de la classe" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </form>

                    <!-- Affichage des classes -->
                    <div class="mt-5">
                        <h2 class="text-center mb-4">Liste des Classes</h2>
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom de la Classe</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($classes) > 0): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['Id_c']); ?></td>
                                            <td><?= htmlspecialchars($class['Nom_c']); ?></td>
                                            <td>
                                                <a href="?delete=<?= $class['Id_c']; ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')">Supprimer</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Aucune classe trouvée.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
</body>

</html>