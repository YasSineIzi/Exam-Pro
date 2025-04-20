<?php
// Inclure la connexion à la base de données
include '../db.php';  // Assurez-vous que le chemin est correct

$students = [];
$section = ""; // Initialisation de la variable section
$classes = []; // Tableau pour stocker les classes

// Requête pour récupérer les classes distinctes
$sql_classes = "SELECT DISTINCT class FROM users";
$result_classes = $conn->query($sql_classes);

if ($result_classes->num_rows > 0) {
    // Récupérer les classes
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row['class'];
    }
}

// Si une section est sélectionnée
if (isset($_POST['section']) && !empty($_POST['section'])) {
    $section = $_POST['section'];

    // Requête pour récupérer les étudiants selon la classe
    $sql = "SELECT * FROM users WHERE class = ? AND role = 'student'";
    $stmt = $conn->prepare($sql);  // Utilisation de $conn avec mysqli

    if ($stmt) {
        $stmt->bind_param("s", $section);  // Lier la variable
        $stmt->execute();  // Exécuter la requête
        $result = $stmt->get_result();  // Obtenir les résultats

        // Récupérer les étudiants
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    } else {
        echo "Erreur de requête : " . $conn->error;
    }
}

// Suppression d'un étudiant
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $studentId = $_GET['delete'];

    // Requête pour supprimer l'étudiant
    $sql = "DELETE FROM users WHERE id = ? AND role = 'student'";
    $stmt = $conn->prepare($sql);  // Utilisation de $conn avec mysqli

    if ($stmt) {
        $stmt->bind_param("i", $studentId);  // Lier l'id
        $stmt->execute();  // Exécuter la requête
        $stmt->close();

        // Redirection après suppression
        header("Location: afficheretudiant.php?section=" . $section);
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
    <link rel="stylesheet" href="style/styleafficheEtudiant.css">
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
                    <h3>Gestion des Étudiants</h3>
                    
                </header>

                <!-- Section Selection -->
                <div class="container mt-4">
                    <div class="mb-3">
                        <form method="POST" action="">
                            <label for="section" class="form-label">Choisissez une classe</label>
                            <select id="section" name="section" class="form-select" onchange="this.form.submit()">
                                <option value="" selected disabled>Sélectionnez une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class ?>" <?= isset($section) && $section == $class ? 'selected' : '' ?>><?= $class ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <!-- Liste des étudiants -->
                    <div id="student-list" class="mt-4">
                        <h5>Liste des étudiants</h5>
                        <?php if (!empty($students)): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>id</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Mot de passe</th>
                                        <th>Classe</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['id']) ?></td>
                                            <td><?= htmlspecialchars($student['name']) ?></td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= htmlspecialchars($student['password']) ?></td>
                                            <td><?= htmlspecialchars($student['class']) ?></td>
                                            <td>
                                                <a href="modifier_etudiant.php?id=<?= $student['id'] ?>"
                                                    class="btn btn-warning btn-sm">Modifier</a>
                                                <a href="?delete=<?= $student['id'] ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')">Supprimer</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning">Aucun étudiant trouvé pour cette classe.</div>
                        <?php endif; ?>
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