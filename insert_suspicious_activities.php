<?php
require_once 'db.php';

// Get student_id and exam_id from URL or use defaults
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 61; // Default to student ID 61 (Chadi)
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 56; // Default to exam ID 56 (LARAVEL)

// Check if the student and exam exist
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

$stmt = $conn->prepare("SELECT id FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();

if ($student_result->num_rows === 0 || $exam_result->num_rows === 0) {
    die("L'étudiant ou l'examen n'existe pas.");
}

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

// First, check if activities already exist for this student and exam
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_activity_logs WHERE user_id = ? AND exam_id = ?");
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo "<p>Des activités suspectes existent déjà pour cet étudiant et cet examen. Voulez-vous les remplacer?</p>";
    echo "<a href='?student_id={$student_id}&exam_id={$exam_id}&confirm=yes'>Oui, remplacer</a> | ";
    echo "<a href='formateur/corrigerExam.php?exam_id={$exam_id}&student_id={$student_id}'>Non, retourner à la correction</a>";

    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete existing activities
        $stmt = $conn->prepare("DELETE FROM exam_activity_logs WHERE user_id = ? AND exam_id = ?");
        $stmt->bind_param("ii", $student_id, $exam_id);
        $stmt->execute();

        // Now continue with insertion
    } else {
        exit;
    }
}

// Prepare the statement for insertion
$stmt = $conn->prepare("INSERT INTO exam_activity_logs (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Insert each activity
$inserted = 0;
foreach ($activities as $activity) {
    $stmt->bind_param("iisssss", $student_id, $exam_id, $activity[0], $activity[1], $activity[2], $activity[3], $activity[4]);
    if ($stmt->execute()) {
        $inserted++;
    }
}

echo "<p>{$inserted} activités suspectes ont été ajoutées pour l'étudiant ID {$student_id} dans l'examen ID {$exam_id}.</p>";
echo "<a href='formateur/corrigerExam.php?exam_id={$exam_id}&student_id={$student_id}'>Retourner à la correction</a>";
?>