-- Check if class_id column exists in exams table
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'exams' 
AND COLUMN_NAME = 'class_id';

-- Add class_id column to exams table if it doesn't exist
SET @query = IF(@columnExists = 0, 
    'ALTER TABLE `exams` ADD COLUMN `class_id` int(11) DEFAULT NULL AFTER `formateur_id`', 
    'SELECT "Column class_id already exists in exams table" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if the foreign key constraint already exists
SET @constraintExists = 0;
SELECT COUNT(*) INTO @constraintExists
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'exams'
AND CONSTRAINT_NAME = 'fk_exams_class';

-- Add foreign key constraint if it doesn't exist
SET @fkQuery = IF(@constraintExists = 0, 
    'ALTER TABLE `exams` 
    ADD CONSTRAINT `fk_exams_class` 
    FOREIGN KEY (`class_id`) REFERENCES `class` (`Id_c`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE', 
    'SELECT "Foreign key constraint fk_exams_class already exists" AS message');

PREPARE fkStmt FROM @fkQuery;
EXECUTE fkStmt;
DEALLOCATE PREPARE fkStmt; 