 <?php
// Configuration de la base de données
$dsn = 'mysql:host=localhost;dbname=exampro';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Connexion à la base de données
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<h1>Vérification de la structure de la base de données</h1>";
    
    // Vérifier si la colonne class_id existe dans la table exams
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exams'
        AND COLUMN_NAME = 'class_id'
    ");
    $stmt->execute();
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<div style='color: green; margin: 10px 0;'>";
        echo "<p>✅ La colonne <strong>class_id</strong> existe dans la table <strong>exams</strong>.</p>";
        echo "<ul>";
        echo "<li>Type: " . $column['DATA_TYPE'] . "</li>";
        echo "<li>Clé: " . ($column['COLUMN_KEY'] ? $column['COLUMN_KEY'] : 'Aucune') . "</li>";
        echo "<li>Nullable: " . $column['IS_NULLABLE'] . "</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='color: red; margin: 10px 0;'>";
        echo "<p>❌ La colonne <strong>class_id</strong> n'existe PAS dans la table <strong>exams</strong>.</p>";
        echo "</div>";
    }
    
    // Vérifier si la contrainte de clé étrangère existe
    $stmt = $pdo->prepare("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exams'
        AND COLUMN_NAME = 'class_id'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute();
    $fk = $stmt->fetch();
    
    if ($fk) {
        echo "<div style='color: green; margin: 10px 0;'>";
        echo "<p>✅ La contrainte de clé étrangère existe pour <strong>class_id</strong>.</p>";
        echo "<ul>";
        echo "<li>Nom de la contrainte: " . $fk['CONSTRAINT_NAME'] . "</li>";
        echo "<li>Table référencée: " . $fk['REFERENCED_TABLE_NAME'] . "</li>";
        echo "<li>Colonne référencée: " . $fk['REFERENCED_COLUMN_NAME'] . "</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='color: red; margin: 10px 0;'>";
        echo "<p>❌ Aucune contrainte de clé étrangère n'existe pour <strong>class_id</strong>.</p>";
        echo "</div>";
    }
    
    // Affiche les détails supplémentaires de la contrainte de clé étrangère
    if ($fk) {
        $stmt = $pdo->prepare("
            SELECT DELETE_RULE, UPDATE_RULE
            FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = ?
        ");
        $stmt->execute([$fk['CONSTRAINT_NAME']]);
        $fkDetails = $stmt->fetch();
        
        if ($fkDetails) {
            echo "<div style='color: green; margin: 10px 0;'>";
            echo "<p>Détails supplémentaires de la clé étrangère:</p>";
            echo "<ul>";
            echo "<li>Règle de suppression: " . $fkDetails['DELETE_RULE'] . "</li>";
            echo "<li>Règle de mise à jour: " . $fkDetails['UPDATE_RULE'] . "</li>";
            echo "</ul>";
            echo "</div>";
        }
    }
    
    echo "<h2>Comment exécuter le script SQL</h2>";
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<p>Si les vérifications ci-dessus montrent des erreurs, vous devez exécuter le script SQL pour ajouter correctement la colonne et la clé étrangère. Voici comment faire:</p>";
    echo "<ol>";
    echo "<li>Ouvrez phpMyAdmin ou votre outil de gestion de base de données préféré</li>";
    echo "<li>Sélectionnez votre base de données exampro</li>";
    echo "<li>Exécutez le contenu du fichier <code>add_class_id_to_exams.sql</code></li>";
    echo "<li>Rafraîchissez cette page pour vérifier que les modifications ont été appliquées</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; margin: 10px 0;'>";
    echo "<p>Erreur de connexion à la base de données: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 