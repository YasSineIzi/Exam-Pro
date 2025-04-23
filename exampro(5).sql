-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 23 avr. 2025 à 12:45
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `exampro`
--

-- --------------------------------------------------------

--
-- Structure de la table `class`
--

CREATE TABLE `class` (
  `Id_c` int(11) NOT NULL,
  `Nom_c` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `class`
--

INSERT INTO `class` (`Id_c`, `Nom_c`) VALUES
(1, 'DEV201'),
(9, 'DEV202');

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

CREATE TABLE `cours` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `published` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `name`, `description`, `file_path`, `created_at`, `published`) VALUES
(1, 'Aproche agile', 'Aproche Agile', 'uploads/7. Approche agile_DevOps.pdf', '2025-01-06 13:18:37', NULL),
(2, 'Aproche agile', 'Aproche Agile', 'uploads/7. Approche agile_DevOps.pdf', '2025-01-06 13:18:46', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `formateur_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `published` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `exams`
--

INSERT INTO `exams` (`id`, `name`, `course_id`, `start_date`, `end_date`, `duration`, `created_at`, `title`, `description`, `user_id`, `status`, `formateur_id`, `class_id`, `published`) VALUES
(56, '', 1, NULL, NULL, 60, '2025-04-21 08:24:29', 'LARAVEL', 'Controlle continue N1', 72, 'published', 0, NULL, 1),
(57, '', 2, NULL, NULL, 60, '2025-04-22 12:18:50', 'jhgf', '', 72, 'published', 0, NULL, 1),
(58, '', 2, NULL, NULL, 60, '2025-04-23 10:00:09', 'dev201', 'l', 72, 'published', 0, 1, 1),
(59, '', 2, NULL, NULL, 1, '2025-04-23 10:25:24', 'dev202', 's', 72, 'published', 0, 9, 1),
(60, '', 1, NULL, NULL, 1, '2025-04-23 10:37:43', 'èyèrygf_è\'g', 'iuygu', 72, 'published', 0, 1, 1),
(61, '', 1, NULL, NULL, 1, '2025-04-23 10:40:27', 'pupu', 'pu', 72, 'published', 0, 9, 1),
(62, '', 1, NULL, NULL, 11, '2025-04-23 10:44:21', 'qauyuyf ', 'yyyg', 72, 'published', 0, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `exam_activity_logs`
--

CREATE TABLE `exam_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exam_progress`
--

CREATE TABLE `exam_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `answers` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exam_security_settings`
--

CREATE TABLE `exam_security_settings` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `prevent_copy_paste` tinyint(1) NOT NULL DEFAULT 1,
  `prevent_tab_switching` tinyint(1) NOT NULL DEFAULT 1,
  `prevent_right_click` tinyint(1) NOT NULL DEFAULT 1,
  `fullscreen_mode` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 0,
  `max_warnings` int(11) NOT NULL DEFAULT 5,
  `log_suspicious_activity` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exam_submissions`
--

CREATE TABLE `exam_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `submission_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `type` enum('mcq','open','short') DEFAULT 'mcq',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `question_title` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `points` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `type`, `created_at`, `question_title`, `details`, `points`) VALUES
(90, 56, '', 'mcq', '2025-04-21 08:24:29', 'Que peut-on faire avec Laravel ?', NULL, 3),
(91, 56, '', 'mcq', '2025-04-21 08:24:29', 'Quels fichiers trouve-t-on dans Laravel ?', NULL, 3),
(92, 56, '', 'open', '2025-04-21 08:24:29', 'À quoi sert le fichier .env dans un projet Laravel ?', NULL, 4),
(93, 56, '', 'open', '2025-04-21 08:24:29', 'Que fait la commande php artisan migrate ?', NULL, 4),
(94, 56, '', 'short', '2025-04-21 08:24:29', 'Quelle commande sert à créer un contrôleur ?', NULL, 3),
(95, 56, '', 'short', '2025-04-21 08:24:29', 'Quel dossier contient les routes dans Laravel ?', NULL, 3),
(96, 57, '', 'mcq', '2025-04-22 12:18:50', 'hh', NULL, 1),
(97, 57, '', 'short', '2025-04-22 12:18:50', 'mlkjhg', NULL, 1),
(98, 58, '', 'mcq', '2025-04-23 10:00:09', 'jrjrj', NULL, 1),
(99, 58, '', 'open', '2025-04-23 10:00:09', 'iruriir', NULL, 1),
(100, 59, '', 'short', '2025-04-23 10:25:24', 'ssssssss', NULL, 20),
(101, 60, '', 'short', '2025-04-23 10:37:43', 'iruopû', NULL, 20),
(102, 61, '', 'short', '2025-04-23 10:40:27', 'u$u$u$u$u$u$u$u$u$u$u$u$u$u$u$', NULL, 20),
(103, 62, '', 'short', '2025-04-23 10:44:21', 'jrjjr', NULL, 20);

-- --------------------------------------------------------

--
-- Structure de la table `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`) VALUES
(80, 90, 'A. Créer des sites web', 1),
(81, 90, 'B. Gérer une base de données ', 1),
(82, 90, ' C. Créer des vidéos ', 0),
(83, 90, ' D. Faire des routes  ', 1),
(84, 90, 'E. Écrire du CSS', 0),
(85, 91, 'A. .env  ', 1),
(86, 91, 'B. composer.json ', 1),
(87, 91, ' C. style.css  ', 0),
(88, 91, 'D. routes/web.php  ', 1),
(89, 91, 'E. config/app.php', 1),
(90, 96, 'gg', 0),
(91, 96, 'yyy', 1),
(92, 98, 'rir', 0),
(93, 98, 'iir', 0),
(94, 98, 'iririr', 1),
(95, 98, 'irii', 0);

-- --------------------------------------------------------

--
-- Structure de la table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('pass','fail','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `results`
--

INSERT INTO `results` (`id`, `student_id`, `exam_id`, `score`, `status`, `created_at`) VALUES
(50, 61, 56, NULL, 'pending', '2025-04-21 08:29:53'),
(51, 61, 56, 16.00, 'pending', '2025-04-21 08:30:43'),
(52, 61, 56, 15.00, 'pending', '2025-04-21 22:17:55'),
(53, 74, 56, NULL, 'pending', '2025-04-22 12:08:25'),
(54, 74, 57, NULL, 'pending', '2025-04-22 12:19:10'),
(55, 74, 57, 2.00, 'pending', '2025-04-22 12:19:55'),
(56, 74, 58, NULL, 'pending', '2025-04-23 10:19:23'),
(57, 61, 59, NULL, 'pending', '2025-04-23 10:27:37'),
(58, 61, 57, NULL, 'pending', '2025-04-23 10:28:59'),
(59, 74, 60, NULL, 'pending', '2025-04-23 10:38:16'),
(60, 61, 61, NULL, 'pending', '2025-04-23 10:41:27'),
(61, 74, 62, NULL, 'pending', '2025-04-23 10:44:53');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `option_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points_attributed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `student_answers`
--

INSERT INTO `student_answers` (`id`, `student_id`, `exam_id`, `question_id`, `answer_text`, `option_id`, `is_correct`, `created_at`, `points_attributed`) VALUES
(72, 61, 56, 90, NULL, 80, 1, '2025-04-21 08:29:53', 3),
(73, 61, 56, 90, NULL, 81, 1, '2025-04-21 08:29:53', NULL),
(74, 61, 56, 90, NULL, 83, 1, '2025-04-21 08:29:53', NULL),
(75, 61, 56, 91, NULL, 85, 1, '2025-04-21 08:29:53', 3),
(76, 61, 56, 91, NULL, 86, 1, '2025-04-21 08:29:53', NULL),
(77, 61, 56, 91, NULL, 88, 1, '2025-04-21 08:29:53', NULL),
(78, 61, 56, 91, NULL, 89, 1, '2025-04-21 08:29:53', NULL),
(79, 61, 56, 92, 'oiuytrezaeghjklkjhygfd', NULL, NULL, '2025-04-21 08:29:53', 0),
(80, 61, 56, 93, 'sert a transferer les donnes de user', NULL, NULL, '2025-04-21 08:29:53', 3),
(81, 61, 56, 94, 'php artisan make:controller cntrler_chadi', NULL, NULL, '2025-04-21 08:29:53', 3),
(82, 61, 56, 95, 'routes', NULL, NULL, '2025-04-21 08:29:53', 3),
(83, 74, 56, 90, NULL, 80, 1, '2025-04-22 12:08:25', NULL),
(84, 74, 56, 90, NULL, 82, 0, '2025-04-22 12:08:25', NULL),
(85, 74, 56, 90, NULL, 83, 1, '2025-04-22 12:08:25', NULL),
(86, 74, 56, 91, NULL, 86, 1, '2025-04-22 12:08:25', NULL),
(87, 74, 56, 91, NULL, 88, 1, '2025-04-22 12:08:25', NULL),
(88, 74, 56, 92, 'oiahdoizh', NULL, NULL, '2025-04-22 12:08:25', NULL),
(89, 74, 56, 93, 'd', NULL, NULL, '2025-04-22 12:08:25', NULL),
(90, 74, 56, 94, 'a', NULL, NULL, '2025-04-22 12:08:25', NULL),
(91, 74, 56, 95, 's', NULL, NULL, '2025-04-22 12:08:25', NULL),
(92, 74, 57, 96, NULL, 91, 1, '2025-04-22 12:19:10', 1),
(93, 74, 57, 97, 'ghj', NULL, NULL, '2025-04-22 12:19:10', 1),
(94, 74, 58, 99, '', NULL, NULL, '2025-04-23 10:19:23', NULL),
(95, 61, 59, 100, 'cv', NULL, NULL, '2025-04-23 10:27:37', NULL),
(96, 61, 57, 97, '', NULL, NULL, '2025-04-23 10:28:59', NULL),
(97, 74, 60, 101, 'àçiu$àtrçu\'àr$i tguj', NULL, NULL, '2025-04-23 10:38:16', NULL),
(98, 61, 61, 102, 'lpspl', NULL, NULL, '2025-04-23 10:41:27', NULL),
(99, 74, 62, 103, 'hiutrhgoiu(r', NULL, NULL, '2025-04-23 10:44:53', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') DEFAULT 'student',
  `class` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `class`, `created_at`, `class_id`) VALUES
(61, 'Chadi', 'VM18941@gmail.com', 'VM18941', 'student', 'DEV202', '2025-01-05 20:34:18', 9),
(63, 'admine', 'admine@gmail.com', 'admin1234', 'admin', NULL, '2025-01-06 13:41:37', NULL),
(72, 'el hayanni issam', 'elhayanni@gmail.com', 'elhayanni', 'teacher', NULL, '2025-04-21 06:42:12', NULL),
(74, 'Yassine izi', 'yassineizi@gmail.com', 'yassineizi', 'student', 'DEV201', '2025-04-22 12:06:19', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`Id_c`);

--
-- Index pour la table `cours`
--
ALTER TABLE `cours`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `fk_exams_class` (`class_id`);

--
-- Index pour la table `exam_activity_logs`
--
ALTER TABLE `exam_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_activity_user` (`user_id`),
  ADD KEY `fk_activity_exam` (`exam_id`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Index pour la table `exam_progress`
--
ALTER TABLE `exam_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Index pour la table `exam_security_settings`
--
ALTER TABLE `exam_security_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_settings` (`exam_id`);

--
-- Index pour la table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Index pour la table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Index pour la table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Index pour la table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `option_id` (`option_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `class` (`class`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `class`
--
ALTER TABLE `class`
  MODIFY `Id_c` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT pour la table `exam_activity_logs`
--
ALTER TABLE `exam_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam_progress`
--
ALTER TABLE `exam_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam_security_settings`
--
ALTER TABLE `exam_security_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT pour la table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT pour la table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exams_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`Id_c`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `exam_activity_logs`
--
ALTER TABLE `exam_activity_logs`
  ADD CONSTRAINT `fk_activity_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exam_progress`
--
ALTER TABLE `exam_progress`
  ADD CONSTRAINT `exam_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `exam_progress_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Contraintes pour la table `exam_security_settings`
--
ALTER TABLE `exam_security_settings`
  ADD CONSTRAINT `fk_security_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Contraintes pour la table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `student_answers_ibfk_4` FOREIGN KEY (`option_id`) REFERENCES `question_options` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
