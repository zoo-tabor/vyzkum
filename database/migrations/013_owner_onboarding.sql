-- Onboarding majitele po nastaveni hesla (kontrola udaju + potvrzeni psu)
-- + telefon noveho majitele u zadosti o prevod.

ALTER TABLE owners
  ADD COLUMN IF NOT EXISTS onboarding_completed_at DATETIME NULL;

ALTER TABLE ownership_transfer_requests
  ADD COLUMN IF NOT EXISTS new_owner_phone VARCHAR(40) NULL AFTER new_owner_email;
