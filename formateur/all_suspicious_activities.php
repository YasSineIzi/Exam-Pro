<?php
session_start();
require_once '../db.php';

// Verify user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get view mode from URL or set default to 'exams'
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'exams';
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;

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
    if (empty($dateTime))
        return 'Non disponible';
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i:s');
}

function formatDate($dateTime)
{
    if (empty($dateTime))
        return 'Non disponible';
    return date('d/m/Y H:i', strtotime($dateTime));
}

function formatUserAgent($ua)
{
    if (empty($ua))
        return 'Non disponible';

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

function getSeverity($high_risk_count)
{
    if ($high_risk_count >= 10) {
        return [
            'level' => 'high',
            'text' => 'Élevé',
            'class' => 'danger'
        ];
    } elseif ($high_risk_count >= 5) {
        return [
            'level' => 'medium',
            'text' => 'Moyen',
            'class' => 'warning'
        ];
    } else {
        return [
            'level' => 'low',
            'text' => 'Faible',
            'class' => 'success'
        ];
    }
}

// Define high-risk activities
$high_risk_activities = [
    'copy_attempt',
    'cut_attempt',
    'paste_attempt',
    'tab_switch',
    'alt_tab_detected',
    'dev_tools_attempt',
    'exit_fullscreen',
    'attempted_page_exit'  // Add explicit exit attempt
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités Suspectes - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-left: 280px;
        }

        .main-content {
            padding: 20px;
        }

        @media (max-width: 768px) {
            body {
                padding-left: 90px;
            }
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 15px 20px;
        }

        .activity-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-right: 10px;
            background-color: #dc3545;
            color: white;
        }

        .table th {
            font-weight: 600;
            white-space: nowrap;
        }

        .activity-alert {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .severity-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .severity-high {
            background-color: #dc3545;
        }

        .severity-medium {
            background-color: #ffc107;
        }

        .severity-low {
            background-color: #28a745;
        }

        .timeline-item {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: white;
            border: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Activités Suspectes</h1>
            </div>

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $view_mode == 'exams' ? 'active' : ''; ?>" href="?view=exams">
                        <i class="bi bi-list-task"></i> Liste des examens
                    </a>
                </li>
                <?php if ($exam_id && $view_mode == 'exam_details'): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="?view=exam_details&exam_id=<?php echo $exam_id; ?>">
                            <i class="bi bi-people"></i> Étudiants pour l'examen
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($exam_id && $student_id && $view_mode == 'student_details'): ?>
                    <li class="nav-item">
                        <a class="nav-link active"
                            href="?view=student_details&exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>">
                            <i class="bi bi-person-exclamation"></i> Détails des activités
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($view_mode == 'exams'): ?>
                <!-- EXAMS LIST VIEW -->
                <?php
                // Get all exams for this teacher with suspicious activities
                $sql = "
                SELECT e.id, e.title, e.name, e.start_date, e.end_date, 
                       COUNT(DISTINCT eal.user_id) as students_with_activities,
                       COUNT(eal.id) as total_activities
                FROM exams e
                JOIN exam_activity_logs eal ON e.id = eal.exam_id
                WHERE e.formateur_id = ?
                GROUP BY e.id
                ORDER BY e.start_date DESC
            ";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("Erreur SQL (requête examens) : " . $conn->error);
                }
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $exams_with_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Get student names with prohibited activities for each exam
                $exams_with_students = [];
                foreach ($exams_with_activities as $exam) {
                    $sql = "
                    SELECT DISTINCT u.id, u.name, 
                           COUNT(eal.id) as activity_count,
                           MAX(eal.created_at) as last_activity
                    FROM exam_activity_logs eal
                    JOIN users u ON eal.user_id = u.id
                    WHERE eal.exam_id = ? AND eal.activity_type IN (
                        'copy_attempt', 'cut_attempt', 'paste_attempt', 'tab_switch', 
                        'alt_tab_detected', 'dev_tools_attempt', 'exit_fullscreen'
                    )
                    GROUP BY u.id
                    ORDER BY activity_count DESC
                    LIMIT 5
                ";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        die("Erreur SQL (requête étudiants) : " . $conn->error);
                    }
                    $stmt->bind_param("i", $exam['id']);
                    $stmt->execute();
                    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    $exam['students'] = $students;
                    $exams_with_students[] = $exam;
                }

                // Get recent suspicious activities across all exams
                $stmt = $conn->prepare("
                SELECT eal.*, u.name as student_name, e.title as exam_title, e.name as exam_name
                FROM exam_activity_logs eal
                JOIN users u ON eal.user_id = u.id
                JOIN exams e ON eal.exam_id = e.id
                WHERE e.formateur_id = ? AND eal.activity_type IN (
                    'copy_attempt', 'cut_attempt', 'paste_attempt', 'tab_switch', 
                    'alt_tab_detected', 'dev_tools_attempt', 'exit_fullscreen'
                )
                ORDER BY eal.created_at DESC
                LIMIT 10
            ");
                if (!$stmt) {
                    die("Erreur SQL (requête activités récentes) : " . $conn->error);
                }
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>

                <?php if (empty($exams_with_activities)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucune activité suspecte n'a été détectée pour vos examens.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Examens avec Activités Suspectes</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Examen</th>
                                                    <th>Date</th>
                                                    <th>Étudiants impliqués</th>
                                                    <th>Détails</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($exams_with_students)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Aucune activité suspecte détectée</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($exams_with_students as $exam): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($exam['title'] ?: $exam['name']); ?></td>
                                                            <td><?php echo date('d/m/Y', strtotime($exam['start_date'])); ?></td>
                                                            <td>
                                                                <?php if (!empty($exam['students'])): ?>
                                                                    <ul class="list-unstyled mb-0">
                                                                        <?php foreach ($exam['students'] as $student): ?>
                                                                            <li>
                                                                                <span
                                                                                    class="text-primary"><?php echo htmlspecialchars($student['name']); ?></span>
                                                                                <span
                                                                                    class="badge bg-danger"><?php echo $student['activity_count']; ?>
                                                                                    activités</span>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                    <?php if ($exam['students_with_activities'] > count($exam['students'])): ?>
                                                                        <small class="text-muted">+
                                                                            <?php echo $exam['students_with_activities'] - count($exam['students']); ?>
                                                                            autres étudiants</small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Aucun étudiant</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="?view=exam_details&exam_id=<?php echo $exam['id']; ?>"
                                                                    class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-eye"></i> Voir détails
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Activités suspectes récentes</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php if (empty($recent_activities)): ?>
                                            <div class="list-group-item">
                                                <p class="mb-0 text-muted">Aucune activité récente.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($recent_activities as $activity): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">
                                                            <?php echo htmlspecialchars(formatActivityType($activity['activity_type'])); ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <?php echo formatDateTime($activity['created_at']); ?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong>
                                                        dans l'examen
                                                        <strong><?php echo htmlspecialchars($activity['exam_title'] ? $activity['exam_title'] : $activity['exam_name']); ?></strong>
                                                    </p>
                                                    <small>
                                                        <a
                                                            href="?view=student_details&exam_id=<?php echo $activity['exam_id']; ?>&student_id=<?php echo $activity['user_id']; ?>">
                                                            Voir l'analyse complète
                                                        </a>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Statistiques</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="activity-badge bg-danger">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Total activités à haut risque</h6>
                                            <?php
                                            $stmt = $conn->prepare("
                                            SELECT COUNT(*) as count
                                            FROM exam_activity_logs eal
                                            JOIN exams e ON eal.exam_id = e.id
                                            WHERE e.formateur_id = ? AND eal.activity_type IN (
                                                'copy_attempt', 'cut_attempt', 'paste_attempt', 'tab_switch', 
                                                'alt_tab_detected', 'dev_tools_attempt', 'exit_fullscreen'
                                            )
                                        ");
                                            $stmt->bind_param("i", $teacher_id);
                                            $stmt->execute();
                                            $high_risk_count = $stmt->get_result()->fetch_assoc()['count'];
                                            ?>
                                            <p class="h3 mb-0"><?php echo $high_risk_count; ?></p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="activity-badge bg-warning text-dark">
                                                <i class="bi bi-people-fill"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Étudiants avec activités suspectes</h6>
                                            <?php
                                            $stmt = $conn->prepare("
                                            SELECT COUNT(DISTINCT eal.user_id) as count
                                            FROM exam_activity_logs eal
                                            JOIN exams e ON eal.exam_id = e.id
                                            WHERE e.formateur_id = ?
                                        ");
                                            $stmt->bind_param("i", $teacher_id);
                                            $stmt->execute();
                                            $students_count = $stmt->get_result()->fetch_assoc()['count'];
                                            ?>
                                            <p class="h3 mb-0"><?php echo $students_count; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($view_mode == 'exam_details'): ?>
                <!-- EXAM DETAILS VIEW -->
                <?php
                // Get exam info
                $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND formateur_id = ?");
                $stmt->bind_param("ii", $exam_id, $teacher_id);
                $stmt->execute();
                $exam = $stmt->get_result()->fetch_assoc();

                if (!$exam) {
                    echo '<div class="alert alert-danger">Examen non trouvé ou non autorisé.</div>';
                    echo '<div class="text-center mt-3"><a href="?view=exams" class="btn btn-primary">Retour à la liste</a></div>';
                    exit;
                }

                // Get students with suspicious activities for this exam
                $stmt = $conn->prepare("
                SELECT u.id, u.name, u.email, c.Nom_c as class_name, 
                       COUNT(eal.id) as activity_count,
                       GROUP_CONCAT(DISTINCT eal.activity_type) as activity_types,
                       MAX(eal.created_at) as last_activity
                FROM users u
                JOIN exam_activity_logs eal ON u.id = eal.user_id
                LEFT JOIN class c ON u.class_id = c.Id_c
                WHERE eal.exam_id = ?
                GROUP BY u.id
                ORDER BY activity_count DESC
            ");
                $stmt->bind_param("i", $exam_id);
                $stmt->execute();
                $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Get summary statistics
                $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT eal.user_id) as students_count,
                    COUNT(eal.id) as activities_count,
                    COUNT(CASE WHEN eal.activity_type IN ('copy_attempt', 'cut_attempt', 'paste_attempt', 'tab_switch', 'alt_tab_detected', 'dev_tools_attempt', 'exit_fullscreen') THEN 1 END) as high_risk_count
                FROM exam_activity_logs eal
                WHERE eal.exam_id = ?
            ");
                $stmt->bind_param("i", $exam_id);
                $stmt->execute();
                $summary = $stmt->get_result()->fetch_assoc();
                ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="?view=exams">Activités Suspectes</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo htmlspecialchars($exam['title'] ? $exam['title'] : $exam['name']); ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <a href="?view=exams" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations de l'examen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">Titre:</th>
                                        <td><?php echo htmlspecialchars($exam['title'] ? $exam['title'] : $exam['name']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?php echo !empty($exam['start_date']) ? formatDate($exam['start_date']) : 'Non définie'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Durée:</th>
                                        <td><?php echo htmlspecialchars($exam['duration']); ?> minutes</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-primary"><?php echo $summary['students_count']; ?></h4>
                                            <p class="mb-0 text-muted">Étudiants</p>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-danger"><?php echo $summary['high_risk_count']; ?></h4>
                                            <p class="mb-0 text-muted">Act. à risque</p>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-info"><?php echo $summary['activities_count']; ?></h4>
                                            <p class="mb-0 text-muted">Total activités</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Étudiants avec activités suspectes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Aucune activité suspecte n'a été détectée pour cet examen.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Classe</th>
                                            <th>Niveau de risque</th>
                                            <th>Activités</th>
                                            <th>Dernière activité</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student):
                                            // Calculate high risk count for this student
                                            $high_risk_count = 0;
                                            $activity_types = explode(',', $student['activity_types']);
                                            foreach ($activity_types as $type) {
                                                if (in_array($type, $high_risk_activities)) {
                                                    $high_risk_count++;
                                                }
                                            }

                                            // Get severity
                                            $severity = getSeverity($high_risk_count);
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($student['name']); ?>
                                                    <small
                                                        class="d-block text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['class_name'] ?? 'Non spécifiée'); ?></td>
                                                <td>
                                                    <div>
                                                        <span
                                                            class="severity-indicator severity-<?php echo $severity['level']; ?>"></span>
                                                        <span
                                                            class="text-<?php echo $severity['class']; ?>"><?php echo $severity['text']; ?></span>
                                                    </div>
                                                    <small class="text-muted"><?php echo $high_risk_count; ?> activités à haut
                                                        risque</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $severity['class']; ?>">
                                                        <?php echo $student['activity_count']; ?> activités
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($student['last_activity']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="?view=student_details&exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> Détails
                                                        </a>
                                                        <a href="corrigerExam.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student['id']; ?>"
                                                            class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-pencil"></i> Corriger
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($view_mode == 'student_details'): ?>
                <!-- STUDENT DETAILS VIEW -->
                <?php
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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="?view=exams">Activités Suspectes</a></li>
                                <li class="breadcrumb-item"><a href="?view=exam_details&exam_id=<?php echo $exam_id; ?>">
                                        <?php echo htmlspecialchars($exam['title'] ? $exam['title'] : $exam['name']); ?>
                                    </a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <a href="?view=exam_details&exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à l'examen
                    </a>
                </div>

                <div class="row mb-4">
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
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Résumé des activités suspectes</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="activity-badge bg-<?php echo $severity == 'high' ? 'danger' : ($severity == 'medium' ? 'warning' : 'success'); ?>">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Niveau de risque</h6>
                                        <p
                                            class="h5 mb-0 text-<?php echo $severity == 'high' ? 'danger' : ($severity == 'medium' ? 'warning' : 'success'); ?>">
                                            <?php echo $severity == 'high' ? 'Élevé' : ($severity == 'medium' ? 'Moyen' : 'Faible'); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <p><strong>Activités à haut risque:</strong> <?php echo $high_risk_count; ?></p>
                                <p><strong>Total des activités:</strong> <?php echo count($logs); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Détail des activités</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="alert alert-info">Aucune activité enregistrée.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date et heure</th>
                                            <th>Type d'activité</th>
                                            <th>Navigateur</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log):
                                            $is_high_risk = in_array($log['activity_type'], $high_risk_activities);
                                            ?>
                                            <tr class="<?php echo $is_high_risk ? 'table-danger' : ''; ?>">
                                                <td><?php echo formatDateTime($log['created_at']); ?></td>
                                                <td>
                                                    <?php if ($is_high_risk): ?>
                                                        <span class="badge bg-danger">
                                                            <?php echo formatActivityType($log['activity_type']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?php echo formatActivityType($log['activity_type']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatUserAgent($log['user_agent']); ?></td>
                                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>