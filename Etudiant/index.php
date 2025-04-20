<?php
session_start(); // Assurez-vous que la session est démarrée
// Inclure la connexion à la base de données
include '../db.php';

// Vérifier si la connexion est établie
if (!isset($conn) || $conn === null) {
    die("Erreur de connexion à la base de données.");
} else {
    // Récupérer les cours depuis la base de données
    $sql = "SELECT * FROM cours"; // Assurez-vous que la table 'cours' existe
    $result = $conn->query($sql);

    // Vérifier si des cours sont trouvés
    $courses = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    } else {
        $message = "Aucun cours trouvé.";
    }

    // Vérifier si 'formateur_id' est défini dans la session
    if (isset($_SESSION['formateur_id'])) {
        // Récupérer les examens créés par le formateur depuis la base de données
        $sql = "SELECT * FROM exams WHERE formateur_id = ?"; // Assurez-vous que la table 'exams' et la colonne 'formateur_id' existent
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['formateur_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            // Vérifier si des examens sont trouvés
            $exams = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['published'] = isset($row['published']) ? $row['published'] : 1;
                    $exams[] = $row;
                }
            } else {
                $exam_message = "Aucun examen trouvé.";
            }
        } else {
            $exam_message = "Erreur de préparation de la requête.";
        }
    } else {
        $exam_message = "Formateur non identifié.";
        $exams = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/index.css">
</head>

<style></style>

<body>
    <div class="wrapper">
        <!-- Include your redesigned navbar here -->
        <?php include 'navbar.php'; ?>

        <main class="main-content" id="mainContent">
            <header class="content-header">
                <h1>Mes cours</h1>
             
            </header>

            <!-- Courses Section -->
            <section class="courses-section">
                <?php if (isset($message)): ?>
                    <p class="error-message"><?= htmlspecialchars($message); ?></p>
                <?php endif; ?>
                
                <div class="courses-container">
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <article class="course-card">
                                <div class="card-body">
                                    <h3 class="card-title"><?= htmlspecialchars($course['name']); ?></h3>
                                    <p class="card-text"><?= htmlspecialchars($course['description']); ?></p>
                                    <?php if (!empty($course['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($course['file_path']); ?>" 
                                           class="download-button" 
                                           download>
                                            <i class="fas fa-download"></i>
                                            Télécharger le fichier
                                        </a>
                                    <?php else: ?>
                                        <p class="no-file">Aucun fichier disponible</p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    <!-- <script src="scripts.js"></script> -->
</body>

</html>