# Editing Rules

## Text encoding

Save template text in UTF-8. Do not mass-encode Cyrillic (or any non-ASCII text) into numeric character references (`&#1055;&#1088;…`) unless the API specifically rejects raw UTF-8 for that field.

## Don't invent — verify

The single most common failure mode: producing something that *looks* plausible by analogy instead of confirming it against the actual current state.

- **Variables** — never guess a `$VARIABLE$` name from another module/template. Confirm via `get_variables` or from the `read_template` output you already have.
- **Template code** — never write `code_search` from memory. Copy it byte-for-byte from a fresh `read_template` call.
- **Module/template IDs** — never assume a `module_id`/`template_id` pairing carries over from a different site or theme. Confirm with `list_modules` when unsure.

If a reference file states a fact (e.g. a `module_id`, a global block name) and the live `read_template`/`list_modules` output disagrees, **trust the live output**.

---

## Validation workflow

`validate_template` checks template code without saving it. Use it before committing non-trivial changes.

**When to run:**
- After any multi-line or structurally significant edit (new conditional blocks, global-block references, changes to `<html>`/`<head>`/`<body>` structure).
- Before `update_template` on an existing template.
- Optional for a one-line text change with no markup/logic impact.

**Flow:**
```
1. read_template
2. prepare the edit (or code_search + code_paste)
3. validate_template
4. if fails → fix → validate_template again → repeat
5. patch_template or update_template
```

Do not skip straight to `patch_template`/`update_template` and treat a save failure as your validation step.

---

## `patch_template` — partial edit (default)

Replaces a single unique fragment without rewriting the whole template.

| Parameter | Rule |
|---|---|
| `code_search` | **Byte-exact** match — spaces, tabs, line breaks matter. Copy verbatim from `read_template`; never reformat. Must match **exactly once** or the call fails. |
| `code_paste` | Replacement text. Empty string `""` **deletes** the fragment. Cannot remove `$POWERED_BY$`. |

**On `MATCH_NOT_FOUND` or `AMBIGUOUS_MATCH`:** do not guess or retry blindly. Re-read the template with `read_template` and copy the fragment again, or narrow `code_search` to a smaller unique substring.

## `update_template` — full replace (use sparingly)

Use only for:
- a brand-new template that doesn't exist yet, or
- a genuine full rewrite the user explicitly requests.

**Always confirm with the user** before calling `update_template` on an existing template.

---

## BOM trap

**Symptom:** a persistent blank strip/line at a block boundary that has no matching CSS rule and doesn't go away after styling changes — especially right at the seam of a block inserted via `patch_template`/`update_template`.

**Cause:** a leading UTF-8 BOM (`EF BB BF`, U+FEFF) picked up by the `code_paste` payload. Invisible in normal text rendering, but as a bare text node inside a flex container it still has the font's line-height and renders as a full-width empty bar.

**How to confirm:** do not trust browser console or `Invoke-WebRequest` text views — they normalize or hide a BOM. Fetch the raw response bytes and hex-dump the suspect region; look for `EF BB BF` immediately before the element.

**Fix:** re-save the affected template with `update_template` using a payload you are certain has no BOM (freshly composed content). A `patch_template` matching only the markup will not remove a BOM sitting just before the match boundary.

---

## Auto-backup

Both `patch_template` and `update_template` create a backup automatically before saving. The save is **blocked** if the 20-backup limit is reached.

---

## Backup management

| Parameter | Value |
|---|---|
| Max backups | 20 |
| Auto-backup on save | Yes — every save |
| Blocked at limit | Yes — until an old backup is deleted |

**When the limit is hit:**
1. The MCP returns a backup list.
2. **Confirm with the user** which backup to delete (deletion is irreversible).
3. `delete_backup(backup_id=…)`.
4. Retry the save — a new backup is created automatically.

**Operations:** `list_backups`, `create_backup`, `delete_backup` (confirm first), `restore_backup` (overwrites ALL current templates — confirm first; higher ID = more recent).

**Recommendations:**
- Never delete all backups; keep at least 2–3 recent ones.
- Before a large or risky edit, run `create_backup` manually as extra insurance.

**Large-scale campaigns (30–60+ saves):**
- The 20-backup cap will be hit repeatedly — budget for it.
- Delete oldest mid-session backups (not pre-session ones) when clearing space.
- Batch related rule changes into fewer, larger `patch_template` calls per component group rather than one call per property.
- It is acceptable to ask the user once for a standing "delete oldest as needed" policy rather than re-asking for every deletion.

---

## General editing algorithm

```
1. list_modules
   → find module_id / template_id for the target template

2. read_template(module_id, template_id)

3. Locate the target element:
   ├─ $GLOBAL_AHEADER$ → edit module_id=1, template_id=AHEADER
   ├─ $GLOBAL_BFOOTER$ → edit module_id=1, template_id=BFOOTER
   ├─ $GLOBAL_CLEFTER$ → edit module_id=1, template_id=CLEFTER
   └─ inline in this template → edit it directly

4. Styles? → CSS template (module 3/3) or module-specific CSS

5. Task-specific side flows:
   - Menu → use menu_* actions; output only via $NMENU_<ID>$ / $SMENU_<ID>$
   - Mail form → use mail_* actions; output via $MFORM_<ID>$;
     sync the mail template after field changes

6. validate_template (optional; recommended for non-trivial changes)

7. Save:
   - targeted change → patch_template(module_id, template_id, code_search, code_paste)
   - full rewrite / new template → update_template(module_id, template_id, content)
     (confirm with user if template already exists)

8. If fails → fix → validate_template → retry
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `MATCH_NOT_FOUND` from `patch_template` | `code_search` doesn't byte-match the live template | Re-run `read_template`; copy fragment verbatim |
| `AMBIGUOUS_MATCH` from `patch_template` | `code_search` matches more than once | Narrow `code_search` to a smaller, more unique fragment |
| `VALIDATION_ERROR` on `page_add`/`page_update`/`update_template` | `$POWERED_BY$` missing from `page_tmpl`/template content | Add `$POWERED_BY$` — mandatory in every full HTML document |
| Save blocked with backup-limit message | 20/20 backups exist | Confirm with user, `delete_backup`, then retry |
| Variable renders literally as `$SOME_VAR$` on the live page | Variable doesn't exist for that module/template | Re-check with `get_variables` |
| `module_uninstall` fails | Protected module (Users, SEO, etc.) | Not removable — inform the user, do not retry |
| `modules_tool` / `ftp_password_*` fails with a permissions error | API key lacks **Control Panel settings access** | User must reissue the key with that scope enabled |
| FTP action fails to connect | Wrong/stale FTP credentials | Confirm current credentials; `ftp_password_get` shows connection settings |
| Menu renders but links look broken / styling is off | A menu item has an empty URL | Check `menu_get`; ensure every item has a non-empty URL |
| Email is missing a field or references a deleted one | Mail template not synced after field changes | `mail_get_template` → update HTML → `mail_save_template` |
| `patch_template` on module 3/3 reports success but change is invisible | Checking the wrong URL (`/.s/src/css/2301.css` is a static asset, never reflects edits) | Fetch the actual `/_st/*.css` bundle from a live page's `<head>` and search it for your change |
