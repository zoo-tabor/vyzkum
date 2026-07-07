# Plan prekladu (i18n) - 2. cast (faze 7-11)

Navazuje na `PLAN_PREKLADY.md` (faze 0-6 HOTOVE A NASAZENE). Tento dokument pokryva
zbyle nedodelane body prekladu zjistene po dokonceni faze 6. Konvence stejne:
UI/data s diakritikou, .md/komentare/commit ASCII; deploy prubezne do main, migrace
rucne pres `ensure_schema.sql`; po fazi rovnou commit + push.

## Vychozi stav (co uz funguje)
- I18n jadro: `t()`/`tc()` (UI omitka, cesky zdroj = klic) + `td($domain,$key,$fallback)`
  (staticke DB ciselniky klicovane stabilnim kodem, katalogy v souborech).
- Tabulka `translations` + `TranslationRepository` (admin-authored obsah = dotazniky).
- Obaleno: cele UI (auth/portal/QR/admin), znacka. Priciny umrti (domenovy katalog).
  Dotazniky (translations + admin UI Preklady). Katalogy ZATIM PRAZDNE (fallback cs).

## Rozhodnuti usera (potvrzeno pro tuto cast)
- B1 E-MAILY: udelat jako dotazniky - sablona e-mailu s poli pro vsechny jazyky, rozeslani
  dle `owners.language` (fallback cs, kdyz jazyk nezname).
- B2 ENUM LABELY: prelozit (typy otazek, typy zdrav. udalosti, stavy vzorku, GWAS).
- B3 TITULKY STRANEK: prelozit `<title>` (vc. suffixu "Vyzkum ZOO Tabor").
- B4 KLUBOVA SEKCE: doobalit zbyle neobalene retezce.
- C1 PLEMENA: prelozit nazvy plemen do jazyka diveka.
- C2 BARVY: NEPREKLADAT - v DB ulozene anglicky dle FCI, zustavaji tak.
- C3 NAZVY KLUBU: NEPREKLADAT.

## Mechanismus (recap - ktery pristup pro co)
- Staticke/seedove ciselniky (enumy, plemena) -> DOMENOVY KATALOG `td('domena', kod, cs)`,
  soubory `resources/lang/{domena}/{loc}.php`, generator v `bin/`. Fallback na cesky zdroj.
  Vyplnuje se editaci souboru (jako ostatni katalogy) + commit.
- Admin-authored dynamicky obsah (e-mailove sablony) -> tabulka `translations` + admin UI
  (jako Preklady u dotazniku).
- Titulky/klub = jen doobaleni do `t()`.

---

## Faze 7 - enum labely (domenove katalogy `td()`)  [HOTOVO - commit d4267ca]
Mechanicka, uzavrena, bez migrace. Zvalidovala `td()` pro enumy pred sirsim nasazenim (faze 8).

- [x] `resources/lang/form_types/{loc}.php` - typy otazek (`FormSchema::TYPES`), klic = kod typu.
      Overlay pres novou `FormSchema::typeLabel()` v `admin/forms/show.php` + `question.php`.
- [x] `resources/lang/health_event_types/{loc}.php` - nova `App\Support\HealthEventType` (cesky
      zdroj + `label()` s td overlay; kody dle `HealthEventRepository::TYPES`). Overlay v
      admin health/index, dogs/show, club/dashboard, builder dropdown (show/question).
      POZN: typy se dosud zobrazovaly jako syrove EN kody -> ted maji i cesky popisek.
- [x] `resources/lang/sample_status/{loc}.php` - nova `App\Support\SampleStatus` (cesky zdroj +
      `label()`). Overlay v `admin/samples/index` + `detail` (radek Stav i select). CSV export
      zustava syrovy kod (data). POZN: taktez dosud syrove EN kody.
- [x] GWAS: `resources/lang/gwas/{loc}.php`; `Gwas::label()`/`options()` pridano td overlay ->
      automaticky vsude, kde se volaji (dogs/show, samples index/detail/edit).
- [x] Generator `bin/i18n_enums.php` - cte konstanty ze Support trid (jeden zdroj pravdy),
      generuje kostry vsech 4 enum katalogu (merge zachova vyplnene).
- [x] Lint + testy (74/0) + funkcni test overlay + commit + push. Katalogy ZATIM PRAZDNE.

## Faze 8 - plemena  [HOTOVO - commit 3dde132]
Owner-facing (vysoky dopad na portal). ZMENA MECHANISMU vuci puvodnimu navrhu: plemena jsou
DATA v DB spravovana adminem (ne staticky seed) a user chce editovat preklady PRIMO Z UI
aplikace (nezavisle na lokalnim DB dumpu) -> misto souboroveho `td()` katalogu pouzita
tabulka `translations` (z faze 6b) + admin UI. Odpada generator i dev krok pri pridani plemena.

- [x] Helper `App\Support\Breeds::translate($name)` - overlay nazvu dle jazyka diveka. Klic
      prekladu = breed id (rename-safe), lookup podle NAZVU (mapa name->id z BreedRepository).
      Cache per request; cs short-circuit (bez DB dotazu); `enabled()` guard (no-op bez tabulky).
      Preklad v translations (entity_type 'breed', field 'name').
- [x] OWNER-FACING obaleno: portal dogs/dog/form/messages/onboarding, auth onboarding, QR dog/form.
- [x] ADMIN obaleno: dogs index/show/form, samples index/detail/batches, genetics index/dog,
      forms index/show/broadcast, owners/show, prepinac plemene v layout.
- [x] Admin UI: `/admin/breeds/{id}/translations` (GET tabulka jazyk->preklad nazvu, jedno pole
      napric jazyky; POST saveTranslations) + odkaz "Preklady" v seznamu plemen.
      `TranslationRepository::localesFor()`.
- [x] NEPREKLADA se: sprava plemen (admin/breeds - kanonicky cesky nazev/zdroj), klubove
      konfig checkboxy (admin/clubs), JS autocomplete (genetics/samples - z API JSON).
- [x] Lint (26) + testy (74/0) + smoke + commit + push. Vyzaduje translations tabulku
      (ensure_schema.sql z faze 6b). Preklady se zadavaji v adminu, ZATIM prazdne -> fallback cs.

## Faze 9 - titulky stranek (`<title>`)  [HOTOVO - commit ed789ff]
Drobne, bez migrace.

- [x] `layout.php` + `public.php`: `$pageTitle = t($title) . ' - ' . t('Vyzkum ZOO Tabor')`.
      `<html lang>` nastaven dle aktualniho jazyka (I18n::locale()).
- [x] Novy klic "Vyzkum ZOO Tabor" (plain suffix, jiny nez znacka se span v topbaru).
- [x] bin/i18n_extract.php nove sbira i `'title' => '...'` literaly jako klice -> vsechny
      titulky prekladatelne bez rucnich napoved (vetsina uz existovala z nadpisu).
- [x] Dynamicke titulky (jmeno psa/majitele = promenna) projdou fallbackem beze zmeny.
- [x] Extrakt (788 klicu) + lint + testy + commit + push + overeno na zivu (<title>, <html lang>).

## Faze 10 - klubova sekce  [HOTOVO - commit ed789ff]
Drobne, bez migrace.

- [x] `layout.php` klubovy topbar/nav: "Prehled", "Psi", role "klub" -> `t()`.
- [x] Klubove views cele obaleny: `club/dashboard.php` + `club/dogs.php` (nadpisy, tabulky,
      stavy, pager); nazvy plemen v klubovych selectech pres `Breeds::translate`.
- [x] ClubController = jen titulky (bez flash) -> reseno extraktorem title literalu.
- [x] Extrakt + lint + testy (74/0) + commit + push.
      POZN: agregat pricin umrti v club dashboard zustava cesky (grupovano dle denorm. textu,
      bez id - mimo rozsah).

## Faze 11 - vicejazycne e-maily (sablony) [VLASTNI NAVRH PRED IMPLEMENTACI]
Nejvetsi kus. Prechod z natvrdo generovanych PHP tel na EDITOVATELNE sablony s preklady per
jazyk, rozeslani dle jazyka prijemce. Ma vlastni navrh builder UX - KONZULTOVAT pred zacatkem.

- [ ] KROK 1 - inventura odesilatelu a sablon: zmapovat vsechny e-maily a jejich placeholdery.
      Kandidati: pozvanka k nastaveni hesla (`InviteService`), pripadny reset hesla, prevod
      vlastnictvi (`OwnershipTransferService`), rozeslani dotazniku (`FormBroadcastService`),
      dalsi transakcni e-maily. (Testovaci e-mail z Diagnostiky = neprekladat.)
- [ ] KROK 2 - migrace `email_templates` (id, `key` UNIQUE, subject, body, placeholders_doc,
      updated_at) + seed z dnesnich ceskych tel (cestina = kanonicky zdroj). Registrace v
      `ensure_schema.sql` (+ numbered migrace).
- [ ] KROK 3 - preklady subject/body pres stavajici tabulku `translations`
      (entity_type='email_template', field 'subject'/'body') + `TranslationRepository`.
- [ ] KROK 4 - admin UI "Sablony e-mailu": seznam sablon + per-sablona obrazovka Preklady
      (jazyk + zdroj->preklad, jako u dotazniku). Zobrazit seznam povolenych placeholderu.
- [ ] KROK 5 - render v odeslani: MailService/odesilatele skladaji e-mail podle `key` + jazyka
      prijemce (owners.language), interpolace placeholderu (placeholdery ZUSTAVAJI nedotcene
      napric jazyky, jako u `t()` params). Fallback na cestinu.
- [ ] KROK 6 - urceni jazyka prijemce kdyz owner nezname (napr. prevod na novy e-mail bez uctu):
      fallback cs (pripadne volba jazyka pri akci).
- [ ] Lint + testy + commit + push. Migrace: spustit `ensure_schema.sql` v phpMyAdmin.

---

## Poradi a zavislosti
- Faze 7 -> 8: sdili `td()` mechanismus (7 uzavrena/mala = warm-up; 8 stejny vzor, sirsi reach).
- Faze 9, 10: nezavisle drobne cistky (`t()` obaleni), lze kdykoliv.
- Faze 11: samostatny velky celek (migrace + admin UI + rework odesilani), az nakonec; ma
  vlastni navrh UX pred implementaci.
- Kazda faze samostatne nasaditelna. Katalogy/preklady zustavaji prazdne (cestina beze zmeny),
  dokud se nedoplni hodnoty.

## Mimo rozsah (zamerne se NEPREKLADA)
- Barvy psu - v DB anglicky dle FCI (C2).
- Nazvy klubu (C3).
- Volny uzivatelsky vstup (jmena psu, volnotextove odpovedi, tela zprav).
