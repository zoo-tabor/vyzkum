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

- [x] **F3 - novy typ otazky "pricina umrti"** v admin/forms builderu (cause-picker, analogicky
      disease_history). UKLADA. Umrti zakladano pres setAliveStatus (NE dvojity event).
      Implementace: FormSchema::TYPES + isDeathCause; portal fillForm predava causeTree (treeForBreed);
      submitForm -> storeDeathCause (datum <input type=date> ISO + cause-picker; prazdne datum = nic
      nehlasi; jinak setAliveStatus source 'owner_form', continue = preskoci maybeHealthEvent).
      portal/form.php render death_cause (datum + cause-picker s data-cause-prefix q_<id>_) + emise
      #cause-tree + cause-picker.js. cause-picker.js generalizovan (data-cause-prefix, zpetne
      kompatibilni). Builder: data-qtypes=death_cause poznamka, health_event tagging skryt
      (data-qhide=disease_history,death_cause). Odpoved: value_text cesky snapshot + value_json
      (answersLocalized fallback). Overeno JS harnessem (prefix izolace, top-level i vnorene listy, note).

- [x] **F4 - admin/forms/{}/send: moznost 3 = poslat konkretnimu jednomu majiteli** (naseptavac dle
      jmena). Treti radio "Konkretnimu majiteli" + <datalist> unikatnich majitelu psu plemene +
      datalist-id.js (jmeno -> hidden owner_id). recipientsForBreed dostal ?ownerId filtr;
      FormBroadcastService::send dostal ?ownerId; sendBroadcast: mode 'owner' validuje owner_id
      (OwnerRepository::find), posle pro vsechny psy plemene daneho majitele (i uhynule, livingOnly=false).
      Chybove hlasky per mode. Onfocus na naseptavaci zaskrtne radio.

- [x] **F5 - genetika (3v1):** (a) sloupec "Vzorky" v dashboard datatable radi dle DATA (pridan
      data-sort=ISO na bunku; drive se radilo alfabeticky dle sample_id); (b) nova hodnota zdroje
      genotypu 'sekvenace_GWAS' => 'Sekvenace + GWAS' v GenotypeSource::LABELS (auto ve vsech source
      selectech pres options()); (c) detail /admin/genetics/{} dostal select "Zdroj" (default
      "- beze zmeny -" = null zachova; jinak prepise) -> GeneticsController::update predava normalize(source)
      do upsertGenotype (misto natvrdo null). Pozn.: GenotypeSource labely nejsou v td enum katalozich
      (admin-only, cesky) - konzistentni se stavajicim Sekvenace/GWAS.

- [x] **F6 - admin/health card "Nemoci":** vypis nazvu vazaneho na kod (ne jen kod).
      Disease health_events maji normalized_code = kod z ciselniku death_causes (napr. "1.10.2"),
      card ukazovala jen kod. Nova DeathCauseRepository::labelsByCodeForBreed() (rowsForBreed +
      translate -> mapa kod => prelozeny nazev); HealthController predava causeLabels; card ukazuje
      "Nazev <kod muted>" (nenamapovany kod, napr. "(neuvedeno)", zustava jak byl) + hlavicka tabulky.
      Pozn.: card "Vysetreni" ma v normalized_code hodnoty odpovedi (ne kody ciselniku) -> nemapuje se.

- [ ] **F7 - preklady** novych/zmenenych stringu do 8 jazyku (finalni konsolidace).

## Poznamky
- Server nema CLI ani lokalni DB -> DB toky se overuji az na zivem webu (vyzkum.zootabor.eu) po deployi.
- Kazdy push do main = produkcni deploy (GitHub Actions FTP).
