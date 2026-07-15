# Content fields and format preservation

Use this procedure for every material, category, page, forum message, or shop object read through uAPI. Field names and format controls vary by module; the current OpenAPI update schema is authoritative.

## Inventory all returned fields

1. Read the complete object, preferring a single-object/detail endpoint.
2. Read the update operation and request schema from the module’s current `*-openapi.yaml`.
3. For every returned property, classify it:
   - primary translatable text;
   - optional translatable text;
   - related object requiring another endpoint;
   - protected/technical;
   - response-only.
4. Offer a field for translation only when it is non-empty user-visible text and the update schema accepts it or documents a dedicated update endpoint.
5. Show optional candidates before translation and ask whether to include them.

Common optional candidates include `meta_title`, `meta_description`, `meta_keywords`, tags, `other1`…`otherN`, custom text fields, category names/descriptions, and editable image `alt`/`title`. Inspect module field metadata when a custom field’s meaning or type is unclear.

Do not offer URLs, file paths, author names, state strings, counters, dates, IDs, or nested category objects merely because they contain strings. A category is a separate object and requires separate scope and endpoint.

## Ask about the slug separately

If the update schema accepts `hgu_title`, `ownurl`, or an equivalent slug:

1. Show its current value and the current public URL.
2. Ask whether to localize it.
3. Warn that changing it may change the public URL, affect indexed pages, and break inbound links unless redirects exist.
4. If approved, show the proposed slug and resulting URL in the preview.
5. Otherwise omit the slug field from the request.

Approval to translate a title, body, “all text”, or SEO does not include the slug.

## Detect the source format

Determine format separately for every rich-text field. Use evidence in this order:

1. Explicit response metadata such as `description_type`, `message_type`, `includehtml_*`, `end2br_*`, `includehtml`, or `end2br`.
2. A documented module field/type endpoint or detail response.
3. Raw properties such as `original_description` and `original_message`:
   - actual HTML tags/attributes → `html`;
   - BBCode tokens such as `[b]`, `[url=…]`, `[img]` → `bb`;
   - neither → `plain`.
4. Compare processed and raw values to infer line-break conversion only when explicit flags are absent.

Do not infer HTML from an already escaped literal such as `&lt;p&gt;`; that represents plain text containing markup characters unless other trustworthy metadata proves it was corrupted by a prior write. Do not infer format from the field name alone.

## Preserve the format on write

Send the translated field in the same source format and include the module’s documented controls explicitly.

For modules whose schema exposes the common controls:

| Content | Value control | HTML control | Line-break control |
|---|---|---|---|
| Short description | `description_type` | `includehtml_desc` | `end2br_desc` |
| Full message | `message_type` | `includehtml_msg` | `end2br_msg` |

- HTML: keep tags and attributes, send `*_type=html`, enable the matching HTML flag, and preserve the detected line-break behavior.
- BBCode: keep BBCode structure, send `*_type=bb`, and preserve documented HTML/line-break controls when they exist.
- Plain text: send `*_type=plain` and preserve line-break conversion; never insert HTML merely for formatting.
- Forum and other module-specific objects: use their own controls such as `includehtml`/`end2br`; do not invent common News fields.

If the GET response omits format flags but `original_*` contains real HTML, send the documented HTML type and enable flag explicitly. This prevents uAPI defaults from escaping markup.

## Verify after write

Read the object again and verify:

- translated values are present;
- the raw/original field retains the expected representation;
- actual HTML is not stored as literal `&lt;...&gt;` text;
- BBCode tokens remain tokens and are not converted or escaped;
- format and line-break controls match the source behavior;
- protected fields and related objects are unchanged;
- the public URL is unchanged unless the slug update was explicitly approved.

If representation changed, stop the batch. Correct the request with the documented format controls or restore the original value before continuing.
