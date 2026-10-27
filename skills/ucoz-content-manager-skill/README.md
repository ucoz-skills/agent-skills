# uCoz Content Manager Skill

Version: 1.2

An agent skill for managing the content of a uCoz site through the official uCoz MCP: materials of every content module, categories and access rights, SEO fields and own URLs, covers and gallery images (with bulk uploads and image generation), site pages and menus, module installation and settings, and the template edits that make SEO data render.

## What it does

- Publishes and edits materials in news, blog, articles, files, board, sites catalog, FAQ, photo, video, guestbook, polls — in the right format, with meta fields, tags, covers and images.
- Builds category structures that guests can actually see (copies access rights; a bare `category_add` is admin-only).
- Runs SEO end to end: SEO module and URL settings on, meta fields filled, `$SEO_TITLE$` / meta description patched into templates, custom URLs only via `ownurl`, with HTTP checks.
- Generates covers and illustrations (or prepares prompts when no generator is available) and uploads them in bulk: parallel `upload_direct`, then one bind call per material.
- Installs modules, handles their demo content, reads and changes module settings with dry runs.
- Creates site pages and edits menus without losing items.

## Verified platform behaviour it relies on

| Behaviour | Consequence in the skill |
|---|---|
| `category_add` without access → `access2category: [4]` | Access arrays are always passed and checked as a guest |
| System slugs (`hgu_title`) are automatic | Never sent; custom URLs only via `ownurl` |
| Stock templates print `$TITLE_BAR$`, no meta description | Template SEO patch with fallback |
| `image_upload_ids` overwrites from slot 1 | Explicit append procedure |
| Empty values are not sent | Fields cannot be cleared via MCP; `ownurl` values agreed before writing |
| ASCII quotes in titles → `&quot;` (and `_quot_` in the auto URL) | «» in titles |
| `module_install` creates demo content and enables uAPI | Demo content is reviewed with the user |
| MCP writes don't enforce `images.max_count` | The agent respects the module limit itself |
| Cover and material images are separate; slot images show only via `$IMAGEn$` / `$IMG_URLn$` in the text | One `add` binds cover + images and references them in the text; every `<img>` gets alt and `class="ulightbox"` |
| `$IMG_URLn$` resolves at save time, `$IMAGEn$` stays live | After replacing images, the text is re-sent with `$IMG_URLn$` |
| News/blog category pages are rendered by the archive template | Rendering template found by fingerprint before patching |
| `fields` setting: shift on blog, `fname` stuck on in news, forced `required` in gb, false "not found" reply in stuff (others correct) | Snapshot → write → re-read → compensate; warn first for news/gb |
| CSS template exceeds the 200 000-char limit | New elements styled inline |
| With `options_show_adddate: true`, `update` resets the publication date; `add_date` is ignored on update | Date sent as `fields {ya, ma, da, ha, mia}` on every update; `uplastmod: 1` only for real content changes |
| Quarantine / robots can block indexing site-wide | Checked and reported in every SEO task |

## Files

- `SKILL.md` — entry point: tool map, non-negotiables, workflow, output format.
- `references/TOOLS_REFERENCE.md` — actions and parameters of the content, files, settings, modules and template tools.
- `references/MODULES.md` — per-module fields and quirks.
- `references/CATEGORIES_AND_ACCESS.md` — categories, access groups, filters, tags.
- `references/FORMATS.md` — HTML / BBCode / plain.
- `references/SEO_AND_URLS.md` — SEO module, meta, `ownurl`, verification.
- `references/MEDIA_WORKFLOW.md` — covers, galleries, category images, bulk uploads.
- `references/IMAGE_GENERATION_GUIDE.md` — generating images and alt texts.
- `references/TEMPLATES.md` — SEO head patch, H1, Open Graph, JSON-LD, backups.
- `references/SETTINGS_AND_MODULES.md` — module install, settings keys, dangerous keys.
- `references/PAGES_AND_MENUS.md` — site pages and menus.
- `references/COPY_GUIDE.md` — general writing rules (facts, lengths, meta, URL, tags, alt).
- `references/styles/` — one file per material type: news, guide, article, review, listicle, announcement, story, faq, category, board-ad, page, file; `README.md` = style picker, writing pipeline, voice presets, HTML components.
- `references/HUMANIZER.md` — removing AI tells from Russian (and English) site texts without touching facts, HTML or image codes; synthesized from MIT-licensed blader/humanizer, ilyautov/humanizer-ru, smixs/humanizer-ru, Vladimir-Human/humanizer-ru; editing order inspired by unicodef1wn/russian-writing-skills (editor-ru).
- `references/PLAYBOOK.md` — 14 scenarios.
- `references/CHECKLIST.md` — audit checklist.
- `examples/PROMPTS.md` — example requests.

## Not covered

Online shop (`ucoz-shop-optimizer-skill`), forum (`forum_*` tools), full redesign (`ucoz-design-editor-skill`), landings (`ucoz-landing-skill`), translation (`ucoz-site-translator-skill`), server-side code (`ucoz-server-scripts-skill`).
