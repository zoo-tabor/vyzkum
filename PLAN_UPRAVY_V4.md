# Plan uprav v4

Zdroj zadani: internal_docs/poznamky_upravy_v4.txt. Konvence: UI/data s diakritikou,
.md/komentare/commit ASCII. Deploy prubezne do main; migrace rucne pres ensure_schema.sql.
Po fazi commit + push. Vse prizpusobit prekladum (t()/tc()/td()).

## Prehled ukolu (8)
Serazeno od nejjednodussich/nizkorizikovych po slozite (nektere maji designove rozhodnuti).

### Faze 1 - Schovani sidebaru  [jednoduche, frontend]
- [ ] Tlacitko pro sbaleni/rozbaleni bocniho menu (admin, pripadne portal/klub).
- [ ] Stav zapamatovat (localStorage), CSS pro sbaleny stav. Bez migrace.

### Faze 2 - admin/samples/{} smazani vzorku  [jednoduche]
- [ ] Tlacitko Smazat vzorek na detailu + potvrzeni + SampleController::destroy + routa.
- [ ] Osetrit vazby (genotypy/health_events/soubory ze vzorku) - zkontrolovat FK a bezpecne smazat.
- [ ] Audit log. Bez migrace.

### Faze 3 - admin/genetics sloupec Vzorky (jen nejnovejsi)  [stredni]
- [ ] Do seznamu genetiky pridat sloupec Vzorky; kdyz ma pes vice vzorku, ZOBRAZIT POUZE NEJNOVEJSI.
- [ ] Rozsirit dotaz (nejnovejsi sample_id + datum) - bez N+1. Bez migrace.

### Faze 4 - admin/dogs/new naseptavac (zeme + majitel)  [stredni, frontend]
- [ ] Zeme puvodu: naseptavac (fulltext zuzeni) z Countries.
- [ ] Majitel: naseptavac z existujicich majitelu (OwnerRepository::allForSelect uz je).
- [ ] Reseni bez zavislosti (nativni <datalist> nebo lehky JS typeahead). Bez migrace.

### Faze 5 - admin/genetics/markers editace genu z UI  [stredni, CRUD]
- [ ] V UI menit gen: symbol, nazev, poznamka + markery (dnes read-only).
- [ ] GeneticsController + repo update; validace. Pripadna migrace jen kdyz chybi sloupce.

### Faze 6 - admin/dogs/{} zmena majitele z UI  [DESIGN pred implementaci]
- [ ] Admin prehodi psa na jineho majitele primo z detailu psa.
- [ ] ROZHODNUTI: vybrat existujiciho majitele / zalozit noveho? Vytvorit zaznam v dog_owners
      (stary is_current=0, novy is_current=1) = historie prevodu? Reuse OwnershipTransferService
      (potvrzovaci e-mail) NEBO primy prevod adminem bez potvrzeni? Audit.

### Faze 7 - /login "zapamatovat uzivatele" (3 mesice)  [DESIGN + BEZPECNOST]
- [ ] Checkbox "Zapamatovat" na loginu -> trvale prihlaseni 3 mesice na tom zarizeni.
- [ ] ROZHODNUTI: bezpecny remember-me token (hash v DB, httponly cookie, rotace) vs. dlouha
      session cookie. Doporuceno token v DB (revokovatelny). Migrace remember_tokens.
- [ ] Zvazit security-review (persistentni prihlaseni je citlive).

### Faze 8 - Nastaveni: editor ciselniku pricin umrti (death_causes) per plemeno  [DESIGN, nejvetsi]
- [ ] Admin UI pro spravu viceurovnoveho stromu pricin umrti + per plemeno (parovani pres death_causes).
- [ ] ROZHODNUTI: UX editoru stromu (pridat/upravit/smazat/presunout uzel, urovne, has_note,
      breed-scope). Provazani s prekladem (td death_causes) a se zdravotni historii (disease vetev).
- [ ] Migrace zadna (death_causes uz existuje) - jen CRUD + poradi (position) + parent_id.

## Poradi a zavislosti
Faze 1-5 jsou nezavisle, jdou hned (rychle winy). Faze 6-8 maji designova rozhodnuti -
konzultovat pred zacatkem kazde. Kazda faze samostatne nasaditelna, po dokonceni commit + push.
