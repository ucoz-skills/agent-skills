# Text formats — HTML, BBCode, plain

## Controls

| Text | Parameter | Format control | Reply flags |
|---|---|---|---|
| Lead / brief / FAQ question | `description` | `description_type` | `includehtml_desc`, `end2br_desc`, `original_description` |
| Full text / FAQ answer | `message` / `answer` | `message_type` | `includehtml_msg`, `end2br_msg`, `original_message` |

Values: `html` keeps markup, `bb` converts BBCode to HTML, `plain` escapes markup.

- `news` and `load` always keep HTML.
- `blog`, `publ`, `board`, `dir`, `stuff`, `faq` keep HTML only when the acting user's group may use HTML in the module; otherwise markup is escaped. The MCP user is normally the site owner (administrator), so HTML works — still verify.
- Without a `*_type`, a non-empty text is saved as html. Send it anyway: explicit beats default.

## Default choice

Write new content as **HTML** (`description_type: html`, `message_type: html`). Edit existing content in **its own format**: detect it from the reply flags and `original_*` (real tags → html; `[b]`, `[url=…]` → bb; neither → plain) and send it back the same way. Never convert a BBCode site to HTML as a side effect.

## Allowed HTML

Semantic and light: `h2`, `h3`, `p`, `ul/ol/li`, `strong`, `em`, `a`, `blockquote`, `figure/img/figcaption`, `table/thead/tbody/tr/th/td`, `br` (rarely), `code/pre`.

Avoid: `h1` (the template owns the page heading), inline `style` and `class` unless the site already uses them (allowed: `class="ulightbox"` on images and the minimal spacing styles of styles/README.md components), `script`, `iframe` (except video embeds in `object` for the video module, or when the user explicitly asks), external CSS.

Links: relative for internal pages (`/blog/...`), absolute for external, `rel="nofollow noopener"` + `target="_blank"` for external links unless the user prefers otherwise.

## Lead vs body

- `description` is the teaser on lists, RSS and often the category page; on many modules the page shows `$MESSAGE$` only. Write the lead as 1–3 sentences that work on their own; do not repeat it word for word as the first paragraph of `message`.
- Blog with `entry_split` on: if `description` is empty, the list shows the first N symbols of `message` — so the first paragraph must read well alone.

## Escaping traps (verified)

- ASCII `"` in a **title** is stored as `&quot;` and leaks into the slug (`..._quot_...`). Use «ёлочки» in Russian, typographic quotes elsewhere.
- `&ndash;`, `&nbsp;` in existing texts are fine; don't "clean" them unless asked.
- Plain text with `<` must go as `plain` or escaped — otherwise it becomes markup.

## After every write

1. Write reply: `includehtml_*` is `yes` for HTML content. (Ignore an empty `original_message` in an `update` reply — it happens for correctly saved texts.)
2. `content_read get`: `original_message` / `original_description` contain real tags, not `&lt;p&gt;`.
3. The public page shows formatted text (spot-check).

If 2 or 3 fails, stop the batch, fix the call (format parameter) and re-save the original value if it was damaged.

## Image codes inside texts

`$IMAGEn$` and `$IMG_URLn$` — the only variables meant for material texts. Rules, markup with `class="ulightbox"`, and how they are stored: MEDIA_WORKFLOW.md §2. When editing a text with images, start from `get` → `original_message` and keep `$IMAGEn$` and the `<!--IMG_URLn-->` blocks (or turn the blocks back into `$IMG_URLn$`).
