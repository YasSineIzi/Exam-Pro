<?php
session_start();
require_once '../db.php';

// Vérifier si l'utilisateur est connecté et a le rôle approprié (admin ou teacher)
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
//     header('Location: login.php');
//     exit;
// }

// Récupérer la liste des examens pour le filtre
$exams = [];
try {
    $stmt = $conn->prepare("SELECT id, title FROM exams");
    $stmt->execute();
    $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer l'ID de l'examen sélectionné (s'il existe)
$selected_exam_id = $_GET['exam_id'] ?? null;

// Récupérer les étudiants qui ont passé l'examen sélectionné
$students = [];
if ($selected_exam_id) {
    try {
        $stmt = $conn->prepare("SELECT DISTINCT u.id AS student_id, u.name FROM student_answers sa JOIN users u ON sa.student_id = u.id WHERE sa.exam_id = ?");
        $stmt->bind_param("i", $selected_exam_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <title>Étudiants ayant passé l'examen - ExamPro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --secondary: #6366F1;
            --accent: #10B981;
            --background: #F8FAFC;
            --text: #1E293B;
            --border: #E2E8F0;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);

            color: var(--text-color);
            background-color: var(--secondary-color);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: "";
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: var(--background);
            transform: skewY(-2deg);
        }

        .content-section {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .container-fluid {
            display: flex;
        }

        /* Main Content Styles */
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

        .exam-select {
            width: 100%;
            padding: 0.875rem 1.25rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--background);
            transition: all 0.3s ease;
        }

        .exam-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .table-responsive {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: var(--primary);
            color: white;
        }

        .table th {
            font-weight: 500;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-warning {
            background: #F59E0B;
            border: none;
            color: white;
        }

        .btn-warning:hover {
            background: #D97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .no-students {
            text-align: center;
            padding: 3rem;
            color: #6C757D;
        }

        .no-students i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            background: rgba(79, 70, 229, 0.1);
            padding: 0.75rem;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="display-5 mb-3">Étudiants ayant passé l'examen</h1>
                <p class="lead mb-0">Consultez la liste des étudiants et corrigez leurs examens</p>
            </div>

            <!-- Exam Selection Section -->
            <div class="content-section">
                <h2 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Sélectionner un examen
                </h2>
                <form method="get" action="students_who_took_exam.php">
                    <select name="exam_id" id="exam_id" class="exam-select" required onchange="this.form.submit()">
                        <option value="">Choisir un examen...</option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?= htmlspecialchars($exam['id']) ?>" <?= ($selected_exam_id == $exam['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($exam['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <style>
                .exam-select {
    width: 100%;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    color: #1E293B;
    background-color: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    appearance: none;
    cursor: pointer;
    transition: all 0.3s ease;
}
            </style>

            <!-- Students List Section -->
            <?php if ($selected_exam_id): ?>
                <!-- Students List Section -->
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i>
                        Liste des étudiants
                    </h2>
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom de l'étudiant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['name']) ?></td>
                                            <td>
                                                <a href="corrigerExam.php?exam_id=<?= $selected_exam_id ?>&student_id=<?= $student['student_id'] ?>"
                                                    class="btn btn-action">
                                                    <i class="fas fa-check-circle"></i> Corriger l'examen
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <style>
                    .table-wrapper {
                        background: white;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 4px 24px rgba(79, 70, 229, 0.08);
                        margin: 1.5rem 0;
                        border: 1px solid rgba(79, 70, 229, 0.1);
                    }

                    .table {
                        width: 100%;
                        border-collapse: separate;
                        border-spacing: 0;
                        margin: 0;
                    }

                    .table thead {
                        background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
                    }

                    .table thead th {
                        color: white;
                        font-weight: 600;
                        padding: 1.25rem 1.5rem;
                        font-size: 0.95rem;
                        letter-spacing: 0.025em;
                        text-transform: uppercase;
                        border: none;
                        position: relative;
                    }

                    .table thead th:not(:last-child)::after {
                        content: "";
                        position: absolute;
                        right: 0;
                        top: 25%;
                        height: 50%;
                        width: 1px;
                        background: rgba(255, 255, 255, 0.2);
                    }

                    .table tbody tr {
                        transition: all 0.2s ease;
                    }

                    .table tbody tr:hover {
                        background: rgba(79, 70, 229, 0.02);
                        transform: translateX(6px);
                    }

                    .table tbody td {
                        padding: 1.25rem 1.5rem;
                        color: #1E293B;
                        border-bottom: 1px solid #F1F5F9;
                        font-size: 0.95rem;
                    }

                    .table tbody tr:last-child td {
                        border-bottom: none;
                    }

                    .btn-action {
                        padding: 0.75rem 1.5rem;
                        border-radius: 12px;
                        font-weight: 500;
                        transition: all 0.2s ease;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.75rem;
                        background: #F0FDF4;
                        color: #16A34A;
                        border: 1px solid #BBF7D0;
                        text-decoration: none;
                    }

                    .btn-action i {
                        font-size: 1.1rem;
                        color: #22C55E;
                    }

                    .btn-action:hover {
                        background: #DCFCE7;
                        color: #15803D;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
                    }

                    @media (max-width: 768px) {
                        .table-wrapper {
                            border-radius: 12px;
                            margin: 1rem 0;
                        }

                        .table thead th {
                            padding: 1rem;
                            font-size: 0.875rem;
                        }

                        .table tbody td {
                            padding: 1rem;
                            font-size: 0.875rem;
                        }

                        .btn-action {
                            padding: 0.625rem 1rem;
                            font-size: 0.875rem;
                        }
                    }

                    @keyframes fadeIn {
                        from {
                            opacity: 0;
                            transform: translateY(10px);
                        }

                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }

                    .table tbody tr {
                        animation: fadeIn 0.3s ease forwards;
                        animation-delay: calc(var(--row-index, 0) * 0.05s);
                    }
                </style>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>