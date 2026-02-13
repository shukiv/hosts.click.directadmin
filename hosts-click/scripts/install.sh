#!/usr/bin/env bash
set -euo pipefail

SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
DIR="$(cd "$(dirname "${SCRIPT}")/.." && pwd)"
bash "${DIR}/install.sh"
