<?php
session_start();
require_once '../db.php';

// Vérifie que l'utilisateur est connecté et formateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Récupère les activités suspectes liées aux examens de ce formateur
$stmt = $conn->prepare("
    SELECT eal.*, u.name AS student_name, e.title AS exam_title
    FROM exam_activity_logs eal
    JOIN users u ON eal.user_id = u.id
    JOIN exams e ON eal.exam_id = e.id
    WHERE e.formateur_id = ? AND eal.activity_type IN (
        'copy_attempt', 'cut_attempt', 'paste_attempt', 'tab_switch', 
        'alt_tab_detected', 'dev_tools_attempt', 'exit_fullscreen'
    )
    ORDER BY eal.created_at DESC
");
if (!$stmt) {
    die("Erreur SQL : " . $conn->error);
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fonction d'affichage lisible du type d'activité
function formatActivity($type) {
    $labels = [
        'copy_attempt' => 'Copie détectée',
        'cut_attempt' => 'Couper détecté',
        'paste_attempt' => 'Coller détecté',
        'tab_switch' => 'Changement d’onglet',
        'alt_tab_detected' => 'Alt+Tab détecté',
        'dev_tools_attempt' => 'Outils dev ouverts',
        'exit_fullscreen' => 'Sortie plein écran',
        'right_click_attempt' => 'Clic droit',
        'print_screen_attempt' => 'Capture écran',
        'idle_detected' => 'Inactivité',
    ];
    return $labels[$type] ?? $type;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Activités Suspectes</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <h1>🕵️ Activités suspectes détectées</h1>
    <?php if (count($activities) === 0): ?>
        <p>Aucune activité suspecte détectée pour le moment.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Examen</th>
                    <th>Type d'activité</th>
                    <th>Détails</th>
                    <th>Adresse IP</th>
                    <th>Agent utilisateur</th>
                    <th>Date/Heure</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['student_name']) ?></td>
                        <td><?= htmlspecialchars($activity['exam_title']) ?></td>
                        <td><?= formatActivity($activity['activity_type']) ?></td>
                        <td><?= nl2br(htmlspecialchars($activity['details'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars($activity['ip_address']) ?></td>
                        <td><?= htmlspecialchars(substr($activity['user_agent'], 0, 50)) ?>...</td>
                        <td><?= (new DateTime($activity['created_at']))->format('d/m/Y H:i:s') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
