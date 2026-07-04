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

CREATE TABLE IF NOT EXISTS genes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(64) NOT NULL,
  name VARCHAR(160) NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY genes_symbol_unique (symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS genetic_markers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gene_id INT UNSIGNED NOT NULL,
  marker_code VARCHAR(64) NOT NULL,
  locus VARCHAR(120) NULL,
  reference_allele VARCHAR(20) NULL,
  alternate_allele VARCHAR(20) NULL,
  allowed_values VARCHAR(190) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY genetic_markers_code_unique (marker_code),
  INDEX genetic_markers_gene_idx (gene_id),
  CONSTRAINT genetic_markers_gene_fk FOREIGN KEY (gene_id) REFERENCES genes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS genetic_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  lab_name VARCHAR(160) NULL,
  tested_at DATE NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'manual',
  source_file_id INT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX genetic_tests_dog_idx (dog_id),
  CONSTRAINT genetic_tests_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT genetic_tests_file_fk FOREIGN KEY (source_file_id) REFERENCES files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dog_genotypes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  breed_id INT UNSIGNED NULL,
  marker_id INT UNSIGNED NOT NULL,
  allele_1 VARCHAR(20) NULL,
  allele_2 VARCHAR(20) NULL,
  genotype VARCHAR(40) NOT NULL,
  genetic_test_id INT UNSIGNED NULL,
  validation_status VARCHAR(20) NOT NULL DEFAULT 'imported',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY dog_genotypes_dog_marker_unique (dog_id, marker_id),
  INDEX dog_genotypes_dog_idx (dog_id, marker_id),
  INDEX dog_genotypes_breed_marker_idx (breed_id, marker_id, genotype),
  CONSTRAINT dog_genotypes_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT dog_genotypes_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL,
  CONSTRAINT dog_genotypes_marker_fk FOREIGN KEY (marker_id) REFERENCES genetic_markers(id) ON DELETE CASCADE,
  CONSTRAINT dog_genotypes_test_fk FOREIGN KEY (genetic_test_id) REFERENCES genetic_tests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_threads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  subject VARCHAR(190) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  created_by_user_id INT UNSIGNED NULL,
  last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX message_threads_entity_idx (entity_type, entity_id),
  INDEX message_threads_status_idx (status, last_message_at),
  CONSTRAINT message_threads_creator_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id INT UNSIGNED NOT NULL,
  sender_user_id INT UNSIGNED NULL,
  sender_role VARCHAR(40) NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX messages_thread_idx (thread_id, created_at),
  CONSTRAINT messages_thread_fk FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
  CONSTRAINT messages_sender_fk FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ownership_transfer_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  from_owner_id INT UNSIGNED NULL,
  new_owner_name VARCHAR(190) NOT NULL,
  new_owner_email VARCHAR(190) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  invite_token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  confirmed_at DATETIME NULL,
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ownership_transfer_token_unique (invite_token_hash),
  INDEX ownership_transfer_dog_idx (dog_id, status),
  CONSTRAINT ownership_transfer_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT ownership_transfer_from_fk FOREIGN KEY (from_owner_id) REFERENCES owners(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS health_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  breed_id INT UNSIGNED NULL,
  event_type VARCHAR(40) NOT NULL,
  event_date DATE NULL,
  event_end_date DATE NULL,
  source_type VARCHAR(40) NOT NULL DEFAULT 'manual',
  source_id INT UNSIGNED NULL,
  normalized_code VARCHAR(120) NULL,
  value_json JSON NULL,
  note TEXT NULL,
  validation_status VARCHAR(20) NOT NULL DEFAULT 'unvalidated',
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX health_events_breed_idx (breed_id, event_type, event_date),
  INDEX health_events_dog_idx (dog_id, event_type),
  CONSTRAINT health_events_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT health_events_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL,
  CONSTRAINT health_events_creator_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS form_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_definition_id INT UNSIGNED NOT NULL,
  form_version_id INT UNSIGNED NOT NULL,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NULL,
  recipient_email VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'sent',
  email_status VARCHAR(20) NULL,
  form_response_id INT UNSIGNED NULL,
  sent_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX form_assignments_def_idx (form_definition_id, status),
  INDEX form_assignments_dog_idx (dog_id),
  CONSTRAINT form_assignments_def_fk FOREIGN KEY (form_definition_id) REFERENCES form_definitions(id) ON DELETE CASCADE,
  CONSTRAINT form_assignments_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT form_assignments_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE owners
  ADD COLUMN IF NOT EXISTS onboarding_completed_at DATETIME NULL;

ALTER TABLE ownership_transfer_requests
  ADD COLUMN IF NOT EXISTS new_owner_phone VARCHAR(40) NULL AFTER new_owner_email;

ALTER TABLE dog_genotypes
  ADD COLUMN IF NOT EXISTS gene_id INT UNSIGNED NULL AFTER marker_id;

UPDATE dog_genotypes g
  JOIN genetic_markers m ON m.id = g.marker_id
  SET g.gene_id = m.gene_id
  WHERE g.gene_id IS NULL;

ALTER TABLE dog_genotypes
  ADD INDEX IF NOT EXISTS dog_genotypes_dog_gene_idx (dog_id, gene_id);

CREATE TABLE IF NOT EXISTS message_reads (
  thread_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  last_read_at DATETIME NOT NULL,
  PRIMARY KEY (thread_id, user_id),
  CONSTRAINT message_reads_thread_fk FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
  CONSTRAINT message_reads_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS death_causes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  breed_id INT UNSIGNED NULL,
  parent_id INT UNSIGNED NULL,
  code VARCHAR(20) NOT NULL,
  label VARCHAR(190) NOT NULL,
  has_note TINYINT(1) NOT NULL DEFAULT 0,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY death_causes_breed_code_uq (breed_id, code),
  INDEX death_causes_parent_idx (parent_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE dogs
  ADD COLUMN IF NOT EXISTS death_cause_id INT UNSIGNED NULL AFTER death_cause,
  ADD COLUMN IF NOT EXISTS death_cause_note TEXT NULL AFTER death_cause_id;

-- Plemeno cavalier (seznam je navazany na nej).
SET @cav := (SELECT id FROM breeds WHERE slug = 'cavalier-king-charles-spaniel' LIMIT 1);

-- Pojistka: pripadny drivejsi globalni seznam (breed_id NULL) premapovat na cavaliera.
UPDATE death_causes SET breed_id = @cav WHERE breed_id IS NULL AND @cav IS NOT NULL;

-- Seed (idempotentni: jen kdyz plemeno existuje a jeste nema koren seznamu).
SET @seeded := (SELECT COUNT(*) FROM death_causes WHERE breed_id = @cav AND code = '1');
INSERT INTO death_causes (breed_id, code, label, has_note, position)
SELECT @cav, code, label, has_note, position FROM (
  SELECT '1' AS code, 'Nemoc' AS label, 0 AS has_note, 1 AS position
  UNION ALL
  SELECT '1.1' AS code, 'Endokrinní onemocnění' AS label, 0 AS has_note, 2 AS position
  UNION ALL
  SELECT '1.1.1' AS code, 'Cukrovka' AS label, 0 AS has_note, 3 AS position
  UNION ALL
  SELECT '1.1.2' AS code, 'Cushingův syndrom' AS label, 0 AS has_note, 4 AS position
  UNION ALL
  SELECT '1.1.3' AS code, 'Hypotyreóza' AS label, 0 AS has_note, 5 AS position
  UNION ALL
  SELECT '1.1.4' AS code, 'Jiné endokrinní onemocnění' AS label, 1 AS has_note, 6 AS position
  UNION ALL
  SELECT '1.2' AS code, 'Imunologické onemocnění' AS label, 0 AS has_note, 7 AS position
  UNION ALL
  SELECT '1.2.1' AS code, 'Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)' AS label, 0 AS has_note, 8 AS position
  UNION ALL
  SELECT '1.2.2' AS code, 'Trombocytopenie' AS label, 0 AS has_note, 9 AS position
  UNION ALL
  SELECT '1.2.3' AS code, 'Jiné imunologické onemocnění' AS label, 1 AS has_note, 10 AS position
  UNION ALL
  SELECT '1.3' AS code, 'Kožní onemocnění' AS label, 0 AS has_note, 11 AS position
  UNION ALL
  SELECT '1.3.1' AS code, 'Jiné kožní onemocnění' AS label, 1 AS has_note, 12 AS position
  UNION ALL
  SELECT '1.4' AS code, 'Nádorová onemocnění' AS label, 0 AS has_note, 13 AS position
  UNION ALL
  SELECT '1.4.1' AS code, 'Lymfom' AS label, 0 AS has_note, 14 AS position
  UNION ALL
  SELECT '1.4.2' AS code, 'Nádor jater, ledvin nebo střevního traktu' AS label, 0 AS has_note, 15 AS position
  UNION ALL
  SELECT '1.4.3' AS code, 'Nádor kostí nebo kloubů' AS label, 0 AS has_note, 16 AS position
  UNION ALL
  SELECT '1.4.4' AS code, 'Nádor kůže nebo podkoží' AS label, 0 AS has_note, 17 AS position
  UNION ALL
  SELECT '1.4.5' AS code, 'Nádor mléčné žlázy' AS label, 0 AS has_note, 18 AS position
  UNION ALL
  SELECT '1.4.6' AS code, 'Nádor močového měchýře' AS label, 0 AS has_note, 19 AS position
  UNION ALL
  SELECT '1.4.7' AS code, 'Nádor nervové soustavy' AS label, 0 AS has_note, 20 AS position
  UNION ALL
  SELECT '1.4.8' AS code, 'Nádor plic' AS label, 0 AS has_note, 21 AS position
  UNION ALL
  SELECT '1.4.9' AS code, 'Nádor sleziny, srdce nebo cévního systému' AS label, 0 AS has_note, 22 AS position
  UNION ALL
  SELECT '1.4.10' AS code, 'Jiné nádorové onemocnění' AS label, 1 AS has_note, 23 AS position
  UNION ALL
  SELECT '1.5' AS code, 'Neurologické onemocnění' AS label, 0 AS has_note, 24 AS position
  UNION ALL
  SELECT '1.5.1' AS code, 'Epilepsie' AS label, 0 AS has_note, 25 AS position
  UNION ALL
  SELECT '1.5.2' AS code, 'Syringomyelie' AS label, 0 AS has_note, 26 AS position
  UNION ALL
  SELECT '1.5.3' AS code, 'Jiné neurologické onemocnění' AS label, 1 AS has_note, 27 AS position
  UNION ALL
  SELECT '1.6' AS code, 'Oční onemocnění' AS label, 0 AS has_note, 28 AS position
  UNION ALL
  SELECT '1.6.1' AS code, 'Slepota' AS label, 0 AS has_note, 29 AS position
  UNION ALL
  SELECT '1.6.2' AS code, 'Syndrom suchého oka' AS label, 0 AS has_note, 30 AS position
  UNION ALL
  SELECT '1.6.3' AS code, 'Jiné oční onemocnění' AS label, 1 AS has_note, 31 AS position
  UNION ALL
  SELECT '1.7' AS code, 'Onemocnění pohybového aparátu' AS label, 0 AS has_note, 32 AS position
  UNION ALL
  SELECT '1.7.1' AS code, 'Artróza jiného kloubu než kyčelního nebo loketního' AS label, 0 AS has_note, 33 AS position
  UNION ALL
  SELECT '1.7.2' AS code, 'Deformující spondylóza' AS label, 0 AS has_note, 34 AS position
  UNION ALL
  SELECT '1.7.3' AS code, 'Dysplazie kyčelního kloubu a následná artróza' AS label, 0 AS has_note, 35 AS position
  UNION ALL
  SELECT '1.7.4' AS code, 'Dysplazie loketního kloubu a následná artróza' AS label, 0 AS has_note, 36 AS position
  UNION ALL
  SELECT '1.7.5' AS code, 'Imunitně zprostředkovaná polyartritida' AS label, 0 AS has_note, 37 AS position
  UNION ALL
  SELECT '1.7.6' AS code, 'Jiná dysplazie kostí nebo kloubů' AS label, 0 AS has_note, 38 AS position
  UNION ALL
  SELECT '1.7.7' AS code, 'Luxace čéšky' AS label, 0 AS has_note, 39 AS position
  UNION ALL
  SELECT '1.7.8' AS code, 'Poranění předního zkříženého vazu' AS label, 0 AS has_note, 40 AS position
  UNION ALL
  SELECT '1.7.9' AS code, 'Syndrom kaudy equiny' AS label, 0 AS has_note, 41 AS position
  UNION ALL
  SELECT '1.7.10' AS code, 'Výhřez meziobratlové ploténky' AS label, 0 AS has_note, 42 AS position
  UNION ALL
  SELECT '1.7.11' AS code, 'Jiné onemocnění pohybového aparátu' AS label, 1 AS has_note, 43 AS position
  UNION ALL
  SELECT '1.8' AS code, 'Onemocnění trávicí soustavy' AS label, 0 AS has_note, 44 AS position
  UNION ALL
  SELECT '1.8.1' AS code, 'Exokrinní pankreatická insuficience (EPI)' AS label, 0 AS has_note, 45 AS position
  UNION ALL
  SELECT '1.8.2' AS code, 'Jaterní insuficience / selhání jater' AS label, 0 AS has_note, 46 AS position
  UNION ALL
  SELECT '1.8.3' AS code, 'Megaezofagus' AS label, 0 AS has_note, 47 AS position
  UNION ALL
  SELECT '1.8.4' AS code, 'Neprůchodnost střeva způsobená cizím tělesem' AS label, 0 AS has_note, 48 AS position
  UNION ALL
  SELECT '1.8.5' AS code, 'Jiné onemocnění trávicí soustavy' AS label, 1 AS has_note, 49 AS position
  UNION ALL
  SELECT '1.9' AS code, 'Respirační onemocnění' AS label, 0 AS has_note, 50 AS position
  UNION ALL
  SELECT '1.9.1' AS code, 'Kolaps průdušnice' AS label, 0 AS has_note, 51 AS position
  UNION ALL
  SELECT '1.9.2' AS code, 'Pneumonie' AS label, 0 AS has_note, 52 AS position
  UNION ALL
  SELECT '1.9.3' AS code, 'Jiné respirační onemocnění' AS label, 1 AS has_note, 53 AS position
  UNION ALL
  SELECT '1.10' AS code, 'Srdeční onemocnění' AS label, 0 AS has_note, 54 AS position
  UNION ALL
  SELECT '1.10.1' AS code, 'Endokardióza' AS label, 0 AS has_note, 55 AS position
  UNION ALL
  SELECT '1.10.2' AS code, 'Kardiomyopatie' AS label, 0 AS has_note, 56 AS position
  UNION ALL
  SELECT '1.10.3' AS code, 'Jiné srdeční onemocnění' AS label, 1 AS has_note, 57 AS position
  UNION ALL
  SELECT '1.11' AS code, 'Urologická onemocnění' AS label, 0 AS has_note, 58 AS position
  UNION ALL
  SELECT '1.11.1' AS code, 'Infekce dělohy / pyometra' AS label, 0 AS has_note, 59 AS position
  UNION ALL
  SELECT '1.11.2' AS code, 'Ledvinové kameny' AS label, 0 AS has_note, 60 AS position
  UNION ALL
  SELECT '1.11.3' AS code, 'Močová inkontinence' AS label, 0 AS has_note, 61 AS position
  UNION ALL
  SELECT '1.11.4' AS code, 'Selhání ledvin' AS label, 0 AS has_note, 62 AS position
  UNION ALL
  SELECT '1.11.5' AS code, 'Jiné urologické onemocnění' AS label, 1 AS has_note, 63 AS position
  UNION ALL
  SELECT '1.12' AS code, 'Ušní onemocnění' AS label, 0 AS has_note, 64 AS position
  UNION ALL
  SELECT '1.12.1' AS code, 'Chronický nebo opakovaný zánět ucha' AS label, 0 AS has_note, 65 AS position
  UNION ALL
  SELECT '1.12.2' AS code, 'Jiné ušní onemocnění' AS label, 1 AS has_note, 66 AS position
  UNION ALL
  SELECT '1.13' AS code, 'Vrozená vada' AS label, 0 AS has_note, 67 AS position
  UNION ALL
  SELECT '1.13.1' AS code, 'Jiná vývojová porucha' AS label, 0 AS has_note, 68 AS position
  UNION ALL
  SELECT '1.13.2' AS code, 'Vrozená anomálie obratlů' AS label, 0 AS has_note, 69 AS position
  UNION ALL
  SELECT '1.13.3' AS code, 'Vrozená vada nebo malformace štěněte' AS label, 0 AS has_note, 70 AS position
  UNION ALL
  SELECT '1.13.4' AS code, 'Vrozená vývojová vada srdce' AS label, 0 AS has_note, 71 AS position
  UNION ALL
  SELECT '1.13.5' AS code, 'Jiné vrozené onemocnění' AS label, 1 AS has_note, 72 AS position
  UNION ALL
  SELECT '1.14' AS code, 'Jiné nespecifikované onemocnění' AS label, 1 AS has_note, 73 AS position
  UNION ALL
  SELECT '2' AS code, 'Stáří' AS label, 0 AS has_note, 74 AS position
  UNION ALL
  SELECT '3' AS code, 'Nehoda' AS label, 1 AS has_note, 75 AS position
  UNION ALL
  SELECT '4' AS code, 'Jiné' AS label, 1 AS has_note, 76 AS position
) t WHERE @cav IS NOT NULL AND @seeded = 0;

-- Napojeni parent_id podle kodu (napr. 1.10.1 -> 1.10).
UPDATE death_causes c
  JOIN death_causes p ON p.breed_id <=> c.breed_id
     AND p.code = SUBSTRING_INDEX(c.code, '.', LENGTH(c.code) - LENGTH(REPLACE(c.code, '.', '')))
  SET c.parent_id = p.id
  WHERE LOCATE('.', c.code) > 0;

-- DNA izolace + GWAS se vedou na vzorku (samples), ne na psovi. Sloupce
-- dogs.dna_isolated_at / dogs.gwas_status zustavaji jako legacy (necteme je).
ALTER TABLE samples
  ADD COLUMN IF NOT EXISTS dna_isolated_at DATE NULL AFTER received_at,
  ADD COLUMN IF NOT EXISTS gwas_status VARCHAR(20) NULL AFTER dna_isolated_at,
  ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER gwas_status;

-- Backfill z psa na jeho nejnovejsi vzorek (idempotentni).
UPDATE samples s
  JOIN dogs d ON d.id = s.dog_id
  JOIN (
    SELECT dog_id,
           CAST(SUBSTRING_INDEX(
             GROUP_CONCAT(id ORDER BY (received_at IS NULL), received_at DESC, id DESC),
             ',', 1) AS UNSIGNED) AS newest_id
    FROM samples
    WHERE dog_id IS NOT NULL
    GROUP BY dog_id
  ) pick ON pick.dog_id = s.dog_id AND pick.newest_id = s.id
  SET s.dna_isolated_at = d.dna_isolated_at,
      s.gwas_status = d.gwas_status
  WHERE (d.dna_isolated_at IS NOT NULL OR d.gwas_status IS NOT NULL)
    AND s.dna_isolated_at IS NULL AND s.gwas_status IS NULL;

-- Oznaceni migraci jako provedenych (bez chyby, kdyz uz tam jsou).
INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_core.sql'), ('002_dogs_owners.sql'), ('003_invites_mail.sql'),
       ('004_forms.sql'), ('005_form_responses.sql'), ('006_samples.sql'),
       ('007_genetics.sql'), ('008_messages.sql'), ('009_ownership_transfer.sql'),
       ('010_health_events.sql'), ('011_dogs_extra_colours.sql'),
       ('012_form_assignments.sql'), ('013_owner_onboarding.sql'),
       ('014_genotype_gene.sql'), ('015_message_reads.sql'),
       ('016_death_causes.sql'), ('017_sample_dna_gwas.sql');
