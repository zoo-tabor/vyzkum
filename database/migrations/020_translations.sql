-- Obecna tabulka prekladu pro admin-authored dynamicky obsah (dotazniky ap.).
-- Kanonicka (ceska) data zustavaji ve svych sloupcich; tato tabulka je jen
-- prekladova vrstva klicovana radkovym id entity. Pridani jazyka = nove radky.

CREATE TABLE IF NOT EXISTS translations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  field VARCHAR(40) NOT NULL,
  locale VARCHAR(5) NOT NULL,
  text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY translations_uq (entity_type, entity_id, field, locale),
  INDEX translations_lookup_idx (entity_type, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
