-- Faze v3/4: DNA izolace + GWAS se vedou na vzorku (samples), ne na psovi.
-- Sloupce dogs.dna_isolated_at / dogs.gwas_status se ZACHOVAVAJI jako legacy
-- (aplikace je uz necte ani nezapisuje) - drop by byl nevratny, data zustavaji.

ALTER TABLE samples
  ADD COLUMN IF NOT EXISTS dna_isolated_at DATE NULL AFTER received_at,
  ADD COLUMN IF NOT EXISTS gwas_status VARCHAR(20) NULL AFTER dna_isolated_at,
  ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER gwas_status;

-- Backfill: prenest hodnoty z psa na jeho NEJNOVEJSI vzorek. Idempotentni -
-- jen kdyz vzorek jeste zadne DNA/GWAS nema. Nejnovejsi = nejpozdejsi
-- received_at (NULL az naposled), pak nejvyssi id.
UPDATE samples s
  JOIN dogs d ON d.id = s.dog_id
  JOIN (
    SELECT dog_id,
           CAST(SUBSTRING_INDEX(
             GROUP_CONCAT(id ORDER BY (received_at IS NULL), received_at DESC, id DESC),
             ',', 1) AS UNSIGNED) AS newest_id
    FROM samples
    WHERE dog_id IS NOT NULL
    GROUP BY dog_id
  ) pick ON pick.dog_id = s.dog_id AND pick.newest_id = s.id
  SET s.dna_isolated_at = d.dna_isolated_at,
      s.gwas_status = d.gwas_status
  WHERE (d.dna_isolated_at IS NOT NULL OR d.gwas_status IS NOT NULL)
    AND s.dna_isolated_at IS NULL AND s.gwas_status IS NULL;
