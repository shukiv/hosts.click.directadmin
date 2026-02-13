#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "This installer must be run as root." >&2
  exit 1
fi

PLUGIN_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DA_PLUGIN_DIR="/usr/local/directadmin/plugins/hosts-click"
CONFIG_FILE="${DA_PLUGIN_DIR}/config.json"
CUSTOM_DIR="/usr/local/directadmin/data/skins/evolution/custom"
FOOTER_FILE="${CUSTOM_DIR}/footer.html"

SRC_DIR="${PLUGIN_ROOT}"
if [ -d "${PLUGIN_ROOT}/hosts-click" ]; then
  SRC_DIR="${PLUGIN_ROOT}/hosts-click"
fi

mkdir -p "${DA_PLUGIN_DIR}"
TMP_CONFIG=""
if [ -f "${CONFIG_FILE}" ]; then
  TMP_CONFIG="$(mktemp)"
  cp "${CONFIG_FILE}" "${TMP_CONFIG}"
fi

rm -rf "${DA_PLUGIN_DIR:?}/"*
cp -R "${SRC_DIR}/"* "${DA_PLUGIN_DIR}/"
chmod -R 0755 "${DA_PLUGIN_DIR}"

if [ ! -f "${CONFIG_FILE}" ]; then
  cat <<'JSON' > "${CONFIG_FILE}"
{
  "api_base_url": "https://hostsclick.com",
  "api_key": "",
  "license_status": "guest",
  "last_checked": null
}
JSON
fi

if [ -n "${TMP_CONFIG}" ] && [ -f "${TMP_CONFIG}" ]; then
  mv "${TMP_CONFIG}" "${CONFIG_FILE}"
fi

if id diradmin >/dev/null 2>&1; then
  chown -R diradmin:diradmin "${DA_PLUGIN_DIR}"
  chown diradmin:diradmin "${CONFIG_FILE}"
  chmod 0660 "${CONFIG_FILE}"
else
  chmod 0640 "${CONFIG_FILE}"
fi

mkdir -p "${CUSTOM_DIR}"
if [ ! -f "${FOOTER_FILE}" ]; then
  touch "${FOOTER_FILE}"
fi

if ! grep -q "hosts-click-start" "${FOOTER_FILE}"; then
  cat <<'HTML' >> "${FOOTER_FILE}"
<!-- hosts-click-start -->
<script src="/CMD_PLUGINS/hosts-click/assets/domains-inject.js" defer></script>
<!-- hosts-click-end -->
HTML
fi

echo "Hosts.click DirectAdmin plugin installed."
