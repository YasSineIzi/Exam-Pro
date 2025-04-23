# Système Anti-Triche pour ExamPro

Ce document décrit le système anti-triche implémenté dans ExamPro, conçu pour maintenir l'intégrité des examens en ligne.

## Fonctionnalités Anti-Triche

### 1. Mesures Préventives Côté Client

- **Prévention du copier-coller** : Bloque la copie et le collage de texte
- **Détection des changements d'onglet** : Détecte quand l'étudiant quitte la page d'examen
- **Blocage du clic droit** : Désactive le menu contextuel
- **Mode plein écran obligatoire** : Encourage l'utilisation du mode plein écran
- **Prévention des captures d'écran** : Détecte les tentatives d'utilisation de la touche Print Screen
- **Blocage des raccourcis clavier dangereux** : Détecte Alt+Tab, Ctrl+P, F12, etc.
- **Détection du redimensionnement suspect** : Repère les tentatives de minimisation de fenêtre
- **Confirmation de sortie de page** : Demande confirmation avant de quitter la page d'examen
- **Détection de l'inactivité prolongée** : Surveille les périodes d'inactivité suspectes

### 2. Mélange des Questions et Réponses

- **Mélange des questions** : L'ordre des questions peut être randomisé pour chaque étudiant
- **Mélange des options** : Pour les QCM, l'ordre des options peut être randomisé
- **Personnalisation par examen** : Ces options peuvent être activées individuellement pour chaque examen

### 3. Surveillance et Journalisation

- **Enregistrement des tentatives suspectes** : Toutes les activités suspectes sont enregistrées
- **Système d'avertissements progressifs** : Les étudiants reçoivent des avertissements visuels
- **Soumission automatique** : Après un nombre défini d'avertissements, l'examen est automatiquement soumis
- **Conservation des métadonnées** : IP, agent utilisateur, horodatage, etc.

### 4. Interface d'Administration

- **Visualisation des activités suspectes** : Interface dédiée pour les formateurs
- **Filtrage par examen ou étudiant** : Possibilité de filtrer les données
- **Statistiques et résumés** : Vue d'ensemble des comportements suspects
- **Liste des étudiants problématiques** : Identification rapide des étudiants ayant le plus d'activités suspectes

## Installation

1. Exécutez le script SQL pour créer les tables nécessaires :
   ```sql
   mysql -u [username] -p [database_name] < add_anti_cheating_tables.sql
   ```

2. Assurez-vous que les fichiers suivants sont bien copiés dans le dossier approprié :
   - `Etudiant/anti_cheating.js` : Script JavaScript du système anti-triche
   - `Etudiant/log_suspicious_activity.php` : Endpoint pour enregistrer les activités
   - `formateur/view_suspicious_activities.php` : Interface de visualisation

## Configuration

Le système anti-triche peut être configuré au niveau de chaque examen avec les options suivantes :

```javascript
const securitySettings = {
    examId: 123,
    userId: 456,
    shuffleQuestions: false,   // Activer/désactiver le mélange des questions
    shuffleOptions: false,     // Activer/désactiver le mélange des options
    preventCopyPaste: true,    // Activer/désactiver la prévention du copier-coller
    preventTabSwitching: true, // Activer/désactiver la détection de changement d'onglet
    preventRightClick: true,   // Activer/désactiver le blocage du clic droit
    preventPrintScreen: true,  // Activer/désactiver la détection des captures d'écran
    fullscreenMode: true,      // Activer/désactiver le mode plein écran
    logSuspiciousActivity: true, // Activer/désactiver la journalisation
    maxWarnings: 5             // Nombre d'avertissements avant soumission auto
};
```

## Limitations

Il est important de noter que ces mesures ne sont pas infaillibles :

1. Un étudiant techniquement avancé pourrait désactiver JavaScript
2. La détection de certaines touches comme Print Screen n'est pas 100% fiable
3. Un second appareil (téléphone, tablette) reste une voie de contournement possible

Pour une sécurité maximale, ces mesures devraient être complétées par :
- Une surveillance par webcam 
- Des examens à durée limitée
- Des questions uniques ou générées aléatoirement
- Une vérification d'identité

## Accès à l'Interface d'Administration

Les formateurs peuvent accéder à l'interface de surveillance à l'adresse :
`formateur/view_suspicious_activities.php`

Cette interface permet de :
- Voir toutes les activités suspectes
- Filtrer par examen ou étudiant
- Consulter des statistiques globales
- Identifier les comportements récurrents 