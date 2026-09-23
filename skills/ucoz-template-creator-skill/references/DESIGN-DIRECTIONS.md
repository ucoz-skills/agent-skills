# Design directions

## Contents

- Selection rule
- Niche → direction routing
- Editorial atelier
- Neo-brutalist index
- Warm craft journal
- Technical observatory
- Swiss cultural grid
- Soft playful studio
- Semantic SVG system
- Anti-homogenization checklist

## Selection rule

Choose a direction from the site's subject, audience, content density, and supplied brand assets. Combine at most two compatible directions. Change hierarchy and composition, not only colors. Record the selected direction in the final report.

**Pick the named direction before writing any CSS.** Do not default to teal gradients, glass cards, or Inter. After selecting a direction, write non-negotiables + must-not-look-like per [VISUAL.md](VISUAL.md) before writing CSS. Typography/spacing/tokens/icons/CSS toolbox in `VISUAL.md` override soft vibe notes here when they conflict.

## Niche → direction routing

When the brief has no brand palette/fonts, route niche → one of the **six** directions below (pick one; offer an alt if useful). Do **not** invent a twelve-style catalog — stay inside these six. State the choice in one sentence before CSS.

| Niche | Default candidates (pick one) | Only on explicit request |
|---|---|---|
| Clinic / medical / legal / finance | Swiss cultural grid, Editorial atelier | Neo-brutalist index |
| Education / courses / coaching | Soft playful studio, Warm craft journal, Editorial atelier; Technical observatory for tech courses | — |
| Construction / repair / logistics | Neo-brutalist index, Technical observatory, Swiss cultural grid | Soft playful studio |
| Beauty / real estate / premium retail | Editorial atelier, Warm craft journal, Swiss cultural grid | — |
| Wellness / eco / food / family | Warm craft journal, Soft playful studio | — |
| IT / SaaS / startups / science | Technical observatory, Swiss cultural grid | Neo-brutalist index |
| Events / kids / casual consumer | Soft playful studio, Warm craft journal | Neo-brutalist index |
| Creative agency / portfolio / culture / photography | Swiss cultural grid, Editorial atelier | Neo-brutalist index |
| Communities / independent media / event archives | Neo-brutalist index, Soft playful studio | — |
| Museums / architecture / publications | Swiss cultural grid, Editorial atelier | — |

**Exotic looks are opt-in only** (ask or wait for explicit request): glassmorphism, neumorphism, multi-neon cyberpunk, heavy kinetic motion. They clash with platform chrome (menus, `_uWnd`, Search) and cost more QA than they are worth as a silent default.

Example framework starters in `examples/` (editorial-journal, technical-catalog, compact-community, gallery-atelier, brutalist-bulletin) illustrate compositions that map into these directions — they are not a second catalog.

## Editorial atelier

- Best for fashion, culture, photography, essays.
- Typography: high-contrast serif display plus neutral sans text.
- Composition: oversized titles, asymmetric image crops, thin rules, generous vertical rhythm.
- Palette: ink, paper, one mineral accent; avoid generic neon gradients.
- Radius: restrained, mostly 0–12 px.
- Motion: subtle image reveal and underline transitions.
- Icons: fine 1.5 px strokes.

## Neo-brutalist index

- Best for communities, independent media, event archives.
- Typography: grotesk or mono labels, large compact headings.
- Composition: visible grid, hard borders, offset blocks, numbered navigation.
- Palette: near-white/black plus one saturated signal color.
- Radius: 0–4 px; shadows are hard offsets, not blur.
- Motion: immediate state changes and small translations.
- Icons: geometric filled/outlined forms.

## Warm craft journal

- Best for food, travel, handmade work, local businesses.
- Typography: humanist serif headings and warm sans body.
- Composition: tactile paper sections, image-first stories, editorial notes, soft dividers.
- Palette: oat, terracotta, moss, plum; preserve strong text contrast.
- Radius: mixed 8–28 px, never every element as a pill.
- Motion: calm opacity/translate transitions.
- Icons: rounded strokes and simple organic motifs.

## Technical observatory

- Best for software, science, analytics, gaming.
- Typography: squared sans with mono metadata.
- Composition: dashboard rails, data labels, modular panels, precise alignment.
- Palette: deep navy or pale gray with cyan/lime status accents.
- Radius: 6–14 px with crisp 1 px borders.
- Motion: deliberate panel/state transitions; respect reduced motion.
- Icons: consistent 2 px SVG strokes.

## Swiss cultural grid

- Best for museums, portfolios, architecture, publications.
- Typography: one strong neo-grotesk family with extreme scale contrast.
- Composition: disciplined columns, large whitespace, edge-aligned labels, image/text alternation.
- Palette: white, charcoal, red/blue accent.
- Radius: minimal or none.
- Motion: restrained directional wipes.
- Icons: minimal arrows and chevrons.

## Soft playful studio

- Best for education, creators, family products, friendly services.
- Typography: rounded display with highly readable sans body.
- Composition: layered shapes, varied card silhouettes, illustrations, clear CTA islands.
- Palette: light base with two coordinated accents; avoid low-contrast pastels.
- Radius: intentionally varied 12–36 px.
- Motion: small spring-like transforms without blocking interaction.
- Icons: friendly rounded SVG, no emoji substitutions.

## Semantic SVG system

Icons label uCoz values; they are not interchangeable decoration. Copy only required symbols from [assets/ui-icons.svg](../assets/ui-icons.svg) into one framework global block and render that block once from the header. One shared sprite avoids duplicate symbol IDs in repeated entries.

Technical contract:

- every symbol has a `viewBox`, normally renders at 18–24 px, and inherits `currentColor`;
- keep one stroke/fill language per theme;
- decorative SVGs use `aria-hidden="true"` and `focusable="false"`; icon-only controls have an `aria-label`;
- retain visible metadata text and never use emoji, icon fonts, remote icon libraries, or disappearing standard-design assets;
- do not draw static rating stars: vote-capable full pages use the native generator and state sprite from `RATING.md`.

Semantic map:

- News: `ui-news`; date `ui-calendar`, views `ui-eye`, author `ui-user`, category `ui-folder`, comments `ui-comment`.
- Blog: `ui-pen`; date/time, author, comments, native rating.
- Publ: `ui-article`; category, views, `ui-signature` for `$AUTHOR_NAME$`, `ui-user` for `$USERNAME$`.
- File catalog: `ui-download`; `$LOADS$` uses download while `$READS$` uses eye.
- Site directory: `ui-external`; `$REDIRECTS$` is not a view or download.
- Board: `ui-megaphone`; category, views, date, user, comments.
- Photo: `ui-camera`; reviews/views and comments remain distinct from rating.
- Games: `ui-game`; runs/play and reads/views remain distinct.
- FAQ: `ui-help`; author and native rating.
- Guestbook: `ui-book`; author/date and native like/dislike actions.
- Video: `ui-video`; views/play, `ui-clock` duration, comments, native rating.
- Forum: `ui-forum`; preserve native thread/post state icons and controls.
- Shop: `ui-bag`, `ui-cart`, `ui-compare`, and `ui-tag`; preserve basket, compare, wishlist, options, stock, and pricing behavior.

An icon never makes an unverified variable safe. Retrieve the source and call `get_variables` for targeted templates. Apply the same metadata pattern to list and full-material views. If a standard partial cannot be changed, a narrowly scoped CSS `mask-image` may add a verified semantic icon through `::before`; otherwise use a visible label and record the limitation instead of guessing.

## Anti-homogenization checklist

- Do not default to dark teal/orange gradients.
- Do not wrap every section in the same rounded card.
- Do not center every heading and CTA.
- Do not use one repeated pill for categories, buttons, and metadata.
- Do not use glassmorphism unless content and brand justify it.
- Vary image ratio, whitespace, density, navigation, and article rhythm between projects.
- Use one coherent icon language per design, but draw icons for their functions.
- Preserve readability and platform behavior even when the composition is expressive.
