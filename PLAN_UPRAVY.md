# Plan uprav (navazuje na dokonceny vyvoj)

Zdroj zadani: `internal_docs/poznamky_upravy.txt` (gitignored).
Rozhodnuti (po upresneni): GWAS = stav u psa (sloupec `dogs.gwas_status`);
"posledni kontakt" majitele = nejnovejsi jakakoli akce majitele; "Rozeslat
dotaznik" = e-mail + sledovani (assignments), text e-mailu editovatelny pred
odeslanim, jeden ukol/e-mail na psa; vek se pocita od posledni odpovedi "pes zije"
(nove `dogs.alive_confirmed_at`).

Konvence textu: UI texty (app/Views) + uzivatelska data (zeme, flash hlasky) s
diakritikou; .md dokumenty, komentare a commit zpravy ASCII (viz memory styl-cestina-repo).

## Faze 1 - datovy model + ciselniky  [HOTOVO]
- Migrace 011: `dogs.country` (CHAR3), `dogs.dna_isolated_at`, `dogs.gwas_status`,
  `dogs.alive_confirmed_at`; nova tabulka `colours` (breed_id, name).
- Ciselnik zemi (`Support/Countries.php`, ISO 3166-1 alpha-3, kod -> cesky nazev).
- Nastaveni -> Barvy: sprava barev per plemeno (ColourController/Repository).
- `setAliveStatus`: pri "zije" ulozi `alive_confirmed_at = dnes`.
- Vzorky: zuzeni stavu (odebrano analysis_ready, archived, excluded).

## Faze 2 - admin/dogs (seznam)  [HOTOVO]
- Sloupce: Vek (Age helper - death/alive_confirmed/newest sample -> today), Zeme (kod
  ISO alpha-3, title=nazev), Vzorky (cisla + datum prijeti), DNA izol., genotypy dle
  markeru plemene (jen kdyz je vybrano plemeno; markery = distinct z dog_genotypes),
  GWAS. JS razeni pres hlavicku (A-Z/Z-A, per stranka), uzsi sloupec jmena.
- Filtr: naseptavac jmena + "Chovatelska stanice" (JSON /admin/dogs/suggest);
  odebran filtr Cip/prukaz i razeni selecty. Bez nove migrace.

## Faze 3 - admin/dogs/{} + admin/dogs/new  [HOTOVO]
- Detail: zeme (nazev), vek, DNA izol., GWAS; "Zdravotni udalosti" -> "Zdravotni zaznamy".
- Formular psa: vyber zeme (Countries), barva ze `colours` plemene + "jine" (JS dle
  vybraneho plemene), DNA izol., GWAS. Novy pes: inline vzorek (custom cislo + datum
  prijeti) -> ensureImportedSample spari se psem. DogRepository create/update rozsireno.

## Faze 4 - admin/owners + admin/owners/{}  [HOTOVO]
- Seznam: sloupce Tel. cislo (Phone::formatCz), Datum posledni aktualizace
  (GREATEST z updated_at / posledni zprava / dotaznik / potvrzeni psa / last_login),
  Poznamky. Telefon jako +420/... (00 -> +).
- Detail: tlacitko Upravit; editace majitele (OwnerController edit/update,
  OwnerRepository update/setPrimaryEmail). Bez nove migrace.

## Faze 5 - admin/samples + new-batch
- Rucni pridani vzorku bez veterinare (bez davky, custom cislo).
- new-batch bez plemene (jen cislo + veterinar); plemeno zada majitel v QR.
- Auto `analysis_done` po nahrani genetiky.

## Faze 6 - verejne vet/{} a dog/{}
- Cip bez omezeni (znaky i delka). "Pocet odebranych zkumavek" -> "Pocet".
- vet/: hlavicka CSS jako admin. dog/: vyhodit zdravotni stav (nechat rodokmen);
  1. souhlas -> odkaz /documents/gdpr.html; 2. souhlas novy text; 3. souhlas pryc.

## Faze 7 - admin/forms
- "Rozeslat dotaznik" (e-mail majitelum plemene + assignments, editace textu pred
  odeslanim, 1 ukol/e-mail na psa). Odebrat filtr Plemeno.

## Pozn. - interni data
`internal_docs/` = DB dump + dog_profiles.csv + medical_exams.csv (scrape Cavalier
klubu). Pripadny import realnych dat = samostatny ukol po upravach.
