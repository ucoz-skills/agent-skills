# QUICK_START — uCoz Site Provision Helper for Cursor / Codex / Claude v1.1

## Install

Unpack this directory into your agent workspace, then install browser runner dependencies:

```bash
cd browser-runner
npm install
npm run install-browser
node --check ucoz-provision.cjs
```

## Attach instructions

Point the agent to `SKILL.md` and use the MCP template matching your client from `templates/`.

## FTP helper fix in this version

The runner now extracts FTP connection details from File Manager helper before `ftppass`: `/panel/?a=fm` plus `uCoz.utils.helper(1)`. It parses `FTP host`, `FTP login`, and FTP password status. Then it opens `/panel/?a=ftppass` only for setting/changing the FTP password and handles an extra panel password gate if shown.

Do not print raw uAPI tokens, FTP passwords, account passwords, cookies, or `.env` values.
