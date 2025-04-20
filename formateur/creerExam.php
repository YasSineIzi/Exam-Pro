<?php
session_start();
require_once '../db.php'; // Assurez-vous que le fichier de connexion à la base de données est correct

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fonction pour enregistrer l'examen
function saveExam($conn, $examData)
{
    try {
        $conn->begin_transaction();

        // Insérer l'examen
        $stmt = $conn->prepare("INSERT INTO exams (title, description, duration, user_id, course_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssiiis', $examData['title'], $examData['description'], $examData['duration'], $examData['user_id'], $examData['course_id'], $examData['status']);
        $stmt->execute();

        // Récupérer l'ID de l'examen inséré
        $examId = $conn->insert_id;
        $conn->commit();
        return $examId;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Fonction pour enregistrer une question
function saveQuestion($conn, $examId, $question)
{
    try {
        // Insérer la question
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_title, type, points) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $examId, $question['title'], $question['type'], $question['points']);
        $stmt->execute();

        // Récupérer l'ID de la question insérée
        $questionId = $conn->insert_id;

        // Si la question est de type QCM, enregistrer les options
        if ($question['type'] === 'mcq' && isset($question['details']['options'])) {
            foreach ($question['details']['options'] as $option) {
                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->bind_param('isi', $questionId, $option['text'], $option['correct']);
                $stmt->execute();
            }
        }
    } catch (Exception $e) {
        throw $e;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Données de l'examen
        $examData = [
            'title' => $_POST['examTitle'],
            'description' => $_POST['examDescription'],
            'duration' => $_POST['examDuration'],
            'course_id' => $_POST['courseId'],
            'user_id' => $_SESSION['user_id'],
            'status' => 'published'
        ];

        // Enregistrer l'examen
        $examId = saveExam($conn, $examData);

        // Enregistrer les questions
        $questions = json_decode($_POST['questions'], true);
        foreach ($questions as $question) {
            saveQuestion($conn, $examId, $question);
        }

        // Réponse JSON en cas de succès
        echo json_encode(['status' => 'success', 'examId' => $examId]);
    } catch (Exception $e) {
        // Réponse JSON en cas d'erreur
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit; // Arrêter l'exécution du script après la réponse
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un examen - ExamPro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        :root {
            --primary-color: #6366f1;

            --primary-hover: #2563eb;
            --secondary-color: #f3f4f6;
            --text-color: #1f2937;
            --border-color: #e5e7eb;
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --radius: 0.375rem;
            --transition-speed: 0.3s;
            --sidebar-width: 260px;
            --collapsed-width: 80px;
        }


        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            color: var(--text-color);
            background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);

            background-color: var(--secondary-color);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            /* max-width: 800px; */
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* 
         */

        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
        }

        /* .container-fluid {
            display: flex;
        } */
        .sidebar.collapsed~.container-fluid .main-content {
            margin-left: var(--collapsed-width);
        }



        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card-content {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row>* {
            flex: 1;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .btn-outline:hover {
            background-color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: var(--danger-hover);
        }

        .option-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .option-group input[type="checkbox"] {
            width: auto;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
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

        .question-card {
            animation: fadeIn 0.3s ease-out;
        }

        .header-title {
            /* padding: 2rem; */
            /* background-color: #f8f9fa; */
            /* border-bottom: 1px solid #dee2e6; */
            margin-bottom: 2rem;
            /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); */
        }

        h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            /* background-color: #3498db; */
            margin: 1rem auto;
            border-radius: 2px;
        }
    </style>
</head>

<body>


    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <div class="main-content">
            <main class="">
                <div class="container ">
                    <h2 class="mb-4">Créer un nouvel examen</h2>

                    <form id="examForm" method="POST">
                        <!-- Informations générales de l'examen -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Informations générales</h5>
                                <div class="mb-3">
                                    <label for="examTitle" class="form-label">Titre de l'examen</label>
                                    <input type="text" class="form-control" id="examTitle" name="examTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="examDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="examDescription" name="examDescription"
                                        rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="examDuration" class="form-label">Durée (minutes)</label>
                                    <input type="number" class="form-control" id="examDuration" name="examDuration"
                                        value="60" min="15" required>
                                </div>
                                <div class="mb-3">
                                    <label for="courseId" class="form-label">ID du cours</label>
                                    <input type="text" class="form-control" id="courseId" name="courseId" required>
                                </div>
                            </div>
                        </div>

                        <!-- Conteneur pour les questions -->
                        <div id="questionsContainer"></div>

                        <button type="button" class="btn btn-outline-primary mb-3" id="addQuestion">
                            Ajouter une question
                        </button>
                        <button type="submit" class="btn btn-primary mb-3">Enregistrer l'examen</button>
                        <style>
                            .custom-hover {
                                transition: all 0.3s ease;
                                border-width: 2px;
                            }

                            .btn-outline-success.custom-hover:hover {
                                background: var(--bs-success);
                                color: white;
                                transform: translateY(-1px);
                                box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);
                            }

                            .btn-primary.custom-hover:hover {
                                transform: translateY(-1px);
                                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
                                background: #0b5ed7;
                                border-color: #0a58ca;
                            }

                            .rounded-3 {
                                border-radius: 12px !important;
                            }
                        </style>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        let questionCounter = 0;

        // Fonction pour ajouter une nouvelle question
        function addQuestion() {
            questionCounter++;
            const questionTemplate = `
                <div class="card mb-3 question-card" data-question-id="${questionCounter}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title">Question ${questionCounter}</h5>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeQuestion(${questionCounter})">
                                Supprimer
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type de question</label>
                            <select class="form-select question-type" onchange="updateQuestionFields(${questionCounter})">
                                <option value="mcq">QCM</option>
                                <option value="short">Réponse courte</option>
                                <option value="open">Question ouverte</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Énoncé de la question</label>
                            <input type="text" class="form-control question-title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control question-points" value="1" min="1" required>
                        </div>

                        <div class="question-details"></div>
                    </div>
                </div>
            `;

            document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', questionTemplate);
            updateQuestionFields(questionCounter);
        }

        // Fonction pour mettre à jour les champs en fonction du type de question
        function updateQuestionFields(questionId) {
            const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
            const type = questionCard.querySelector('.question-type').value;
            const detailsContainer = questionCard.querySelector('.question-details');

            let template = '';
            if (type === 'mcq') {
                template = `
                    <div class="mb-3">
                        <label class="form-label">Options</label>
                        <div class="options-container">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control option-text" placeholder="Option 1" required>
                                <div class="input-group-text">
                                    <input type="checkbox" class="option-correct" title="Cocher si c'est la bonne réponse">
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOption(${questionId})">
                            Ajouter une option
                        </button>
                    </div>
                `;
            } else if (type === 'short') {
                template = `
                    <div class="mb-3">
                        <label class="form-label">Réponse attendue</label>
                        <input type="text" class="form-control expected-answer" required>
                    </div>
                `;
            } else if (type === 'open') {
                template = `
                    <div class="mb-3">
                        <label class="form-label">Guide de correction</label>
                        <textarea class="form-control correction-guide" rows="3"></textarea>
                    </div>
                `;
            }

            detailsContainer.innerHTML = template;
        }

        // Fonction pour ajouter une option à une question QCM
        function addOption(questionId) {
            const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
            const optionsContainer = questionCard.querySelector('.options-container');
            const optionCount = optionsContainer.children.length + 1;

            const optionTemplate = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control option-text" placeholder="Option ${optionCount}" required>
                    <div class="input-group-text">
                        <input type="checkbox" class="option-correct" title="Cocher si c'est la bonne réponse">
                    </div>
                </div>
            `;
            optionsContainer.insertAdjacentHTML('beforeend', optionTemplate);
        }

        // Fonction pour supprimer une question
        function removeQuestion(questionId) {
            const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
            questionCard.remove();
        }

        // Gestion de la soumission du formulaire
        document.getElementById('examForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const questions = [];

            // Collecter les données des questions
            document.querySelectorAll('.question-card').forEach((questionCard) => {
                const type = questionCard.querySelector('.question-type').value;
                const title = questionCard.querySelector('.question-title').value;
                const points = questionCard.querySelector('.question-points').value;
                const details = {};

                if (type === 'mcq') {
                    details.options = [];
                    questionCard.querySelectorAll('.input-group').forEach((group) => {
                        const optionText = group.querySelector('.option-text').value;
                        const isCorrect = group.querySelector('.option-correct').checked;
                        details.options.push({ text: optionText, correct: isCorrect });
                    });
                } else if (type === 'short') {
                    details.answer = questionCard.querySelector('.expected-answer').value;
                } else if (type === 'open') {
                    details.guide = questionCard.querySelector('.correction-guide').value;
                }

                questions.push({ title, type, points, details });
            });

            // Envoyer les données au serveur
            try {
                const response = await fetch('creerExam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        examTitle: formData.get('examTitle'),
                        examDescription: formData.get('examDescription'),
                        examDuration: formData.get('examDuration'),
                        courseId: formData.get('courseId'),
                        questions: JSON.stringify(questions)
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    alert('Examen créé avec succès!');
                    window.location.href = 'lesExamCreé.php';
                } else {
                    alert('Erreur lors de la création de l\'examen : ' + result.message);
                }
            } catch (error) {
                alert('Erreur : ' + error.message);
            }
        });

        // Ajouter une question initiale
        document.getElementById('addQuestion').addEventListener('click', addQuestion);
    </script>
</body>

</html>