-- Upravy fáze 7 - rozeslani dotazniku majitelum psu (jeden ukol/e-mail na psa).

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
