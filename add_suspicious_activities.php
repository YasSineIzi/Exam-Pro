<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin or teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header('Location: login.php');
    exit;
}

// Get list of exams
$stmt = $conn->prepare("SELECT id, title, name FROM exams ORDER BY id DESC");
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get list of students
$stmt = $conn->prepare("SELECT u.id, u.name, u.email, c.Nom_c as class_name 
                        FROM users u 
                        LEFT JOIN class c ON u.class_id = c.Id_c 
                        WHERE u.role = 'student' 
                        ORDER BY u.name");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter des activités suspectes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Nunito', sans-serif;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .card-header {
            background-color: #5c6bc0;
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
        }

        .btn-primary {
            background-color: #5c6bc0;
            border-color: #5c6bc0;
        }

        .btn-primary:hover {
            background-color: #3949ab;
            border-color: #3949ab;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Ajouter des activités suspectes pour un étudiant</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Cet outil vous permet d'ajouter des activités suspectes simulées pour un étudiant afin de tester les
                    fonctionnalités de détection de tricherie.
                </p>

                <form action="insert_suspicious_activities.php" method="get">
                    <div class="mb-3">
                        <label for="exam_id" class="form-label">Examen</label>
                        <select name="exam_id" id="exam_id" class="form-select" required>
                            <option value="">Sélectionnez un examen</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= $exam['id'] ?>">
                                    <?= htmlspecialchars(($exam['title'] ? $exam['title'] : $exam['name']) . ' (ID: ' . $exam['id'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="student_id" class="form-label">Étudiant</label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            <option value="">Sélectionnez un étudiant</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['name'] . ' (' . ($student['class_name'] ?? 'Aucune classe') . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> Cette action va ajouter plusieurs activités suspectes simulées pour
                        l'étudiant sélectionné, telles que:
                        <ul class="mb-0">
                            <li>Tentatives de copier/coller</li>
                            <li>Changements d'onglet</li>
                            <li>Utilisation d'Alt+Tab</li>
                            <li>Tentatives d'utiliser les outils de développement</li>
                            <li>Sortie du mode plein écran</li>
                            <li>et d'autres activités suspectes</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="formateur/index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Ajouter des activités suspectes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>