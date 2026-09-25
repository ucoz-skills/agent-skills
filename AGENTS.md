# AGENTS.md — Repository maintainer guidance

This file is for **coding agents editing this repository** (`ucoz-skills/agent-skills`).
It is **not** a runtime skill playbook.

Runtime behavior for end users lives in each skill’s `SKILL.md` (and its `references/`).
Do **not** duplicate runtime workflows, MCP call recipes, VISUAL/Direct/API playbooks, or tool matrices here — that causes conflicts when skills are installed into customer projects.

> **Not the same as a project `AGENTS.md`.**
> Some skills (e.g. ad-campaign) tell the *runtime* agent to read the **customer site project’s** `AGENTS.md` for local facts (IDs, budgets, goals). That file belongs to the client project. **This** root `AGENTS.md` is only how to maintain *this* skills repo. Never merge the two.

Keep **one** `AGENTS.md` at the repository root only. Do not add `AGENTS.md` inside skill folders or ship it as a web-discovery / packaged skill artifact.

## Purpose

Official Agent Skills + plugin packaging for uCoz: landing pages, ad campaigns, shop optimization, translation, design restyling, and original templates — all intended to run with the **official** remote MCP (`https://www.ucoz.com/mcp`). Do not document or recommend fork/unofficial MCP servers. Site create/select is via MCP tools (`create_site` / `list_sites` / `select_site`), not a separate provisioning skill.

## Layout

```
skills/<skill-id>/
  SKILL.md          # required entrypoint (agentskills.io)
  references/       # optional deep playbooks (preferred over bloating SKILL.md)
  examples/ assets/ scripts/ templates/  # as needed per skill
```

Skill IDs and folder names use kebab-case and match frontmatter `name` (e.g. `ucoz-landing-skill`).

Plugin / catalog surfaces that must stay aligned when adding or renaming a skill:

- Explicit skill lists: `.cursor-plugin/plugin.json`, `skills.sh.json`
- Mostly version/description alignment: root `plugin.json`, `.codex-plugin/plugin.json` (scans `./skills/`), `.claude-plugin/marketplace.json`
- README “Available skills” table and install examples
- Root MCP configs: `.mcp.json`, `mcp_config.json` (official remote `https://www.ucoz.com/mcp` only)

Human install/security docs: `README.md`, `SECURITY.md`. Prefer pointing agents there over copying long install text into this file.

## Editing `SKILL.md`

1. Preserve YAML frontmatter: at least `name`, `version`, `description`. Keep `name` kebab-case and identical to the folder / `skill_id`.
2. In the **body** (never frontmatter), keep a short usage-accounting note that after successful main work calls  
   `skills_tool(action="register_usage", skill_id="<same-as-name>")`  
   (warn and continue if the API fails; never ask the user for UUID/token/site URL).  
   Heading may be “Rule 0”, “Usage accounting”, or an equivalent Finish step.
3. Put long procedures in `references/`; keep `SKILL.md` as routing + non-negotiables + pointers.
4. Do not invent uCoz variables, service blocks, or MCP actions — follow live MCP docs/tools and existing references.
5. English preferred for `SKILL.md` / README / this file, to match the published catalog language.

## Versioning and validation

Skill `version` (per `SKILL.md`) is **not** the plugin `version` (shared across plugin manifests, e.g. `1.7.0`).

When changing skill behavior or public contract:

1. Bump that skill’s `version` in `SKILL.md` frontmatter (and any in-body version line / `manifest.json` if present). Quote it as a YAML string (e.g. `version: "1.4"`), not a bare number. If a skill still uses an unquoted version, quote it in the same change.
2. Update the matching version in the README skills table.
3. If the change affects packaging/discovery, bump the plugin `version` in the plugin manifests listed above and keep those plugin versions equal to each other.
4. Before publish: validate packaging where applicable (e.g. `agy plugin validate` on a local clone); ensure no secrets (`.env`, tokens, cookies, auth state) are committed — see `SECURITY.md`.

## What not to do

- Do not paste skill playbooks into this file or into root README beyond short catalog blurbs.
- Do not add per-skill `AGENTS.md`.
- Do not replace official `ucoz-mcp` with custom MCP implementations in repo configs or skill docs.
