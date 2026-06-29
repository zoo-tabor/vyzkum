-- Faze 5B - samoobsluzna zmena majitele.

CREATE TABLE ownership_transfer_requests (
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
