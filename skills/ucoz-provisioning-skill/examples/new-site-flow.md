# Example: new site scenario

User: Create a new uCoz site and connect it to my agent.

Agent:
1. Clarifies whether the user wants a specific site address (or full `prefix.zone` string).
2. Requests uCoz email/password (does not repeat secrets in the response).
3. Runs `browser-runner/ucoz-provision.cjs` (or `npm run provision`) with environment variables (see `references/browser-runner.md`).
4. Takes `UCOZ_SITE_URL` and configures modules, uAPI, FTP as needed (continue per `SKILL.md`).

Example masked summary:

```text
Done: uCoz site prepared for MCP.
Site: https://example.ucoz.net/
Scenario: new
Site configuration: <selected card>
Enabled modules: <modules>
uAPI: created, sk_live_***abcd
FTP: configured, user=<ftp user>, host=example.ucoz.net
MCP config: assembled
Verification: public URL and MCP read passed
```
