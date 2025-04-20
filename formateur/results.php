<?php
session_start();
require_once '../db.php';

// Définir les informations de connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=exampro';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Créer la connexion PDO
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Récupérer la liste des examens du formateur
try {
    $stmt = $pdo->prepare("
        SELECT id, title 
        FROM exams 
        WHERE user_id = :user_id 
        ORDER BY title
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer les résultats si un examen est sélectionné
if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];
    
    try {
        // Récupérer les résultats pour cet examen
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as student_name, u.class,
                   e.title as exam_title,
                   (SELECT COUNT(*) FROM questions WHERE exam_id = r.exam_id) as total_questions
            FROM results r
            JOIN users u ON r.student_id = u.id
            JOIN exams e ON r.exam_id = e.id
            WHERE r.exam_id = :exam_id
            ORDER BY u.class, u.name
        ");
        $stmt->execute([':exam_id' => $exam_id]);
        $results = $stmt->fetchAll();

        // Calculer les statistiques
        if (!empty($results)) {
            $totalStudents = count($results);
            $scores = array_column($results, 'score');
            $averageScore = number_format(array_sum($scores) / $totalStudents, 2);
            $highestScore = max($scores);
            $lowestScore = min($scores);
        }
    } catch (Exception $e) {
        die("Erreur : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats des Examens - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #333333;
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --transition-speed: 0.3s;
            --sidebar-width: 260px;
            --collapsed-width: 80px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);

            color: var(--text-color);
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            box-sizing: border-box; 
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            transition: all var(--transition-speed) ease;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
        }

        /* Ajout pour le mode réduit */
        .sidebar.collapsed~.container-fluid .main-content {
            margin-left: var(--collapsed-width);
        }

        header {
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .exam-select,
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            background-color: var(--card-bg);
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background-color: var(--card-bg);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-color);
            opacity: 0.8;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            color: var(--primary-color);
        }

        tbody tr {
            background-color: var(--card-bg);
            transition: transform 0.3s;
        }

        tbody tr:hover {
            transform: scale(1.02);
        }

        .score-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            color: #ffffff;
        }

        .score-high {
            background-color: var(--secondary-color);
        }

        .score-medium {
            background-color: var(--primary-color);
        }

        .score-low {
            background-color: var(--accent-color);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                margin-bottom: 15px;
            }

            td {
                position: relative;
                padding-left: 50%;
            }

            td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <div class="main-content">
            <header>
                <h1>Résultats des Examens</h1>
            </header>

            <div class="card mb-4">
                <div class="card-body">
                <div class="select-container">
    <form method="GET" class="exam-form">
        <div class="select-wrapper">
            <select name="exam_id" class="custom-select" onchange="this.form.submit()">
                <option value="">Sélectionner un examen</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?= $exam['id'] ?>" 
                            <?= (isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($exam['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="select-icon">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </form>
</div>

<style>
.select-container {
    max-width: 100%;
    margin: 1.5rem 0;
}

.exam-form {
    position: relative;
    width: 100%;
}

.select-wrapper {
    position: relative;
    width: 100%;
}

.custom-select {
    width: 100%;
    padding: 1rem 3rem 1rem 1.25rem;
    font-size: 0.95rem;
    line-height: 1.5;
    color: #1e293b;
    background-color: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* .custom-select:hover {
    border-color: #cbd5e1;
    background-color: #f8fafc;
} */

.custom-select:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    background-color: #ffffff;
}

.select-icon {
    position: absolute;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    transition: all 0.2s ease;
}

.select-icon i {
    color: #64748b;
    font-size: 0.875rem;
    transition: transform 0.2s ease;
}

.custom-select:focus + .select-icon i {
    color: #4f46e5;
    transform: rotate(180deg);
}

/* Styling for options */
.custom-select option {
    padding: 0.75rem;
    background-color: #ffffff;
    color: #1e293b;
}

.custom-select option:checked {
    background-color: #4f46e5;
    color: #ffffff;
}

/* Hover effect */
.select-wrapper::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: -1;
}

/* .select-wrapper:hover::after {
    opacity: 0.05;
} */

/* Loading state */
.custom-select:disabled {
    background-color: #f1f5f9;
    cursor: not-allowed;
}

/* Animation */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.select-container {
    animation: slideDown 0.3s ease-out forwards;
}

/* Responsive design */
@media (max-width: 640px) {
    .custom-select {
        padding: 0.875rem 2.5rem 0.875rem 1rem;
        font-size: 0.875rem;
    }

    .select-icon {
        right: 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .custom-select {
        background-color: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }

    .custom-select:hover {
        background-color: #0f172a;
        border-color: #475569;
    }

    .custom-select option {
        background-color: #1e293b;
        color: #f1f5f9;
    }
}
</style>

                    <?php if (isset($results) && !empty($results)): ?>
                        <div class="stats-grid mb-4">
                            <div class="stat-card">
                                <div class="stat-value"><?= $totalStudents ?></div>
                                <div class="stat-label">Étudiants</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $averageScore ?></div>
                                <div class="stat-label">Moyenne</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $highestScore ?></div>
                                <div class="stat-label">Note max</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $lowestScore ?></div>
                                <div class="stat-label">Note min</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Score</th>
                                        <!-- <th>Statut</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($result['student_name']) ?></td>
                                            <td><?= htmlspecialchars($result['class']) ?></td>
                                            <td>
                                                <span class="score-badge <?= $result['score'] >= 10 ? 'score-high' : 'score-low' ?>">
                                                    <?= number_format($result['score'], 2) ?>/20
                                                </span>
                                            </td>
                                           
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (isset($_GET['exam_id'])): ?>
                        <div class="alert alert-info">
                            Aucun résultat disponible pour cet examen.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>