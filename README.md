# CMR Invultool (PHP)

Eenvoudige lokale CMR-webapp:
- 24 velden invullen
- velden 6 t/m 12 met meerdere regels
- admin voor bedrijven (afzenders/ontvangers)
- zoeken/kiezen van bestaande bedrijven voor veld 1 en 2
- opslaan in SQLite of MySQL/MariaDB
- ingevulde PDF genereren op basis van `pdf/CMR.pdf` (alle 4 pagina's)

## 1) Installatie lokaal

Vereisten:
- PHP 8.1+

In deze map:

```bash
php -S localhost:8080 -t public
```

Open daarna:
- [http://localhost:8080](http://localhost:8080)
- Setup: [http://localhost:8080/setup.php](http://localhost:8080/setup.php)
- Archief: [http://localhost:8080/history.php](http://localhost:8080/history.php)
- Admin bedrijven: [http://localhost:8080/admin.php](http://localhost:8080/admin.php)

## 1b) Docker / Dockge (aanbevolen)

Deze repository bevat een complete Docker setup met versie `1.0.0`.

Starten met Docker Compose:

```bash
docker compose up -d --build
```

Open daarna:
- [http://localhost:8080/index.php](http://localhost:8080/index.php)

Belangrijke bestanden:
- `docker-compose.yml`
- `Dockerfile`
- `.dockerignore`
- `VERSION`

Voor Dockge:
1. Voeg een nieuwe stack toe met de inhoud van `docker-compose.yml`.
2. Deploy de stack.
3. Zorg dat poort `8080` vrij is of pas de host-poort aan.

Persistente data volumes:
- `cmr_data` voor databasebestanden in `data/`
- `cmr_generated` voor gegenereerde PDF's in `storage/generated/`

## 2) Gebruik

1. Voeg in `Admin` eerst je eigen bedrijven toe als `Afzender`.
2. Voeg klanten toe als `Ontvanger`.
3. In het CMR scherm kies je voor veld 1 en 2 een bedrijf of vul je handmatig in.
4. Vul de overige velden in.
5. Voeg bij 6 t/m 12 extra regels toe met `Regel toevoegen`.
6. Klik `Opslaan en PDF maken`.

Gegenereerde PDFs staan in:
- `storage/generated/`

Database:
- via setup wizard (`/setup.php`) kies je SQLite of MySQL/MariaDB

## 3) Op webserver zetten

Open daarna op de server:
- `https://jouwdomein/setup.php`

Vul databasegegevens in en klik `Installeren / Bijwerken`.

Zorg dat deze mappen schrijfbaar zijn door de webserver (voor SQLite en PDF-output):
- `data/`
- `storage/generated/`

Document root moet `public/` zijn.

## 4) Opmerking over exacte uitlijning

De velden zijn gepositioneerd op basis van jullie aangeleverde `CMR.pdf` template.
Als je een andere CMR-template gebruikt, kunnen kleine correcties in `src/PdfRenderer.php` nodig zijn.
