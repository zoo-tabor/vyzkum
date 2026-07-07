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

## Faze 8 - plemena (`td('breeds', slug, nazev)`)
Owner-facing (nejvyssi dopad na portal). Stejny mechanismus jako faze 7, ale sirsi zasah
(nazev plemena se zobrazuje na mnoha mistech pres joined `breed_name`).

- [ ] `resources/lang/breeds/{loc}.php` - klic = `breeds.slug`, hodnota = preklad nazvu.
      Generator `bin/i18n_breeds.php` (cte slug+name z tabulky breeds nebo pevny seznam).
- [ ] Helper/overlay pro preklad nazvu plemene dle slugu (BreedRepository nebo maly support).
- [ ] Aplikovat na OWNER-FACING mista (priorita): `portal/dogs` (seznam), `portal/dog` (detail),
      QR `dog/form` (pokud ukazuje plemeno). Poznamka: `breed_name` chodi z joinu -> overlay dle
      slugu, ktery je take k dispozici (jinak dojoinovat slug).
- [ ] Aplikovat na ADMIN mista (konzistence): seznam/detail psu, prepinac plemene, owners detail,
      forms (breed_name).
- [ ] CAVEAT: pridani plemena v adminu (`BreedController::create`) => novy slug nutno doplnit do
      katalogu (dev krok). Plemen je malo a pridavaji se vzacne. (Volitelne pozdeji: admin UI.)
- [ ] Lint + testy + commit + push.

## Faze 9 - titulky stranek (`<title>`)
Drobne, bez migrace.

- [ ] `layout.php` + `public.php`: sestaveni `$pageTitle` obalit `t()` - `t($title)` (vetsina
      titulku uz existuje jako klic z nadpisu stranek) + prelozitelny suffix.
- [ ] Novy klic pro suffix "Vyzkum ZOO Tabor" (plain, bez span - jiny nez znacka v topbaru).
- [ ] Dynamicke titulky (jmeno psa/majitele) projdou fallbackem beze zmeny.
- [ ] Extrakt klicu + lint + commit + push.

## Faze 10 - klubova sekce
Drobne, bez migrace. Doobalit zbyle neobalene retezce.

- [ ] `layout.php` klubovy topbar/sidebar: "Prehled", "Psi", role "klub" -> `t()`.
- [ ] Klubove views (`club/*` - index, dogs) projit a obalit texty do `t()`/`tc()`.
- [ ] Klubove flash/validace v prislusnem controlleru (pokud jsou).
- [ ] Extrakt klicu + lint + commit + push.

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
