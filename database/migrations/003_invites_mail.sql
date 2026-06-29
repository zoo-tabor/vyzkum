-- Faze 3 - pozvanky pro nastaveni hesla + log odeslanych e-mailu.

CREATE TABLE password_invites (
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

CREATE TABLE email_log (
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
