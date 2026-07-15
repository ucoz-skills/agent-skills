# Configure access to uCoz sites

This skill never configures credentials itself. The user sets them up in the client's own MCP/secret configuration, outside the chat. The examples below show the shape of that configuration for common clients.

## In-place translation (one site)

Requires a single existing `ucoz-mcp` connection. If the client already has it configured, nothing else is needed.

Example MCP client config entry (adjust to the actual client's config file, e.g. `claude_desktop_config.json` or a project `.mcp.json`):

```json
{
  "mcpServers": {
    "ucoz-mcp": {
      "command": "npx",
      "args": ["-y", "ucoz-mcp@latest"],
      "env": {
        "UCOZ_SITE_URL": "https://example-site.ucoz.net",
        "UCOZ_API_TOKEN": "***"
      }
    }
  }
}
```

The exact `command`/`args` for the official uCoz MCP server come from uCoz's own documentation (`https://api.ucoz.net/mcp.html`), not from this skill. Never paste a real token into chat; put it directly in the client's config or secret store.

## Cross-site migration (two sites)

Requires two separate, distinctly named connections so tool calls cannot be confused: one read-only (source), one read-write (target).

### Option A — two MCP connections

```json
{
  "mcpServers": {
    "ucoz-source": {
      "command": "npx",
      "args": ["-y", "ucoz-mcp@latest"],
      "env": {
        "UCOZ_SITE_URL": "https://source-site.ucoz.net",
        "UCOZ_API_TOKEN": "***source-token***"
      }
    },
    "ucoz-target": {
      "command": "npx",
      "args": ["-y", "ucoz-mcp@latest"],
      "env": {
        "UCOZ_SITE_URL": "https://target-site.ucoz.net",
        "UCOZ_API_TOKEN": "***target-token***",
        "UCOZ_FTP_HOST": "ftp.target-site.ucoz.net",
        "UCOZ_FTP_USER": "***",
        "UCOZ_FTP_PASS": "***"
      }
    }
  }
}
```

FTP variables are only needed when template/global-block static assets must move; omit them otherwise.

### Option B — direct uAPI via environment variables

Use this only when the client cannot run a second MCP server instance and a direct uAPI call is unavoidable (see [UAPI.md](UAPI.md)):

```
UCOZ_SITE_URL=https://target-site.ucoz.net
UCOZ_API_TOKEN=***target-token***
UCOZ_FTP_HOST=ftp.target-site.ucoz.net   # optional
UCOZ_FTP_USER=***                         # optional
UCOZ_FTP_PASS=***                         # optional
```

Set these in the client's environment/secret configuration (shell profile, `.env` loaded outside the conversation, or the client's secret manager) — never inline in a chat message.

## What each token needs

- `UCOZ_API_TOKEN`: the site's uAPI bearer token, scoped to the modules being translated/migrated (materials, categories, pages, templates, menus as applicable).
- `UCOZ_FTP_*`: only for moving static template/global-block assets that live outside uAPI-managed storage.

## Verifying the setup

Ask the assistant to run a read-only discovery call (e.g. list modules or read one material) against each named connection before any write. A successful, correctly-scoped read confirms the token and site URL are wired to the right connection name.
