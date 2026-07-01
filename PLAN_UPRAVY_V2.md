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

## Faze 3 - admin/samples na datatabulku
- Aplikovat modul; horni card-filtr nahradit vyhledavanim vzorku podle jmena psa
  (naseptavac). Strankovani dole, razeni + filtry. Bez migrace.

## Faze 4 - genetika: model + dashboard (gene-centric)
- Migrace 014: dog_genotypes.gene_id (backfill z markeru), unikat (dog_id, gene_id).
  (013 pouzita pro onboarding majitele - viz nize.)
  UI prestane pracovat s markery; CSV import ponechan (hlavicky <KOD>_genotype = symbol
  genu). Dashboard /admin/genetics: radek = pes, sloupec = gen plemene, bunka = genotyp;
  zuzit prvni sloupec; + datatabulka z faze 1. Sloupce genu propsat i do seznamu Psi.

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
