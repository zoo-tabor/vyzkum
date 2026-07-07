# Plan prekladu (i18n) - prepinac jazyka

Nova samostatna funkcionalita (ne "upravy v4"): moznost prepnout jazyk rozhrani.
Zdroj zadani: `internal_docs/upravy_v3.txt` radky 1-4 + upresneni od usera.
Konvence: UI texty + data s diakritikou; .md/komentare/commit ASCII (viz
memory styl-cestina-repo). Deploy prubezne do main; migrace rucne pres
ensure_schema.sql. Po fazi rovnou commit + push (viz memory deploy-po-fazi).

## Cil
Prepinac jazyka (vpravo nahore vedle loga) s nazvy v originale:
Cestina / English / Espanol. Architektura tak, aby PRIDANI JAZYKA = 1 katalog +
1 radek v registru. Cestina je vychozi/zdrojovy jazyk.

## Rozhodnuti usera (potvrzeno)
1. Katalog = CESKY ZDROJ JAKO KLIC (gettext styl). Homonyma resi kontext (viz tc()).
2. Zatim JEN KOSTRY KATALOGU (en/es prazdne k pozdejsimu dopsani); cestina funguje hned.
3. Admin: prepinac ZOBRAZIT i v adminu + PRIPRAVIT katalog (obaleni admin sablon = vlastni
   vetsi faze, preklady se doplni pozdeji).
4. Verejne QR formulare /vet a /dog ZAHRNOUT hned.
5. Jazyk majitele drzet v owners.language (SQL) + cookie (kvuli login page pred prihlasenim);
   jazyk ukazat i v /admin/owners dashboardu; dotazniky udelat vicejazycne s defaultem dle
   owners.language (vlastni faze, vlastni navrh).
6. Rizene DB ciselniky (priciny umrti, otazky/volby dotazniku, typy zdrav. udalosti):
   DB drzi KANONICKY (id/kod, vzdy stejne), UI zobrazi dle jazyka diveka. Uloziste prekladu
   = JEDNA OBECNA tabulka `translations`. UI omitka zustava na t()/tc() (cesky zdroj jako klic).
   Obe vrstvy ziji VEDLE SEBE - nemeni se cely pristup, jen se doplnuje druha vrstva pro DB.

## Tri druhy obsahu (klicove pro navrh)
1. UI OMITKA (staticke sablony: nadpisy, tlacitka, napovedy, flash, validace) -> katalog
   `t()/tc()` (cesky zdroj = klic). Fallback na cestinu.
2. RIZENE DB CISELNIKY ulozene KANONICKY (id/kod): priciny umrti (`death_causes`), otazky
   a volby dotazniku (`form_questions`/`form_question_options`/`form_definitions`), typy
   zdravotnich udalosti. DB drzi referenci, UI vykresli STITEK v jazyce diveka (fallback cs).
   -> preklady v tabulce `translations` (viz nize). Typy zdrav. udalosti = drobny pevny enum
   -> staci maly katalog/helper (jako Gwas), bez DB.
3. VOLNY VSTUP uzivatele (jmena psu, volnotextove odpovedi, telo zprav) -> NEprekladá se,
   zobrazi se jak zadano. Cislo/datum se jen naformatuje dle locale.

## Mechanismus (ciste PHP, bez Composeru)
- `resources/lang/locales.php` -> `['cs'=>'Cestina','en'=>'English','es'=>'Espanol']` (REGISTR,
  z nej se generuje prepinac; pridani jazyka = pridat radek + katalog).
- `resources/lang/en.php`, `es.php` -> `['cesky zdroj' => 'preklad']`. `cs` je zdroj (bez souboru).
- `App\Support\I18n`: setLocale/locale/available/load; `t($text, $params=[])` +
  `tc($context, $text, $params=[])`.
  - `t()`: cesky zdroj = klic; chybejici preklad -> FALLBACK na cestinu (nic se nerozbije);
    `{param}` interpolace.
  - `tc()`: kontext+text (resi HOMONYMA - `tc('sample','Stav')` vs `tc('dog','Stav')` maji
    v katalogu dve ruzne polozky). V katalogu ulozeno jako klic `"kontext\x04text"`.
- `app/helpers.php`: globalni funkce `t()` a `tc()` (tenke obaly nad I18n).
- `bin/i18n_extract.php`: projde zdrojaky, najde t()/tc() volani a vygeneruje/aktualizuje
  KOSTRY `en.php`/`es.php` (vsechny klice, prazdne hodnoty) - sprava katalogu bez rucniho
  hledani retezcu. (Poznamka: bin/ na serveru nejde spustit, extraktor bezi lokalne.)

## Prekladatelne DB ciselniky - tabulka `translations`
Jedna obecna tabulka pro preklady vsech rizenych DB ciselniku (bez zmeny schematu pri
pridani jazyka):
```
translations(id, entity_type, entity_id, field, locale, text,
             UNIQUE(entity_type, entity_id, field, locale),
             INDEX(entity_type, locale))
```
- entity_type/field, ktere se prekladaji:
  - `death_cause` / `label`
  - `form_definition` / `name`, `description`
  - `form_question` / `label`, `help_text`
  - `form_option` / `label`
- `TranslationRepository`: `for($entityType, $ids[], $locale)` -> mapa `[id][field]=>text`
  (davkove, bez N+1); `set(...)`, `delete(...)`.
- RENDER: cteni ciselniku prekryje zakladni (cesky) label prekladem pro locale; kdyz preklad
  chybi -> zustava cesky. Kanonicka hodnota v DB se NEMENI (id/kod), preklada se jen zobrazeni.
  - priciny umrti: vykreslovat z `death_cause_id` (ne z denormalizovaneho `dogs.death_cause`);
    `DeathCauseRepository` dostane locale-overlay. Denormalizovany cesky label zustava jako
    snapshot/fallback pro zaznamy bez id (volny text).
  - odpovedi na VYBEROVE otazky: vykreslit z reference (`option_id` / ulozene option_key), ne
    z ulozeneho `value_text` (cesky snapshot) -> zobrazi se prelozeny stitek volby. Volnotext
    zustava; cislo/datum se formatuje dle locale.
- Editace prekladu: v builderu dotazniku pole per jazyk (faze 6); u priciny umrti sprava
  prekladu labelu (faze 6, pripadne male admin UI).

## Urceni + perzistence jazyka
- SESSION drzi aktualni locale (funguje i pro anonymni /login).
- COOKIE (dlouhodoba, server-readable) = pamet zarizeni pred prihlasenim (login page).
- `owners.language` = preference uctu majitele (cross-device), zdroj defaultu + sloupec
  v /admin/owners + default jazyk dotazniku.
- Priorita resolveru (v bootstrapu, brzy): session -> cookie -> owners.language (po prihlaseni)
  -> Accept-Language -> `cs`.
- Prepnuti: `GET /locale/{lang}` (validace proti registru, redirect zpet dle `?r=` nebo Referer)
  -> nastavi session + cookie; u prihlaseneho majitele i owners.language.
- Po prihlaseni majitele: seed session z owners.language (preference prebije stare cookie).

## Migrace
- `owners.language VARCHAR(5) NULL` (faze 4).
- tabulka `translations` (faze 6 - rizene ciselniky).
- `users.language` az u admin/klub perzistence (pokud bude potreba cross-device pro ne-majitele).

## Prepinac UI
- portal: topbar vedle loga; auth/verejne karty: nahore v karte; admin: topbar (jen zobrazit).
- odkazy v originalnich nazvech (Cestina | English | Espanol), aktivni zvyraznen, bez JS
  (ciste server-side prepnuti pres /locale).

## Faze
### Faze 0 - jadro i18n (bez prekladoveho obsahu)
- I18n + t()/tc() + registr locales + loader katalogu + fallback na cestinu.
- prazdne `en.php`/`es.php` (kostry), `bin/i18n_extract.php`.
- resolver locale + `GET /locale/{lang}` routa + cookie/session.
- migrace `owners.language`.
- bootstrap wiring (nastaveni locale brzy v requestu).
- prepinac jako sdilena komponenta (portal topbar + auth karty + admin topbar).
- (v teto fazi se jeste NEobaluji stranky - jen infrastruktura; cestina vsude beze zmeny.)

### Faze 1 - auth/verejne stranky
Obalit t()/tc(): login, forgot_password, set_password, onboarding (auth varianta),
transfer/confirm, transfer/done + prislusne flash/validace v AuthController,
ForgotPasswordController, SetPasswordController, TransferController.

### Faze 2 - portal (owner)
Obalit owner cast layout.php (sidebar/topbar popisky, Odhlasit), dogs, dog, form,
form_response, forms, messages, messages_thread, settings, contacts, onboarding
+ owner-facing flash/validace v PortalController.

### Faze 3 - verejne QR formulare
Obalit vet/form, vet/done, dog/* (registrace), done + flash/validace v
PublicSampleController. (public.php layout hlavicka.)

### Faze 4 - owners.language napojeni
Default locale majitele z owners.language; ulozeni pri prepnuti; sloupec "Jazyk"
v /admin/owners dashboardu (OwnerRepository paginate + view).

### Faze 5 - admin priprava
Prepinac v adminu (uz z faze 0). Obaleni admin sablon do t()/tc() - VELKE, deli se po
sekcich (layout+dashboard, dogs, owners, samples, forms, genetics, ...). Preklady se
doplni pozdeji; do te doby fallback na cestinu.

### Faze 6 - rizene DB ciselniky (translations tabulka)
Migrace: tabulka `translations` + TranslationRepository. Typy zdrav. udalosti = maly katalog
(bez DB). Deli se na dve casti:

6a) PRICINY UMRTI: locale-overlay v DeathCauseRepository (treeForBreed/findLeaf/rowsForBreed),
    vykreslovani z `death_cause_id` v portalu (portal/dog.php) i adminu (admin/dogs show/edit,
    cause-picker). Male admin UI pro spravu prekladu labelu ciselniku (nebo primo v seedu).

6b) DOTAZNIKY (VLASTNI DETAILNI NAVRH PRED IMPLEMENTACI): builder UI pro zadani prekladu
    name/description (definice), label/help (otazky), label (volby) per jazyk. Portal renderuje
    otazky/volby dle owners.language s fallbackem na cestinu. Zobrazeni ODPOVEDI (portal
    form_response, admin response) prekresli vyberove odpovedi z reference (option) na
    prelozeny stitek; volnotext zustava. Tato cast potrebuje samostatny navrh builder UX.

## Poradi a zavislosti
Faze 0 je zaklad pro vse. 1-3 lze delat nezavisle po sobe (obalovani stranek). 4 navazuje
na 0 (owners.language). 5 je velka a muze bezet nakonec / prubezne. 6 (rizene ciselniky) je
nejvetsi: 6a (priciny umrti) je uzavrena a jde udelat driv; 6b (dotazniky) ma vlastni navrh
builder UX pred zacatkem. Kazda faze samostatne nasaditelna; katalogy i tabulka translations
zustavaji prazdne (cestina beze zmeny), dokud user/prekladatel nedoplni en/es hodnoty.
