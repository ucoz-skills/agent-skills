# Copy guide — titles, leads, bodies, meta, alt

Write in the site's language and voice. Read 2–3 existing materials of the module first and match their tone, length and formatting.

The numbers below are defaults. Whatever the user specified — exact title, length («3–5 тысяч знаков»), structure, tone — wins over them.

## Facts

- Use facts from the user, the site and its existing materials. Never invent numbers, prices, dates, quotes, studies, laws, awards, reviews or contact data.
- If a claim needs a source, ask the user or leave it out. Mark assumptions in the preview.
- Medical, legal, financial topics: neutral, informational, "consult a specialist" where appropriate; no promises.

## Title (`title`)

- 40–70 characters, specific, the main phrase early.
- Formats that work: how-to ("Как выбрать …: 7 критериев"), list ("10 идей …"), question, news ("Город открыл …").
- «Ёлочки» for quotes, no ALL CAPS, no clickbait, no emoji unless the site uses them.

## Lead (`description`)

- 1–3 sentences, 150–300 characters: what the material gives the reader.
- Works standalone in lists and RSS; does not duplicate the first paragraph.

## Body (`message`)

Structure, HTML skeleton and formulas depend on the kind of material — take them from `styles/<style>.md` (index: `styles/README.md`). Then run the humanizer pass (`HUMANIZER.md`).

Length guides (defaults): news 1 500–3 000 characters; blog post 3 000–6 000; article/guide 6 000–15 000; listicle 3 000–8 000; FAQ answer 300–1 500; board ad 300–1 200; file description 600–2 000 with requirements/version/licence when known; category description 300–700. A text that ends shorter because there are no more facts is fine — never pad it to reach a range.

Paragraphs ≤ 4–5 sentences. One `h2` every 1 500–2 500 characters.

## Meta title (`meta_title`)

- 50–60 characters, unique on the site.
- Pattern: `Main phrase — clarifier | Brand` (brand only if the template doesn't append it; `$SEO_TITLE$` doesn't).

## Meta description (`meta_description`)

- 140–160 characters, a complete sentence with the benefit and a soft call to action.
- No quotes `"` (break attributes in some templates), no prices/dates unless current and supplied.

## URL (`ownurl`)

Only when the user wants a custom URL: transliterate 2–5 key words, lower case, hyphens: `kak-vybrat-noutbuk`. The automatic system slug (`hgu_title`) is never touched.

## Tags

3–7 short lower-case tags; reuse the tags you see on the module's public pages (no tool lists the site's tags).

## Alt text

5–15 words describing what is visible and relevant to the material.

## Before you save, check

- [ ] No invented facts, figures, quotes.
- [ ] Title, lead, meta title, meta description are all different texts.
- [ ] HTML is clean (no `h1`, no inline styles), links work.
- [ ] Spelling and typography: «», —, non-breaking spaces where the site uses them.
- [ ] Unique vs. existing materials (no near-duplicates).
