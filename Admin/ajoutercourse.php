<?php
// Inclure la connexion à la base de données
include '../db.php';

$message = '';

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $file = $_FILES['file'];

    // Vérifier si tous les champs sont remplis
    if (!empty($name) && !empty($description)) {
        // Gérer le téléchargement du fichier
        if ($file['error'] === 0) {
            // Définir le répertoire de destination
            $uploadDir = 'uploads/';
            // Créer le répertoire s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Définir le chemin du fichier
            $fileName = basename($file['name']);
            $filePath = $uploadDir . $fileName;

            // Vérifier le type du fichier (optionnel)
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'mp4'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            if (!in_array($fileExtension, $allowedExtensions)) {
                $message = "Le fichier doit être un PDF, une image ou une vidéo.";
            } else {
                // Déplacer le fichier téléchargé vers le répertoire
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Préparer et exécuter la requête d'insertion
                    $sql = "INSERT INTO cours (name, description, file_path) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param("sss", $name, $description, $filePath);
                        if ($stmt->execute()) {
                            $message = "Le cours a été ajouté avec succès.";
                        } else {
                            $message = "Erreur lors de l'ajout du cours.";
                        }
                        $stmt->close();
                    } else {
                        $message = "Erreur lors de la préparation de la requête.";
                    }
                } else {
                    $message = "Erreur lors du téléchargement du fichier.";
                }
            }
        } else {
            // Si aucun fichier n'est téléchargé, insérer le cours sans fichier
            $sql = "INSERT INTO cours (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ss", $name, $description);
                if ($stmt->execute()) {
                    $message = "Le cours a été ajouté avec succès.";
                } else {
                    $message = "Erreur lors de l'ajout du cours.";
                }
                $stmt->close();
            } else {
                $message = "Erreur lors de la préparation de la requête.";
            }
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Ajouter un Cours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/ajoutercourse.css">
</head>

<style>
 
</style>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require "navbar.php"?>


            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 offset-md-3 offset-lg-2">
                <!-- Header -->
                <header class="d-flex justify-content-between align-items-center border-bottom py-3 px-4">
                    <h3>Ajouter un Nouveau Cours</h3>
                    
                </header>

                <!-- Form to Add Course -->
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card form-card">
                                <div class="card-body">
                                    <h5 class="card-title">Ajouter un Cours</h5>
                                    <?php if (!empty($message)): ?>
                                        <div class="alert alert-info text-center">
                                            <?= htmlspecialchars($message); ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nom du Cours</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Nom du cours" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Description du cours" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="file" class="form-label">Télécharger un fichier</label>
                                            <input type="file" class="form-control" id="file" name="file">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3">
        <p class="mb-0">&copy; 2024 ExamPro. Tous droits réservés.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>
