# Navrh aplikace

## Cil

Aplikace eviduje odberove sady a biologicke vzorky psu zarazenych do GWAS studie dlouhovekosti. Prvni verze je mobilne optimalizovana PHP 8.2 webova aplikace s pripravenym zakladem pro PWA.

Zakladni jednotkou evidence je `sample_id`. Cislo cipu psa je biologicky identifikator, ale workflow a fyzicky pohyb vzorku se ridi pres `sample_id`.

## Technologicke mantinely

- PHP 8.2
- MariaDB 10.4.34
- konfigurace pres `.env`
- verzovani pres Git
- verejny document root pouze `public/`
- citlive uploady mimo verejnou slozku ve `storage/uploads`

## QR odkazy

Na fyzicke odberove sade jsou dva QR kody:

- veterinarni QR: `/vet/{sample_id}/{token}`
- majitelsky QR: `/dog/{sample_id}/{token}`

`sample_id` je citelne uvedene na stitku, ale URL obsahuje i nahodny token. Duvodem je ochrana proti hadani URL a spamovemu vyplnovani formulare. V databazi se neuklada token, ale pouze jeho SHA-256 hash.

## Workflow

1. Vyzkumny tym vygeneruje davku vzorku pomoci CLI skriptu.
2. Z CSV se vytisknou QR kody a stitky.
3. Veterinar naskenuje veterinarni QR a vyplni pouze cip, typ vzorku, pocet materialu a datum odberu.
4. Majitel naskenuje majitelsky QR a doplni udaje o psovi, rodokmen, kontakt a souhlas.
5. Administrace zkontroluje data, rodokmen a fyzicke doruceni vzorku.
6. Data lze exportovat pro dalsi vyzkumne zpracovani.

## Role

### Veterinar

Bez klasickeho prihlaseni. Pristup ma pres jednorazovy QR odkaz navazany na konkretni sadu.

### Majitel

Bez hesla v prvni verzi. Pristup ma pres QR odkaz navazany na konkretni sadu. Pozdejsi editace dat nebo follow-up ma byt resen bezpecnym e-mailovym odkazem.

### Tym studie

Administrace je chranena prihlasenim pres heslo ulozene jako hash v `.env`. V prvni verzi maji clenove tymu spolecny administracni pristup; jemnejsi role pro laboranty, administrativu a vyzkumniky lze doplnit pozdeji.

## Bezpecnostni pravidla

- zadne heslo ani tajny klic se neukladaji do Gitu,
- `.env` je v `.gitignore`,
- vsechny databazove dotazy pouzivaji PDO prepared statements,
- formulare pouzivaji CSRF token,
- administrace vyzaduje prihlaseni,
- uploady rodokmenu nejsou verejne pristupne,
- veterinarni i majitelske odkazy jsou tokenizovane,
- veterinarni odkaz je po odeslani standardne nepouzitelny pro dalsi zaznam,
- produkcni provoz musi bezet pres HTTPS.

## MVP rozsah v kodu

- zakladni routing bez frameworku,
- databazove schema pro MariaDB,
- veterinarni formular,
- majitelsky formular,
- upload rodokmenu,
- verzovany souhlas,
- zakladni administrace,
- zmena stavu vzorku,
- CSV export,
- generator davky vzorku a QR URL,
- priprava PWA manifestu a service workeru.

## Dalsi faze

- uzivatelske ucty a role pro jednotlive cleny tymu,
- sprava veterinaru primo v administraci,
- obrazovka pro generovani stitku a QR kodu,
- evidence laboratornich vysledku a importu,
- schvalovane follow-up e-maily,
- bezpecne odkazy pro doplneni data a priciny umrti,
- prehled a kontrola rodokmenu vcetne nahledu souboru,
- audit log pro klicove zmeny,
- plnejsi PWA rezim.
