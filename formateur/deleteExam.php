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
    header('Location: login.php');
    exit;
}

$examId = $_GET['examId'];

try {
    // Delete the exam
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = :examId");
    $stmt->execute([':examId' => $examId]);

    // Redirect to the exam list page
    header('Location: lesExamCreé.php');
    exit;
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
