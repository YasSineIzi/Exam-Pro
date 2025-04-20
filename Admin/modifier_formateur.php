<?php
// Inclure la connexion à la base de données
include '../db.php';

// Vérifier si un ID de formateur est passé
if (isset($_GET['id'])) {
    $formateurId = $_GET['id'];

    // Récupérer les informations actuelles du formateur
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'teacher'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $formateurId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $formateur = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Formateur introuvable.</div>";
        exit();
    }
} else {
    echo "<div class='alert alert-danger'>Aucun ID de formateur spécifié.</div>";
    exit();
}

// Mettre à jour les informations du formateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sqlUpdate = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("sssi", $name, $email, $password, $formateurId);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Formateur mis à jour avec succès.</div>";
        // Actualiser les données affichées
        $formateur['name'] = $name;
        $formateur['email'] = $email;
        $formateur['password'] = $password;
    } else {
        echo "<div class='alert alert-danger'>Erreur lors de la mise à jour : " . $stmt->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Formateur - ExamPro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/modifier_formateur.css">
</head>
<style>

</style>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Modifier le Formateur</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?= htmlspecialchars($formateur['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($formateur['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="text" id="password" name="password" class="form-control"
                                    value="<?= htmlspecialchars($formateur['password']) ?>" required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-success">Mettre à jour</button>
                                <a href="afficherformateur.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>