-- =====================================================================
-- INSTALACE SCHEMATU - Faze 1 (jadro)
-- Pro import v phpMyAdmin (kdyz PHP nedosahne na DB pres siet).
-- Odpovida migraci `database/migrations/001_core.sql`. Zdroj pravdy jsou
-- migrace; tento soubor je jen pohodlny instalator pro phpMyAdmin.
-- Na konci oznaci migraci 001 jako provedenou, aby na ni `bin/migrate.php`
-- pozdeji nesahal a spoustel jen novejsi migrace.
-- =====================================================================

CREATE TABLE schema_migrations (
  version VARCHAR(190) NOT NULL PRIMARY KEY,
  executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE breeds (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  club_id INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY breeds_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
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

CREATE TABLE user_breed_access (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  breed_id INT UNSIGNED NOT NULL,
  access_level VARCHAR(40) NOT NULL DEFAULT 'read',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY user_breed_unique (user_id, breed_id),
  CONSTRAINT uba_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uba_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
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

CREATE TABLE login_throttle (
  throttle_key VARCHAR(190) NOT NULL PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  INDEX login_throttle_expires_idx (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oznaceni migrace 001 jako provedene (aby ji migrate.php neopakoval).
INSERT INTO schema_migrations (version) VALUES ('001_core.sql');
