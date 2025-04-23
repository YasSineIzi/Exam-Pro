<?php
session_start();
require_once '../db.php'; // Ensure the correct path to the config file

// Define the DSN and options for PDO
$dsn = 'mysql:host=localhost;dbname=exampro'; // Change this to your DSN
$username = 'root'; // Change this to your database username
$password = ''; // Change this to your database password
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Define the PDO variable for database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ');
    exit;
}

$user_id = $_SESSION['user_id'];
$exams = [];

try {
    // Récupérer les examens créés par le formateur avec plus de détails
    $stmt = $pdo->prepare("
        SELECT e.*, 
            COUNT(DISTINCT q.id) as question_count,
            COUNT(DISTINCT sa.student_id) as student_count
        FROM exams e
        LEFT JOIN questions q ON e.id = q.exam_id
        LEFT JOIN student_answers sa ON e.id = sa.exam_id
        WHERE e.user_id = :user_id
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens Créés - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Base styles */
        :root {
            --primary-color: #6366f1;
            --hover-bg: #f8fafc;
            --transition-speed: 0.3s;
            --sidebar-width: 260px;
            --collapsed-width: 80px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
            color: #1F2937;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
        }

        /* Ajout pour le mode réduit */
        .sidebar.collapsed~.container .main-content {
            margin-left: var(--collapsed-width);
        }

        .container {
            padding: 2rem;
        }

        /* Header styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .graduation-icon {
            color: #4F46E5;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1F2937;
        }

        .create-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #4F46E5;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .create-button:hover {
            background-color: #4338CA;
        }

        /* Exam grid */
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        /* Exam card */
        .exam-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #F3F4F6;
            padding: 1.5rem;
            transition: box-shadow 0.3s;
        }

        .exam-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .exam-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .exam-title svg {
            color: #4F46E5;
        }

        .exam-title h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1F2937;
        }

        .duration-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background-color: #EEF2FF;
            color: #4F46E5;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .exam-description {
            color: #4B5563;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .exam-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            color: #6B7280;
            font-size: 0.875rem;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .exam-actions {
            display: flex;
            gap: 0.5rem;
        }

        .view-button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: #4F46E5;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .view-button:hover {
            background-color: #4338CA;
        }

        .edit-button,
        .delete-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .edit-button {
            color: #4B5563;
            text-decoration: none;
        }

        .edit-button:hover {
            background-color: #F3F4F6;
        }

        .delete-button {
            color: #DC2626;
        }

        .delete-button:hover {
            background-color: #FEE2E2;
        }

        /* Empty state styling */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 3rem;
            background: white;
            border-radius: 0.75rem;
            text-align: center;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: #9CA3AF;
        }

        .empty-state p {
            font-size: 1.125rem;
            color: #4B5563;
            margin: 0;
        }

        .empty-state .btn {
            text-decoration: none;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .exam-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.collapsed~.container .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="container">
        <div class="main-content">
            <!-- Header -->
            <div class="header">
    <div class="header-content">
        <div class="title-group">
            <div class="icon-wrapper bg-primary-soft">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" 
                     stroke-linejoin="round" class="graduation-icon text-primary">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <div class="title-text">
                <h1 class="page-title">Examens Créés</h1>
                <p class="page-subtitle">Gérez vos évaluations académiques</p>
            </div>
        </div>
        
        <a href="creerExam.php" class="cta-button">
            <span class="button-content">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                     stroke-linejoin="round" class="button-icon">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                Nouvel Examen
            </span>
            <span class="hover-effect"></span>
        </a>
    </div>
</div>

<style>
:root {
    --primary: #4F46E5;
    --primary-hover: #4338CA;
    --primary-soft: #EEF2FF;
    --text-primary: #111827;
    --text-secondary: #6B7280;
    --surface: rgba(255, 255, 255, 0.8);
    --border: rgba(0, 0, 0, 0.08);
}

.header {
    background: var(--surface);
    backdrop-filter: blur(12px);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-content {
    max-width: 1440px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.title-group {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-grow: 1;
}

.title-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    font-size: 1.625rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

.page-subtitle {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0;
    font-weight: 400;
}

.cta-button {
    position: relative;
    background: var(--primary);
    color: white;
    padding: 0.875rem 1.75rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1),
                0 2px 4px -1px rgba(79, 70, 229, 0.06);
    white-space: nowrap;
}

/* Le reste des styles d'animation reste identique */

@media (max-width: 768px) {
    .header-content {
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    
    .cta-button {
        width: 100%;
        justify-content: center;
    }
    
    .title-group {
        width: 100%;
    }
}
</style>
            <!-- Exam Grid -->
            <div class="exam-grid">
                <?php
                // Initialize $exams if it's not set
                if (!isset($exams)) {
                    $exams = [];
                }

                if (!empty($exams)): ?>
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-card">
                            <div class="exam-header">
                                <div class="exam-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                                    </svg>
                                    <h3><?= htmlspecialchars($exam['title']) ?></h3>
                                </div>
                                <div class="duration-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                    <?= htmlspecialchars($exam['duration']) ?> min
                                </div>
                            </div>

                            <p class="exam-description"><?= htmlspecialchars($exam['description'] ?? 'Aucune description') ?>
                            </p>

                            <div class="exam-stats">
                                <span class="stat">
                                    <i class="fas fa-question-circle"></i>
                                    <?= isset($exam['question_count']) ? htmlspecialchars($exam['question_count']) : 0 ?>
                                    questions
                                </span>
                                <span class="stat">
                                    <i class="fas fa-calendar"></i>
                                    <?= isset($exam['created_at']) ? date('d/m/Y', strtotime($exam['created_at'])) : date('d/m/Y') ?>
                                </span>
                            </div>

                            <div class="exam-actions">
                                <a href="viewExam.php?id=<?= htmlspecialchars($exam['id']) ?>" class="view-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    Voir
                                </a>
                                <a href="editExam.php?id=<?= htmlspecialchars($exam['id']) ?>" class="edit-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                        <path d="m15 5 4 4" />
                                    </svg>
                                </a>
                                <button onclick="deleteExam(<?= htmlspecialchars($exam['id']) ?>)" class="delete-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 6h18" />
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>Vous n'avez pas encore créé d'examens</p>
                        <a href="creerExam.php" class="btn create-button">
                            <i class="fas fa-plus"></i>
                            Créer votre premier examen
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function deleteExam(examId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
                window.location.href = `deleteExam.php?id=${examId}`;
            }
        }
    </script>
</body>

</html>