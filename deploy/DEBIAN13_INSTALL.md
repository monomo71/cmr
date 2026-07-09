# Debian 13 installatiehandleiding

Dit project draait als een Docker Compose stack met een PHP/Apache container.

## Wat moet er op de server draaien

- Debian 13
- Docker Engine
- Docker Compose plugin

Niet nodig:

- PHP op de host
- Composer op de host
- Node.js op de host
- MySQL of PostgreSQL op de host

De database staat in de container en wordt via volumes bewaard. CMR gebruikt daarnaast een PDF-template in de container en schrijft gegenereerde bestanden naar een volume.

## Server-eisen

- Debian 13
- 1 vCPU minimum, 2 vCPU of meer is praktischer
- 2 GB RAM minimum, 4 GB is comfortabeler
- 10 GB schijfruimte minimum
- netwerktoegang om Docker packages te installeren
- poort `8489` moet bereikbaar zijn als je de app direct op de VM wilt openen

## Installatie in het kort

1. Installeer of gebruik een schone Debian 13 VM.
2. Zet de CMR-code op de server.
3. Draai het Docker setup-script.
4. Open de applicatie op poort `8489`.
5. Doorloop eventueel de browser setup op `setup.php`.

## Stap voor stap

### 1. Installeer basispakketten

De scriptinstalleert Docker zelf. Handmatig hoeft alleen de code en sudo-toegang beschikbaar te zijn.

### 2. Zet de code op de server

Kloon de repository bijvoorbeeld naar `/opt/cmr`.

```bash
sudo mkdir -p /opt/cmr
sudo chown "$USER":"$USER" /opt/cmr
git clone <repo-url> /opt/cmr
cd /opt/cmr
```

### 3. Draai het setup-script

```bash
sudo APP_DIR=/opt/cmr bash deploy/debian13-setup.sh
```

Wat het script doet:

- installeert `docker.io` en `docker-compose-plugin`
- start Docker
- bouwt de CMR container
- start de stack op poort `8489`

### 4. Open de applicatie

Gebruik het IP-adres of domein van de VM:

- [http://server-ip:8489](http://server-ip:8489)
- setup wizard: [http://server-ip:8489/setup.php](http://server-ip:8489/setup.php)

### 5. Doorloop de setup wizard

Open `setup.php` om de database te kiezen en de basisconfiguratie af te ronden.

## Controle en beheer

Status van de stack:

```bash
cd /opt/cmr
docker compose ps
```

Logs bekijken:

```bash
cd /opt/cmr
docker compose logs -f
```

Opnieuw bouwen en starten:

```bash
cd /opt/cmr
docker compose up -d --build
```

## Back-up

Maak back-ups van:

- de Docker volumes `cmr_data` en `cmr_generated`
- eventuele ingevoerde configuratie of exports

## TLS

Voor productie is HTTPS aan te raden. Dat kan met een reverse proxy of met een load balancer / webserver voor de VM.
