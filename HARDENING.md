# Hardening a provoz (Faze 8)

Stav: 2026-06-30. Aplikace `vyzkum.zootabor.eu` (wedos, PHP 8.x, MariaDB).

## 1. Co je hotovo v kodu

- **HTTP bezpecnostni hlavicky** (`App\Support\SecurityHeaders`): X-Content-Type-Options,
  X-Frame-Options: DENY, Referrer-Policy, Content-Security-Policy (jen self + inline,
  zadne externi zdroje), frame-ancestors none, form-action self. Posila se ze vstupniho
  bodu `public/index.php`.
- **Session hardening**: use_strict_mode, use_only_cookies, httponly, SameSite=Lax,
  cookie `secure` podle skutecneho HTTPS, regenerace ID pri loginu, invalidace pri logoutu.
- **Hesla**: Argon2id (`password_hash`), needs-rehash pri loginu.
- **2FA (TOTP)** povinne pro research_admin (lze docasne vypnout `ENFORCE_ADMIN_2FA=false`).
- **CSRF** na vsech state-changing formularich (POST/PUT/DELETE).
- **Rate limiting** na login a 2FA (tabulka `login_throttle`).
- **RBAC + breed scope**: role research_admin / club_viewer / vet(token) / owner;
  klub vidi jen prirazena plemena (`user_breed_access`), bez kontaktnich udaju majitelu.
- **Tokeny** (pozvanky, QR, prevod majitele): do DB jen SHA-256 hash; expirace.
- **Soubory**: mimo web root (`storage/uploads`), deny `.htaccess`, stahovani jen pres
  autorizovany `/files/{id}` + kontrola path traversal; upload whitelist MIME + limit 10 MB.
- **Chyby**: na produkci se nezobrazuji (APP_DEBUG=false), loguji se do `storage/logs`.
- **Prepared statements** vsude (PDO, EMULATE_PREPARES=false).

Kontrola pripravenosti: `php bin/check_config.php` (APP_KEY, APP_DEBUG, DB, migrace, storage).

## 2. Co musi zaridit provoz (mimo kod)

- **Server `.env`** (vyloucen z deploye): `APP_DEBUG=false`, `APP_KEY` nastaveny,
  spravne `DB_*` (d48423_vyzkum), `MAIL_ENABLED=true`, `MAIL_TRANSPORT=mail`.
- **HTTPS certifikat** pro `vyzkum.zootabor.eu` (zatim chyba principalu) - nutne pro
  odkazy v e-mailech a `secure` cookies. Po nasazeni certu vynutit presmerovani http->https.
- **SPF/DKIM/DMARC** pro `zootabor.eu`, aby maily z wedos (`mail()`) nepadaly do spamu.
- **Migrace**: deploy NESPOUSTI migrace. Po kazdem novem `database/migrations/NNN_*.sql`
  spustit v phpMyAdmin (nebo `database/ensure_schema.sql` - idempotentni reconcile).

## 3. Zalohy

- **Databaze**: denni export. Wedos ma vlastni zalohy; navic doporuceno pravidelny
  `mysqldump` / phpMyAdmin Export (struktura + data) ulozit mimo hosting.
- **Soubory**: `storage/uploads` (rodokmeny, dokumenty, soubory z dotazniku) - stahnout
  pres FTP nebo zalohovat na strane hostingu. Nejsou v gitu ani v deployi.
- **Test obnovy**: aspon jednou overit obnovu DB dumpu do testovaci DB + nahrani souboru.

## 4. Monitoring

- **E-maily**: kazde odeslani je v tabulce `email_log` (stav sent/failed/logged + chyba).
  Diagnostika v adminu: `/admin/diagnostics/smtp` (+ test odeslani).
- **Chyby aplikace**: `storage/logs/php-error.log`. Doporuceno pravidelne kontrolovat /
  napojit na hostingovy alerting.
- **Dostupnost**: externi uptime monitor na `https://vyzkum.zootabor.eu/login`.

## 5. N+1 / vykon - audit

Klicove seznamy jsou navrzene bez N+1 (jeden dotaz na stranku + agregace):

- Seznam psu: 1 dotaz (join breed + aktualni majitel + primarni e-mail subselect).
- Seznam majitelu, genotypu, vzorku: 1 dotaz, server-side LIMIT/strankovani.
- Form builder: moznosti otazek nactene hromadne (`optionsByQuestion`).
- Statistiky (klub/zdravi): agregace v SQL (GROUP BY), ne v PHP.

Drobnost: admin stranka Kluby nacita prirazena plemena per ucet (N dotazu pro N klubu) -
kluby jsou jednotky, takze zanedbatelne. Pri vetsim rustu prejit na keyset pagination
u nejvetsich tabulek (genotypy, vzorky) a read-modely pro statistiky (viz NAVRH kap. 13).

## 6. Bezpecnostni checklist (rychla revize)

- [x] CSRF na vsech POST formularich
- [x] RBAC guard na admin/portal/club routach; owner/club nevidi /admin
- [x] Breed scope u klubovych dat; kluby bez kontaktu majitelu
- [x] Hesla Argon2id, tokeny jen hash, expirace
- [x] 2FA pro admina
- [x] Soubory mimo web root + autorizovane stahovani + path-traversal guard
- [x] Security hlavicky + CSP
- [ ] HTTPS cert (provoz)
- [ ] SPF/DKIM/DMARC (provoz)
- [ ] Zalohy + test obnovy (provoz)

## 7. Deploy

Push do `main` -> GitHub Actions -> FTP na wedos (`.github/workflows/deploy.yml`).
Vyloucene z deploye: `.git*`, `.github`, `.env`. Po deployi s novou migraci spustit
SQL v phpMyAdmin. Pred ostrym provozem: `php bin/check_config.php` na serveru.
