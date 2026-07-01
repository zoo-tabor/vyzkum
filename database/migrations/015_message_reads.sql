-- Sledovani precteni vlaken zprav (pro zvyrazneni nezobrazenych zprav majiteli).

CREATE TABLE IF NOT EXISTS message_reads (
  thread_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  last_read_at DATETIME NOT NULL,
  PRIMARY KEY (thread_id, user_id),
  CONSTRAINT message_reads_thread_fk FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
  CONSTRAINT message_reads_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
