-- Ciselnik pricin umrti (hierarchicky strom). Seed = seznam pro cavalier (globalni, breed_id NULL).

CREATE TABLE IF NOT EXISTS death_causes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  breed_id INT UNSIGNED NULL,
  parent_id INT UNSIGNED NULL,
  code VARCHAR(20) NOT NULL,
  label VARCHAR(190) NOT NULL,
  has_note TINYINT(1) NOT NULL DEFAULT 0,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY death_causes_breed_code_uq (breed_id, code),
  INDEX death_causes_parent_idx (parent_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE dogs
  ADD COLUMN IF NOT EXISTS death_cause_id INT UNSIGNED NULL AFTER death_cause,
  ADD COLUMN IF NOT EXISTS death_cause_note TEXT NULL AFTER death_cause_id;

-- Seed (idempotentni: vlozi jen kdyz jeste neni koren globalniho seznamu).
SET @seeded := (SELECT COUNT(*) FROM death_causes WHERE breed_id IS NULL AND code = '1');
INSERT INTO death_causes (breed_id, code, label, has_note, position)
SELECT NULL, code, label, has_note, position FROM (
  SELECT '1' AS code, 'Nemoc' AS label, 0 AS has_note, 1 AS position
  UNION ALL
  SELECT '1.1' AS code, 'Endokrinní onemocnění' AS label, 0 AS has_note, 2 AS position
  UNION ALL
  SELECT '1.1.1' AS code, 'Cukrovka' AS label, 0 AS has_note, 3 AS position
  UNION ALL
  SELECT '1.1.2' AS code, 'Cushingův syndrom' AS label, 0 AS has_note, 4 AS position
  UNION ALL
  SELECT '1.1.3' AS code, 'Hypotyreóza' AS label, 0 AS has_note, 5 AS position
  UNION ALL
  SELECT '1.1.4' AS code, 'Jiné endokrinní onemocnění' AS label, 1 AS has_note, 6 AS position
  UNION ALL
  SELECT '1.2' AS code, 'Imunologické onemocnění' AS label, 0 AS has_note, 7 AS position
  UNION ALL
  SELECT '1.2.1' AS code, 'Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)' AS label, 0 AS has_note, 8 AS position
  UNION ALL
  SELECT '1.2.2' AS code, 'Trombocytopenie' AS label, 0 AS has_note, 9 AS position
  UNION ALL
  SELECT '1.2.3' AS code, 'Jiné imunologické onemocnění' AS label, 1 AS has_note, 10 AS position
  UNION ALL
  SELECT '1.3' AS code, 'Kožní onemocnění' AS label, 0 AS has_note, 11 AS position
  UNION ALL
  SELECT '1.3.1' AS code, 'Jiné kožní onemocnění' AS label, 1 AS has_note, 12 AS position
  UNION ALL
  SELECT '1.4' AS code, 'Nádorová onemocnění' AS label, 0 AS has_note, 13 AS position
  UNION ALL
  SELECT '1.4.1' AS code, 'Lymfom' AS label, 0 AS has_note, 14 AS position
  UNION ALL
  SELECT '1.4.2' AS code, 'Nádor jater, ledvin nebo střevního traktu' AS label, 0 AS has_note, 15 AS position
  UNION ALL
  SELECT '1.4.3' AS code, 'Nádor kostí nebo kloubů' AS label, 0 AS has_note, 16 AS position
  UNION ALL
  SELECT '1.4.4' AS code, 'Nádor kůže nebo podkoží' AS label, 0 AS has_note, 17 AS position
  UNION ALL
  SELECT '1.4.5' AS code, 'Nádor mléčné žlázy' AS label, 0 AS has_note, 18 AS position
  UNION ALL
  SELECT '1.4.6' AS code, 'Nádor močového měchýře' AS label, 0 AS has_note, 19 AS position
  UNION ALL
  SELECT '1.4.7' AS code, 'Nádor nervové soustavy' AS label, 0 AS has_note, 20 AS position
  UNION ALL
  SELECT '1.4.8' AS code, 'Nádor plic' AS label, 0 AS has_note, 21 AS position
  UNION ALL
  SELECT '1.4.9' AS code, 'Nádor sleziny, srdce nebo cévního systému' AS label, 0 AS has_note, 22 AS position
  UNION ALL
  SELECT '1.4.10' AS code, 'Jiné nádorové onemocnění' AS label, 1 AS has_note, 23 AS position
  UNION ALL
  SELECT '1.5' AS code, 'Neurologické onemocnění' AS label, 0 AS has_note, 24 AS position
  UNION ALL
  SELECT '1.5.1' AS code, 'Epilepsie' AS label, 0 AS has_note, 25 AS position
  UNION ALL
  SELECT '1.5.2' AS code, 'Syringomyelie' AS label, 0 AS has_note, 26 AS position
  UNION ALL
  SELECT '1.5.3' AS code, 'Jiné neurologické onemocnění' AS label, 1 AS has_note, 27 AS position
  UNION ALL
  SELECT '1.6' AS code, 'Oční onemocnění' AS label, 0 AS has_note, 28 AS position
  UNION ALL
  SELECT '1.6.1' AS code, 'Slepota' AS label, 0 AS has_note, 29 AS position
  UNION ALL
  SELECT '1.6.2' AS code, 'Syndrom suchého oka' AS label, 0 AS has_note, 30 AS position
  UNION ALL
  SELECT '1.6.3' AS code, 'Jiné oční onemocnění' AS label, 1 AS has_note, 31 AS position
  UNION ALL
  SELECT '1.7' AS code, 'Onemocnění pohybového aparátu' AS label, 0 AS has_note, 32 AS position
  UNION ALL
  SELECT '1.7.1' AS code, 'Artróza jiného kloubu než kyčelního nebo loketního' AS label, 0 AS has_note, 33 AS position
  UNION ALL
  SELECT '1.7.2' AS code, 'Deformující spondylóza' AS label, 0 AS has_note, 34 AS position
  UNION ALL
  SELECT '1.7.3' AS code, 'Dysplazie kyčelního kloubu a následná artróza' AS label, 0 AS has_note, 35 AS position
  UNION ALL
  SELECT '1.7.4' AS code, 'Dysplazie loketního kloubu a následná artróza' AS label, 0 AS has_note, 36 AS position
  UNION ALL
  SELECT '1.7.5' AS code, 'Imunitně zprostředkovaná polyartritida' AS label, 0 AS has_note, 37 AS position
  UNION ALL
  SELECT '1.7.6' AS code, 'Jiná dysplazie kostí nebo kloubů' AS label, 0 AS has_note, 38 AS position
  UNION ALL
  SELECT '1.7.7' AS code, 'Luxace čéšky' AS label, 0 AS has_note, 39 AS position
  UNION ALL
  SELECT '1.7.8' AS code, 'Poranění předního zkříženého vazu' AS label, 0 AS has_note, 40 AS position
  UNION ALL
  SELECT '1.7.9' AS code, 'Syndrom kaudy equiny' AS label, 0 AS has_note, 41 AS position
  UNION ALL
  SELECT '1.7.10' AS code, 'Výhřez meziobratlové ploténky' AS label, 0 AS has_note, 42 AS position
  UNION ALL
  SELECT '1.7.11' AS code, 'Jiné onemocnění pohybového aparátu' AS label, 1 AS has_note, 43 AS position
  UNION ALL
  SELECT '1.8' AS code, 'Onemocnění trávicí soustavy' AS label, 0 AS has_note, 44 AS position
  UNION ALL
  SELECT '1.8.1' AS code, 'Exokrinní pankreatická insuficience (EPI)' AS label, 0 AS has_note, 45 AS position
  UNION ALL
  SELECT '1.8.2' AS code, 'Jaterní insuficience / selhání jater' AS label, 0 AS has_note, 46 AS position
  UNION ALL
  SELECT '1.8.3' AS code, 'Megaezofagus' AS label, 0 AS has_note, 47 AS position
  UNION ALL
  SELECT '1.8.4' AS code, 'Neprůchodnost střeva způsobená cizím tělesem' AS label, 0 AS has_note, 48 AS position
  UNION ALL
  SELECT '1.8.5' AS code, 'Jiné onemocnění trávicí soustavy' AS label, 1 AS has_note, 49 AS position
  UNION ALL
  SELECT '1.9' AS code, 'Respirační onemocnění' AS label, 0 AS has_note, 50 AS position
  UNION ALL
  SELECT '1.9.1' AS code, 'Kolaps průdušnice' AS label, 0 AS has_note, 51 AS position
  UNION ALL
  SELECT '1.9.2' AS code, 'Pneumonie' AS label, 0 AS has_note, 52 AS position
  UNION ALL
  SELECT '1.9.3' AS code, 'Jiné respirační onemocnění' AS label, 1 AS has_note, 53 AS position
  UNION ALL
  SELECT '1.10' AS code, 'Srdeční onemocnění' AS label, 0 AS has_note, 54 AS position
  UNION ALL
  SELECT '1.10.1' AS code, 'Endokardióza' AS label, 0 AS has_note, 55 AS position
  UNION ALL
  SELECT '1.10.2' AS code, 'Kardiomyopatie' AS label, 0 AS has_note, 56 AS position
  UNION ALL
  SELECT '1.10.3' AS code, 'Jiné srdeční onemocnění' AS label, 1 AS has_note, 57 AS position
  UNION ALL
  SELECT '1.11' AS code, 'Urologická onemocnění' AS label, 0 AS has_note, 58 AS position
  UNION ALL
  SELECT '1.11.1' AS code, 'Infekce dělohy / pyometra' AS label, 0 AS has_note, 59 AS position
  UNION ALL
  SELECT '1.11.2' AS code, 'Ledvinové kameny' AS label, 0 AS has_note, 60 AS position
  UNION ALL
  SELECT '1.11.3' AS code, 'Močová inkontinence' AS label, 0 AS has_note, 61 AS position
  UNION ALL
  SELECT '1.11.4' AS code, 'Selhání ledvin' AS label, 0 AS has_note, 62 AS position
  UNION ALL
  SELECT '1.11.5' AS code, 'Jiné urologické onemocnění' AS label, 1 AS has_note, 63 AS position
  UNION ALL
  SELECT '1.12' AS code, 'Ušní onemocnění' AS label, 0 AS has_note, 64 AS position
  UNION ALL
  SELECT '1.12.1' AS code, 'Chronický nebo opakovaný zánět ucha' AS label, 0 AS has_note, 65 AS position
  UNION ALL
  SELECT '1.12.2' AS code, 'Jiné ušní onemocnění' AS label, 1 AS has_note, 66 AS position
  UNION ALL
  SELECT '1.13' AS code, 'Vrozená vada' AS label, 0 AS has_note, 67 AS position
  UNION ALL
  SELECT '1.13.1' AS code, 'Jiná vývojová porucha' AS label, 0 AS has_note, 68 AS position
  UNION ALL
  SELECT '1.13.2' AS code, 'Vrozená anomálie obratlů' AS label, 0 AS has_note, 69 AS position
  UNION ALL
  SELECT '1.13.3' AS code, 'Vrozená vada nebo malformace štěněte' AS label, 0 AS has_note, 70 AS position
  UNION ALL
  SELECT '1.13.4' AS code, 'Vrozená vývojová vada srdce' AS label, 0 AS has_note, 71 AS position
  UNION ALL
  SELECT '1.13.5' AS code, 'Jiné vrozené onemocnění' AS label, 1 AS has_note, 72 AS position
  UNION ALL
  SELECT '1.14' AS code, 'Jiné nespecifikované onemocnění' AS label, 1 AS has_note, 73 AS position
  UNION ALL
  SELECT '2' AS code, 'Stáří' AS label, 0 AS has_note, 74 AS position
  UNION ALL
  SELECT '3' AS code, 'Nehoda' AS label, 1 AS has_note, 75 AS position
  UNION ALL
  SELECT '4' AS code, 'Jiné' AS label, 1 AS has_note, 76 AS position
) t WHERE @seeded = 0;

-- Napojeni parent_id podle kodu (napr. 1.10.1 -> 1.10).
UPDATE death_causes c
  JOIN death_causes p ON p.breed_id <=> c.breed_id
     AND p.code = SUBSTRING_INDEX(c.code, '.', LENGTH(c.code) - LENGTH(REPLACE(c.code, '.', '')))
  SET c.parent_id = p.id
  WHERE LOCATE('.', c.code) > 0;
