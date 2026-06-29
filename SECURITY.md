# Security Policy

## Scope

This repository contains Agent Skills and a browser-automation runner for the
official uCoz platform (ucoz.ru / ucoz.com ).

## How credentials are handled

- `UCOZ_EMAIL` and `UCOZ_PASSWORD` are used **only** to authenticate with the
  official uCoz control panel. The runner validates `UCOZ_ORIGIN` against an
  explicit allowlist before any credential is used.
- All secrets in stdout are redacted by default (`OUTPUT_SECRETS=false`).
- `provision-new-site.mjs` passes only explicitly allowed environment variables
  to the child process — never the full `process.env`.
- No credentials are sent to third-party services.

## Runtime dependencies

The browser runner installs `playwright` and `dotenv` as npm dependencies
at setup time (one-time, local only). This is intentional and documented.
