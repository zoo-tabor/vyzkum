CREATE TABLE vets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  clinic_name VARCHAR(160) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(60) NULL,
  address VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE samples (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id VARCHAR(40) NOT NULL UNIQUE,
  vet_id INT UNSIGNED NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'created',
  vet_token_hash CHAR(64) NOT NULL,
  owner_token_hash CHAR(64) NOT NULL,
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
  CONSTRAINT samples_vet_fk FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE owners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NULL,
  contact_consent TINYINT(1) NOT NULL DEFAULT 0,
  newsletter_consent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY owners_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dogs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id INT UNSIGNED NOT NULL UNIQUE,
  owner_id INT UNSIGNED NOT NULL,
  chip_number VARCHAR(32) NOT NULL,
  name VARCHAR(160) NOT NULL,
  breed VARCHAR(160) NOT NULL,
  sex ENUM('male','female','unknown') NOT NULL DEFAULT 'unknown',
  birth_date DATE NOT NULL,
  pedigree_number VARCHAR(120) NOT NULL,
  registry VARCHAR(120) NULL,
  health_status_at_collection VARCHAR(80) NOT NULL,
  health_note TEXT NULL,
  death_date DATE NULL,
  death_cause VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT dogs_sample_fk FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE,
  CONSTRAINT dogs_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE RESTRICT,
  INDEX dogs_chip_idx (chip_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id INT UNSIGNED NOT NULL,
  dog_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NOT NULL,
  consent_version VARCHAR(40) NOT NULL,
  research_consent TINYINT(1) NOT NULL DEFAULT 0,
  gdpr_consent TINYINT(1) NOT NULL DEFAULT 0,
  future_contact_consent TINYINT(1) NOT NULL DEFAULT 0,
  results_consent TINYINT(1) NOT NULL DEFAULT 0,
  owner_name_at_consent VARCHAR(160) NOT NULL,
  ip_address VARCHAR(45) NULL,
  consented_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT consents_sample_fk FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE,
  CONSTRAINT consents_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT consents_owner_fk FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedigree_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  check_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  checked_by VARCHAR(160) NULL,
  checked_at DATETIME NULL,
  notes TEXT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pedigree_documents_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lab_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sample_id INT UNSIGNED NOT NULL,
  received_at DATETIME NULL,
  lab_status VARCHAR(80) NULL,
  result_file VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT lab_records_sample_fk FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_type VARCHAR(40) NOT NULL,
  actor_label VARCHAR(160) NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NOT NULL,
  metadata JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
