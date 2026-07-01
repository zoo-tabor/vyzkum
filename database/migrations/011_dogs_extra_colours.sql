-- Upravy fáze 1 - rozšíření psů (země, DNA, GWAS, potvrzení naživu) + barvy plemen.

ALTER TABLE dogs
  ADD COLUMN IF NOT EXISTS country CHAR(3) NULL AFTER pedigree_number,
  ADD COLUMN IF NOT EXISTS dna_isolated_at DATE NULL AFTER sample_received_at,
  ADD COLUMN IF NOT EXISTS gwas_status VARCHAR(20) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS alive_confirmed_at DATE NULL AFTER death_cause;

CREATE TABLE IF NOT EXISTS colours (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  breed_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX colours_breed_idx (breed_id, position),
  CONSTRAINT colours_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
