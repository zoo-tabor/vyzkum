-- Faze 3 B3 - odpovedi na dotazniky.

CREATE TABLE form_responses (
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

CREATE TABLE form_answers (
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
