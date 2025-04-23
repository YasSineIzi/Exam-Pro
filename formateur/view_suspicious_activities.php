<?php
session_start();
require_once '../db.php';

// Verify user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// Get exam ID and student ID from URL
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;

if ($exam_id && $student_id) {
    // Redirect to the student details view in the new unified page
    header('Location: all_suspicious_activities.php?view=student_details&exam_id=' . $exam_id . '&student_id=' . $student_id);
} elseif ($exam_id) {
    // Redirect to the exam details view if only exam ID is provided
    header('Location: all_suspicious_activities.php?view=exam_details&exam_id=' . $exam_id);
} else {
    // Redirect to the main view if no IDs are provided
    header('Location: all_suspicious_activities.php');
}
exit;

// Helper function to format activity types in human-readable form
function formatActivityType($type)
{
    $types = [
        'session_start' => 'Début de session',
        'session_end' => 'Fin de session',
        'copy_attempt' => 'Tentative de copie',
        'cut_attempt' => 'Tentative de couper',
        'paste_attempt' => 'Tentative de coller',
        'tab_switch' => 'Changement d\'onglet',
        'returned_to_exam' => 'Retour à l\'examen',
        'right_click_attempt' => 'Clic droit',
        'print_screen_attempt' => 'Capture d\'écran',
        'print_attempt' => 'Tentative d\'impression',
        'alt_tab_detected' => 'Alt+Tab détecté',
        'dev_tools_attempt' => 'Outils développeur',
        'exit_fullscreen' => 'Sortie plein écran',
        'idle_detected' => 'Inactivité détectée',
        'suspicious_resize' => 'Redimensionnement suspect',
        'attempted_page_exit' => 'Tentative de quitter',
        'max_warnings_exceeded' => 'Avertissements max',
    ];

    return $types[$type] ?? $type;
}

// Helper function to format date and time
function formatDateTime($dateTime)
{
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i:s');
}

// Helper function to format user agent info
function formatUserAgent($ua)
{
    if (empty($ua))
        return 'Non disponible';

    // Extract basic browser and OS info
    $browser = 'Navigateur inconnu';
    $os = 'Système inconnu';

    if (strpos($ua, 'Firefox') !== false)
        $browser = 'Firefox';
    elseif (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') !== false)
        $browser = 'Edge';
    elseif (strpos($ua, 'Chrome') !== false)
        $browser = 'Chrome';
    elseif (strpos($ua, 'Safari') !== false)
        $browser = 'Safari';
    elseif (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident') !== false)
        $browser = 'Internet Explorer';
    elseif (strpos($ua, 'Opera') !== false)
        $browser = 'Opera';

    if (strpos($ua, 'Windows') !== false)
        $os = 'Windows';
    elseif (strpos($ua, 'Mac') !== false)
        $os = 'Mac OS';
    elseif (strpos($ua, 'Linux') !== false)
        $os = 'Linux';
    elseif (strpos($ua, 'Android') !== false)
        $os = 'Android';
    elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false)
        $os = 'iOS';

    return "$browser sur $os";
}

// Get exam and student info
try {
    // Get exam info
    $stmt = $conn->prepare("SELECT title, name, duration FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();

    if (!$exam) {
        die("Examen non trouvé.");
    }

    // Get student info
    $stmt = $conn->prepare("SELECT name, email, class_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student) {
        die("Étudiant non trouvé.");
    }

    // Get class name
    if ($student['class_id']) {
        $stmt = $conn->prepare("SELECT Nom_c FROM class WHERE Id_c = ?");
        $stmt->bind_param("i", $student['class_id']);
        $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();
        $student['class_name'] = $class ? $class['Nom_c'] : 'Non spécifié';
    } else {
        $student['class_name'] = 'Non spécifié';
    }

    // Get all suspicious activities
    if ($conn->query("SHOW TABLES LIKE 'exam_activity_logs'")->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT *
            FROM exam_activity_logs 
            WHERE user_id = ? AND exam_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->bind_param("ii", $student_id, $exam_id);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $logs = [];
    }

    // Get summary of suspicious activities
    $stmt = $conn->prepare("
        SELECT activity_type, COUNT(*) as count, 
               MIN(created_at) as first_occurrence, 
               MAX(created_at) as last_occurrence
        FROM exam_activity_logs 
        WHERE user_id = ? AND exam_id = ?
        GROUP BY activity_type 
        ORDER BY count DESC
    ");
    $stmt->bind_param("ii", $student_id, $exam_id);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get potential severity level
    $severity = 'low';
    $high_risk_activities = [
        'copy_attempt',
        'cut_attempt',
        'paste_attempt',
        'tab_switch',
        'alt_tab_detected',
        'dev_tools_attempt',
        'exit_fullscreen'
    ];
    $high_risk_count = 0;

    foreach ($summary as $activity) {
        if (in_array($activity['activity_type'], $high_risk_activities)) {
            $high_risk_count += $activity['count'];
        }
    }

    if ($high_risk_count >= 10) {
        $severity = 'high';
    } elseif ($high_risk_count >= 5) {
        $severity = 'medium';
    }

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités suspectes - <?php echo htmlspecialchars($student['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .timeline-item {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: white;
            border: 1px solid #dee2e6;
        }

        .severity-low {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .severity-medium {
            background-color: #fff3cd;
            border-color: #ffeeba;
        }

        .severity-high {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Activités suspectes - <?php echo htmlspecialchars($student['name']); ?></h1>
            <a href="corrigerExam.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>"
                class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la correction
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informations de l'examen</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Titre:</strong> <?php echo htmlspecialchars($exam['title'] ?: $exam['name']); ?></p>
                        <p><strong>Durée:</strong> <?php echo htmlspecialchars($exam['duration']); ?> minutes</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informations de l'étudiant</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nom:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Classe:</strong> <?php echo htmlspecialchars($student['class_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucune activité suspecte n'a été détectée pour cet étudiant.
            </div>
        <?php else: ?>
            <div class="card severity-<?php echo $severity; ?> mb-4">
                <div class="card-body">
                    <h4>
                        <i class="bi bi-exclamation-triangle"></i>
                        Niveau de risque:
                        <span
                            class="text-<?php echo $severity === 'high' ? 'danger' : ($severity === 'medium' ? 'warning' : 'success'); ?>">
                            <?php echo $severity === 'high' ? 'Élevé' : ($severity === 'medium' ? 'Moyen' : 'Faible'); ?>
                        </span>
                    </h4>
                    <p>
                        <?php
                        if ($severity === 'high') {
                            echo "L'activité de cet étudiant comporte de nombreuses actions suspectes qui méritent une attention particulière.";
                        } elseif ($severity === 'medium') {
                            echo "Quelques activités suspectes ont été détectées pour cet étudiant. Une vérification supplémentaire est recommandée.";
                        } else {
                            echo "Peu d'activités suspectes ont été détectées. Le risque de fraude est considéré comme faible.";
                        }
                        ?>
                    </p>
                    <p><strong>Actions à haut risque détectées:</strong> <?php echo $high_risk_count; ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Résumé des activités</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type d'activité</th>
                                    <th>Nombre</th>
                                    <th>Première occurrence</th>
                                    <th>Dernière occurrence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(formatActivityType($activity['activity_type'])); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo in_array($activity['activity_type'], $high_risk_activities) ? 'danger' : 'secondary'; ?>">
                                                <?php echo $activity['count']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(formatDateTime($activity['first_occurrence'])); ?></td>
                                        <td><?php echo htmlspecialchars(formatDateTime($activity['last_occurrence'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Chronologie des activités</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($logs as $log): ?>
                        <div
                            class="timeline-item <?php echo in_array($log['activity_type'], $high_risk_activities) ? 'severity-medium' : ''; ?>">
                            <div class="d-flex justify-content-between">
                                <h5><?php echo htmlspecialchars(formatActivityType($log['activity_type'])); ?></h5>
                                <small class="text-muted"><i class="bi bi-clock"></i>
                                    <?php echo htmlspecialchars(formatDateTime($log['created_at'])); ?></small>
                            </div>
                            <?php if (!empty($log['details'])): ?>
                                <p><?php echo htmlspecialchars($log['details']); ?></p>
                            <?php endif; ?>
                            <div class="text-muted small">
                                <?php if (!empty($log['user_agent'])): ?>
                                    <div>Navigateur: <?php echo htmlspecialchars(formatUserAgent($log['user_agent'])); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($log['ip_address'])): ?>
                                    <div>Adresse IP: <?php echo htmlspecialchars($log['ip_address']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>