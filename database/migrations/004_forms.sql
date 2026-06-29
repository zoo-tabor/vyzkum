-- Faze 3 B2 - form builder: definice dotazniku, verze, otazky, moznosti.

CREATE TABLE form_definitions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  breed_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT form_definitions_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE CASCADE,
  CONSTRAINT form_definitions_creator_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX form_definitions_breed_idx (breed_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_versions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_definition_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  archived_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT form_versions_def_fk FOREIGN KEY (form_definition_id) REFERENCES form_definitions(id) ON DELETE CASCADE,
  INDEX form_versions_def_idx (form_definition_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_version_id INT UNSIGNED NOT NULL,
  question_key VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  help_text VARCHAR(255) NULL,
  type VARCHAR(30) NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  config_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT form_questions_version_fk FOREIGN KEY (form_version_id) REFERENCES form_versions(id) ON DELETE CASCADE,
  INDEX form_questions_version_idx (form_version_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_question_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  option_key VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT form_options_question_fk FOREIGN KEY (question_id) REFERENCES form_questions(id) ON DELETE CASCADE,
  INDEX form_options_question_idx (question_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
