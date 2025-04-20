<?php
// Inclure la connexion à la base de données
include '../db.php';

// Initialiser un tableau pour stocker les classes
$classes = [];
if ($conn) {
    $sql = "SELECT Id_c, Nom_c FROM class"; // Utiliser Nom_c ici
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    } else {
        echo "<div class='alert alert-danger'>Erreur de requête ou aucune classe trouvée : " . $conn->error . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données : " . $conn->connect_error . "</div>";
}

// Ajouter un utilisateur
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Mot de passe brut (ou haché selon vos besoins)
    $role = $_POST['role'];
    $class = isset($_POST['class']) ? $_POST['class'] : null; // Only required if role is 'student'

    // Vérifier le rôle et affecter la classe en conséquence

    if ($conn) {
        // Préparer la requête d'insertion
        $sql = "INSERT INTO users (name, email, password, role, class) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters. If class is null (for teachers), it will be handled correctly.
            $stmt->bind_param("sssss", $name, $email, $password, $role, $class);

            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Utilisateur ajouté avec succès.</div>";
            }
            $stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Erreur de préparation : " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Utilisateur - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/AjouterÉtudiant.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php require "navbar.php" ?>


            <main class="col-md-9 col-lg-10">
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Ajouter un Utilisateur</h3>
                </header>

                <div class="container py-5">
                    <h4 class="mb-4 text-primary">Ajouter un utilisateur</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="text" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select id="role" name="role" class="form-select" onchange="toggleClassField()">
                                <option value="" selected disabled>Sélectionnez un rôle</option>
                                <option value="teacher">Enseignant</option>
                                <option value="student">Étudiant</option>
                            </select>
                        </div>
                        <div class="mb-3" id="classField" style="display: none;">
                            <label for="class" class="form-label">Classe</label>
                            <select id="class" name="class" class="form-select" required>
                                <option value="" selected disabled>Sélectionnez une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['Nom_c']; ?>"><?= $class['Nom_c']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="btn btn-primary btn-lg w-100">Ajouter
                            l'utilisateur</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // This function toggles the visibility of the 'class' field based on the role selected
        function toggleClassField() {
            var role = document.getElementById('role').value;
            var classField = document.getElementById('classField');

            // If the role is 'student', show the 'class' field, otherwise hide it
            classField.style.display = (role === 'student') ? 'block' : 'none';
        }
    </script>
</body>

</html>