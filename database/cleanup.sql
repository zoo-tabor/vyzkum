-- =====================================================================
-- UPLNY CLEANUP DATABAZE
-- Smaze VSECHNY tabulky v aktualne vybrane databazi (jedeme od nuly).
-- Spustte v phpMyAdmin na databazi `d328675_vyzkum` (data jsou zalohovana!).
-- Po spusteni pokracujte importem `database/install.sql`.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

SET @tables = NULL;
SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';

SET @drop = IFNULL(CONCAT('DROP TABLE IF EXISTS ', @tables), 'SELECT "Zadne tabulky ke smazani"');
PREPARE stmt FROM @drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- Pokud by v DB byly i pohledy (views), smazte je rucne:
--   DROP VIEW IF EXISTS nazev_view;
--
-- Alternativa bez prepared statementu (explicitni vyjmenovani starych tabulek):
--   SET FOREIGN_KEY_CHECKS = 0;
--   DROP TABLE IF EXISTS audit_logs, lab_records, pedigree_documents, consents,
--                        dogs, owners, samples, sample_batches, vets,
--                        schema_migrations;
--   SET FOREIGN_KEY_CHECKS = 1;
