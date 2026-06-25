#!/usr/bin/env bash
# Fill in values locally; do not commit real secrets.
export UCOZ_API_TOKEN="${UCOZ_API_TOKEN}"
export UCOZ_SITE_URL="${UCOZ_SITE_URL}"
export UCOZ_FTP_HOST="${UCOZ_FTP_HOST}"
export UCOZ_FTP_USER="${UCOZ_FTP_USER}"
export UCOZ_FTP_PASS="${UCOZ_FTP_PASS}"

npx -y ucoz-mcp@latest
