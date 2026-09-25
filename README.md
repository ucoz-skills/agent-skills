<p align="center">
  <img src="./assets/ucoz-banner.webp" alt="uCoz Agent Skills" width="100%">
</p>

# uCoz Agent Skills

Official [Agent Skills](https://agentskills.io) for AI agents working with uCoz: landing pages, ad campaigns, shop optimization, site translation, design restyling, and original site templates.

Each skill ships with the [uCoz remote MCP](https://www.ucoz.com/mcp) — install the plugin once and get both instructions and tools. Create or select a site via MCP (`create_site` / `list_sites` / `select_site`).

> **Security:** Credentials are used only with the official uCoz platform.
> See [SECURITY.md](SECURITY.md) for the full security policy.

Website catalog: [ucoz.com/ai/skills](https://www.ucoz.com/ai/skills)  
MCP documentation: [api.ucoz.net/mcp](https://api.ucoz.net/en/mcp.html) · Public MCP repo: [ucoz-skills/ucoz-mcp](https://github.com/ucoz-skills/ucoz-mcp)

## Available skills

| Skill | Description | Version |
|-------|-------------|---------|
| [ucoz-landing-skill](skills/ucoz-landing-skill/SKILL.md) | Generate and edit homepage landings via MCP (visual design system + playbook) | 1.4 |
| [ucoz-ad-campaign-landing-skill](skills/ucoz-ad-campaign-landing-skill/SKILL.md) | Hyper-segmentation experiment matrix → per-segment landings + UTM, Yandex Direct drafts/packages, Metrica goals (Google Ads on request) | 2.0 |
| [ucoz-shop-optimizer-skill](skills/ucoz-shop-optimizer-skill/SKILL.md) | Audit and improve uCoz Online Shop via MCP and uAPI | 0.6 |
| [ucoz-site-translator-skill](skills/ucoz-site-translator-skill/SKILL.md) | Translate and localize uCoz content in place or copy/migrate to another site via MCP and uAPI | 1.1 |
| [ucoz-design-editor-skill](skills/ucoz-design-editor-skill/SKILL.md) | Redesign and restyle a uCoz site via MCP: CSS, chrome, global blocks, menus, and module templates | 1.2 |
| [ucoz-template-creator-skill](skills/ucoz-template-creator-skill/SKILL.md) | Create an original site-wide uCoz template via MCP: framework, CSS, modules, informers, rating, and custom Pages | 1.2 |

## Installation

### Cursor (skills + MCP)

Install from GitHub — skills and MCP are configured together via [`.cursor-plugin/plugin.json`](.cursor-plugin/plugin.json):

**Settings → Rules → New Rule → Add from Github** → `https://github.com/ucoz-skills/agent-skills.git`

Or submit the repo at [cursor.directory/plugins/new](https://cursor.directory/plugins/new).

After install, open **Settings → Tools & MCP** and authorize the bundled remote `ucoz-mcp` (see [MCP setup](#mcp-setup)).

### Codex (skills + MCP)

**Codex App** — install the uCoz plugin from the plugin catalog.

**Codex CLI** — run `/plugins`, select **uCoz**, and choose **Install Plugin**.

Skills load from `skills/`; MCP config is read from [`.mcp.json`](.mcp.json) via [`.codex-plugin/plugin.json`](.codex-plugin/plugin.json). Authorize remote MCP in Codex settings (see [MCP setup](#mcp-setup)).

### Antigravity (IDE + CLI)

[Google Antigravity](https://antigravity.google/docs/skills) discovers skills from `skills/` and supports bundled MCP via native plugins ([`plugin.json`](plugin.json) + [`mcp_config.json`](mcp_config.json)).

**Antigravity CLI — plugin install (recommended, skills + MCP):**

```bash
agy plugin install https://github.com/ucoz-skills/agent-skills.git
```

Installs globally under `~/.gemini/config/plugins/ucoz/`. Verify with `agy plugin list` and browse skills with `/skills` in the CLI.

**Antigravity IDE — Skills CLI:**

```bash
npx skills add ucoz-skills/agent-skills
```

Installs skill folders to `~/.agents/skills/` (discovered by Antigravity IDE). For skills shared across all Antigravity tools, copy them to `~/.gemini/skills/` instead — see [Antigravity skills docs](https://antigravity.google/docs/skills).

**Workspace-only install** — copy skill folders into `.agents/skills/` at your project root.

After install, authorize the bundled remote MCP (see [MCP setup](#mcp-setup)). When using the plugin path, MCP is bundled from [`mcp_config.json`](mcp_config.json); otherwise merge that file into `~/.gemini/config/mcp_config.json`.

Validate a local clone before installing:

```bash
agy plugin validate /path/to/agent-skills
```

### Hermes (Skills Hub)

[Hermes Agent](https://hermes-agent.nousresearch.com/docs/user-guide/features/skills) discovers skills from GitHub taps and the [skills.sh](https://skills.sh) index.

**Subscribe to the repo (custom tap):**

```bash
hermes skills tap add ucoz-skills/agent-skills
```

**Install skills:**

```bash
hermes skills install ucoz-skills/agent-skills/skills/ucoz-landing-skill
hermes skills install ucoz-skills/agent-skills/skills/ucoz-ad-campaign-landing-skill
hermes skills install ucoz-skills/agent-skills/skills/ucoz-shop-optimizer-skill
hermes skills install ucoz-skills/agent-skills/skills/ucoz-site-translator-skill
hermes skills install ucoz-skills/agent-skills/skills/ucoz-design-editor-skill
hermes skills install ucoz-skills/agent-skills/skills/ucoz-template-creator-skill
```

**Skills CLI** (also registers the repo with skills.sh telemetry for catalog indexing):

```bash
npx skills add ucoz-skills/agent-skills
```

After the first install, skills appear on [skills.sh/ucoz-skills/agent-skills](https://skills.sh/ucoz-skills/agent-skills) and in the Hermes Skills Hub index (refreshed periodically). Updates: push to GitHub; users run `hermes skills check` and `hermes skills update`.

> Hermes uses the GitHub API to browse taps. Set `GITHUB_TOKEN` in `~/.hermes/.env` (or run `gh auth login`) if you hit API rate limits.

### Claude Code (marketplace)

```bash
/plugin marketplace add ucoz-skills/agent-skills
/plugin install ucoz@ucoz-skills
```

Skills load from `skills/`; MCP config is read from [`.mcp.json`](.mcp.json) at the plugin root.

### Skills CLI (skills only)

```bash
npx skills add ucoz-skills/agent-skills
```

Install a single skill:

```bash
npx skills add ucoz-skills/agent-skills --skill ucoz-landing-skill
npx skills add ucoz-skills/agent-skills --skill ucoz-ad-campaign-landing-skill
npx skills add ucoz-skills/agent-skills --skill ucoz-shop-optimizer-skill
npx skills add ucoz-skills/agent-skills --skill ucoz-site-translator-skill
npx skills add ucoz-skills/agent-skills --skill ucoz-design-editor-skill
npx skills add ucoz-skills/agent-skills --skill ucoz-template-creator-skill
```

> Skills CLI installs instructions only. Add [`.mcp.json`](.mcp.json) to your project or IDE separately for MCP tools.

### Manual install

Copy a skill folder into your agent skills directory:

- `skills/ucoz-landing-skill`
- `skills/ucoz-ad-campaign-landing-skill`
- `skills/ucoz-shop-optimizer-skill`
- `skills/ucoz-site-translator-skill`
- `skills/ucoz-design-editor-skill`
- `skills/ucoz-template-creator-skill`

| Tool | Skills path |
|------|-------------|
| Cursor | `.cursor/skills/` or `.agents/skills/` |
| Claude Code | `.claude/skills/` |
| Antigravity | `.agents/skills/` (workspace) or `~/.gemini/skills/` (global) |

## MCP setup

The repo includes [`.mcp.json`](.mcp.json) — bundled automatically when you install the **Cursor**, **Codex**, **Claude Code**, or **Antigravity** plugin.

Remote MCP (Control Panel auth — no API key in client config):

```
https://www.ucoz.com/mcp
```

Example MCP config (also in [`.mcp.json`](.mcp.json) and [`mcp_config.json`](mcp_config.json)):

```json
{
  "mcpServers": {
    "ucoz-mcp": {
      "url": "https://www.ucoz.com/mcp"
    }
  }
}
```

After connecting, authorize in the Control Panel, then `list_sites` → `select_site` (or `create_site`). More connect docs: [ucoz-skills/ucoz-mcp](https://github.com/ucoz-skills/ucoz-mcp). Official Registry: `io.github.ucoz-skills/ucoz-mcp`.

### MCP tools

| Tool | Purpose |
|------|---------|
| `list_sites` / `select_site` / `create_site` | Account: list, select, or create a site |
| `templates_tool` | Templates, menus, mail forms, pages, backups |
| `tmaker_tool` | Whole-site design skeleton |
| `content_tool` | Content modules |
| `shop_tool` | Online shop |
| `files_tool` | Site file manager |
| `ftp_tool` | FTP password management only |
| `modules_tool` | Modules, uAPI, quarantine |
| `skills_tool` | Official agent skills |

## License

MIT — see [LICENSE](LICENSE).
