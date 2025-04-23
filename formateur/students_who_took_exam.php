<?php
session_start();
require_once '../db.php';

// Récupérer les groupes
$groups = [];
try {
    $stmt = $conn->prepare("SELECT Id_c, Nom_c FROM class");
    $stmt->execute();
    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Erreur lors de la récupération des groupes : " . $e->getMessage());
}

// Récupérer le groupe sélectionné
$selected_group_id = $_GET['group_id'] ?? null;

// Récupérer les étudiants du groupe sélectionné
$students = [];
if ($selected_group_id) {
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id AS student_id, u.name
            FROM users u
            WHERE u.class_id = ?
        ");
        $stmt->bind_param("i", $selected_group_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        die("Erreur lors de la récupération des étudiants : " . $e->getMessage());
    }
}

// Fonction pour récupérer les examens passés par un étudiant
function getExamsForStudent($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT e.id, e.title
        FROM exams e
        JOIN student_answers sa ON sa.exam_id = e.id
        WHERE sa.student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étudiants ayant passé des examens - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/students_who_took_exam.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="display-5 mb-3">Étudiants ayant passé un examen</h1>
                <p class="lead mb-0">Consultez la liste des étudiants et corrigez leurs examens</p>
            </div>

            <!-- Sélection du groupe -->
            <div class="content-section">
                <h2 class="section-title"><i class="fas fa-layer-group"></i> Sélectionner un groupe</h2>
                <form method="get" action="students_who_took_exam.php">
                    <select name="group_id" id="group_id" class="exam-select" required onchange="this.form.submit()">
                        <option value="">Choisir un groupe...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['Id_c']) ?>"
                                <?= ($selected_group_id == $group['Id_c']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['Nom_c']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Liste des étudiants -->
            <?php if ($selected_group_id): ?>
                <div class="content-section">
                    <h2 class="section-title"><i class="fas fa-users"></i> Étudiants du groupe</h2>
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID Étudiant</th>
                                        <th>Nom</th>
                                        <th>Examens Passés</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['name']) ?></td>
                                            <td>
                                                <ul>
                                                    <?php
                                                        $exams = getExamsForStudent($conn, $student['student_id']);
                                                        if (count($exams) > 0):
                                                            foreach ($exams as $exam):
                                                    ?>
                                                        <li>
                                                            <?= htmlspecialchars($exam['title']) ?>
                                                            <a href="corrigerExam.php?exam_id=<?= $exam['id'] ?>&student_id=<?= $student['student_id'] ?>" class="btn btn-sm btn-action">
                                                                <i class="fas fa-check-circle"></i> Corriger
                                                            </a>
                                                        </li>
                                                    <?php
                                                            endforeach;
                                                        else:
                                                            echo "<li>Aucun examen passé</li>";
                                                        endif;
                                                    ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
