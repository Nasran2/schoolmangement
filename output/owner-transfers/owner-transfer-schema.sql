-- Select the EXISTING school database in phpMyAdmin, then import this file.
-- Adds owner transfer corrections and cash returns without resetting any data.
-- Each addition is guarded so repeating the import does not duplicate records or columns.
-- Back up the database before installation. Import BEFORE uploading the new PHP files.

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND COLUMN_NAME = 'destination'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD COLUMN `destination` VARCHAR(190) NULL'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND COLUMN_NAME = 'deleted_at'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND COLUMN_NAME = 'deleted_by'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD COLUMN `deleted_by` BIGINT UNSIGNED NULL'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND COLUMN_NAME = 'deletion_reason'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD COLUMN `deletion_reason` TEXT NULL'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND COLUMN_NAME = 'version'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD COLUMN `version` INT UNSIGNED NOT NULL DEFAULT 0'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

SET @owner_transfer_schema_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'owner_transfers' AND CONSTRAINT_NAME = 'owner_transfers_deleted_by_foreign'),
    'SELECT 1',
    'ALTER TABLE `owner_transfers` ADD CONSTRAINT `owner_transfers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);
PREPARE owner_transfer_schema_stmt FROM @owner_transfer_schema_sql;
EXECUTE owner_transfer_schema_stmt;
DEALLOCATE PREPARE owner_transfer_schema_stmt;

CREATE TABLE IF NOT EXISTS `owner_transfer_returns` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_transfer_id` BIGINT UNSIGNED NOT NULL,
    `submission_id` CHAR(36) NOT NULL,
    `returned_at` DATE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `notes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `owner_transfer_returns_submission_id_unique` (`submission_id`),
    KEY `owner_transfer_returns_returned_at_index` (`returned_at`),
    CONSTRAINT `owner_transfer_returns_owner_transfer_id_foreign` FOREIGN KEY (`owner_transfer_id`) REFERENCES `owner_transfers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `owner_transfer_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The guarded Laravel migration can run later and detects these existing additions.
