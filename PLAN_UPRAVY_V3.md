# Plan uprav v3 (navazuje na PLAN_UPRAVY_V2.md)

Zdroj zadani: `internal_docs/upravy_v3.txt` (gitignored).
Konvence: UI texty + uzivatelska data s diakritikou; .md/komentare/commit ASCII
(viz memory styl-cestina-repo). Deploy prubezne do main; migrace rucne pres
ensure_schema.sql (server nema CLI). WEDOS FTP deploy obcas spadne na
"AggregateError: (control socket)" = prechodne, staci dalsi push / Re-run.

ODLOZENO (dalsi faze, TEDA NERESIT): jazykovy prepinac / preklady - topbar vedle
loga, users.language, jen /login,/forgot-password,/portal,/set-password,/transfer;
prepinani na serveru v PHP. (upravy_v3.txt radky 1-4.)

## Faze 1 - drobne UI (bez migrace)
- admin/owners: sloupec "Posledni aktualizace" -> "Aktualizace"; novy sloupec
  "Registrovan" (Ano/Ne dle users.password_hash IS NULL).
- portal (Moji psi): vyhodit sloupec "Vztah" -> nahradit datem posledni aktualizace.
- admin/dogs: vek na 2 desetinna mista (xx.xx) - Age helper (novy vypocet float).
- portal/dogs/{}: poznamka k pricine umrti viditelna v tabulce Udaje; reword textu
  na "Pokud jste omylem oznacili psa za uhynuleho (napr. ukradeny pes, ktery se
  pozdeji nasel), muzete ho nize oznacit zpet jako ziveho."
- portal/dogs/{}/forms/{}: odstranit vestavenou otazku "Je pes naziva?"
  (builtin_alive) z vyplnovani dotazniku (+ PortalController::submitForm prestat
  cist builtin_alive).

## Faze 2 - kastrace (bez migrace; sloupce castration_status/date uz existuji)
- admin/dogs/{}/edit: pole kastrace (stav select + datum); admin/dogs/{} detail zobrazi.
- portal/dogs/{}: kastrace v tabulce udaju.

## Faze 3 - zapomenute heslo (bez migrace)
- login: odkaz "Zapomneli jste heslo?" -> /forgot-password (label E-mail + input +
  button "Odeslat odkaz pro obnovu hesla").
- vygeneruje reset invite (password_invites, purpose set_password) a posle odkaz na
  /set-password/{token} (znovupouziti stavajiciho flow). Neprozrazovat, zda e-mail
  existuje (stejna hlaska).

## Faze 4 - presun DNA izolace + GWAS z psa na vzorek (VELKA MIGRACE)
- Migrace: presun dna_isolated_at, gwas_status z dogs -> samples; backfill = prenest
  stavajici hodnoty z psa na jeho NEJNOVEJSI vzorek; pridat hodnotu GWAS_none.
- UI mapovani GWAS: GWAS_sent=Odeslano, GWAS_failed=Nevyslo, GWAS_ok=Vyslo, GWAS_none=Ne.
- admin/dogs list + admin/samples list: DNA izol./GWAS z NEJNOVEJSIHO vzorku psa.
- admin/samples/{}: tlacitko Upravit -> edit (datum izolace, GWAS, poznamka).
- admin/samples/manual: prirazeni vzorku primo ke psovi (naseptavac).
- admin/dogs/{}/edit: odebrat DNA/GWAS pole (presunuto na vzorek).

## Faze 5 - genetika
- admin/genetics/export.csv: UTF-8 s BOM + ";" delimiter; format dle dashboardu
  (dog;breed;gen1;...;tested_at;status;source); zrusit marker i lab.
- admin/genetics: sloupec "Zdroj" (sekvenace/GWAS) - migrace dog_genotypes.source;
  u rucniho zadani vyber s VYCHOZI hodnotou (sekvenace), ale zmenitelny; import default sekvenace.
- admin/genetics/{}: zrusit laborator; pridat poznamku K JEDNOTLIVYM GENUM
  (dog_genotypes.note - poznamka na genotyp psa).
- admin/genetics/markers: poznamka k jednotlivym genum (genes.note - definice genu).

## Faze 6 - formulare, prevod, uklid
- admin/forms/{}/send: karta pod nadpisem s volbou "vsem majitelum plemene" / "jen
  zivym" (FormBroadcastService recipientsForBreed rozsirit o parametr).
- transfer: kdyz novy majitel uz ma ucet (users), NEPOSILAT pozvanku k heslu.
- admin/colours: ZACHOVAT sekci; jen odebrat spodni "Plemeno" select v "Nova barva"
  formulari - plemeno se bere z prepinace v topbaru (BreedContext::current).

## Faze 7 - responsivita portalu
- /portal @media pro telefon/tablet; sidebar schovat do hamburgeru (hamburger button).

## Rozhodnuti (potvrzeno userem)
1. Faze 4: v seznamu Psi DNA/GWAS z NEJNOVEJSIHO vzorku; migrace prenese z psa na nej.
2. Faze 5 zdroj: dog_genotypes.source s vychozi hodnotou, ale zmenitelny.
3. Faze 5 poznamka v genetics/{}: k jednotlivym genum (dog_genotypes.note).
4. admin/colours: sekci ZACHOVAT, odebrat jen redundantni Plemeno select v "Nova barva".
