-- Faze 5/6 - strukturovane zdravotni udalosti (z dotazniku, umrti, importu).

CREATE TABLE health_events (
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
