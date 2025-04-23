<?php
session_start();
require_once '../db.php';

// Verify user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

// Get exam ID if provided
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;

// Get student ID if provided
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Get list of exams for the filter
$exams = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, title FROM exams 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get list of students who have suspicious activity logs
$students = [];
try {
    $params = [];
    $sql = "
        SELECT DISTINCT u.id, u.name, u.email, c.Nom_c as class_name
        FROM exam_activity_logs l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN class c ON u.class_id = c.Id_c
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    $params[] = $_SESSION['user_id'];
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    $sql .= " ORDER BY u.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get activity logs based on filters
$logs = [];
try {
    $params = [];
    $sql = "
        SELECT l.*, 
            u.name as student_name,
            e.title as exam_title
        FROM exam_activity_logs l
        JOIN users u ON l.user_id = u.id
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    $params[] = $_SESSION['user_id'];
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    if ($student_id) {
        $sql .= " AND l.user_id = ?";
        $params[] = $student_id;
    }
    
    $sql .= " ORDER BY l.created_at DESC LIMIT 1000";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get summary statistics
$summary = [
    'total_logs' => 0,
    'total_students' => 0,
    'activities_by_type' => [],
    'activity_count_by_exam' => [],
    'top_offenders' => []
];

try {
    // Total logs
    $params = [$_SESSION['user_id']];
    $sql = "
        SELECT COUNT(*) as count
        FROM exam_activity_logs l
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    if ($student_id) {
        $sql .= " AND l.user_id = ?";
        $params[] = $student_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $summary['total_logs'] = $result ? $result['count'] : 0;
    
    // Total students with suspicious activity
    $params = [$_SESSION['user_id']];
    $sql = "
        SELECT COUNT(DISTINCT l.user_id) as count
        FROM exam_activity_logs l
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    if ($student_id) {
        $sql .= " AND l.user_id = ?";
        $params[] = $student_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $summary['total_students'] = $result ? $result['count'] : 0;
    
    // Activities by type
    $params = [$_SESSION['user_id']];
    $sql = "
        SELECT l.activity_type, COUNT(*) as count
        FROM exam_activity_logs l
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    if ($student_id) {
        $sql .= " AND l.user_id = ?";
        $params[] = $student_id;
    }
    
    $sql .= " GROUP BY l.activity_type ORDER BY count DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary['activities_by_type'] = $stmt->fetchAll();
    
    // Activity count by exam
    $params = [$_SESSION['user_id']];
    $sql = "
        SELECT e.id, e.title, COUNT(*) as count
        FROM exam_activity_logs l
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    
    if ($student_id) {
        $sql .= " AND l.user_id = ?";
        $params[] = $student_id;
    }
    
    $sql .= " GROUP BY e.id ORDER BY count DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary['activity_count_by_exam'] = $stmt->fetchAll();
    
    // Top offenders
    $params = [$_SESSION['user_id']];
    $sql = "
        SELECT u.id, u.name, c.Nom_c as class_name, COUNT(*) as count
        FROM exam_activity_logs l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN class c ON u.class_id = c.Id_c
        JOIN exams e ON l.exam_id = e.id
        WHERE e.user_id = ?
    ";
    
    if ($exam_id) {
        $sql .= " AND l.exam_id = ?";
        $params[] = $exam_id;
    }
    
    $sql .= " GROUP BY u.id ORDER BY count DESC LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary['top_offenders'] = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités suspectes - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --primary-bg-light: #EEF2FF;
        }
        
        body {
            background-color: #f9fafb;
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .activity-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
            background-color: #fff;
            transition: all 0.2s;
        }
        
        .activity-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .activity-title {
            font-weight: 600;
            color: #1F2937;
        }
        
        .activity-date {
            color: #6B7280;
            font-size: 0.875rem;
        }
        
        .activity-meta {
            display: flex;
            gap: 10px;
            color: #6B7280;
            font-size: 0.875rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .activity-type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: var(--primary-bg-light);
            color: var(--primary-color);
            margin-right: 5px;
        }
        
        .filter-card {
            position: sticky;
            top: 20px;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: #fff;
            margin-bottom: 10px;
        }
        
        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: var(--primary-bg-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .summary-content {
            flex-grow: 1;
        }
        
        .summary-title {
            font-size: 0.875rem;
            color: #6B7280;
            margin-bottom: 2px;
        }
        
        .summary-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1F2937;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f3f4f6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 12px 15px;
            color: #4B5563;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="main-content">
            <div class="row">
                <div class="col-12 mb-4">
                    <h1 class="h3 mb-3">Activités suspectes</h1>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0">Cet outil vous permet d'analyser les activités suspectes des étudiants pendant les examens. Utilisez les filtres pour affiner vos recherches.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-lg-3">
                    <div class="card filter-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Filtres</h5>
                            <a href="view_suspicious_activities.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-redo-alt"></i> Réinitialiser
                            </a>
                        </div>
                        <div class="card-body">
                            <form action="" method="get">
                                <div class="mb-3">
                                    <label for="exam_id" class="form-label">Examen</label>
                                    <select class="form-select" id="exam_id" name="exam_id">
                                        <option value="">Tous les examens</option>
                                        <?php foreach ($exams as $exam): ?>
                                            <option value="<?= $exam['id'] ?>" <?= ($exam_id == $exam['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($exam['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Étudiant</label>
                                    <select class="form-select" id="student_id" name="student_id">
                                        <option value="">Tous les étudiants</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student['id'] ?>" <?= ($student_id == $student['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['class_name'] ?? 'Aucun groupe') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Appliquer les filtres
                                </button>
                            </form>
                            
                            <hr>
                            
                            <h6 class="mb-3">Résumé</h6>
                            
                            <div class="summary-item">
                                <div class="summary-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="summary-content">
                                    <div class="summary-title">Activités suspectes totales</div>
                                    <div class="summary-value"><?= $summary['total_logs'] ?></div>
                                </div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="summary-content">
                                    <div class="summary-title">Étudiants concernés</div>
                                    <div class="summary-value"><?= $summary['total_students'] ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($summary['activities_by_type'])): ?>
                                <h6 class="mt-4 mb-3">Types d'activités</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Nombre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($summary['activities_by_type'] as $activity): ?>
                                                <tr>
                                                    <td><?= formatActivityType($activity['activity_type']) ?></td>
                                                    <td><?= $activity['count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($exam_id) && !empty($summary['activity_count_by_exam'])): ?>
                                <h6 class="mt-4 mb-3">Examens concernés</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Examen</th>
                                                <th>Nombre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($summary['activity_count_by_exam'] as $examActivity): ?>
                                                <tr>
                                                    <td>
                                                        <a href="?exam_id=<?= $examActivity['id'] ?>">
                                                            <?= htmlspecialchars(shortText($examActivity['title'], 25)) ?>
                                                        </a>
                                                    </td>
                                                    <td><?= $examActivity['count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($student_id) && !empty($summary['top_offenders'])): ?>
                                <h6 class="mt-4 mb-3">Étudiants avec le plus d'activités</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Étudiant</th>
                                                <th>Nombre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($summary['top_offenders'] as $offender): ?>
                                                <tr>
                                                    <td>
                                                        <a href="?student_id=<?= $offender['id'] ?><?= $exam_id ? '&exam_id=' . $exam_id : '' ?>">
                                                            <?= htmlspecialchars(shortText($offender['name'], 20)) ?>
                                                        </a>
                                                        <small class="d-block text-muted"><?= htmlspecialchars($offender['class_name'] ?? 'Aucun groupe') ?></small>
                                                    </td>
                                                    <td><?= $offender['count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-lg-9">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Activités suspectes détectées</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune activité suspecte n'a été détectée avec les filtres actuels.
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <small class="text-muted">Affichage des <?= count($logs) ?> dernières activités<?= ($summary['total_logs'] > count($logs)) ? ' (sur ' . $summary['total_logs'] . ' au total)' : '' ?></small>
                                </div>
                                
                                <?php foreach ($logs as $log): ?>
                                    <div class="activity-item">
                                        <div class="activity-header">
                                            <div>
                                                <span class="activity-type-badge"><?= formatActivityType($log['activity_type']) ?></span>
                                                <span class="activity-title"><?= htmlspecialchars($log['student_name']) ?></span>
                                            </div>
                                            <span class="activity-date"><?= formatDateTime($log['created_at']) ?></span>
                                        </div>
                                        <div class="activity-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-file-alt"></i>
                                                <?= htmlspecialchars(shortText($log['exam_title'], 30)) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-desktop"></i>
                                                <?= formatUserAgent($log['user_agent']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($log['ip_address']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit the form when the filters change
        document.querySelectorAll('#exam_id, #student_id').forEach(select => {
            select.addEventListener('change', () => {
                document.querySelector('form').submit();
            });
        });
    </script>
</body>
</html>

<?php
// Helper functions
function formatActivityType($type) {
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

function formatDateTime($dateTime) {
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i:s');
}

function shortText($text, $maxLength = 30) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    
    return substr($text, 0, $maxLength - 3) . '...';
}

function formatUserAgent($ua) {
    if (strpos($ua, 'Mobile') !== false) {
        return 'Mobile';
    } elseif (strpos($ua, 'Tablet') !== false) {
        return 'Tablette';
    } else {
        return 'Ordinateur';
    }
}
?> 