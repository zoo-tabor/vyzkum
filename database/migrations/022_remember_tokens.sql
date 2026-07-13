-- Trvale prihlaseni ("zapamatovat uzivatele"): v DB jen HASH tokenu, plaintext je
-- v httponly cookie na zarizeni. Token se pri kazdem pouziti rotuje, logout/expirace
-- ho zrusi. Vydava se az po plne autentizaci (vc. 2FA), takze auto-login 2FA preskoci.

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
