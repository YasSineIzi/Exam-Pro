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
  KEY `fk_activity_user` (`user_id`),
  KEY `fk_activity_exam` (`exam_id`),
  KEY `idx_activity_type` (`activity_type`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 