-- Preklady/i18n: preferovany jazyk rozhrani majitele (kod locale, napr. cs/en/es).
-- Slouzi jako default jazyka portalu i dotazniku a zobrazuje se v /admin/owners.

ALTER TABLE owners
  ADD COLUMN IF NOT EXISTS language VARCHAR(5) NULL AFTER preferred_contact_method;
