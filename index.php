<?php
// Démarre une session pour stocker les informations de l'utilisateur
session_start();

// Inclure la connexion à la base de données
include 'db.php';

$error = ''; // Variable pour stocker les messages d'erreur

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les informations du formulaire et les nettoyer
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Vérifier si les champs sont remplis
    if (!empty($email) && !empty($password)) {
        // Préparer une requête pour récupérer l'utilisateur en fonction de l'email
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            // Vérifier si un utilisateur avec cet email existe
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $name, $db_email, $db_password, $role);
                $stmt->fetch();

                // Vérifier si le mot de passe correspond
                if ($password === $db_password) { // Comparaison directe des mots de passe
                    // Les informations de connexion sont correctes, démarrer la session
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $db_email;
                    $_SESSION['user_role'] = $role;

                    // Rediriger l'utilisateur en fonction de son rôle
                    if ($role == 'admin') {
                        header("Location: Admin/");
                    } elseif ($role == 'teacher') {
                        header("Location: formateur/");
                    } else {
                        header("Location: Etudiant/");
                    }
                    exit(); // Arrêter l'exécution du script après la redirection
                } else {
                    // Mot de passe incorrect
                    $error = "Mot de passe incorrect.";
                }
            } else {
                // Email non trouvé
                $error = "Aucun utilisateur trouvé avec cet email.";
            }

            $stmt->close();
        } else {
            $error = "Erreur lors de la connexion à la base de données.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
    header("Location: ");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --gradient-start: #3b82f6;
            --gradient-end: #60a5fa;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(96, 165, 250, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 1rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
        }

        .text-center {
            text-align: center;
        }

        .logo-title {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6B7280;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .input-group {
            display: flex;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #E5E7EB;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #6B7280;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
        }

        .form-control {
            flex: 1;
            border: 2px solid #E5E7EB;
            border-left: none;
            border-radius: 0 12px 12px 0;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .input-group:focus-within .form-control {
            border-color: var(--primary-color);
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            opacity: 0.9;
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .logo-icon {
            animation: float 3s ease-in-out infinite;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card">
            <div class="text-center">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="logo-title">ExamPro</h1>
                <p class="subtitle">Veuillez vous connecter pour accéder à votre compte</p>
            </div>

            <form method="POST" action="">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <label for="email" class="form-label">Adresse Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="exemple@domaine.com" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Entrez votre mot de passe" required>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Connexion</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>