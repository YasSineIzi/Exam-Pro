<?php
// Inclure la connexion à la base de données
include '../db.php';

// Vérifier si l'ID est passé
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID d'étudiant manquant.");
}

$studentId = intval($_GET['id']);

// Récupérer les informations actuelles de l'étudiant
$sql = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Étudiant introuvable.");
}

$student = $result->fetch_assoc();

// Mettre à jour les informations si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $class = $_POST['class'];

    $updateSql = "UPDATE users SET name = ?, email = ?, password = ?, class = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ssssi", $name, $email, $password, $class, $studentId);

    if ($updateStmt->execute()) {
        echo "<div class='alert alert-success'>Étudiant mis à jour avec succès.</div>";
        // Actualiser les données
        $student['name'] = $name;
        $student['email'] = $email;
        $student['password'] = $password;
        $student['class'] = $class;
    } else {
        echo "<div class='alert alert-danger'>Erreur lors de la mise à jour : " . $updateStmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/modifier_etidiant.css">
    
</head>


<body>
    <div class="container mt-5">
        <h3>Modifier les informations de l'étudiant</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Nom</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="<?= htmlspecialchars($student['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?= htmlspecialchars($student['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="text" id="password" name="password" class="form-control"
                    value="<?= htmlspecialchars($student['password']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="class" class="form-label">Classe</label>
                <input type="text" id="class" name="class" class="form-control"
                    value="<?= htmlspecialchars($student['class']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="afficheretudiant.php" class="btn btn-secondary">Retour</a>
        </form>
    </div>
</body>

</html>