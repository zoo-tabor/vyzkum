-- Faze 6 - genetika: geny, markery, testy, genotypy psu.

CREATE TABLE genes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(64) NOT NULL,
  name VARCHAR(160) NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY genes_symbol_unique (symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE genetic_markers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gene_id INT UNSIGNED NOT NULL,
  marker_code VARCHAR(64) NOT NULL,
  locus VARCHAR(120) NULL,
  reference_allele VARCHAR(20) NULL,
  alternate_allele VARCHAR(20) NULL,
  allowed_values VARCHAR(190) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY genetic_markers_code_unique (marker_code),
  INDEX genetic_markers_gene_idx (gene_id),
  CONSTRAINT genetic_markers_gene_fk FOREIGN KEY (gene_id) REFERENCES genes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE genetic_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  lab_name VARCHAR(160) NULL,
  tested_at DATE NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'manual',
  source_file_id INT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX genetic_tests_dog_idx (dog_id),
  CONSTRAINT genetic_tests_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT genetic_tests_file_fk FOREIGN KEY (source_file_id) REFERENCES files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dog_genotypes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dog_id INT UNSIGNED NOT NULL,
  breed_id INT UNSIGNED NULL,
  marker_id INT UNSIGNED NOT NULL,
  allele_1 VARCHAR(20) NULL,
  allele_2 VARCHAR(20) NULL,
  genotype VARCHAR(40) NOT NULL,
  genetic_test_id INT UNSIGNED NULL,
  validation_status VARCHAR(20) NOT NULL DEFAULT 'imported',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY dog_genotypes_dog_marker_unique (dog_id, marker_id),
  INDEX dog_genotypes_dog_idx (dog_id, marker_id),
  INDEX dog_genotypes_breed_marker_idx (breed_id, marker_id, genotype),
  CONSTRAINT dog_genotypes_dog_fk FOREIGN KEY (dog_id) REFERENCES dogs(id) ON DELETE CASCADE,
  CONSTRAINT dog_genotypes_breed_fk FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL,
  CONSTRAINT dog_genotypes_marker_fk FOREIGN KEY (marker_id) REFERENCES genetic_markers(id) ON DELETE CASCADE,
  CONSTRAINT dog_genotypes_test_fk FOREIGN KEY (genetic_test_id) REFERENCES genetic_tests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
