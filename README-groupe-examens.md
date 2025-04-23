# Fonctionnalité d'attribution d'examens par groupe

Cette fonctionnalité permet aux formateurs d'attribuer des examens à des groupes spécifiques d'étudiants, garantissant que seuls les étudiants du groupe désigné peuvent voir et passer ces examens.

## Modifications effectuées

### 1. Structure de la base de données
- Ajout d'une colonne `class_id` à la table `exams` pour associer un examen à un groupe spécifique
- Création d'une contrainte de clé étrangère entre `exams.class_id` et `class.Id_c` pour maintenir l'intégrité référentielle
- Configuration de la clé étrangère avec `ON DELETE SET NULL` (si un groupe est supprimé, les examens deviennent disponibles pour tous) et `ON UPDATE CASCADE` (si l'ID d'un groupe change, les références sont mises à jour)
- Si `class_id` est NULL, l'examen est disponible pour tous les groupes

### 2. Formulaire de création d'examen (`formateur/creerExam.php`)
- Ajout d'un menu déroulant pour sélectionner le groupe destinataire lors de la création d'un examen
- Mise à jour de la fonction `saveExam()` pour stocker l'ID du groupe dans la base de données

### 3. Affichage des examens pour les étudiants (`Etudiant/Examens.php`)
- Modification de la requête SQL pour récupérer uniquement les examens:
  - Assignés au groupe de l'étudiant
  - OU sans groupe spécifique (accessibles à tous)
- Ajout d'un indicateur visuel montrant à quel groupe l'examen est destiné

### 4. Sécurité pour la passation d'examen (`Etudiant/takeExam.php`)
- Vérification supplémentaire pour s'assurer qu'un étudiant ne peut accéder qu'aux examens:
  - Assignés à son groupe
  - OU sans groupe spécifique (accessibles à tous)

### 5. Interface d'administration des examens (`formateur/lesExamCreé.php`)
- Ajout de l'information du groupe dans la liste des examens créés

## Vérification de la base de données

Avant d'utiliser cette fonctionnalité, il est essentiel de s'assurer que la structure de la base de données a été correctement mise à jour:

1. Exécutez le script SQL `add_class_id_to_exams.sql` pour ajouter la colonne `class_id` et la contrainte de clé étrangère
2. Utilisez le script `verify_db_changes.php` pour vérifier que les modifications ont été correctement appliquées
3. Assurez-vous que la clé étrangère est bien configurée pour maintenir l'intégrité des données

## Utilisation

1. **Pour les formateurs:**
   - Lors de la création d'un examen, sélectionnez un groupe spécifique dans le menu déroulant
   - Si vous souhaitez que l'examen soit accessible à tous les groupes, laissez l'option par défaut "Sélectionner un groupe"

2. **Pour les étudiants:**
   - Les étudiants ne verront que les examens assignés à leur groupe et ceux sans groupe spécifique
   - Une tentative d'accès direct à un examen d'un autre groupe via l'URL sera automatiquement bloquée

## Remarques
- Si vous créez un examen sans spécifier de groupe, il sera visible par tous les étudiants
- Les examens déjà créés avant cette mise à jour ne sont pas assignés à un groupe spécifique et restent donc visibles par tous les étudiants
- La clé étrangère protège contre les incohérences dans la base de données (par exemple, l'assignation d'un examen à un groupe qui n'existe pas) 