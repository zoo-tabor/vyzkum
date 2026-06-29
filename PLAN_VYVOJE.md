# Plan vyvoje CRM pro vyzkum plemen psu

Datum: 2026-06-29
Navazuje na: `NAVRH_ARCHITEKTURY_CRM.md`, `old_app/`, `import_sablona_*.csv`

Tento dokument je konkretni, serazeny postup vyvoje v bodech. Kazda faze ma:
ukoly, zavislosti, co prevzit z `old_app`, a kriterium hotovo.

Legenda zdroju:
- PORT = prevzit/upravit kod z `old_app`
- NEW = napsat nove
- DECISION = nutne rozhodnuti pred zacatkem

---

## Faze 0 - Priprava (pred psanim kodu)

Cil: odblokovat vyvoj, zalozit prostredi.

- [ ] DECISION: potvrdit rozsah MVP podle `NAVRH_ARCHITEKTURY_CRM.md` kap. 19.
- [ ] Doplnit `.env` SMTP/IMAP hodnoty (port, heslo, SMTP_FROM, jmeno odesilatele,
      STARTTLS/SSL, IMAP slozka odeslane posty). Bez nich nejde Faze 3 (e-maily).
- [ ] Vyzadat finalni GDPR/souhlas texty od pravniho oddeleni; ulozit jako HTML
      fragment nacitany do layoutu (ne cela HTML stranka).
- [ ] Potvrdit prvotni import sablonu `import_sablona_psi_majitele_vzorky.csv`.
- [ ] Potvrdit PCR import mapu: presne nazvy markeru (B3GALNT1, NLRP1, PARP14,
      COL9A1, ...), povolene hodnoty a vyznam `X` / prazdne bunky.
- [ ] DECISION: IMAP jen pro ulozeni odeslane posty, nebo i nacitani odpovedi?
- [ ] Zalozit Git repozitar (POZOR: `.env` a `storage/` do `.gitignore`,
      zadne secrets do Gitu).
- [ ] Pripravit prostredi: PHP 8.2+, MariaDB 10.4+, Composer (jen na dev tooling:
      PHPUnit, php-cs-fixer), lokalni virtualhost na `vyzkum.test`.
- [ ] Zalozit strukturu projektu (viz Faze 1).

Hotovo kdyz: `.env` je kompletni, repo bezi, lokalni server vraci uvodni stranku.

---

## Faze 1 - Skelet aplikace a jadro

Cil: bezpecny zaklad s migracemi, prihlasenim, rolemi, breed kontextem a auditem.
Bez teto faze nelze stavet nic dalsiho.

> STAV (2026-06-29): HOTOVO (vcetne 2FA). Implementovano: skelet + middleware
> router, migracni runner (`001_core.sql` + `database/install.sql` pro phpMyAdmin),
> login + role/RBAC + `user_breed_access`, session hardening + CSRF + rate
> limiting, breed context, audit log, admin layout, TOTP 2FA pro research_admin
> (vynucene pri prvnim prihlaseni), 16 unit testu (zelene). Login POST, migrace a
> dashboard vyzaduji bezici DB. `old_app/` neni v repu (lokalni reference).

### 1.1 Skelet a jadro (vetsinou PORT z old_app)
- [ ] PORT: `app/Core/Router.php`, `Config.php`, `Database.php`, `Session.php`,
      `Csrf.php`, `View.php`, `helpers.php`, `bootstrap.php`, autoloader, `index.php`.
- [ ] NEW: rozsirit Router o HTTP metody PUT/DELETE a o middleware vrstvu
      (auth, role, csrf, breed-scope) pred handlerem.
- [ ] NEW: struktura slozek `app/{Core,Controllers,Services,Repositories,Models,
      Middleware,Views}`, `database/migrations`, `storage/{logs,uploads,exports}`,
      `public/` (web root jen sem; `app/`, `storage/`, `.env` mimo public root).
- [ ] NEW: jednotny error/exception handler + log do `storage/logs`.

### 1.2 Migrace (NEW - framework to neresi)
- [ ] NEW: jednoduchy migracni runner (tabulka `schema_migrations`, prikaz
      `php migrate up/down/status`), idempotentni, transakcni.
- [ ] NEW: prvni migrace = `breeds`, `users`, `audit_logs` (v2 dle kap. 6.2).
- [ ] Poznamka: NEbrat `old_app/database/schema.sql` 1:1 - stary model ma
      `dogs.breed` jako VARCHAR, `dogs.owner_id` primy FK a `dogs.sample_id` 1:1.
      Novy model = `breed_id`, vazba `dog_owners` (historie), vzorek odkazuje na psa.

### 1.3 Auth + RBAC (NEW, nahrazuje Basic Auth)
- [ ] NEW: nahradit `AdminAuth` (HTTP Basic) plnohodnotnym loginem:
      formular, session, `password_hash` (Argon2id), regenerace session ID.
- [ ] NEW: role `research_admin`, `club_viewer`, `vet`, `owner` (sloupec `role`
      v `users`; `roles/permissions` az kdyz to nestaci).
- [ ] NEW: tabulka `user_breed_access` (user_id, breed_id, access_level) - hlavne
      pro kluby; `research_admin` v MVP vidi vse.
- [ ] NEW: middleware: `RequireAuth`, `RequireRole`, `RequireBreedAccess`.
- [ ] NEW: session hardening (httponly, samesite, secure, idle timeout),
      CSRF na vsech POST/PUT/DELETE, rate limiting na login.
- [ ] NEW: 2FA pro `research_admin` (TOTP) - lze zaradit na konec faze.
- [ ] NEW: seed prvniho `research_admin` uctu (CLI skript, ne v UI).

### 1.4 Breed context (NEW)
- [ ] NEW: globalni prepinac plemene v admin layoutu (Vsechna / konkretni /
      naposledy pouzita), ulozeny v session.
- [ ] NEW: breed-scope helper - kazdy domenovy dotaz dostane `breed_id` filtr,
      pokud uzivatel nema pravo "videt vse". Vynutit jako konvenci v repository
      vrstve (ne spolehat na controllery).

### 1.5 Audit log (NEW, rozsireny)
- [ ] NEW: `AuditService::log(actor_user_id, actor_role, action, entity_type,
      entity_id, old_json, new_json, ip)`. PORT konceptu z `old_app` audit_logs,
      ale s `actor_user_id` misto `actor_type/label`.

### 1.6 Admin layout + testy
- [ ] NEW: zakladni admin layout (navigace dle kap. 10.1, prepinac plemene,
      vyhledavani, uzivatel) - zatim prazdne sekce.
- [ ] NEW: testovaci infrastruktura (PHPUnit), prvni feature testy:
      login, role guard, breed-scope guard, CSRF.

Hotovo kdyz: lze se prihlasit jako admin, prepnout plemeno, vidim prazdny
dashboard, role/breed guardy a CSRF maji testy, migrace bezi.

---

## Faze 2 - Plemena, psi, majitele, import

Cil: jadro CRM dat + prvotni naplneni daty pred prihlasenim majitelu.

> STAV (2026-06-29): HOTOVO. Increment A: migrace `002_dogs_owners.sql`, admin
> seznam psu s filtry + razenim + strankovanim BEZ N+1, detail psa s historii
> majitelu, rucni create/edit, prirazeni majitele, majitele (seznam/detail/create
> + vice kontaktu). Increment B: CSV import dle sablony (nahled s validaci +
> deduplikace majitelu podle e-mailu + transakcni commit) a CSV export seznamu
> psu (respektuje filtry/plemeno). 31 unit testu (Paginator, DogQuery, Csv,
> importer helpery). POZN.: migrace 002 nutno spustit v phpMyAdmin (deploy
> nespousti migrace); CSV import zatim ignoruje sample_* (modul vzorku = Faze 4).

### 2.1 Datovy model (migrace)
- [ ] NEW migrace: `breeds`, `owners`, `owner_emails`, `owner_phones`, `dogs`,
      `dog_owners`, `dog_death_reports`, `health_documents`, `files`
      (presne sloupce dle kap. 6.2).
- [ ] NEW indexy: `dogs(breed_id,name)`, `dogs(chip_number)`,
      `dogs(pedigree_number)`, `dogs(breed_id,status)`,
      `dog_owners(owner_id,is_current)`, `dog_owners(dog_id,is_current)`,
      `owner_emails(email)`.

### 2.2 Sprava plemen
- [ ] NEW: CRUD plemen (slug immutable po vytvoreni, name, club_id, is_active).
      Slug nikdy z raw UI vstupu do nazvu DB objektu.

### 2.3 Psi a majitele - admin
- [ ] NEW: seznam psu = query/read model s joiny (1 dotaz radky + 1 dotaz
      aktualni majitele pres `dog_id IN (...)` + 1 dotaz agregace) - ZADNE N+1.
- [ ] NEW: filtry: plemeno, majitel, jmeno psa, chip/prukaz, zivy/uhynuly,
      datum posledni aktualizace; server-side pagination (keyset u velkych dat).
- [ ] NEW: detail psa (zakladni udaje, majitele+historie, vzorky, zdravi,
      formulare, genetika, zpravy, audit) - sekce se plni v dalsich fazich.
- [ ] NEW: detail majitele (kontakty, psi, historie).
- [ ] NEW: rucni vytvoreni/uprava psa a prirazeni pes<->majitel pres `dog_owners`.

### 2.4 Prvotni import (CSV/XLSX)
- [ ] NEW: import dle `import_sablona_psi_majitele_vzorky.csv`.
- [ ] NEW: validace - plemeno (breed_slug) existuje, format chipu, validni e-mail,
      duplicity chipu, duplicity prukazu, konflikt majitelu.
- [ ] NEW: deduplikace majitele podle primarniho e-mailu (fallback jmeno+telefon).
- [ ] NEW: parsovani vice e-mailu/telefonu oddelenych strednikem; datumy YYYY-MM-DD.
- [ ] NEW: preview importu -> potvrzeni -> vytvoreni `dogs`+`owners`+`dog_owners`
      (stav vazby `pending_owner_registration`). Pokud `sample_id` vyplnen,
      zalozit i vzorek (navazuje na Faze 4).
- [ ] NEW: import bezi v transakci / davkove; vse auditovano.

### 2.5 Export
- [ ] PORT/NEW: CSV/XLSX export seznamu (PORT z `AdminController::exportSamples`,
      rozsirit o breed-scope a nova pole).

Hotovo kdyz: admin nahraje CSV, vidi preview, potvrdi a v seznamu jsou psi s
majiteli; filtry a pagination funguji; testy importu zelene.

---

## Faze 3 - Owner portal, "Odeslat heslo", e-mail

Cil: majitel se prihlasi a vidi sve psy; funguji pozvanky a notifikace.

> STAV (2026-06-29): increment A HOTOVO. Migrace `003_invites_mail.sql`
> (password_invites, email_log). MailService (SMTP STARTTLS+AUTH LOGIN, bez
> knihoven; pri MAIL_ENABLED=false jen loguje do storage/logs/mail.log + email_log).
> TokenService (token v odkazu, do DB jen sha256). InviteService + tlacitko
> "Odeslat heslo" na detailu majitele (vytvori ucet owner + pozvanku 1 mesic +
> posle odkaz). Verejne /set-password/{token} (validace, nastaveni hesla, prihlaseni).
> Role-based redirect po loginu (owner -> /portal). Portal majitele /portal
> ("Moji psi" + kontakty, read-only). +2 testy (TokenService). ZBYVA increment B:
> samoobsluha majitele (potvrzeni psa, uprava kontaktu, datum umrti, nahrani
> zdrav. dokumentu). POZN.: spustit migraci 003 v phpMyAdmin (nebo ensure_schema.sql).
>
> STAV mail (2026-06-29): wedos blokuje odchozi SMTP 25 -> MailService pouziva
> PHP mail() (MAIL_TRANSPORT=mail|smtp). Diagnostika /admin/diagnostics/smtp.
>
> STAV increment B1 (2026-06-29): HOTOVO (bez migrace). FileStorage - uploady
> prejmenovane a trideny do storage/uploads/<plemeno>/owner_<id>/dog_<id>/
> (mimo web root, deny .htaccess); stahovani pres /files/{id} s autorizaci.
> Portal majitele: detail psa, potvrzeni "pes je muj", blok "naziva?/datum umrti
> DD.MM.RRRR" (propis do psa + dog_death_reports), nahrani zdrav. dokumentu,
> uprava kontaktu (adresa/telefony/sekundarni e-maily). +6 testu (Dates, FileStorage).
> STAV B2 (2026-06-29): HOTOVO. Migrace `004_forms.sql` (form_definitions,
> form_versions, form_questions, form_question_options). Admin builder /admin/forms:
> dotaznik per plemeno, verzovani (publikace = zamek, uprava = nova draft verze
> klonovanim), typy otazek (kratka/dlouha odpoved, single/multiple choice, cislo,
> datum, ano/ne, soubor), podminene otazky (visible_if), poradi (nahoru/dolu),
> moznosti pres "klic|popisek". FormSchema (deterministicky slug + parseOptions)
> +4 testy. ZBYVA B3: vyplneni dotazniku majitelem (render typu + podminek +
> soubor u otazky + poznamka na konci + vestaveny blok naziva/umrti) + ulozeni
> odpovedi (form_assignments/responses/answers) + admin prehled odpovedi.

### 3.1 Mail modul (NEW)
- [ ] NEW: `MailService` (SMTP z `.env`, `vyzkum@zootabor.eu`), sablony,
      audit odeslani (komu, sablona, stav, chyba) do DB.
- [ ] NEW: cron/lehka fronta pro odeslani (neblokovat request).
- [ ] DECISION/NEW: IMAP ulozeni do "Odeslane" dle rozhodnuti z Faze 0.

### 3.2 Tokeny a pozvanky (NEW)
- [ ] NEW migrace: `password_invites` (token_hash, purpose, expires_at, used_at,
      sent_at, created_by_user_id).
- [ ] NEW: tokeny min. 128 bit entropie, do DB jen SHA-256 hash, po pouziti
      invalidovat. Set-password expirace 1 mesic, reset hesla 1 hodina.

### 3.3 "Odeslat heslo" (workflow 9.2)
- [ ] NEW: tlacitko v detailu psa/majitele -> vytvori/najde `user` pro majitele
      -> `password_invite` 1 mesic -> e-mail s odkazem.
- [ ] NEW: tlacitko zmizi kdyz heslo nastaveno / invite pouzit; po expiraci
      zobrazit "Odeslat znovu".

### 3.4 Owner portal (workflow 9.3)
- [ ] NEW: set-password stranka (validace tokenu), login majitele.
- [ ] NEW: "Moji psi" - majitel vidi jen psy pres aktualni/historicky `dog_owners`.
- [ ] NEW: majitel muze: potvrdit psa, upravit vlastni kontakty (e-maily/telefony),
      ulozit datum umrti (`dog_death_reports` + propis do `dogs.death_date`,
      original auditovan), nahrat zdravotni dokumentaci (`health_documents`).
- [ ] NEW: majitel NEMENI jadrove udaje psa (chip, datum narozeni, chov.stanice,
      pedigree, genotypy) - jen pres zadost adminovi; vse se zdrojem `owner`.

Hotovo kdyz: admin posle heslo, majitel si ho nastavi, prihlasi se, vidi sve psy,
upravi kontakt, potvrdi psa; odeslane e-maily jsou v audit logu.

---

## Faze 4 - QR / vzorky modul (port + napojeni)

Cil: prenest funkcni QR workflow ze stare aplikace na novy datovy model.

### 4.1 Datovy model
- [ ] NEW migrace: `samples`, `sample_batches`, `vets` (+ `vet_clinics` dle
      potreby), `consents`. PORT konceptu z `old_app/database/schema.sql`, ale:
      `samples.dog_id` + `samples.breed_id` napojeny na NOVE `dogs`/`breeds`;
      ZADNE duplicitni `owners`/`dogs` jen pro QR modul.
- [ ] NEW indexy: `samples(sample_id)`, `samples(breed_id,status)`,
      `samples(dog_id)`.

### 4.2 Port QR workflow (PORT)
- [ ] PORT: generovani davek + `sample_id` + tokeny + tisk stitku/QR
      (`SampleRepository::createBatch/batches/batchLabels`, view `print_labels`,
      `assets/vendor/qrcode.min.js`).
- [ ] DECISION: zda dal ukladat viditelne tokeny (`vet_token`,`owner_token`)
      kvuli opakovanemu tisku stitku, nebo jen hash + "zobrazit jednou".
      Doc doporucuje neukladat viditelne tokeny, pokud neni nutne.
- [ ] PORT: vet formular `/vet/{sampleId}/{token}` (chip, typ vzorku, pocet
      materialu, datum odberu) - `VetController`, `SampleRepository::submitVet`,
      view `vet/form`, `vet/done`. Mobilni UI.
- [ ] PORT: owner QR formular `/dog/{sampleId}/{token}` - `OwnerController`,
      `OwnerRegistrationService`, view `owner/form`, `owner/done`.
- [ ] UPRAVA: `OwnerRegistrationService` zapisuje do NOVYCH `owners/owner_emails/
      dogs/dog_owners/consents/files` misto starych tabulek.
- [ ] PORT: souhlasy (`consents`) - verzovane; upload rodokmenu pres `files`
      (mimo public root, whitelist MIME+velikost) - PORT z `storePedigree`.

### 4.3 Stavovy model a napojeni
- [ ] NEW: formalni stavove prechody vzorku (`created` ... `archived`/`excluded`
      dle kap. 5.5) misto volneho `status` stringu.
- [ ] NEW: QR registrace muze zalozit/napojit noveho majitele a poslat mu
      set-password odkaz (napojeni na Faze 3 mail/invite); zdroj dat `owner_qr`.
- [ ] NEW: kompatibilni routy `/vet/{sample_id}/{token}` a `/dog/{sample_id}/{token}`
      jako ve stare aplikaci (test kompatibility).
- [ ] NEW: anonymizovany veterinarni dashboard (pocet odeslanych vzorku, rozpad
      podle plemen a casu; zadne PII, zadne identifikovatelne vysledky).

Hotovo kdyz: admin vygeneruje davku+stitky, vet pres QR odesle vzorek, majitel
pres QR zaregistruje psa a dostane pozvanku; data jsou v novem modelu; testy
QR tokenu a kompatibilnich URL zelene.

---

## Faze 5 - Zpravy a zmena majitele

Cil: interni komunikace a samoobsluzny prevod psa.

### 5.1 Interni zpravy (kap. 5.9)
- [ ] NEW migrace: `message_threads`, `message_participants`, `messages`,
      `message_attachments`; index `message_threads(entity_type,entity_id)`.
- [ ] NEW: vlakna ke psovi / majiteli / odpovedi formulare / prevodu majitele;
      stavy otevrene/ceka na majitele/vyreseno/archivovano; admin odpovedi.
- [ ] NEW: majitelska poznamka/zadost adminovi z portalu.

### 5.2 Zmena majitele (workflow 9.4)
- [ ] NEW migrace: `ownership_transfer_requests`.
- [ ] NEW: majitel zada jmeno+e-mail noveho majitele -> system posle
      registracni/set-password e-mail -> po potvrzeni novym majitelem AUTOMATICKY
      ukoncit stare `dog_owners.is_current` a zalozit nove (bez admin schvaleni).
- [ ] NEW: admin dostane informacni notifikaci + audit zaznam celeho procesu.

Hotovo kdyz: majitel zalozi prevod, novy majitel potvrdi a prevezme psa, stary
ztrati pristup, vse je auditovano; admin/majitel komunikuji ve vlaknu.

---

## Faze 6 - Form builder a zdravotni data

Cil: tvoritelne dotazniky (jako Google Forms) s normalizovanymi daty.

### 6.1 Model formularu (kap. 6.2)
- [ ] NEW migrace: `form_definitions`, `form_versions`, `form_questions`,
      `form_question_options`, `form_assignments`, `form_responses`,
      `form_answers`, `health_events`.
- [ ] NEW indexy: `health_events(breed_id,event_type,event_date)`,
      `health_events(dog_id,event_type)`, `form_assignments(owner_id,status)`,
      `form_responses(dog_id,submitted_at)`.

### 6.2 Form builder (admin)
- [ ] NEW: tvorba formulare + verzovani (publikovana verze immutable;
      zmena otazky = nova verze; moznosti maji stabilni `option_key`, ne jen text).
- [ ] NEW: typy otazek (kratka/dlouha odpoved, single/multiple choice, datum,
      cislo, ano/ne, soubor, opakovatelna skupina) + vzdy volitelna poznamka.
- [ ] NEW: prirazeni formulare primarne celemu plemeni; jednorazovy vs. opakovany.

### 6.3 Vyplnovani a mapovani (workflow 9.6)
- [ ] NEW: majitel vidi ukol v portalu / dostane e-mail, vyplni, ulozi raw
      `form_responses`/`form_answers` + volitelna poznamka.
- [ ] NEW: mapovani odpovedi na `health_events` (zdroj `owner_form`, stav validace).
- [ ] NEW: kdyz se opakuji poznamky chybejici struktury -> admin vytvori novou
      verzi formulare s novou strukturovanou moznosti.
- [ ] NEW: zakladni zdravotni statistiky (predpriprava read modelu - Faze 7).

Hotovo kdyz: admin vytvori a publikuje formular pro plemeno, majitel ho vyplni,
odpovedi se ulozi a mapuji do health_events; nova verze nerozbije stara data.

---

## Faze 7 - Genetika a klubove statistiky

Cil: PCR markery, genotypy, sortovatelne tabulky, klubovy read-only pristup.

### 7.1 Model genetiky (kap. 5.8 / 6.2)
- [ ] NEW migrace: `genes`, `genetic_markers`, `genetic_tests`, `dog_genotypes`.
- [ ] NEW indexy: `dog_genotypes(dog_id,marker_id)`,
      `dog_genotypes(breed_id,marker_id,genotype)`, `genetic_markers(gene_id)`.

### 7.2 Genetika (admin/vyzkum)
- [ ] NEW: sprava genu/markeru (symbol, lokus, ref/alt alela, povolene hodnoty).
- [ ] NEW: rucni zadani genotypu + sortable/filter tabulky (gen, pes, plemeno,
      genotyp `GG/CC`, datum, stav validace); server-side pagination.
- [ ] NEW: CSV import dle `import_sablona_pcr_genetika.csv` (workflow 9.7):
      parovani podle `sample_id`, dynamicke genotypove sloupce mapovane na markery
      pres import mapu, vyznam `X`/prazdne; validace pes/marker/format/duplicita;
      vsechny vysledky do `dog_genotypes` (GWAS mimo rozsah v1).
- [ ] NEW: PCR/geneticke vysledky SKRYTE pred majiteli; viditelne vyzkum + kluby;
      export pro vyzkum; audit zmen.

### 7.3 Klubove statistiky (kap. 5.10)
- [ ] NEW: klubovy dashboard - vyber plemene, seznam jednotlivych psu se JMENEM
      majitele BEZ kontaktnich udaju (e-mail/telefon/adresa skryte).
- [ ] NEW: agregace - pocty/stav, zivi/uhynuli, vekova struktura, prumerny vek,
      priciny umrti, frekvence nemoci, vysledky vysetreni, geneticke rozlozeni
      podle markeru, trend v case; export agregaci.
- [ ] NEW: kazdy report filtrovany podle opravnenych `breed_id`.

### 7.4 Read modely (kap. 13)
- [ ] NEW: `breed_stats_daily`, `breed_health_stats`, `breed_genetic_stats`,
      `dog_search_index`; prepocet jobem po zmene dat + rucni "Prepocitat".

Hotovo kdyz: admin naimportuje PCR CSV, vysledky jsou v sortovatelne tabulce a
skryte majitelum; klub vidi agregace a psy bez kontaktu; statistiky jedou z
read modelu.

---

## Faze 8 - Hardening a produkce

Cil: bezpecne, vykonne a provozovatelne nasazeni na `vyzkum.zootabor.eu`.

- [ ] Performance + N+1 review klicovych seznamu (psi, vzorky, genetika);
      testy na maximalni pocet SQL dotazu na obrazovku.
- [ ] Bezpecnostni review: RBAC + object-level autorizace, breed-scope vsude,
      CSRF, prepared statements, file upload whitelist, tokeny/QR, rate limiting.
- [ ] GDPR: verzovane souhlasy, export dat majitele, proces opravy, minimalizace
      PII v logach, opatrne mazani (vyzkumna data mohou mit duvod uchovani).
- [ ] E-mail: SPF/DKIM/DMARC na domene, alert na selhani odeslani.
- [ ] Zalohy: denni DB backup + zalohy souboru + TEST obnovy ze zalohy.
- [ ] Monitoring dostupnosti + log chyb + staging prostredi.
- [ ] Dokumentace provozu + migracni runbook; kontrolovane spousteni migraci.
- [ ] Smoke testy + e2e testy (admin, majitel, vet, klub) po deployi.
- [ ] Prepnout stare URL/funkce na novou aplikaci na `vyzkum.zootabor.eu`.

Hotovo kdyz: projde security/perf/N+1 review, obnova ze zalohy overena,
monitoring a staging bezi, e2e testy zelene, aplikace nasazena na produkci.

---

## Kriticke zavislosti (poradi se nesmi obejit)

1. Faze 1 (auth, RBAC, breed context, migrace) je zaklad pro vse.
2. Faze 3 (mail+invites) musi byt pred QR registraci majitele (4.3) a pred
   zmenou majitele (5.2) - obe posilaji set-password e-maily.
3. Faze 2 (`dogs`/`breeds`/`dog_owners`) musi byt pred Faze 4 - vzorky se na ne
   napojuji (oprava oproti staremu modelu).
4. `health_events` (6.1) je cil pro mapovani formularu (6.3) i pro statistiky (7.3).
5. Read modely (7.4) az kdyz existuji zdrojova data (Faze 2,6,7).

## Hlavni rozdily oproti `old_app`, ktere je nutne vyresit

- Basic Auth admin -> plny login + role + 2FA.
- `dogs.breed` (VARCHAR) -> `breed_id` (FK na `breeds`).
- `dogs.owner_id` 1:1 + `dogs.sample_id` 1:1 -> `dog_owners` (historie M:N),
  vzorek odkazuje na psa (`samples.dog_id`).
- Jeden e-mail/telefon -> `owner_emails` / `owner_phones`.
- Viditelne tokeny v DB -> rozhodnout (hash-only vs. reprint stitku).
- Volny `status` string vzorku -> formalni stavovy automat.
- Custom migracni UI ze stare app NEpouzivat jako dlouhodobe reseni -> migracni runner.
