-- =====================================================================
-- RECONCILE SCHEMA - idempotentni (CREATE TABLE IF NOT EXISTS).
-- Bezpecne spustit kdykoli v phpMyAdmin: doplni chybejici tabulky,
-- existujici (vcetne dat) nechá byt. Pokryva migrace 001 + 002.
-- =====================================================================

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(190) NOT NULL PRIMARY KEY,
  executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS breeds (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  club_id INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY breeds_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'owner',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  totp_secret VARCHAR(64) NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY users_email_unique (email),
  INDEX users_role_idx (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_throttle (
  throttle_key VARCHAR(190) NOT NULL PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  INDEX login_throttle_expires_idx (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_breed_access (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  breed_id INT UNSIGNED NOT NULL,
  access_level VARCHAR(40) NOT NULL DEFAULT 'read',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY user_breed_unique (user_id, breed_id),
  CONSTRAINT uba_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uba_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT UNSIGNED NULL,
  actor_role VARCHAR(40) NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NULL,
  old_values_json JSON NULL,
  new_values_json JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX audit_entity_idx (entity_type, entity_id, created_at),
  INDEX audit_actor_idx (actor_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  display_name VARCHAR(190) NOT NULL,
  first_name VARCHAR(120) NULL,
  last_name VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  preferred_contact_method VARCHAR(20) NOT NULL DEFAULT 'email',
  contact_consent TINYINT(1) NOT NULL DEFAULT 0,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT owners_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX owners_name_idx (display_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owner_emails (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id INT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  verified_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT owner_emails_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
  INDEX owner_emails_email_idx (email),
  INDEX owner_emails_owner_idx (owner_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owner_phones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id INT UNSIGNED NOT NULL,
  phone VARCHAR(60) NOT NULL,
  label VARCHAR(60) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT owner_phones_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
  INDEX owner_phones_owner_idx (owner_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dogs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  breed_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  kennel_name VARCHAR(160) NULL,
  chip_number VARCHAR(40) NULL,
  pedigree_number VARCHAR(120) NULL,
  sex ENUM('male','female','unknown') NOT NULL DEFAULT 'unknown',
  birth_date DATE NULL,
  death_date DATE NULL,
  death_cause VARCHAR(255) NULL,
  castration_status VARCHAR(40) NULL,
  castration_date DATE NULL,
  color VARCHAR(80) NULL,
  test_group VARCHAR(80) NULL,
  health_summary TEXT NULL,
  sample_received_at DATE NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT dogs_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE RESTRICT,
  INDEX dogs_breed_name_idx (breed_id, name),
  INDEX dogs_chip_idx (chip_number),
  INDEX dogs_pedigree_idx (pedigree_number),
  INDEX dogs_breed_status_idx (breed_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dog_owners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NOT NULL,
  relationship_type VARCHAR(40) NOT NULL DEFAULT 'owner',
  is_current TINYINT(1) NOT NULL DEFAULT 1,
  valid_from DATE NULL,
  valid_to DATE NULL,
  confirmed_at DATETIME NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT dog_owners_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT dog_owners_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE RESTRICT,
  INDEX dog_owners_owner_idx (owner_id, is_current),
  INDEX dog_owners_dog_idx (dog_id, is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dog_death_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NULL,
  death_date DATE NULL,
  death_cause VARCHAR(255) NULL,
  note TEXT NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'owner',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT dog_death_reports_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT dog_death_reports_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_type VARCHAR(40) NOT NULL,
  owner_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  size INT UNSIGNED NOT NULL,
  storage_disk VARCHAR(40) NOT NULL DEFAULT 'local',
  uploaded_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX files_owner_idx (owner_type, owner_id),
  CONSTRAINT files_uploader_fk FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS health_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NULL,
  file_id INT UNSIGNED NULL,
  document_type VARCHAR(80) NULL,
  document_date DATE NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT health_documents_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT health_documents_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL,
  CONSTRAINT health_documents_file_fk FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_invites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  owner_id INT UNSIGNED NULL,
  token_hash CHAR(64) NOT NULL,
  purpose VARCHAR(40) NOT NULL DEFAULT 'set_password',
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY password_invites_token_unique (token_hash),
  INDEX password_invites_user_idx (user_id),
  INDEX password_invites_owner_idx (owner_id),
  CONSTRAINT password_invites_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT password_invites_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL,
  CONSTRAINT password_invites_creator_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  template VARCHAR(80) NULL,
  status VARCHAR(20) NOT NULL,
  error VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX email_log_recipient_idx (recipient),
  INDEX email_log_created_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_definitions (
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

CREATE TABLE IF NOT EXISTS form_versions (
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

CREATE TABLE IF NOT EXISTS form_questions (
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

CREATE TABLE IF NOT EXISTS form_question_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  option_key VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT form_options_question_fk FOREIGN KEY (question_id) REFERENCES form_questions(id) ON DELETE CASCADE,
  INDEX form_options_question_idx (question_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_responses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_version_id INT UNSIGNED NOT NULL,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NULL,
  submitted_by_user_id INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'submitted',
  note TEXT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT form_responses_version_fk FOREIGN KEY (form_version_id) REFERENCES form_versions(id) ON DELETE CASCADE,
  CONSTRAINT form_responses_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT form_responses_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL,
  INDEX form_responses_dog_idx (dog_id, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  response_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  value_text TEXT NULL,
  value_number DECIMAL(20,6) NULL,
  value_date DATE NULL,
  value_json JSON NULL,
  option_id INT UNSIGNED NULL,
  CONSTRAINT form_answers_response_fk FOREIGN KEY (response_id) REFERENCES form_responses(id) ON DELETE CASCADE,
  CONSTRAINT form_answers_question_fk FOREIGN KEY (question_id) REFERENCES form_questions(id) ON DELETE CASCADE,
  INDEX form_answers_response_idx (response_id),
  INDEX form_answers_question_idx (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  clinic_name VARCHAR(160) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sample_batches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(160) NULL,
  breed_id INT UNSIGNED NULL,
  vet_id INT UNSIGNED NULL,
  sample_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT sample_batches_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL,
  CONSTRAINT sample_batches_vet_fk FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE SET NULL,
  CONSTRAINT sample_batches_creator_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS samples (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id VARCHAR(40) NOT NULL,
  batch_id INT UNSIGNED NULL,
  batch_sequence INT UNSIGNED NULL,
  breed_id INT UNSIGNED NULL,
  dog_id INT UNSIGNED NULL,
  vet_id INT UNSIGNED NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'created',
  vet_token_hash CHAR(64) NOT NULL,
  owner_token_hash CHAR(64) NOT NULL,
  vet_token VARCHAR(128) NULL,
  owner_token VARCHAR(128) NULL,
  vet_submitted_at DATETIME NULL,
  owner_submitted_at DATETIME NULL,
  sample_type VARCHAR(80) NULL,
  sample_type_other VARCHAR(120) NULL,
  material_count VARCHAR(40) NULL,
  collection_date DATE NULL,
  chip_number_vet VARCHAR(32) NULL,
  received_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY samples_sample_id_unique (sample_id),
  INDEX samples_breed_status_idx (breed_id, status),
  INDEX samples_dog_idx (dog_id),
  INDEX samples_batch_idx (batch_id),
  CONSTRAINT samples_batch_fk FOREIGN KEY (batch_id) REFERENCES sample_batches(id) ON DELETE SET NULL,
  CONSTRAINT samples_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL,
  CONSTRAINT samples_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE SET NULL,
  CONSTRAINT samples_vet_fk FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id INT UNSIGNED NOT NULL,
  dog_id INT UNSIGNED NULL,
  owner_id INT UNSIGNED NULL,
  consent_version VARCHAR(40) NOT NULL,
  research_consent TINYINT(1) NOT NULL DEFAULT 0,
  gdpr_consent TINYINT(1) NOT NULL DEFAULT 0,
  future_contact_consent TINYINT(1) NOT NULL DEFAULT 0,
  results_consent TINYINT(1) NOT NULL DEFAULT 0,
  owner_name_at_consent VARCHAR(160) NOT NULL,
  ip_address VARCHAR(45) NULL,
  consented_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT consents_sample_fk FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE,
  CONSTRAINT consents_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE SET NULL,
  CONSTRAINT consents_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oznaceni migraci jako provedenych (bez chyby, kdyz uz tam jsou).
INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_core.sql'), ('002_dogs_owners.sql'), ('003_invites_mail.sql'),
       ('004_forms.sql'), ('005_form_responses.sql'), ('006_samples.sql');
