-- Faze v3/5: zdroj genotypu (sekvenace/GWAS) a poznamky.
-- dog_genotypes.source - biologicky zdroj (sekvenace vs GWAS), na jednotlivy genotyp.
-- dog_genotypes.note   - poznamka k jednotlivemu genotypu psa.
-- genes.note           - poznamka k definici genu.

ALTER TABLE dog_genotypes
  ADD COLUMN IF NOT EXISTS source VARCHAR(40) NULL AFTER validation_status,
  ADD COLUMN IF NOT EXISTS note VARCHAR(255) NULL AFTER source;

ALTER TABLE genes
  ADD COLUMN IF NOT EXISTS note VARCHAR(255) NULL AFTER description;

-- Stavajici genotypy pochazeji z PCR sekvenace (idempotentni).
UPDATE dog_genotypes SET source = 'sekvenace' WHERE source IS NULL;
