-- Sablony transakcnich e-mailu. Cesky zdroj (subject/body) je editovatelny z admin
-- UI; preklady do ostatnich jazyku jdou pres tabulku translations
-- (entity_type='email_template', field 'subject'/'body'). Rozeslani dle jazyka
-- prijemce (owners.language, fallback cs). Placeholdery se nahrazuji pri odeslani.

CREATE TABLE IF NOT EXISTS email_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(64) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  placeholders VARCHAR(255) NULL,
  updated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY email_templates_key_uq (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed (idempotentni dle klice; admin muze pozdeji zdroj upravit, INSERT IGNORE neprepise).
INSERT IGNORE INTO email_templates (`key`, subject, body, placeholders) VALUES
('set_password', 'Nastavení hesla - Výzkum ZOO Tábor',
'Dobrý den,

do systému výzkumu plemen psů ZOO Tábor vám byl založen účet.
Pro nastavení hesla použijte tento odkaz (platí 1 měsíc):

{odkaz}

Po nastavení hesla se budete moci přihlásit a vidět své psy.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('password_reset', 'Obnova hesla - Výzkum ZOO Tábor',
'Dobrý den,

obdrželi jsme žádost o obnovu hesla k vašemu účtu ve výzkumu plemen psů ZOO Tábor.
Nové heslo si nastavíte tímto odkazem (platí 2 hodiny):

{odkaz}

Pokud jste o obnovu hesla nežádali, tento e-mail ignorujte - vaše heslo zůstává beze změny.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('ownership_transfer', 'Převzetí psa - Výzkum ZOO Tábor',
'Dobrý den,

stávající majitel vás uvedl jako nového majitele psa v rámci výzkumu plemen psů ZOO Tábor.
Pro potvrzení převzetí psa použijte tento odkaz (platí 1 měsíc):

{odkaz}

Po potvrzení vám přijde odkaz pro nastavení hesla do portálu.

S pozdravem
Výzkumný tým ZOO Tábor', '{odkaz}'),

('form_broadcast', 'Dotazník k vašemu psovi - Výzkum ZOO Tábor',
'Dobrý den,

v rámci výzkumu dlouhověkosti psů ZOO Tábor vás prosíme o vyplnění dotazníku "{dotaznik}" k vašemu psovi {pes}.

Dotazník vyplníte po přihlášení do portálu zde:
{odkaz}

Předem děkujeme za spolupráci.

S pozdravem
Výzkumný tým ZOO Tábor', '{dotaznik}, {pes}, {majitel}, {odkaz}');
