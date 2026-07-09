#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"

usage() {
  cat <<'EOF'
Usage:
  sudo APP_DIR=/opt/cmr bash deploy/debian13-setup.sh

This script installs Docker and Docker Compose on Debian 13 and starts the CMR stack.
The application itself is exposed on port 8489 by docker-compose.yml.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

if [[ $EUID -ne 0 ]]; then
  echo "Run this script as root with sudo."
  exit 1
fi

if [[ ! -f "$APP_DIR/docker-compose.yml" || ! -f "$APP_DIR/Dockerfile" ]]; then
  echo "APP_DIR does not look like the CMR project root: $APP_DIR"
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y ca-certificates curl git docker.io docker-compose-plugin

systemctl enable --now docker

cd "$APP_DIR"
docker compose up -d --build --remove-orphans

echo
echo "CMR installation completed."
echo "Open: http://<server-ip>:8489"
echo "Browser setup: http://<server-ip>:8489/setup.php"
echo "Status: docker compose ps"
echo "Logs: docker compose logs -f"
