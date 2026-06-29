-- Faze 4 - vzorky, davky, veterinari, souhlasy (port z old_app na novy model).

CREATE TABLE vets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  clinic_name VARCHAR(160) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sample_batches (
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

CREATE TABLE samples (
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

CREATE TABLE consents (
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
