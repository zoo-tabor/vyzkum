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

-- Tabulka `consents` zrusena: informovany souhlas (/gdpr) je povinny a uklada se
-- do owners.contact_consent (sjednoceno s onboardingem/adminem). DROP se provede rucne.

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

-- Zdroj genotypu (sekvenace/GWAS) + poznamky ke genotypu a k definici genu.
ALTER TABLE dog_genotypes
  ADD COLUMN IF NOT EXISTS source VARCHAR(40) NULL AFTER validation_status,
  ADD COLUMN IF NOT EXISTS note VARCHAR(255) NULL AFTER source;

ALTER TABLE genes
  ADD COLUMN IF NOT EXISTS note VARCHAR(255) NULL AFTER description;

-- Stavajici genotypy pochazeji z PCR sekvenace (idempotentni).
UPDATE dog_genotypes SET source = 'sekvenace' WHERE source IS NULL;

-- i18n: preferovany jazyk rozhrani majitele.
ALTER TABLE owners
  ADD COLUMN IF NOT EXISTS language VARCHAR(5) NULL AFTER preferred_contact_method;

-- i18n: obecna prekladova vrstva pro admin-authored obsah (dotazniky ap.).
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

-- Sablony transakcnich e-mailu (cesky zdroj editovatelny z UI, preklady v translations).
CREATE TABLE IF NOT EXISTS email_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(64) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  placeholders VARCHAR(255) NULL,
  updated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY email_templates_key_uq (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO email_templates (`key`, subject, body, placeholders) VALUES
('set_password', 'Nastavení hesla - Výzkum ZOO Tábor',
'Dobrý den,

do systému výzkumu plemen psů ZOO Tábor vám byl založen účet.
Pro nastavení hesla použijte tento odkaz (platí 1 měsíc):

{odkaz}

Po nastavení hesla se budete moci přihlásit a vidět své psy.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('password_reset', 'Obnova hesla - Výzkum ZOO Tábor',
'Dobrý den,

obdrželi jsme žádost o obnovu hesla k vašemu účtu ve výzkumu plemen psů ZOO Tábor.
Nové heslo si nastavíte tímto odkazem (platí 2 hodiny):

{odkaz}

Pokud jste o obnovu hesla nežádali, tento e-mail ignorujte - vaše heslo zůstává beze změny.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('ownership_transfer', 'Převzetí psa - Výzkum ZOO Tábor',
'Dobrý den,

stávající majitel vás uvedl jako nového majitele psa v rámci výzkumu plemen psů ZOO Tábor.
Pro potvrzení převzetí psa použijte tento odkaz (platí 1 měsíc):

{odkaz}

Po potvrzení vám přijde odkaz pro nastavení hesla do portálu.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('form_broadcast', 'Dotazník k vašemu psovi - Výzkum ZOO Tábor',
'Dobrý den,

v rámci výzkumu dlouhověkosti psů ZOO Tábor vás prosíme o vyplnění dotazníku "{dotaznik}" k vašemu psovi {pes}.

Dotazník vyplníte po přihlášení do portálu zde:
{odkaz}

Předem děkujeme za spolupráci.

S pozdravem
Výzkumný tým ZOO Tábor', '{dotaznik}, {pes}, {majitel}, {odkaz}');

-- =====================================================================
-- i18n SEED: preklady staticky seedovanych dat (idempotentni).
-- Klicovano stabilnim klicem, entity_id se resi SELECTem (netvrdi natvrdo):
--   death_cause/label -> death_causes WHERE breed_id=@cav AND code=...
--   breed/name        -> breeds WHERE slug=...
-- Prazdne preklady se nevkladaji (fallback na cesky zdroj). @cav viz vyse.
-- =====================================================================

-- death_cause / label (priciny umrti, plemeno cavalier @cav)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Krankheit' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Endokrine Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Diabetes mellitus' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Cushing-Syndrom' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Hypothyreose' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige endokrine Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Immunologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Immunvermittelte hämolytische Anämie (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Thrombozytopenie' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige immunologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Hauterkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Hauterkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Tumorerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Lymphom' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Tumor der Leber, Nieren oder des Darmtrakts' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Knochen- oder Gelenktumor' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Tumor der Haut oder Unterhaut' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Mammatumor' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Harnblasentumor' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Tumor des Nervensystems' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Lungentumor' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Tumor der Milz, des Herzens oder des Gefäßsystems' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Tumorerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Neurologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Epilepsie' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Syringomyelie' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige neurologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Augenerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Blindheit' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Syndrom des trockenen Auges' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Augenerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Erkrankung des Bewegungsapparats' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Arthrose eines anderen Gelenks als Hüfte oder Ellbogen' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Spondylosis deformans' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Hüftgelenksdysplasie mit nachfolgender Arthrose' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Ellbogengelenksdysplasie mit nachfolgender Arthrose' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Immunvermittelte Polyarthritis' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Knochen- oder Gelenkdysplasie' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Patellaluxation' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Vorderer Kreuzbandriss' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Cauda-equina-Syndrom' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Bandscheibenvorfall' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Erkrankung des Bewegungsapparats' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Erkrankung des Verdauungstrakts' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Exokrine Pankreasinsuffizienz (EPI)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Leberinsuffizienz / Leberversagen' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Megaösophagus' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Darmverschluss durch einen Fremdkörper' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Erkrankung des Verdauungstrakts' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Atemwegserkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Trachealkollaps' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Pneumonie' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Atemwegserkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Herzerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Endokardiose' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Kardiomyopathie' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Herzerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Urologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Gebärmutterentzündung / Pyometra' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Nierensteine' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Harninkontinenz' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Nierenversagen' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige urologische Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Ohrerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Chronische oder wiederkehrende Ohrentzündung' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Ohrerkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Angeborener Defekt' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige Entwicklungsstörung' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Angeborene Wirbelanomalie' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Angeborener Defekt oder Fehlbildung des Welpen' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Angeborener Herzfehler' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige angeborene Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstige, nicht näher bezeichnete Erkrankung' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Altersschwäche' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Unfall' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','de','Sonstiges' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Disease' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Endocrine disease' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Diabetes mellitus' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Cushing''s syndrome' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Hypothyroidism' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other endocrine disease' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Immunological disease' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Immune-mediated hemolytic anemia (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Thrombocytopenia' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other immunological disease' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Skin disease' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other skin disease' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Neoplastic disease' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Lymphoma' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tumour of the liver, kidney or intestinal tract' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tumour of the bones or joints' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tumour of the skin or subcutis' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Mammary gland tumour' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Urinary bladder tumour' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tumour of the nervous system' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Lung tumour' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tumour of the spleen, heart or vascular system' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other neoplastic disease' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Neurological disease' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Epilepsy' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Syringomyelia' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other neurological disease' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Eye disease' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Blindness' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Dry eye syndrome (keratoconjunctivitis sicca)' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other eye disease' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Musculoskeletal disease' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Osteoarthritis of a joint other than the hip or elbow' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Spondylosis deformans' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Hip dysplasia and subsequent osteoarthritis' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Elbow dysplasia and subsequent osteoarthritis' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Immune-mediated polyarthritis' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other bone or joint dysplasia' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Patellar luxation' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Cranial cruciate ligament injury' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Cauda equina syndrome' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Intervertebral disc herniation' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other musculoskeletal disease' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Digestive system disease' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Exocrine pancreatic insufficiency (EPI)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Hepatic insufficiency / liver failure' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Megaesophagus' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Intestinal obstruction caused by a foreign body' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other digestive system disease' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Respiratory disease' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Tracheal collapse' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Pneumonia' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other respiratory disease' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Heart disease' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Endocardiosis' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Cardiomyopathy' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other heart disease' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Urological disease' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Uterine infection / pyometra' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Kidney stones' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Urinary incontinence' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Kidney failure' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other urological disease' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Ear disease' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Chronic or recurrent ear inflammation' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other ear disease' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Congenital defect' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other developmental disorder' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Congenital vertebral anomaly' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Congenital defect or malformation of the puppy' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Congenital heart defect' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other congenital disease' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other unspecified disease' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Old age' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Accident' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','en','Other' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad endocrina' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Diabetes mellitus' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Síndrome de Cushing' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Hipotiroidismo' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad endocrina' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad inmunológica' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Anemia hemolítica inmunomediada (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Trombocitopenia' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad inmunológica' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad cutánea' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad cutánea' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad neoplásica' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Linfoma' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de hígado, riñón o tracto intestinal' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de huesos o articulaciones' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de piel o tejido subcutáneo' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de glándula mamaria' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de vejiga urinaria' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor del sistema nervioso' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de pulmón' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Tumor de bazo, corazón o sistema vascular' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad neoplásica' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad neurológica' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Epilepsia' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Siringomielia' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad neurológica' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad ocular' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Ceguera' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Síndrome del ojo seco' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad ocular' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad del aparato locomotor' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Artrosis de una articulación distinta de la cadera o el codo' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Espondilosis deformante' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Displasia de cadera y artrosis secundaria' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Displasia de codo y artrosis secundaria' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Poliartritis inmunomediada' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra displasia ósea o articular' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Luxación de rótula' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Lesión del ligamento cruzado anterior' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Síndrome de cauda equina' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Hernia de disco intervertebral' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad del aparato locomotor' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad del aparato digestivo' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Insuficiencia pancreática exocrina (IPE)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Insuficiencia hepática / fallo hepático' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Megaesófago' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Obstrucción intestinal causada por un cuerpo extraño' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad del aparato digestivo' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad respiratoria' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Colapso traqueal' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Neumonía' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad respiratoria' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad cardíaca' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Endocardiosis' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Cardiomiopatía' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad cardíaca' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad urológica' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Infección uterina / piómetra' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Cálculos renales' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Incontinencia urinaria' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Insuficiencia renal' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad urológica' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Enfermedad del oído' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otitis crónica o recurrente' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad del oído' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Defecto congénito' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otro trastorno del desarrollo' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Anomalía vertebral congénita' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Defecto o malformación congénita del cachorro' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Cardiopatía congénita' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad congénita' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otra enfermedad no especificada' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Vejez' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Accidente' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','es','Otro' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie endocrinienne' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Diabète sucré' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Syndrome de Cushing' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Hypothyroïdie' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie endocrinienne' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie immunologique' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Anémie hémolytique à médiation immune (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Thrombocytopénie' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie immunologique' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie cutanée' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie cutanée' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie tumorale' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Lymphome' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur du foie, des reins ou du tractus intestinal' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur des os ou des articulations' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur de la peau ou du tissu sous-cutané' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur mammaire' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur de la vessie' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur du système nerveux' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur pulmonaire' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Tumeur de la rate, du cœur ou du système vasculaire' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie tumorale' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie neurologique' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Épilepsie' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Syringomyélie' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie neurologique' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie oculaire' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Cécité' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Syndrome de l''œil sec' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie oculaire' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie de l''appareil locomoteur' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Arthrose d''une articulation autre que la hanche ou le coude' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Spondylose déformante' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Dysplasie de la hanche et arthrose consécutive' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Dysplasie du coude et arthrose consécutive' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Polyarthrite à médiation immune' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre dysplasie osseuse ou articulaire' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Luxation de la rotule' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Rupture du ligament croisé antérieur' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Syndrome de la queue de cheval' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Hernie discale intervertébrale' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie de l''appareil locomoteur' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie de l''appareil digestif' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Insuffisance pancréatique exocrine (IPE)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Insuffisance hépatique / défaillance hépatique' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Mégaœsophage' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Occlusion intestinale due à un corps étranger' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie de l''appareil digestif' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie respiratoire' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Collapsus trachéal' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Pneumonie' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie respiratoire' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie cardiaque' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Endocardiose' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Cardiomyopathie' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie cardiaque' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie urologique' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Infection utérine / pyomètre' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Calculs rénaux' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Incontinence urinaire' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Insuffisance rénale' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie urologique' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Maladie de l''oreille' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Otite chronique ou récidivante' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie de l''oreille' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Malformation congénitale' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre trouble du développement' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Anomalie vertébrale congénitale' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Malformation congénitale du chiot' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Malformation cardiaque congénitale' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie congénitale' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre maladie non spécifiée' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Vieillesse' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Accident' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','fr','Autre' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia endocrina' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Diabete mellito' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Sindrome di Cushing' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Ipotiroidismo' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia endocrina' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia immunologica' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Anemia emolitica immunomediata (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Trombocitopenia' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia immunologica' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia cutanea' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia cutanea' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia tumorale' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Linfoma' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore del fegato, dei reni o del tratto intestinale' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore delle ossa o delle articolazioni' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore della cute o del sottocute' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore della ghiandola mammaria' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore della vescica urinaria' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore del sistema nervoso' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore polmonare' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Tumore della milza, del cuore o del sistema vascolare' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia tumorale' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia neurologica' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Epilessia' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Siringomielia' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia neurologica' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia oculare' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Cecità' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Sindrome dell''occhio secco' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia oculare' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia dell''apparato locomotore' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Artrosi di un''articolazione diversa dall''anca o dal gomito' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Spondilosi deformante' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Displasia dell''anca e conseguente artrosi' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Displasia del gomito e conseguente artrosi' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Poliartrite immunomediata' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra displasia ossea o articolare' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Lussazione della rotula' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Lesione del legamento crociato anteriore' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Sindrome della cauda equina' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Ernia del disco intervertebrale' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia dell''apparato locomotore' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia dell''apparato digerente' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Insufficienza pancreatica esocrina (IPE)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Insufficienza epatica' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Megaesofago' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Ostruzione intestinale da corpo estraneo' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia dell''apparato digerente' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia respiratoria' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Collasso tracheale' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Polmonite' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia respiratoria' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia cardiaca' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Endocardiosi' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Cardiomiopatia' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia cardiaca' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia urologica' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Infezione uterina / piometra' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Calcoli renali' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Incontinenza urinaria' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Insufficienza renale' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia urologica' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Malattia dell''orecchio' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Otite cronica o ricorrente' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia dell''orecchio' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Difetto congenito' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altro disturbo dello sviluppo' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Anomalia vertebrale congenita' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Difetto o malformazione congenita del cucciolo' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Cardiopatia congenita' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia congenita' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altra malattia non specificata' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Vecchiaia' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Incidente' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','it','Altro' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Betegség' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Endokrin betegség' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Cukorbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Cushing-szindróma' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Pajzsmirigy-alulműködés' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb endokrin betegség' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Immunológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Immunmediált hemolitikus anémia (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Thrombocytopenia' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb immunológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Bőrbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb bőrbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Daganatos betegség' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Limfóma' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Máj-, vese- vagy béldaganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Csont- vagy ízületi daganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Bőr- vagy bőr alatti daganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Emlődaganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Húgyhólyagdaganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Idegrendszeri daganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Tüdődaganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Lép-, szív- vagy érrendszeri daganat' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb daganatos betegség' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Neurológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Epilepszia' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Syringomyelia' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb neurológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Szembetegség' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Vakság' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Száraz szem szindróma' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb szembetegség' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Mozgásszervi betegség' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Csípő- vagy könyökízülettől eltérő ízület artrózisa' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Spondylosis deformans' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Csípőízületi diszplázia és következményes artrózis' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Könyökízületi diszplázia és következményes artrózis' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Immunmediált polyarthritis' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb csont- vagy ízületi diszplázia' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Térdkalács-ficam' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Elülső keresztszalag sérülése' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Cauda equina szindróma' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Porckorongsérv' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb mozgásszervi betegség' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Emésztőrendszeri betegség' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Exokrin hasnyálmirigy-elégtelenség (EPI)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Májelégtelenség' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Megaoesophagus' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Idegentest okozta bélelzáródás' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb emésztőrendszeri betegség' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Légzőszervi betegség' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Légcsőkollapszus' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Tüdőgyulladás' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb légzőszervi betegség' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Szívbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Endocardiosis' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Kardiomiopátia' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb szívbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Urológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Méhfertőzés / pyometra' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Vesekő' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Vizeletinkontinencia' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Veseelégtelenség' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb urológiai betegség' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Fülbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Krónikus vagy visszatérő fülgyulladás' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb fülbetegség' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Veleszületett rendellenesség' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb fejlődési rendellenesség' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Veleszületett csigolya-rendellenesség' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','A kölyök veleszületett hibája vagy fejlődési rendellenessége' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Veleszületett szívfejlődési rendellenesség' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb veleszületett betegség' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb, nem meghatározott betegség' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Öregség' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Baleset' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','hu','Egyéb' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba endokrynologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Cukrzyca' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zespół Cushinga' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Niedoczynność tarczycy' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba endokrynologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba immunologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Niedokrwistość hemolityczna na tle immunologicznym (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Małopłytkowość' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba immunologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba skóry' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba skóry' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba nowotworowa' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Chłoniak' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór wątroby, nerek lub przewodu jelitowego' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór kości lub stawów' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór skóry lub tkanki podskórnej' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór gruczołu mlekowego' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór pęcherza moczowego' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór układu nerwowego' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór płuc' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nowotwór śledziony, serca lub układu naczyniowego' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba nowotworowa' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba neurologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Padaczka' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Jamistość rdzenia (syringomielia)' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba neurologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba oczu' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Ślepota' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zespół suchego oka' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba oczu' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba układu ruchu' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba zwyrodnieniowa stawu innego niż biodrowy lub łokciowy' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Spondyloza zniekształcająca' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Dysplazja stawu biodrowego i wtórna artroza' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Dysplazja stawu łokciowego i wtórna artroza' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zapalenie wielostawowe na tle immunologicznym' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna dysplazja kości lub stawów' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zwichnięcie rzepki' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Uszkodzenie więzadła krzyżowego przedniego' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zespół ogona końskiego' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Przepuklina krążka międzykręgowego' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba układu ruchu' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba układu pokarmowego' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zewnątrzwydzielnicza niewydolność trzustki (EPI)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Niewydolność wątroby' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Megaprzełyk' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Niedrożność jelit spowodowana ciałem obcym' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba układu pokarmowego' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba układu oddechowego' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zapadnięcie tchawicy' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zapalenie płuc' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba układu oddechowego' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba serca' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Endokardioza' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Kardiomiopatia' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba serca' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba urologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Zakażenie macicy / ropomacicze' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Kamica nerkowa' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Nietrzymanie moczu' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Niewydolność nerek' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba urologiczna' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Choroba uszu' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Przewlekłe lub nawracające zapalenie ucha' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba uszu' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Wada wrodzona' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inne zaburzenie rozwojowe' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Wrodzona anomalia kręgów' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Wada wrodzona lub malformacja szczenięcia' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Wrodzona wada serca' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna choroba wrodzona' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inna nieokreślona choroba' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Starość' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Wypadek' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','pl','Inne' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Болезнь' FROM death_causes WHERE breed_id=@cav AND code='1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nemoc
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Эндокринное заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Сахарный диабет' FROM death_causes WHERE breed_id=@cav AND code='1.1.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cukrovka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Синдром Кушинга' FROM death_causes WHERE breed_id=@cav AND code='1.1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Cushingův syndrom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Гипотиреоз' FROM death_causes WHERE breed_id=@cav AND code='1.1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Hypotyreóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое эндокринное заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné endokrinní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Иммунологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Иммуноопосредованная гемолитическая анемия (IMHA/AIHA)' FROM death_causes WHERE breed_id=@cav AND code='1.2.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Тромбоцитопения' FROM death_causes WHERE breed_id=@cav AND code='1.2.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Trombocytopenie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое иммунологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.2.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné imunologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Кожное заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое кожное заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.3.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné kožní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Онкологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádorová onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Лимфома' FROM death_causes WHERE breed_id=@cav AND code='1.4.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Lymfom
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль печени, почек или кишечного тракта' FROM death_causes WHERE breed_id=@cav AND code='1.4.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor jater, ledvin nebo střevního traktu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль костей или суставов' FROM death_causes WHERE breed_id=@cav AND code='1.4.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль кожи или подкожной клетчатки' FROM death_causes WHERE breed_id=@cav AND code='1.4.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor kůže nebo podkoží
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль молочной железы' FROM death_causes WHERE breed_id=@cav AND code='1.4.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor mléčné žlázy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль мочевого пузыря' FROM death_causes WHERE breed_id=@cav AND code='1.4.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor močového měchýře
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль нервной системы' FROM death_causes WHERE breed_id=@cav AND code='1.4.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor nervové soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль лёгких' FROM death_causes WHERE breed_id=@cav AND code='1.4.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor plic
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Опухоль селезёнки, сердца или сосудистой системы' FROM death_causes WHERE breed_id=@cav AND code='1.4.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nádor sleziny, srdce nebo cévního systému
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое онкологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.4.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nádorové onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Неврологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Эпилепсия' FROM death_causes WHERE breed_id=@cav AND code='1.5.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Epilepsie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Сирингомиелия' FROM death_causes WHERE breed_id=@cav AND code='1.5.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syringomyelie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое неврологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.5.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné neurologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание глаз' FROM death_causes WHERE breed_id=@cav AND code='1.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Слепота' FROM death_causes WHERE breed_id=@cav AND code='1.6.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Slepota
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Синдром сухого глаза' FROM death_causes WHERE breed_id=@cav AND code='1.6.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom suchého oka
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание глаз' FROM death_causes WHERE breed_id=@cav AND code='1.6.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné oční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание опорно-двигательного аппарата' FROM death_causes WHERE breed_id=@cav AND code='1.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Артроз сустава, отличного от тазобедренного или локтевого' FROM death_causes WHERE breed_id=@cav AND code='1.7.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Artróza jiného kloubu než kyčelního nebo loketního
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Деформирующий спондилёз' FROM death_causes WHERE breed_id=@cav AND code='1.7.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Deformující spondylóza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Дисплазия тазобедренного сустава и последующий артроз' FROM death_causes WHERE breed_id=@cav AND code='1.7.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie kyčelního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Дисплазия локтевого сустава и последующий артроз' FROM death_causes WHERE breed_id=@cav AND code='1.7.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Dysplazie loketního kloubu a následná artróza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Иммуноопосредованный полиартрит' FROM death_causes WHERE breed_id=@cav AND code='1.7.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Imunitně zprostředkovaná polyartritida
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другая дисплазия костей или суставов' FROM death_causes WHERE breed_id=@cav AND code='1.7.6' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná dysplazie kostí nebo kloubů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Вывих коленной чашечки' FROM death_causes WHERE breed_id=@cav AND code='1.7.7' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Luxace čéšky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Повреждение передней крестообразной связки' FROM death_causes WHERE breed_id=@cav AND code='1.7.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Poranění předního zkříženého vazu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Синдром конского хвоста' FROM death_causes WHERE breed_id=@cav AND code='1.7.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Syndrom kaudy equiny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Грыжа межпозвонкового диска' FROM death_causes WHERE breed_id=@cav AND code='1.7.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Výhřez meziobratlové ploténky
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание опорно-двигательного аппарата' FROM death_causes WHERE breed_id=@cav AND code='1.7.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění pohybového aparátu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание пищеварительной системы' FROM death_causes WHERE breed_id=@cav AND code='1.8' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Экзокринная недостаточность поджелудочной железы (ЭНПЖ)' FROM death_causes WHERE breed_id=@cav AND code='1.8.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Exokrinní pankreatická insuficience (EPI)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Печёночная недостаточность' FROM death_causes WHERE breed_id=@cav AND code='1.8.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jaterní insuficience / selhání jater
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Мегаэзофагус' FROM death_causes WHERE breed_id=@cav AND code='1.8.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Megaezofagus
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Кишечная непроходимость, вызванная инородным телом' FROM death_causes WHERE breed_id=@cav AND code='1.8.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Neprůchodnost střeva způsobená cizím tělesem
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание пищеварительной системы' FROM death_causes WHERE breed_id=@cav AND code='1.8.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné onemocnění trávicí soustavy
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание дыхательной системы' FROM death_causes WHERE breed_id=@cav AND code='1.9' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Коллапс трахеи' FROM death_causes WHERE breed_id=@cav AND code='1.9.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kolaps průdušnice
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Пневмония' FROM death_causes WHERE breed_id=@cav AND code='1.9.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Pneumonie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание дыхательной системы' FROM death_causes WHERE breed_id=@cav AND code='1.9.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné respirační onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание сердца' FROM death_causes WHERE breed_id=@cav AND code='1.10' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Эндокардиоз' FROM death_causes WHERE breed_id=@cav AND code='1.10.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Endokardióza
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Кардиомиопатия' FROM death_causes WHERE breed_id=@cav AND code='1.10.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Kardiomyopatie
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание сердца' FROM death_causes WHERE breed_id=@cav AND code='1.10.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné srdeční onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Урологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.11' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Urologická onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Инфекция матки / пиометра' FROM death_causes WHERE breed_id=@cav AND code='1.11.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Infekce dělohy / pyometra
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Камни в почках' FROM death_causes WHERE breed_id=@cav AND code='1.11.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ledvinové kameny
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Недержание мочи' FROM death_causes WHERE breed_id=@cav AND code='1.11.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Močová inkontinence
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Почечная недостаточность' FROM death_causes WHERE breed_id=@cav AND code='1.11.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Selhání ledvin
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое урологическое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.11.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné urologické onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Заболевание ушей' FROM death_causes WHERE breed_id=@cav AND code='1.12' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Хроническое или рецидивирующее воспаление уха' FROM death_causes WHERE breed_id=@cav AND code='1.12.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Chronický nebo opakovaný zánět ucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое заболевание ушей' FROM death_causes WHERE breed_id=@cav AND code='1.12.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné ušní onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Врождённый дефект' FROM death_causes WHERE breed_id=@cav AND code='1.13' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое нарушение развития' FROM death_causes WHERE breed_id=@cav AND code='1.13.1' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiná vývojová porucha
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Врождённая аномалия позвонков' FROM death_causes WHERE breed_id=@cav AND code='1.13.2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená anomálie obratlů
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Врождённый порок или порок развития щенка' FROM death_causes WHERE breed_id=@cav AND code='1.13.3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vada nebo malformace štěněte
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Врождённый порок сердца' FROM death_causes WHERE breed_id=@cav AND code='1.13.4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Vrozená vývojová vada srdce
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое врождённое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.13.5' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné vrozené onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое неуточнённое заболевание' FROM death_causes WHERE breed_id=@cav AND code='1.14' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné nespecifikované onemocnění
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Старость' FROM death_causes WHERE breed_id=@cav AND code='2' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Stáří
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Несчастный случай' FROM death_causes WHERE breed_id=@cav AND code='3' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Nehoda
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'death_cause',id,'label','ru','Другое' FROM death_causes WHERE breed_id=@cav AND code='4' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- Jiné

-- breed / name (klic = slug)
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','de','Cavalier King Charles Spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','de','Sonstige' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','de','Irischer Wolfshund' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','de','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','en','Cavalier King Charles Spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','en','Other' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','en','Irish Wolfhound' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','en','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','es','Cavalier King Charles Spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','es','Otras' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','es','Lebrel irlandés' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','es','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','fr','Cavalier King Charles Spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','fr','Autres' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','fr','Lévrier irlandais' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','fr','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','it','Cavalier King Charles Spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','it','Altri' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','it','Levriero irlandese' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','it','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','hu','Cavalier King Charles spániel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','hu','Egyéb' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','hu','Ír farkaskutya' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','hu','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','pl','Cavalier King Charles spaniel' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','pl','Inne' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','pl','Wilczarz irlandzki' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','pl','Leonberger' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','ru','Кавалер-кинг-чарльз-спаниель' FROM breeds WHERE slug='cavalier-king-charles-spaniel' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- kavalír king Charles španěl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','ru','Прочие' FROM breeds WHERE slug='other' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- ostatní
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','ru','Ирландский волкодав' FROM breeds WHERE slug='irish-wolfhound' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- irský vlkodav
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'breed',id,'name','ru','Леонбергер' FROM breeds WHERE slug='leonberger' ON DUPLICATE KEY UPDATE text=VALUES(text);  -- leonberger

-- email_template / subject + body (klic = key)
-- set_password / subject / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','de','Passwort festlegen - ZOO Tábor Forschung' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','de','Guten Tag,

im Forschungssystem für Hunderassen von ZOO Tábor wurde für Sie ein Konto angelegt.
Zum Festlegen des Passworts verwenden Sie bitte diesen Link (gültig 1 Monat):

{odkaz}

Nach dem Festlegen des Passworts können Sie sich anmelden und Ihre Hunde sehen.

Mit freundlichen Grüßen
Ihr Forschungsteam ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','en','Set your password - ZOO Tábor Research' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','en','Hello,

An account has been created for you in the ZOO Tábor dog breed research system.
To set your password, please use this link (valid for 1 month):

{odkaz}

Once you have set your password, you will be able to log in and see your dogs.

Kind regards,
The ZOO Tábor research team' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','es','Establecer la contraseña - Investigación ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','es','Buenos días,

Se ha creado una cuenta para usted en el sistema de investigación de razas caninas de ZOO Tábor.
Para establecer su contraseña, utilice este enlace (válido durante 1 mes):

{odkaz}

Una vez establecida la contraseña, podrá iniciar sesión y ver sus perros.

Un saludo cordial,
El equipo de investigación de ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','fr','Définir le mot de passe - Recherche ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','fr','Bonjour,

Un compte a été créé pour vous dans le système de recherche sur les races canines de ZOO Tábor.
Pour définir votre mot de passe, veuillez utiliser ce lien (valable 1 mois) :

{odkaz}

Une fois votre mot de passe défini, vous pourrez vous connecter et voir vos chiens.

Cordialement,
L''équipe de recherche de ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','it','Imposta la password - Ricerca ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','it','Buongiorno,

Nel sistema di ricerca sulle razze canine di ZOO Tábor è stato creato un account per lei.
Per impostare la password, utilizzi questo link (valido 1 mese):

{odkaz}

Dopo aver impostato la password, potrà accedere e visualizzare i suoi cani.

Cordiali saluti,
Il team di ricerca di ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','hu','Jelszó beállítása - ZOO Tábor Kutatás' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','hu','Jó napot kívánok,

A ZOO Tábor kutyafajta-kutatási rendszerében fiók jött létre az Ön számára.
A jelszó beállításához használja ezt a hivatkozást (1 hónapig érvényes):

{odkaz}

A jelszó beállítása után be tud jelentkezni, és láthatja a kutyáit.

Üdvözlettel,
A ZOO Tábor kutatócsoportja' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','pl','Ustawienie hasła - Badania ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','pl','Dzień dobry,

W systemie badań nad rasami psów ZOO Tábor zostało utworzone konto.
Aby ustawić hasło, należy skorzystać z poniższego linku (ważny 1 miesiąc):

{odkaz}

Po ustawieniu hasła będzie można się zalogować i zobaczyć swoje psy.

Z poważaniem,
Zespół badawczy ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / subject / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','ru','Установка пароля - Исследование ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- set_password / body / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','ru','Здравствуйте,

В системе исследования пород собак ZOO Tábor для вас создана учётная запись.
Чтобы задать пароль, воспользуйтесь этой ссылкой (действительна 1 месяц):

{odkaz}

После установки пароля вы сможете войти в систему и увидеть своих собак.

С уважением,
Исследовательская группа ZOO Tábor' FROM email_templates WHERE `key`='set_password' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','de','Passwort zurücksetzen - ZOO Tábor Forschung' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','de','Guten Tag,

wir haben eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto in der Hunderassenforschung von ZOO Tábor erhalten.
Ein neues Passwort legen Sie über diesen Link fest (gültig 2 Stunden):

{odkaz}

Falls Sie kein Zurücksetzen des Passworts angefordert haben, ignorieren Sie diese E-Mail - Ihr Passwort bleibt unverändert.

Mit freundlichen Grüßen
Ihr Forschungsteam ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','en','Password reset - ZOO Tábor Research' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','en','Hello,

We have received a request to reset the password for your account in the ZOO Tábor dog breed research.
You can set a new password using this link (valid for 2 hours):

{odkaz}

If you did not request a password reset, please ignore this e-mail - your password remains unchanged.

Kind regards,
The ZOO Tábor research team' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','es','Restablecimiento de la contraseña - Investigación ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','es','Buenos días,

Hemos recibido una solicitud para restablecer la contraseña de su cuenta en la investigación de razas caninas de ZOO Tábor.
Puede establecer una nueva contraseña con este enlace (válido durante 2 horas):

{odkaz}

Si no solicitó el restablecimiento de la contraseña, ignore este correo electrónico: su contraseña permanece sin cambios.

Un saludo cordial,
El equipo de investigación de ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','fr','Réinitialisation du mot de passe - Recherche ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','fr','Bonjour,

Nous avons reçu une demande de réinitialisation du mot de passe de votre compte dans la recherche sur les races canines de ZOO Tábor.
Vous pouvez définir un nouveau mot de passe à l''aide de ce lien (valable 2 heures) :

{odkaz}

Si vous n''avez pas demandé la réinitialisation du mot de passe, ignorez cet e-mail - votre mot de passe reste inchangé.

Cordialement,
L''équipe de recherche de ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','it','Reimpostazione della password - Ricerca ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','it','Buongiorno,

Abbiamo ricevuto una richiesta di reimpostazione della password per il suo account nella ricerca sulle razze canine di ZOO Tábor.
Può impostare una nuova password tramite questo link (valido 2 ore):

{odkaz}

Se non ha richiesto la reimpostazione della password, ignori questa e-mail - la sua password rimane invariata.

Cordiali saluti,
Il team di ricerca di ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','hu','Jelszó visszaállítása - ZOO Tábor Kutatás' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','hu','Jó napot kívánok,

Kérést kaptunk a ZOO Tábor kutyafajta-kutatásában lévő fiókjához tartozó jelszó visszaállítására.
Új jelszót ezzel a hivatkozással állíthat be (2 óráig érvényes):

{odkaz}

Ha nem Ön kérte a jelszó visszaállítását, hagyja figyelmen kívül ezt az e-mailt - a jelszava változatlan marad.

Üdvözlettel,
A ZOO Tábor kutatócsoportja' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','pl','Resetowanie hasła - Badania ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','pl','Dzień dobry,

Otrzymaliśmy prośbę o zresetowanie hasła do konta w badaniach nad rasami psów ZOO Tábor.
Nowe hasło można ustawić za pomocą tego linku (ważny 2 godziny):

{odkaz}

Jeśli prośba o zresetowanie hasła nie pochodziła od Państwa, prosimy zignorować tę wiadomość - hasło pozostaje bez zmian.

Z poważaniem,
Zespół badawczy ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / subject / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','ru','Сброс пароля - Исследование ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- password_reset / body / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','ru','Здравствуйте,

Мы получили запрос на сброс пароля для вашей учётной записи в исследовании пород собак ZOO Tábor.
Новый пароль можно задать по этой ссылке (действительна 2 часа):

{odkaz}

Если вы не запрашивали сброс пароля, проигнорируйте это письмо - ваш пароль останется без изменений.

С уважением,
Исследовательская группа ZOO Tábor' FROM email_templates WHERE `key`='password_reset' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','de','Übernahme des Hundes - ZOO Tábor Forschung' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','de','Guten Tag,

der bisherige Besitzer hat Sie im Rahmen der Hunderassenforschung von ZOO Tábor als neuen Besitzer des Hundes angegeben.
Zur Bestätigung der Übernahme des Hundes verwenden Sie bitte diesen Link (gültig 1 Monat):

{odkaz}

Nach der Bestätigung erhalten Sie einen Link zum Festlegen des Passworts für das Portal.

Mit freundlichen Grüßen
Ihr Forschungsteam ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','en','Taking over the dog - ZOO Tábor Research' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','en','Hello,

The current owner has listed you as the new owner of the dog within the ZOO Tábor dog breed research.
To confirm that you are taking over the dog, please use this link (valid for 1 month):

{odkaz}

After confirmation, you will receive a link to set a password for the portal.

Kind regards,
The ZOO Tábor research team' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','es','Recepción del perro - Investigación ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','es','Buenos días,

El propietario actual le ha indicado como nuevo propietario del perro en el marco de la investigación de razas caninas de ZOO Tábor.
Para confirmar la recepción del perro, utilice este enlace (válido durante 1 mes):

{odkaz}

Tras la confirmación, recibirá un enlace para establecer la contraseña del portal.

Un saludo cordial,
El equipo de investigación de ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','fr','Reprise du chien - Recherche ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','fr','Bonjour,

Le propriétaire actuel vous a désigné comme nouveau propriétaire du chien dans le cadre de la recherche sur les races canines de ZOO Tábor.
Pour confirmer la reprise du chien, veuillez utiliser ce lien (valable 1 mois) :

{odkaz}

Après confirmation, vous recevrez un lien pour définir un mot de passe pour le portail.

Cordialement,
L''équipe de recherche de ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','it','Presa in carico del cane - Ricerca ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','it','Buongiorno,

Il proprietario attuale l''ha indicata come nuovo proprietario del cane nell''ambito della ricerca sulle razze canine di ZOO Tábor.
Per confermare la presa in carico del cane, utilizzi questo link (valido 1 mese):

{odkaz}

Dopo la conferma riceverà un link per impostare una password per il portale.

Cordiali saluti,
Il team di ricerca di ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','hu','Kutya átvétele - ZOO Tábor Kutatás' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','hu','Jó napot kívánok,

A jelenlegi tulajdonos Önt jelölte meg a kutya új tulajdonosaként a ZOO Tábor kutyafajta-kutatása keretében.
A kutya átvételének megerősítéséhez használja ezt a hivatkozást (1 hónapig érvényes):

{odkaz}

A megerősítés után hivatkozást kap a portál jelszavának beállításához.

Üdvözlettel,
A ZOO Tábor kutatócsoportja' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','pl','Przejęcie psa - Badania ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','pl','Dzień dobry,

Dotychczasowy właściciel wskazał Państwa jako nowego właściciela psa w ramach badań nad rasami psów ZOO Tábor.
Aby potwierdzić przejęcie psa, należy skorzystać z tego linku (ważny 1 miesiąc):

{odkaz}

Po potwierdzeniu otrzymają Państwo link do ustawienia hasła do portalu.

Z poważaniem,
Zespół badawczy ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / subject / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','ru','Приём собаки - Исследование ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- ownership_transfer / body / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','ru','Здравствуйте,

Текущий владелец указал вас в качестве нового владельца собаки в рамках исследования пород собак ZOO Tábor.
Чтобы подтвердить приём собаки, воспользуйтесь этой ссылкой (действительна 1 месяц):

{odkaz}

После подтверждения вы получите ссылку для установки пароля для портала.

С уважением,
Исследовательская группа ZOO Tábor' FROM email_templates WHERE `key`='ownership_transfer' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','de','Fragebogen zu Ihrem Hund - ZOO Tábor Forschung' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / de
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','de','Guten Tag,

im Rahmen der Forschung von ZOO Tábor zur Langlebigkeit von Hunden bitten wir Sie, den Fragebogen "{dotaznik}" für Ihren Hund {pes} auszufüllen.

Den Fragebogen können Sie nach der Anmeldung im Portal hier ausfüllen:
{odkaz}

Vielen Dank im Voraus für Ihre Mitarbeit.

Mit freundlichen Grüßen
Ihr Forschungsteam ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','en','Questionnaire about your dog - ZOO Tábor Research' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / en
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','en','Hello,

As part of the ZOO Tábor dog longevity research, we kindly ask you to fill in the questionnaire "{dotaznik}" for your dog {pes}.

You can fill in the questionnaire after logging in to the portal here:
{odkaz}

Thank you in advance for your cooperation.

Kind regards,
The ZOO Tábor research team' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','es','Cuestionario sobre su perro - Investigación ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / es
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','es','Buenos días,

En el marco de la investigación sobre la longevidad canina de ZOO Tábor, le pedimos que rellene el cuestionario "{dotaznik}" sobre su perro {pes}.

Podrá rellenar el cuestionario tras iniciar sesión en el portal aquí:
{odkaz}

Le agradecemos de antemano su colaboración.

Un saludo cordial,
El equipo de investigación de ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','fr','Questionnaire sur votre chien - Recherche ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / fr
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','fr','Bonjour,

Dans le cadre de la recherche de ZOO Tábor sur la longévité des chiens, nous vous prions de remplir le questionnaire "{dotaznik}" concernant votre chien {pes}.

Vous pourrez remplir le questionnaire après vous être connecté au portail ici :
{odkaz}

Nous vous remercions par avance de votre collaboration.

Cordialement,
L''équipe de recherche de ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','it','Questionario sul suo cane - Ricerca ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / it
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','it','Buongiorno,

Nell''ambito della ricerca di ZOO Tábor sulla longevità dei cani, le chiediamo di compilare il questionario "{dotaznik}" relativo al suo cane {pes}.

Potrà compilare il questionario dopo aver effettuato l''accesso al portale qui:
{odkaz}

La ringraziamo in anticipo per la collaborazione.

Cordiali saluti,
Il team di ricerca di ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','hu','Kérdőív a kutyájáról - ZOO Tábor Kutatás' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / hu
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','hu','Jó napot kívánok,

A ZOO Tábor kutyák hosszú élettartamára irányuló kutatása keretében kérjük, töltse ki a(z) "{dotaznik}" kérdőívet a(z) {pes} nevű kutyájával kapcsolatban.

A kérdőívet a portálba való bejelentkezés után itt töltheti ki:
{odkaz}

Előre is köszönjük az együttműködését.

Üdvözlettel,
A ZOO Tábor kutatócsoportja' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','pl','Kwestionariusz dotyczący Państwa psa - Badania ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / pl
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','pl','Dzień dobry,

W ramach badań ZOO Tábor nad długowiecznością psów prosimy o wypełnienie kwestionariusza "{dotaznik}" dotyczącego Państwa psa {pes}.

Kwestionariusz można wypełnić po zalogowaniu się do portalu tutaj:
{odkaz}

Z góry dziękujemy za współpracę.

Z poważaniem,
Zespół badawczy ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / subject / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'subject','ru','Анкета о вашей собаке - Исследование ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);
-- form_broadcast / body / ru
INSERT INTO translations (entity_type,entity_id,field,locale,text) SELECT 'email_template',id,'body','ru','Здравствуйте,

В рамках исследования долголетия собак ZOO Tábor просим вас заполнить анкету "{dotaznik}" для вашей собаки {pes}.

Заполнить анкету можно после входа в портал здесь:
{odkaz}

Заранее благодарим за сотрудничество.

С уважением,
Исследовательская группа ZOO Tábor' FROM email_templates WHERE `key`='form_broadcast' ON DUPLICATE KEY UPDATE text=VALUES(text);

-- Trvale prihlaseni (remember-me): v DB jen hash tokenu.
CREATE TABLE IF NOT EXISTS remember_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  last_used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY remember_tokens_hash_uq (token_hash),
  INDEX remember_tokens_user_idx (user_id),
  CONSTRAINT remember_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oznaceni migraci jako provedenych (bez chyby, kdyz uz tam jsou).
INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_core.sql'), ('002_dogs_owners.sql'), ('003_invites_mail.sql'),
       ('004_forms.sql'), ('005_form_responses.sql'), ('006_samples.sql'),
       ('007_genetics.sql'), ('008_messages.sql'), ('009_ownership_transfer.sql'),
       ('010_health_events.sql'), ('011_dogs_extra_colours.sql'),
       ('012_form_assignments.sql'), ('013_owner_onboarding.sql'),
       ('014_genotype_gene.sql'), ('015_message_reads.sql'),
       ('016_death_causes.sql'), ('017_sample_dna_gwas.sql'),
       ('018_genotype_source_note.sql'), ('019_owners_language.sql'),
       ('020_translations.sql'), ('021_email_templates.sql'),
       ('022_remember_tokens.sql');
