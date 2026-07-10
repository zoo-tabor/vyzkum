# Plan uprav v4

Zdroj zadani: internal_docs/poznamky_upravy_v4.txt. Konvence: UI/data s diakritikou,
.md/komentare/commit ASCII. Deploy prubezne do main; migrace rucne pres ensure_schema.sql.
Po fazi commit + push. Vse prizpusobit prekladum (t()/tc()/td()).

## Prehled ukolu (8)
Serazeno od nejjednodussich/nizkorizikovych po slozite (nektere maji designove rozhodnuti).

### Faze 1 - Schovani sidebaru  [HOTOVO]
- [x] Tlacitko (◫) v topbaru sbali/rozbali bocni menu (owner/klub/admin). Stav v localStorage
      (klic sidebarCollapsed), early-apply skript v <head> (bez FOUC), collapse jen desktop
      (>860px); mobil ma dal off-canvas. CSS+JS inline v layout.php. Bez migrace.

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

### Faze 6 - admin/dogs/{} zmena majitele z UI  [design HOTOV]
ROZHODNUTI: PRIMY prehod na EXISTUJICIHO majitele (bez potvrzovaciho e-mailu - admin dela
prevod az po domluve se starym i novym majitelem). Kdyz novy majitel jeste neni v systemu,
zalozi se zvlast (admin/owners/new) a posle se mu pozvanka (existujici tlacitko).
- [ ] Detail psa: sekce "Zmenit majitele" - naseptavac existujicich majitelu -> setCurrentOwner
      (uz existuje: stary is_current=0, novy=1, historie zachovana), source='admin'. Audit. Bez migrace.

### Faze 7 - /login "zapamatovat uzivatele" (3 mesice)  [design HOTOV]
ROZHODNUTI: BEZPECNY TOKEN V DB. Migrace remember_tokens (user_id, token_hash, expires_at,
created_at, pripadne selector/last_used). httponly cookie 90 dni, v DB jen hash, rotace pri
pouziti, logout + expiry revokuje. Na tom zarizeni PRESKOCI 2FA (uz probehla pri vytvoreni tokenu).
- [ ] Checkbox "Zapamatovat" na /login. RememberService (issue/validate/rotate/revoke) + tabulka.
- [ ] Auto-login v bootstrapu kdyz neni session ale je platny remember cookie. Logout revokuje.
- [ ] Migrace remember_tokens + ensure_schema. Zvazit security-review.

### Faze 8 - Nastaveni: editor ciselniku pricin umrti (death_causes) per plemeno  [design HOTOV]
ROZHODNUTI: 'code' = NEMENNY STABILNI KLIC (pri pridani uzlu unikatni code, presun/mazani ho
nemeni; poradi=position, hierarchie=parent_id). Nerozbije preklady (td death_causes) ani reference
(health_events/umrti). UX = odsazeny strom + pridat/upravit/smazat/nahoru-dolu (jako builder otazek),
per plemeno (vyber plemene). Migrace zadna (death_causes uz existuje).
- [ ] DeathCauseRepository CRUD (create/update/delete/move) + editor view v Nastaveni.
- [ ] Provazani: preklady labelu (td), disease vetev pro zdravotni historii, has_note.

## Poradi a zavislosti
Faze 1-5 jsou nezavisle, jdou hned (rychle winy). Faze 6-8 maji designova rozhodnuti -
konzultovat pred zacatkem kazde. Kazda faze samostatne nasaditelna, po dokonceni commit + push.
