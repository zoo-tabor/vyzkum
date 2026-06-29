# CRM pro vyzkum plemen psu (vyzkum.zootabor.eu)

Modularni monoliticka aplikace v cistem PHP 8.2+ a MariaDB/MySQL.
Architektura: viz [`NAVRH_ARCHITEKTURY_CRM.md`](NAVRH_ARCHITEKTURY_CRM.md).
Postup vyvoje po fazich: viz [`PLAN_VYVOJE.md`](PLAN_VYVOJE.md).

Stav: **Faze 1 (skelet a jadro)** - skelet, migrace, prihlaseni, role/RBAC,
prepinac plemene (breed context), audit log, zakladni testy.

## Struktura

```
public/            web root (front controller index.php, assets)
app/
  Core/            Router, Config, Database, Session, Csrf, View, Migrator, Request
  Middleware/      RequireAuth, RequireRole
  Controllers/     Auth, Dashboard, Breed
  Services/        Auth, AuditService, BreedContext, RateLimiter
  Repositories/    User, Breed
  Support/         Policy (cista autorizacni logika)
  Views/           sablony + layout
database/migrations/  cislovane .sql migrace
bin/               CLI: migrate.php, create_admin.php
tests/             lehky test runner (bez Composeru)
storage/           logs, uploads, exports (mimo public root, gitignored)
old_app/           puvodni aplikace - zdroj funkcnosti pro QR/vzorky (Faze 4)
```

## Inicializace databaze

Dve cesty, podle toho, jestli PHP dosahne na DB:

**A) phpMyAdmin (doporuceno pro wedos hosting)** - kdyz je DB vzdalena a hosting
blokuje vzdalene MySQL pripojeni:
1. (volitelne) `database/cleanup.sql` - smaze vsechny tabulky (cisty start).
2. `database/install.sql` - vytvori schema Faze 1 a oznaci migraci 001.
3. Admin ucet: vygenerujte hash lokalne a vlozte uzivatele SQL prikazem:
   ```
   php -r "echo password_hash('SILNE-HESLO', PASSWORD_ARGON2ID), PHP_EOL;"
   ```
   ```sql
   INSERT INTO users (email, password_hash, role, status)
   VALUES ('admin@zootabor.eu', '<vlozeny-hash>', 'research_admin', 'active');
   ```

**B) Migrace z CLI** - kdyz PHP na DB dosahne (typicky primo na serveru, kde
bezi i DB):
```
php bin/migrate.php status
php bin/migrate.php up
php bin/create_admin.php admin@zootabor.eu "silne-heslo-min-10"
```

## Lokalni spusteni

```
cp .env.example .env   # nastavte DB_* a APP_KEY
php -S localhost:8000 -t public public/index.php
```
Otevrete <http://localhost:8000/login>. Pri prvnim prihlaseni vyzkumneho admina
si aplikace vynuti aktivaci 2FA (TOTP) na strance Zabezpeceni.

> `old_app/` je jen lokalni reference (zdroj funkcnosti pro QR/vzorky, Faze 4) a
> NENI soucasti git repozitare.

## Testy

```
php tests/run.php
```

## Bezpecnostni poznamky

- `.env` (a `old_app/.env`) jsou v `.gitignore` - tajne udaje necommitovat.
- Hesla: `password_hash` (Argon2id), CSRF na vsech POST, rate limiting na login.
- Web root je `public/`; `app/`, `storage/` a `.env` jsou mimo nej.
- GDPR/souhlasove texty jsou zatim placeholder (lorem ipsum), dodaji se pozdeji.
