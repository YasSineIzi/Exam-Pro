<?php
session_start();
require_once '../db.php';

// Verify user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

// Get exam ID and student ID from URL
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;

// Check if both are provided
if (!$exam_id || !$student_id) {
    die("ID de l'examen ou de l'étudiant manquant.");
}

// Helper function to format activity types in human-readable form
if (!function_exists('formatActivityType')) {
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
}

// Helper function to format date and time
if (!function_exists('formatDateTime')) {
    function formatDateTime($dateTime)
    {
        $date = new DateTime($dateTime);
        return $date->format('d/m/Y H:i:s');
    }
}

// Helper function to format user agent info
if (!function_exists('formatUserAgent')) {
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
        .timeline {
            position: relative;
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 4px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 15px;
            padding-left: 50px;
        }

        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            background-color: #007bff;
            color: white;
            z-index: 1;
        }

        .timeline-panel {
            position: relative;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
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

        .activity-count {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .activity-chart {
            height: 300px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="corrections.php?id=<?php echo $exam_id; ?>">Corrections</a></li>
                <li class="breadcrumb-item active">Activités suspectes</li>
            </ol>
        </nav>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Activités suspectes - <?php echo htmlspecialchars($student['name']); ?></h3>
                <a href="corrections.php?id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour aux corrections
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Informations de l'examen</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Titre</th>
                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            </tr>
                            <tr>
                                <th>Enseignant</th>
                                <td><?php echo htmlspecialchars($exam['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Durée</th>
                                <td><?php echo htmlspecialchars($exam['duration']); ?> minutes</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>Informations de l'étudiant</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Nom</th>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Classe</th>
                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (empty($logs)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucune activité suspecte n'a été détectée pour cet étudiant.
                    </div>
                <?php else: ?>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card severity-<?php echo $severity; ?>">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Niveau de risque:
                                        <span
                                            class="text-<?php echo $severity === 'high' ? 'danger' : ($severity === 'medium' ? 'warning' : 'success'); ?>">
                                            <?php echo $severity === 'high' ? 'Élevé' : ($severity === 'medium' ? 'Moyen' : 'Faible'); ?>
                                        </span>
                                    </h4>
                                    <p class="card-text">
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
                                    <p class="card-text">
                                        <strong>Actions à haut risque détectées:</strong> <?php echo $high_risk_count; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>Résumé des activités</h4>
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
                                                <td><?php echo htmlspecialchars(formatActivityType($activity['activity_type'])); ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo in_array($activity['activity_type'], $high_risk_activities) ? 'danger' : 'secondary'; ?>">
                                                        <?php echo $activity['count']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(formatDateTime($activity['first_occurrence'])); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars(formatDateTime($activity['last_occurrence'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <h4>Chronologie des activités</h4>
                    <div class="timeline">
                        <?php foreach ($logs as $index => $log): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge">
                                    <i class="bi bi-<?php
                                    if (in_array($log['activity_type'], $high_risk_activities)) {
                                        echo 'exclamation-triangle';
                                    } elseif ($log['activity_type'] === 'session_start') {
                                        echo 'play-circle';
                                    } elseif ($log['activity_type'] === 'session_end') {
                                        echo 'stop-circle';
                                    } else {
                                        echo 'info-circle';
                                    }
                                    ?>"></i>
                                </div>
                                <div
                                    class="timeline-panel <?php echo in_array($log['activity_type'], $high_risk_activities) ? 'severity-medium' : ''; ?>">
                                    <div class="timeline-heading">
                                        <h5 class="timeline-title">
                                            <?php echo htmlspecialchars(formatActivityType($log['activity_type'])); ?>
                                        </h5>
                                        <p><small class="text-muted"><i class="bi bi-clock"></i>
                                                <?php echo htmlspecialchars(formatDateTime($log['created_at'])); ?></small></p>
                                    </div>
                                    <div class="timeline-body">
                                        <?php if (!empty($log['details'])): ?>
                                            <p><?php echo htmlspecialchars($log['details']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($log['user_agent'])): ?>
                                            <p><small class="text-muted">Navigateur:
                                                    <?php echo htmlspecialchars(formatUserAgent($log['user_agent'])); ?></small></p>
                                        <?php endif; ?>
                                        <?php if (!empty($log['ip_address'])): ?>
                                            <p><small class="text-muted">Adresse IP:
                                                    <?php echo htmlspecialchars($log['ip_address']); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Helper functions
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

function formatDateTime($dateTime)
{
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i:s');
}

function shortText($text, $maxLength = 30)
{
    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, $maxLength - 3) . '...';
}

function formatUserAgent($ua)
{
    if (strpos($ua, 'Mobile') !== false) {
        return 'Mobile';
    } elseif (strpos($ua, 'Tablet') !== false) {
        return 'Tablette';
    } else {
        return 'Ordinateur';
    }
}
?>