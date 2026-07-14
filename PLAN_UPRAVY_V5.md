# PLAN UPRAV V5

Zdroj zadani: `internal_docs/poznamky_upravy_v5.txt` (gitignored). Zivy trackovaci dokument.
Postup: fazovy, po jedne fazi + cekani na svoleni usera mezi fazemi.
Preklady novych/zmenenych stringu -> az finalni faze F7 (levne dohledani pres prazdne klice
v resources/lang/*; behem vyvoje fallback na cestinu).

## Faze

- [x] **F1 - BUG: datum umrti se nepropisuje spravne** (portal i admin edit).
      Root cause: dve rozdilne cesty zapisu umrti. Portal = setAliveStatus (dogs+report+health_event),
      admin edit = DogRepository::update (jen dogs, bez reportu/eventu/alive_confirmed_at).
      Navic setAliveStatus pri umrti necistil alive_confirmed_at (pes soucasne "mrtvy" i "potvrzen ze zije").
      Fix: (1) setAliveStatus umrti -> alive_confirmed_at=NULL; (2) setAliveStatus oziveni -> smazat death
      health_event(y) psa (ztraceny->nalezeny uz se nepocita do statistik; dog_death_reports zustava audit);
      (3) DogController::update routuje zmenu umrti pres setAliveStatus (source 'admin', owner=aktualni
      majitel), jen pri realne zmene. Nova metoda HealthEventRepository::deleteByDogAndType.
      => setAliveStatus je JEDINY "death primitiv" (ISO in) - pouzije ho i F3 (formular).

- [x] **F2 - BUG: podminene otazky (visible_if) nefunguji** v portal/dogs/{}/forms/{}.
      Root cause: mechanismus (JS portal/form.php + server FormConditions) byl OK, ale builder ukladal
      do visible_if.eq volny text - admin napsal label ("Ne"), skutecna hodnota je "no" (yes_no) /
      option_key -> nikdy nesedelo. Fix (varianta A): misto volneho pole rozbalovaci picker hodnot dle
      typu ovladajici otazky (yes_no -> yes/no, single/multiple_choice -> klice moznosti, jinak volny
      text). FormController::conditionValues -> mapa do show.php i question.php (data-cond-map),
      form-builder.js populuje picker (disabled trik: submitne se jen aktivni control). Overeno JS
      harnessem v prohlizeci. Stare konfigurace (eq=label) admin preklikne pres novy picker.

- [ ] **F3 - novy typ otazky "pricina umrti"** v admin/forms builderu (cause-picker, analogicky
      disease_history). UKLADA (dle usera). Pozor: umrti zakladat pres setAliveStatus a NE zaroven pres
      obecne "health_event: death" -> jinak dvojity death event.

- [ ] **F4 - admin/forms/{}/send: moznost 3 = poslat konkretnimu jednomu majiteli** (nasep­tavac dle
      jmena z tabulky owners).

- [ ] **F5 - genetika (3v1):** (a) vzorky radit dle data misto nazvu/cisla; (b) pri pridani vzorku volba
      "Sekvenace + GWAS"; (c) editace zdroje na /admin/genetics/{}.

- [ ] **F6 - admin/health card "Nemoci":** vypis nazvu vazaneho na kod (ne jen kod).

- [ ] **F7 - preklady** novych/zmenenych stringu do 8 jazyku (finalni konsolidace).

## Poznamky
- Server nema CLI ani lokalni DB -> DB toky se overuji az na zivem webu (vyzkum.zootabor.eu) po deployi.
- Kazdy push do main = produkcni deploy (GitHub Actions FTP).
