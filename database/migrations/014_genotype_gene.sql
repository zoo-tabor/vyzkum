-- Genetika gene-centric: genotyp navazany primo na gen (ne jen marker).
-- Marker zustava (CSV import), ale UI pracuje s geny.

ALTER TABLE dog_genotypes
  ADD COLUMN IF NOT EXISTS gene_id INT UNSIGNED NULL AFTER marker_id;

-- Backfill gene_id z markeru.
UPDATE dog_genotypes g
  JOIN genetic_markers m ON m.id = g.marker_id
  SET g.gene_id = m.gene_id
  WHERE g.gene_id IS NULL;

ALTER TABLE dog_genotypes
  ADD INDEX IF NOT EXISTS dog_genotypes_dog_gene_idx (dog_id, gene_id);
