<?php
session_start();
require_once '../db.php';

// Verify user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get exam ID from URL
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;

if ($exam_id) {
    // Redirect to the exam details view in the new unified page
    header('Location: all_suspicious_activities.php?view=exam_details&exam_id=' . $exam_id);
} else {
    // Redirect to the main view if no exam ID is provided
    header('Location: all_suspicious_activities.php');
}
exit;

// Get exam info
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND formateur_id = ?");
$stmt->bind_param("ii", $exam_id, $teacher_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    header('Location: suspicious_activities.php');
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

// Get high-risk activity counts per student
$high_risk_activities = [
    'copy_attempt',
    'cut_attempt',
    'paste_attempt',
    'tab_switch',
    'alt_tab_detected',
    'dev_tools_attempt',
    'exit_fullscreen'
];

// Helper function to format activity types
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

// Helper function to determine severity based on high-risk count
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

// Helper function to format date
function formatDate($dateTime)
{
    if (empty($dateTime))
        return 'Non disponible';
    return date('d/m/Y H:i', strtotime($dateTime));
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités Suspectes - <?php echo htmlspecialchars($exam['title'] ? $exam['title'] : $exam['name']); ?>
    </title>
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
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3">Activités Suspectes</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="suspicious_activities.php">Activités Suspectes</a></li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($exam['title'] ? $exam['title'] : $exam['name']); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
                <a href="suspicious_activities.php" class="btn btn-outline-secondary">
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
                            <?php
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
                                                    <a href="view_suspicious_activities.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student['id']; ?>"
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>