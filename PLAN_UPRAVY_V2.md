# Plan uprav v2 (navazuje na PLAN_UPRAVY.md)

Zdroj zadani: `internal_docs/poznamky_upravy_v2.txt` (gitignored).
Dve temata: (A) prepracovani seznamu dogs/owners/samples na "excelovske" tabulky
(razeni celeho datasetu na vsech sloupcich, sloupcovy filtr, vyber poctu radku,
cislovane strankovani dole), (B) genetika = sledovat GENY misto markeru, dashboard
pes x gen a novy editovatelny detail /admin/genetics/{}.

Rozhodnuti: reseno KLIENTSKOU datatabulkou (jeden znovupouzitelny modul
`public/assets/datatable.js`), server posila vsechny radky vybraneho plemene.
Genetika = gene-centric (migrace gene_id do dog_genotypes).

Konvence textu: UI (app/Views) + uzivatelska data s diakritikou; .md/komentare/
commit ASCII (viz memory styl-cestina-repo).

## Faze 1 - sdilena datatabulka + admin/dogs  [HOTOVO]
- `public/assets/datatable.js` + styly v app.css: razeni A->Z/Z->A na VSECH sloupcich
  nad celym datasetem; sloupcovy filtr "trychtyr" (obsahuje + vyber hodnot, jako
  Excel); globalni hledani; vyber poctu radku (25/50/100/vse); cislovane strankovani
  vlevo dole (<< < 1 2 3 ... > >>) + info "od-do z celkem".
  Nastaveni na <th>: data-nosort, data-nofilter, data-type="num" (+ data-sort na bunce).
- admin/dogs: server vraci vsechny psy plemene (bez server-side strankovani), vsechny
  sloupce sortovatelne, DNA izol. razeni pres data-sort (ISO datum), odstranen horni
  card-filtr (Stav je ted sloupcovy filtr) i naseptavac (nahrazen hledanim v tabulce).
  DogController::index zjednoduseno (bez Paginatoru). Bez migrace.

## Faze 2 - admin/owners na datatabulku  [HOTOVO]
- Modul aplikovan: vsechny sloupce sortovatelne (Psi ciselne, Posledni aktualizace pres
  data-sort ISO), sloupcove filtry (Poznamka data-nofilter), globalni hledani + vyber
  poctu + cislovane strankovani. OwnerController::index vraci vsechny majitele (bez
  Paginatoru), horni card-filtr odstranen. Bez migrace.

## Faze 3 - admin/samples na datatabulku  [HOTOVO]
- Modul aplikovan (Odber pres data-sort ISO, Stav jako sloupcovy filtr misto horniho
  card-filtru). Vyhledavani podle jmena psa resi globalni hledani datatabulky (Pes
  sloupec) - konzistentni s dogs/owners, uzivatel schvalil. SampleController::index
  vraci vsechny vzorky plemene (bez horniho filtru). Bez migrace.

## Faze 4 - genetika: model + dashboard (gene-centric)  [HOTOVO]
- Migrace 014: dog_genotypes.gene_id (backfill z markeru) + index (dog_id, gene_id).
  Nutny import ensure_schema.sql. (013 = onboarding majitele.)
- upsertGenotype dopocitava gene_id z markeru; CSV import i rucni zadani nadale funguji.
- Dashboard /admin/genetics prepracovan: radek = pes, sloupec = gen sledovany u plemene
  (GenotypeRepository::genesForBreed/dogsWithGenotypes/genotypesByDogGene), bunka =
  genotyp; prvni sloupec uzky; + datatabulka. Seznam Psi prepnut z markeru na geny
  (DogRepository::genesForBreed/geneGenotypesForDogs). Proklik na psa zatim vede na
  /admin/dogs/{id}; ve fazi 5 se prepoji na novy /admin/genetics/{id}.
- Pozn.: stary flat vypis + filtry/pager na /admin/genetics nahrazen pivotem; export CSV
  a rucni zadani (marker-based) zustavaji.

## Faze 5 - genetika: detail /admin/genetics/{} s editaci
- Nova stranka jen s genetikou psa: pridat/upravit/smazat genotyp per gen + metadata
  testu (lab, datum). Proklik z dashboardu. Doplneni chybejici editace.

## Vlozeny pozadavek - onboarding majitele  [HOTOVO]
Mimo 5 datatable/genetika fazi (na zadost uzivatele). Migrace 013:
owners.onboarding_completed_at + ownership_transfer_requests.new_owner_phone.
Po nastaveni hesla se majitel presmeruje na /portal/onboarding (jednorazove; dokud
nedokonci, v portalu banner). Stranka: kontrola kontaktu (primarni e-mail read-only,
sekundarni e-maily/telefony/adresa editovatelne), seznam aktualnich psu s otazkou
"Jste stale majitelem?" Ano/Ne; Ne -> pole novy majitel (jmeno, e-mail, telefon) ->
OwnershipTransferService::request (rozsireno o telefon), parovani dle e-mailu; jeden
souhlas (odkaz /gdpr + kontaktovani). Ano -> confirmOwnership. markOnboarded ulozi
contact_consent + onboarding_completed_at.
