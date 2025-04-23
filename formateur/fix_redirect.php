<?php
// This is a test file to check path issues with suspicious activities
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de redirection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="alert alert-info">
            <h4>Test de redirection</h4>
            <p>Cette page vérifie l'accès aux fichiers de détection d'activités suspectes.</p>

            <ul>
                <li>Fichier : view_suspicious_activities.php</li>
                <li>Chemin : <?php echo __DIR__ . '/view_suspicious_activities.php'; ?></li>
                <li>Existe : <?php echo file_exists(__DIR__ . '/view_suspicious_activities.php') ? 'Oui' : 'Non'; ?>
                </li>
                <li>Taille :
                    <?php echo file_exists(__DIR__ . '/view_suspicious_activities.php') ? filesize(__DIR__ . '/view_suspicious_activities.php') . ' octets' : 'N/A'; ?>
                </li>
                <li>Permissions :
                    <?php echo file_exists(__DIR__ . '/view_suspicious_activities.php') ? substr(sprintf('%o', fileperms(__DIR__ . '/view_suspicious_activities.php')), -4) : 'N/A'; ?>
                </li>
            </ul>

            <div class="mt-3">
                <p>Liens de test :</p>
                <a href="view_suspicious_activities.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>"
                    class="btn btn-primary">Lien relatif</a>
                <a href="/formateur/view_suspicious_activities.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>"
                    class="btn btn-secondary">Lien absolu</a>
                <a href="<?php echo $_SERVER['REQUEST_SCHEME']; ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/formateur/view_suspicious_activities.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>"
                    class="btn btn-info">Lien complet</a>
            </div>
        </div>
    </div>
</body>

</html>