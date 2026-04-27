# Evidence vzorku psu pro GWAS studii dlouhovekosti

Mobilne optimalizovana PHP 8.2 aplikace pro evidenci odberovych sad, veterinarni potvrzeni odberu, majitelskou registraci psa, souhlas, rodokmeny a administrativni validaci.

## Pozadavky

- PHP 8.2
- MariaDB 10.4.x
- Web server smerujici document root do korene projektu
- HTTPS v produkci

## Konfigurace

1. Zkopirujte `.env.example` do `.env`.
2. Nastavte pripojeni k databazi a silny `APP_KEY`.
3. Spustte SQL migraci z `database/schema.sql`.
4. Web server nastavte na koren projektu. Routing zajistuje `.htaccess`.

Citlive soubory, zejmena rodokmeny a vysledky, se ukladaji do `storage/uploads`, mimo verejnou webovou slozku.

## Nasazeni na Wedos

Testovaci prostredi:

```text
https://vyzkum.sachovaskola.eu
```

Produkce:

```text
https://vyzkum.zootabor.eu
```

Aplikace je prizpusobena beznemu Wedos nastaveni, kde subdomena miri do korene adresare napr. `www/subdom/vyzkum`. Vstupni bod je korenovy `index.php` a routing zajistuje `.htaccess`.

Deploy je pripraveny pres GitHub Actions workflow `.github/workflows/deploy.yml`. Spousti se po pushi do `main` nebo rucne pres `workflow_dispatch` a nahrava projekt do:

```text
korene FTP uctu
```

V GitHub repozitari nastavte tyto secrets:

```text
FTP_SERVER
FTP_USER
FTP_PASS
```

Workflow zamerne nenasazuje `.env`, `.git` ani `.github`. Stare soubory po refaktoru, napr. puvodni adresar `public/`, je potreba jednorazove odstranit pres FTP.

V produkcnim `.env` nastavte:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vyzkum.zootabor.eu
MAIL_FROM=no-reply@vyzkum.zootabor.eu
```

## Lokalne

Pokud je dostupne PHP CLI:

```bash
php -S localhost:8000
```

V tomto prostredi zatim PHP ani Git nejsou v PATH, proto nebylo mozne aplikaci lokalne spustit ani vytvorit commit.

## Administrace

Administrace je chranena HTTP Basic Auth. Heslo se neuklada do kodu, ale jako hash v `.env`.

```bash
php scripts/hash_admin_password.php "silne-heslo"
```

Vysledek vlozte do `ADMIN_PASSWORD_HASH`.

## Generovani odberovych sad

Po vytvoreni databaze lze vygenerovat davku vzorku:

```bash
php scripts/generate_sample_batch.php 50 1 > batch.csv
```

Prvni argument je pocet sad, druhy volitelne `vet_id`. CSV obsahuje `sample_id`, veterinarni URL a majitelskou URL. Z techto URL se vytvori QR kody pro tisk stitku.

Stejnou operaci lze provest i v administraci pres `Nova davka`. Aplikace po vytvoreni davky zobrazi tiskovou stranku se dvema QR kody pro kazdy vzorek. Tuto stranku je nutne vytisknout nebo ulozit hned po vygenerovani, protoze tokeny se v databazi ukladaji pouze jako hash a pozdeji je nelze zpetne zobrazit.

Veterinare a kliniky lze zalozit v administraci pres `Veterinari`; jejich ID se potom pouziva i v CLI skriptu jako volitelny druhy argument.
