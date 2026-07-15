# Preservation rules for uCoz localization

## Translate

- User-visible headings, paragraphs, labels, buttons, hints, validation messages, and navigation labels.
- Approved descriptions, summaries, article bodies, category prose, and SEO fields.
- Text-valued accessibility attributes: `alt`, `title`, `placeholder`, `aria-label`.
- Static text adjacent to a protected variable, while keeping the variable byte-for-byte unchanged.

## Preserve byte-for-byte

- uCoz variables and insertion codes such as `$TITLE$`, `$MESSAGE$`, `$MFORM_1$`, `$SMENU_1$`, and every `$...$` token.
- Template control syntax including `<?if?>`, `<?else?>`, `<?endif?>`, loops, operators, and expressions.
- `$POWERED_BY$`; never remove or hide it.
- HTML tag structure and non-text attributes including `href`, `src`, `action`, `class`, `id`, `style`, `data-*`, and event handlers.
- URLs, slugs, anchors, emails, IDs, relationships, access rules, publication state, dates, and ownership.
- JavaScript, JSON keys, CSS, embedded media, iframe sources, and code/preformatted blocks unless explicitly requested and safely separable.
- Prices, currency, stock, SKUs, product types, variations, order/payment/delivery data.

Do not translate text inside a URL, script, style, variable, or identifier merely because it resembles natural language.

Slugs/ЧПУ are protected by default, but may be localized after a separate explicit approval that includes an old/new URL preview.

## Translate structured content safely

### HTML and template HTML

1. Inventory protected tokens before translating.
2. Translate text nodes and approved text-valued attributes only.
3. Preserve entities when semantically significant; avoid reformatting unrelated markup.
4. Compare the complete protected-token multiset before and after. Values and counts must match.
5. For templates, validate the complete proposed result before any save.

Prefer exact fragment patches that include enough surrounding markup to be unique but do not rewrite unrelated formatting.

### Menu trees

Recursively copy every object and array in the original order. Replace only approved label values. Verify that item count, child count, IDs, URLs, targets, and types match the original.

### Plain materials and pages

Send only approved translated fields, their matching format controls, and endpoint-required fields. Preserve unknown fields by omission, not by guessing. If an endpoint requires a current value, read and resend it unchanged. Follow [CONTENT_FIELDS_AND_FORMATS.md](CONTENT_FIELDS_AND_FORMATS.md) for `plain`, BBCode, HTML, and line-break preservation.


## Quality checks

- Use the requested locale, not only the base language: for example `pt-BR`, `es-MX`, or `en-US`.
- Keep repeated terminology consistent; ask for or derive a small glossary when brand terms are ambiguous.
- Keep menu and button labels concise and warn when expansion may affect layout.
- Localize dates, units, address formats, and currencies only when explicitly requested.
- Treat SEO translation as localized copywriting while preserving factual meaning and avoiding keyword stuffing.
- Do not translate trademarks or product names without an approved localized form.

## Rollback inventory

Keep original values for materials, pages, and menus until verification completes. For templates, record the MCP backup identifier or latest backup entry created by the save. Never place secrets in rollback data.
