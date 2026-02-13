#!/usr/bin/env bash
set -euo pipefail

DA_PLUGIN_DIR="/usr/local/directadmin/plugins/hosts-click"
CUSTOM_DIR="/usr/local/directadmin/data/skins/evolution/custom"
FOOTER_FILE="${CUSTOM_DIR}/footer.html"

rm -rf "${DA_PLUGIN_DIR}"

if [ -f "${FOOTER_FILE}" ]; then
  sed -i '/hosts-click-start/,/hosts-click-end/d' "${FOOTER_FILE}"
fi

echo "Hosts.click DirectAdmin plugin removed."
