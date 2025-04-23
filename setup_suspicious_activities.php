<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

// Verify database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "<p>Database connection successful.</p>";
}

// Create the exam_activity_logs table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `exam_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `exam_id` (`exam_id`),
  KEY `activity_type` (`activity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Try to create the table
if ($conn->query($create_table_sql) === TRUE) {
    echo "<p>Table exam_activity_logs created or already exists.</p>";
} else {
    echo "<p>Error creating table: " . $conn->error . "</p>";
}

// Check if the table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'exam_activity_logs'")->num_rows > 0;

if (!$table_exists) {
    die("Erreur: La table exam_activity_logs n'a pas pu être créée.");
}

// Insert sample data for student with ID 61 (Chadi) and exam ID 65 (LARAVEL)
$student_id = 61; // Default student ID (Chadi)
$exam_id = 65;    // Default exam ID (LARAVEL)

// Check if the student and exam exist
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

$stmt = $conn->prepare("SELECT id FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    die("L'étudiant avec ID {$student_id} n'existe pas ou n'est pas un étudiant.");
}

if ($exam_result->num_rows === 0) {
    die("L'examen avec ID {$exam_id} n'existe pas.");
}

echo "<p>Étudiant et examen vérifiés avec succès.</p>";

// Sample suspicious activities to insert
$activities = [
    // High risk activities
    ['copy_attempt', 'L\'étudiant a tenté de copier du texte', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-45 minutes'))],
    ['paste_attempt', 'L\'étudiant a tenté de coller du texte', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-43 minutes'))],
    ['tab_switch', 'L\'étudiant a changé d\'onglet', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-40 minutes'))],
    ['tab_switch', 'L\'étudiant a changé d\'onglet', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-38 minutes'))],
    ['alt_tab_detected', 'L\'étudiant a utilisé Alt+Tab', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-35 minutes'))],
    ['dev_tools_attempt', 'L\'étudiant a tenté d\'ouvrir les outils de développement', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-30 minutes'))],
    ['exit_fullscreen', 'L\'étudiant est sorti du mode plein écran', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-28 minutes'))],

    // Medium risk activities
    ['right_click_attempt', 'L\'étudiant a fait un clic droit', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-25 minutes'))],
    ['print_screen_attempt', 'L\'étudiant a tenté de faire une capture d\'écran', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-20 minutes'))],
    ['idle_detected', 'L\'étudiant est resté inactif pendant 2 minutes', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-15 minutes'))],

    // Session tracking
    ['session_start', 'L\'étudiant a commencé l\'examen', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-50 minutes'))],
    ['returned_to_exam', 'L\'étudiant est revenu à l\'examen', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-39 minutes'))],
    ['returned_to_exam', 'L\'étudiant est revenu à l\'examen', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-37 minutes'))],
    ['returned_to_exam', 'L\'étudiant est revenu à l\'examen', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-36 minutes'))],
    ['session_end', 'L\'étudiant a terminé l\'examen', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36', date('Y-m-d H:i:s', strtotime('-5 minutes'))],
];

// First, clear any existing data for this student and exam
$stmt = $conn->prepare("DELETE FROM exam_activity_logs WHERE user_id = ? AND exam_id = ?");
$stmt->bind_param("ii", $student_id, $exam_id);
if ($stmt->execute()) {
    echo "<p>Anciennes activités supprimées avec succès.</p>";
} else {
    echo "<p>Erreur lors de la suppression des anciennes activités: " . $stmt->error . "</p>";
}

// Prepare the statement for insertion
$stmt = $conn->prepare("INSERT INTO exam_activity_logs (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo "<p>Erreur de préparation de l'insertion: " . $conn->error . "</p>";
}

// Insert each activity
$inserted = 0;
$failed = 0;
foreach ($activities as $activity) {
    $stmt->bind_param("iisssss", $student_id, $exam_id, $activity[0], $activity[1], $activity[2], $activity[3], $activity[4]);
    if ($stmt->execute()) {
        $inserted++;
    } else {
        $failed++;
        echo "<p>Erreur lors de l'insertion d'une activité (" . $activity[0] . "): " . $stmt->error . "</p>";
    }
}

echo "<p>Activités insérées: {$inserted}, Activités échouées: {$failed}</p>";

// Verify insertion
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_activity_logs WHERE user_id = ? AND exam_id = ?");
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p>Nombre total d'activités dans la base de données: " . $row['count'] . "</p>";

// Confirmation message and redirect
$message = "<p>{$inserted} activités suspectes ont été ajoutées pour l'étudiant ID {$student_id} dans l'examen ID {$exam_id}.</p>";
$message .= "<p>La table exam_activity_logs a été vérifiée avec succès.</p>";
$message .= "<p><a href='formateur/corrigerExam.php?exam_id={$exam_id}&student_id={$student_id}' class='btn btn-primary'>Voir la correction</a></p>";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des activités suspectes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            padding: 50px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #5c6bc0;
            margin-bottom: 30px;
            text-align: center;
        }

        .alert {
            margin-top: 20px;
        }

        .btn-primary {
            background-color: #5c6bc0;
            border-color: #5c6bc0;
        }

        .btn-primary:hover {
            background-color: #3949ab;
            border-color: #3949ab;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Configuration des activités suspectes</h1>

        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>

        <div class="alert alert-info">
            <h4>Activités ajoutées:</h4>
            <ul>
                <?php foreach ($activities as $activity): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($activity[0]); ?></strong> -
                        <?php echo htmlspecialchars($activity[1]); ?>
                        <small class="text-muted">(<?php echo htmlspecialchars($activity[4]); ?>)</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p class="text-center">
            <a href="formateur/corrigerExam.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>"
                class="btn btn-primary">Aller à la correction</a>
        </p>
    </div>
</body>

</html>