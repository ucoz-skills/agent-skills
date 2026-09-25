# Configure access to uCoz sites

Connect the official remote MCP, then select sites with MCP tools. Do not put API tokens in chat.

## Connect remote MCP

```json
{
  "mcpServers": {
    "ucoz-mcp": {
      "url": "https://www.ucoz.com/mcp"
    }
  }
}
```

Authorize through the uCoz Control Panel when prompted. Docs: https://github.com/ucoz-skills/ucoz-mcp

## In-place translation (one site)

1. Ensure `ucoz-mcp` is connected and authorized.
2. `list_sites` → `select_site` for the site to translate.
3. Run discovery (read-only) before writes.

## Cross-site migration (two sites)

One remote MCP connection serves the account. Switch the active site explicitly:

1. `list_sites` — note source and target `site_id` values.
2. Before every **read** from the source: `select_site` → source id.
3. Before every **write** to the target: `select_site` → target id.
4. Say aloud which site is selected before destructive writes.

If the client cannot keep a reliable select_site workflow, stop and ask the user to confirm source/target ids before continuing.

Static assets: use `files_tool` on the selected site (not local FTP env vars). FTP password management only: `ftp_tool`.

## Verifying the setup

Run a read-only call (e.g. `modules_tool` → `active_mods` or list materials) after each `select_site` before writes.
