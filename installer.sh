#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "This installer must be run as root." >&2
  exit 1
fi

PLUGIN_URL_DEFAULT="https://hostsclick.com/downloads/hosts-click-directadmin-plugin.tar.gz"
PLUGIN_URL="${HC_DA_PLUGIN_URL:-$PLUGIN_URL_DEFAULT}"
API_BASE_URL="${HC_API_BASE_URL:-}"

TMP_DIR="$(mktemp -d)"
cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

echo "Downloading Hosts.click DirectAdmin plugin..."
if ! curl -fsSL "$PLUGIN_URL" -o "$TMP_DIR/plugin.tar.gz"; then
  echo "Download failed: $PLUGIN_URL" >&2
  exit 1
fi

tar -xzf "$TMP_DIR/plugin.tar.gz" -C "$TMP_DIR"

if [ -f "$TMP_DIR/directadmin-plugin/install.sh" ]; then
  bash "$TMP_DIR/directadmin-plugin/install.sh"
elif [ -f "$TMP_DIR/install.sh" ]; then
  bash "$TMP_DIR/install.sh"
elif [ -f "$TMP_DIR/hosts-click/install.sh" ]; then
  bash "$TMP_DIR/hosts-click/install.sh"
else
  echo "Installer not found inside archive." >&2
  exit 1
fi

if [ -n "$API_BASE_URL" ] && [ -f /usr/local/directadmin/plugins/hosts-click/config.json ]; then
  sed -i "s#\"api_base_url\": \".*\"#\"api_base_url\": \"${API_BASE_URL}\"#" /usr/local/directadmin/plugins/hosts-click/config.json
fi

echo "Done. Add the API key to /usr/local/directadmin/plugins/hosts-click/config.json or set it before install."
