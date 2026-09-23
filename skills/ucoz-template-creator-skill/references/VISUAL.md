# uCoz Template Creator Visual System

Version: **1.2** (ships with Template Creator Skill **v1.2**)

This file is the **visual contract** for greenfield framework builds via `tmaker_tool`. `FRAMEWORK.md` owns platform mechanics (blocks, variables, migration, QA). `DESIGN-DIRECTIONS.md` picks the **named direction** and soft vibe. **This file** owns what the built template must look like in practice: non-negotiables, layout recipes, typography and spacing floors, color tokens, icons, the modern CSS/JS toolbox, motion, imagery, social proof, copy voice, and accessibility.

**Read this file before writing CSS in `3/3`.** Everything here is mandatory unless marked "situational" or "only on request." When a soft vibe note in `DESIGN-DIRECTIONS.md` conflicts with a floor or toolbox rule here, **this file wins**.

---

## 1) Directions: invent the identity, then lock it

Template Creator builds are greenfield: invent a coherent identity from the brief and content, then encode it once in `3/3` and the framework shell. Do not invent a second look per module.

### 1.1 Named directions

Pick one named direction from [DESIGN-DIRECTIONS.md](DESIGN-DIRECTIONS.md) (six directions: Editorial atelier, Neo-brutalist index, Warm craft journal, Technical observatory, Swiss cultural grid, Soft playful studio). Combine at most two compatible directions. Record the choice in the final report.

**No universal default.** Do not reach for dark-teal gradients, glass cards, rounded pills everywhere, or Inter-by-default regardless of niche.

### 1.2 Evolving an existing project (keep vs. invent)

When the task is evolution of a saved project rather than a blank brief:

- **Keep and systematize** when the project already has a coherent palette/type pair: promote them into the §3 token registry, fill missing hover/muted/border tokens, apply the §2 floors on top.
- **Invent / replace** when the user asks for a new look, or the project has no coherent identity: pick a direction from §1.1 and treat it as greenfield.

Do not silently drift between keep and invent mid-build.

### 1.3 Non-negotiables & anti-references — before any CSS

Immediately after selecting the direction (§1.1), and **before writing a single CSS rule** in `3/3`, write two short lists (a few words per line):

- **Non-negotiables** — 3–5 concrete, checkable rules for *this* build ("zero border-radius" is checkable; "feels premium" is not).
- **Must not look like** — 2–3 named adjacent looks this direction could collapse into if the non-negotiables are skipped.

Working artifact only — not required in the user-facing report. Apply the same lists to every module shell and informer layout, not only the homepage.

**Worked example — Editorial atelier:**

- Non-negotiables: serif display + sans body; oversized H1 within §2.1 clamp; zero or ≤4px radius on cards/buttons; no soft drop shadows — depth from ink/paper inversion and hairline rules; hover/focus ≤150ms; at least one inverted ink-on-paper block on the primary archive/home.
- Must not look like: SaaS template with 14px radii and gradient CTAs; generic "clean corporate" with Inter everywhere and soft card shadows.

**Worked example — Neo-brutalist index:**

- Non-negotiables: hard 2–3px black borders; offset hard shadows (no blur); radius 0–4px; near-white/black plus exactly one saturated signal; grotesk or mono labels; immediate state changes, no fade-in-on-every-card.
- Must not look like: soft pastel playful studio; glassmorphism or neumorphism; rounded pill nav with blur shadows.

**Worked example — Warm craft journal:**

- Non-negotiables: warm paper/cream surfaces (`--aa-color-bg` / `--aa-color-surface-2` in the clay–cream range); humanist serif or soft sans for display, readable sans for body; clay/terracotta as the *only* accent; soft radii allowed (`--aa-radius-md`–`lg`) but no glass/`backdrop-filter`; textured or paper-grain atmosphere used sparingly (one shell-level treatment, not per card); hairline or soft dividers instead of heavy card chrome.
- Must not look like: Soft playful studio (bright dual accents, blob shapes everywhere); Luxury quiet-premium with cool charcoal and metallic accents; generic "handmade" with clip-art textures and emoji flourishes.

**Worked example — Technical observatory:**

- Non-negotiables: cool near-black or slate surfaces; monospace or geometric-sans for labels, numerals, and meta; exactly one vivid signal accent (cyan/lime/amber — pick one); thin 1px hairline borders instead of soft card shadows; data/UI panel or code-adjacent motif somewhere on home or primary archive; hover/focus feedback via border/accent, not lift-and-glow on every card.
- Must not look like: SaaS dark landing with purple glow and glass cards; cyberpunk multi-neon; Soft playful studio; a light corporate theme merely inverted to dark without observatory-specific border/mono decisions.

For any other direction, produce the same two lists at this specificity before CSS.

### 1.4 Layout & grid — fluid container

Do not hardcode the same container width on every build. Derive it once from direction density and lock it in the §3.1 registry:

```css
:root {
  --aa-container-max: 1200px; /* pick once: 1080–1280px by direction density */
}
.aa-container {
  width: min(92%, var(--aa-container-max));
  margin-inline: auto;
}
```

- **Range by density:** dense/data-forward directions (Technical observatory, Neo-brutalist index with grid-heavy archives) sit toward `1280px`; editorial/quiet directions with long prose measures (Editorial atelier, Warm craft journal, Swiss cultural grid) sit toward `1080–1140px` so body measure (§2.1) does not stretch too wide. Restate the chosen value once in `:root`, not per region.
- **Fluid gutters:** side padding via the §2.2 scale and `clamp()`, e.g. `padding-inline: clamp(20px, 5vw, 64px);` — do not invent a second gutter system beside the spacing tokens.
- **Alignment default:** left-align body copy, archive entries, and multi-line lists. Center alignment is reserved for the home hero statement and standalone CTA bands — never for a paragraph of body text. Never use `text-align: justify` (uneven word-spacing rivers in narrow columns).
- **Full-bleed:** regions that intentionally break out of the container (image band, stat strip, inverted ink block) may go edge-to-edge. Put `overflow-x: hidden` on the shell wrapper (or `body` via `3/3`) so a miscalculated full-bleed element cannot introduce horizontal scroll.

### 1.5 Region layout recipes (CMS)

The fastest way a framework reads as generic is every home using the same "centered hero + three equal cards" recipe, then repeating that card grid on every archive. For each region below, **pick one variant** that fits the chosen direction (§1.1) and the real content — do not apply the first column from habit.

| Region | Variant A | Variant B | Variant C |
|---|---|---|---|
| Home hero | **Split** — copy + CTA one side, visual/shape/informer the other | **Centered** — stacked headline/subhead/CTA, no side visual (Editorial / Luxury-leaning) | **Editorial offset** — asymmetric text overlapping a color/image panel, headline breaks the grid slightly |
| Archive / list | **Card grid** — equal cards when entries are genuinely parallel | **Alternating rows** — media left / text right, flips per row (good for 3–8 detailed entries) | **Compact list** — title + meta + excerpt rows, minimal chrome (Editorial / Swiss) |
| Entry page | **Stacked** — title → meta → media → body → comments | **Two-column with rail** — body + aside (related, TOC, shop attrs) when content justifies it | — |
| Sidebar rail | **Stacked cards** — same card token as main, tighter padding; never a third unrelated visual language | — | — |
| Search results | **Card list** — consistent thumb + title + snippet; reuse archive card tokens | — | — |
| Footer | **Brand + nav + copyright** — site mark/name, primary links, then copyright / `$POWERED_BY$` (§10.1) | — | — |

Mixing variants across regions is expected: Split hero + compact list archive + stacked entry reads as designed. Defaulting every home to centered hero + three equal feature cards is forbidden unless the brief and content genuinely call for it.

### 1.6 Page rhythm

Beyond spacing hierarchy inside a region (§2.2), alternate `--aa-color-bg`, `--aa-color-surface`, and `--aa-color-surface-2` between major regions down the page (header chrome, home bands, archive wrap, entry body, footer). A site where every region shares the exact same background reads as one long undifferentiated scroll; alternating on *every* boundary reads as a spreadsheet. Aim for **2–3 deliberate background shifts** on a typical full-page shell (e.g. header/hero on `--aa-color-bg`, main/archive on `--aa-color-surface`, footer on `--aa-color-surface-2`), not a shift at every wrapper.

---

## 2) Typography & spacing floor (mandatory in every direction)

Applies to framework chrome and UI (header, cards, buttons, nav, metadata, breadcrumbs) regardless of direction.

### 2.1 Type scale

| Role | Size | Notes |
|---|---|---|
| H1 (page/hero-level) | `clamp(1.75rem, 4vw, 2.75rem)` | 1–2 lines. Editorial / Luxury-leaning directions may go up to `clamp(2rem, 5vw, 3.25rem)`, never further. |
| H2 (section-level) | `clamp(1.35rem, 2.6vw, 1.75rem)` | Must not visually compete with H1. |
| Body | `16–18px`, line-height `1.5–1.65` | Measure `45–70ch` for prose. |
| Buttons / labels / nav | `14–16px` | Sentence case by default. |
| Eyebrow (only if needed) | `13px`, modest tracking | Reject tracked-out ALL-CAPS eyebrows by default. |

Two font roles max: one display/heading family, one body family (or one family with weight contrast). **Do not default to Inter** — pick fonts that match the direction.

### 2.2 Spacing scale

Use one scale everywhere: `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 96` (px). Prefer `clamp()` for section/card padding, e.g. `padding: clamp(24px, 4vw, 48px);`.

**Hierarchy:** gap *between* sections > gap *inside* a section > gap between related elements (icon → label).

### 2.3 Typography anti-patterns

- Bare `100vh` / `min-height: 100vh` on heroes or drawers — mobile address-bar jump; use `dvh`/`svh` (§5.1).
- Giant hero H1 (68–82px) as the unexamined default.
- Tracked-out ALL-CAPS eyebrow above every heading.
- `→` appended to every button/link.
- Decorative middle-dot meta strings as a new habit (functional single separators in material meta are fine).
- Accenting a single headline word via bold/italic/color as decoration.
- Numbered `01 / 02 / 03` markers unless the content is genuinely a sequence (steps, timeline).
- One word per line in a hero "for drama" without a stated Editorial reason.
- Inter (or any single sans) as the default for every project.

### 2.4 Free wins

- `text-wrap: balance` on H1/H2.
- `text-wrap: pretty` on body paragraphs and long CTAs.

---

## 3) Color & tokens

### 3.1 One registry in `3/3`

Put the entire token contract in the site CSS table (`module 3 / template 3`). **No CSS rules in framework HTML** — only:

```html
<link type="text/css" rel="stylesheet" href="/_st/my.css?v=1">
```

Illustrative prefix `--aa-*` (replace with the project's chosen prefix, but **one prefix only**):

```css
:root {
  /* surfaces */
  --aa-color-bg:          #F7F4EE;
  --aa-color-surface:     #FFFFFF;
  --aa-color-surface-2:   #F0ECE3;

  /* text */
  --aa-color-ink:         #14181C;
  --aa-color-ink-muted:   rgba(20,24,28,0.64);
  --aa-color-on-accent:   #FFFFFF;

  /* brand / accent */
  --aa-color-accent:      #191925;
  --aa-color-accent-hover:#2a2a38; /* prefer §3.3 derivation */

  /* structure */
  --aa-color-border:      rgba(20,24,28,0.12);
  --aa-radius-sm:         8px;
  --aa-radius-md:         14px;
  --aa-radius-lg:         24px;

  /* spacing (mirrors §2.2) */
  --aa-space-1: 4px;  --aa-space-2: 8px;  --aa-space-3: 12px; --aa-space-4: 16px;
  --aa-space-5: 24px; --aa-space-6: 32px; --aa-space-7: 48px; --aa-space-8: 64px; --aa-space-9: 96px;

  /* elevation — hard offsets for neo-brutal; soft only when direction allows */
  --aa-shadow-sm: 0 1px 4px rgba(20,24,28,0.06), 0 4px 16px rgba(20,24,28,0.04);
  --aa-shadow-md: 0 2px 8px rgba(20,24,28,0.06), 0 12px 40px rgba(20,24,28,0.08);

  /* type */
  --aa-font-display: "Fraunces", serif;
  --aa-font-body:     "Source Sans 3", system-ui, sans-serif;

  /* motion easing (see §6) */
  --aa-ease-out: cubic-bezier(0.16,1,0.3,1);

  --aa-container-max: 1200px; /* 1080–1280px by density — §1.4 */

  color-scheme: light; /* or dark — see §3.4 */
}
```

Naming: `--aa-<category>-<role>`. **Do not introduce a second prefix** (`--dx-*`, `--ui-*`, …) alongside the project registry. If evolving a project that already uses another prefix, extend that prefix — do not rename to `--aa-*` and run two registries.

### 3.2 Contrast (WCAG AA)

- Body text on background: **≥ 4.5:1**.
- Large text (24px+/bold 19px+) and UI borders: **≥ 3:1**.
- Practical defaults that always pass without computing: near-black text (`#14181C`-ish) on near-white/cream backgrounds, near-white text on navy/charcoal. If a brand hex is used as body text on a light surface, sanity-check it is clearly darker than mid-grey — when in doubt, darken it rather than trust the raw brand hex for body copy.

### 3.3 Hover/tint via `color-mix()`

```css
:root {
  --aa-color-accent: #191925; /* HEX first — always */
}
@supports (color: color-mix(in oklch, red, blue)) {
  :root {
    --aa-color-accent-hover: color-mix(in oklch, var(--aa-color-accent) 82%, black);
    --aa-color-accent-tint:  color-mix(in oklch, var(--aa-color-accent) 12%, white);
  }
}
```

### 3.4 `color-scheme`

Declare `color-scheme: light;` or `dark;` in `:root` even without a toggle — otherwise native form controls and scrollbars can clash under the visitor OS theme.

### 3.5 Component states

Define once in tokens — not ad hoc per module.

| Component | Default | Hover | `:focus-visible` | Active | Disabled |
|---|---|---|---|---|---|
| Primary button | accent bg, on-accent text | accent-hover bg; optional slight lift (`translateY(-1px)`) when direction allows soft motion | 2px outline accent, offset 2px (ring via `outline` or `box-shadow` so it survives `forced-colors`) | `translateY(0)`, no lift | surface-2 bg, ink-muted text, `cursor: not-allowed` |
| Secondary/ghost | transparent, border outline, ink label | surface-2 fill | same outline rule as primary | border darkens slightly | border @ 50% opacity, no hover fill |
| Text input | surface bg, border | border → ink-muted | border → accent + ring (`box-shadow`, not color alone) | — | surface-2 bg |
| Card | surface, shadow or hard border per direction | shadow/border intensify — nothing else moves unless the whole card is a link | outline on the card if the whole card is a link | — | — |

**One solid primary CTA per viewport.** If a region has primary and secondary actions, the secondary is always ghost/outline — never a second solid-filled button competing for attention.

**CTA specificity — forbid bare link resets that beat `.btn`:**

```css
/* Forbidden at a scope that wins over buttons: */
a { color: inherit; } /* too broad — strips CTA color/contrast */

/* Allowed body reset — unclassed links only; CTAs keep explicit colors: */
a:not([class]):not([style]) { color: inherit; }
a.btn, a.button, .cta a { /* explicit color/background — never rely on inherit */ }

/* Required pattern — class on the anchor, or higher-specificity CTA selectors: */
a.btn,
a.btn--primary { color: var(--aa-color-on-accent); }
```

Never rely on a naked `a { color: inherit }` (or similarly broad `a { color: … }`) at `body`/shell scope if it overrides `.btn` / `.button` / primary CTA classes. Prefer `a.btn`, `.aa-cta a`, or other selectors that keep CTA text on `--aa-color-on-accent` (or the intended CTA ink).

### 3.6 Cross-module consistency

The same button, card, badge, and input treatment must hold across blog, shop, forum, photo, users, and search shells unless a documented scoped exception exists.

- Module CSS tables (`20/9`, `9/10`, `25/8`, …) **may** exist for module-specific layout (entry body, goods grid, topic list). Accents, radii, borders, and type must still **derive from the site token registry** in `3/3` — do not invent a second accent hex inside a module file.
- **Global chrome CSS only in `3/3`** (header, footer, nav, off-canvas, shared `.btn`/`.card`, containers, tokens). Do not duplicate chrome rules into every module template "just in case."

---

## 4) Icons

**Module metadata (date, views, category, author, comments, …):** prefer the semantic sprite in [assets/ui-icons.svg](../assets/ui-icons.svg). Copy only needed symbols into one framework global block; render that block once from the header. Follow the semantic map in `DESIGN-DIRECTIONS.md`. Never use one generic glyph for unrelated values.

**Chrome UI icons** (menu, search, close, chevrons, social, cart chrome): may use path data from [Heroicons](https://heroicons.com/) Outline 24×24, then [Lucide](https://lucide.dev/), then [Tabler Icons](https://tabler.io/icons/). Keep one stroke language (`stroke-width` consistent) across the theme.

```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <!-- path(s) from sprite symbol or Heroicons/Lucide/Tabler -->
</svg>
```

**Forbidden:** emoji as UI icons; icon webfonts; hand-drawn path data for chrome; icon-only controls without `aria-label` or visually hidden text. Site logo/favicon are brand assets, not UI icons — preserve when required by the brief.

Rating stars on vote-capable pages: native generator + state sprite per `RATING.md` — do not draw static decorative stars.

### 4.1 Logo fallback — monogram when no brand asset

If the brief provides no logo file, do not leave the header as plain site-name text with no graphic anchor:

- **Monogram mark:** a rounded-square or circular tile (reuse `--aa-radius-md` / `--aa-radius-lg`) filled with `--aa-color-accent`, containing the site name's first 1–2 letters in `--aa-font-display`, colored `--aa-color-on-accent`. Pair it inline with the site name.
- Keep detail coarse enough to survive favicon downscale to 32×32 — reuse the same tile/initials as the favicon asset when generating one.
- State in the final report that a monogram was generated in absence of a provided logo so the owner can swap it later.

---

## 5) Modern CSS toolbox

**Platform hard rules:**

- **No CSS in framework HTML** — only `/_st/my.css?v=...` (never bare `/my.css` in shells).
- **Nesting depth ≤ 3** everywhere — deeper nesting breaks readable hand-patches via `patch_template` byte-exact matching.
- Overrides of system/theme markup stay outside `@layer` (or in the highest layer) and must be re-checked live after publish.

### 5.1 Mandatory

| Feature | Use |
|---|---|
| Custom Properties | §3.1 registry in `3/3` |
| `clamp()` / `min()` / `max()` / `calc()` | fluid type and spacing |
| `dvh` / `svh` (not bare `vh`) | hero `min-height`, off-canvas/drawer height — forbid bare `100vh` |
| `aspect-ratio` + responsive images (`srcset`/`sizes` when multiple widths exist, `loading="lazy"`, `decoding="async"`) | card/media thumbnails — prefer `srcset`/`sizes` over a single fixed width when assets allow |
| `:focus-visible` | every interactive control |
| `prefers-reduced-motion` | every non-essential animation (§6) |
| `text-wrap: balance` / `pretty` | §2.4 |
| `:where()` / `:is()` | group selectors without specificity spikes |
| `@supports` | gate every situational feature below |

### 5.2 Situational

| Feature | When | Caveat |
|---|---|---|
| CSS Nesting | fine | **≤ 3 levels** |
| `@layer` | organize own tokens/reset/components | never for system-markup overrides |
| Container queries | cards in sidebar vs main | pair with 320/375/768/1024 checks |
| Logical properties (`margin-inline`, `padding-block`, …) | layout that should flip under RTL / site translator | prefer over physical `left`/`right` for chrome gutters |
| `:has()` | empty menus, form states, layout splits | keep shallow; verify live |
| OKLCH + `color-mix()` | hover/tint (§3.3) | HEX first |
| `mask` / `clip-path` | one deliberate shape treatment | spend once per design |
| `backdrop-filter` | only if direction justifies glass | never on ancestors of `position: fixed` overlays |
| Popover API | simple disclosure — FAQ, cookie/consent, compact chrome | not for primary nav (needs close-on-anchor logic) |
| `content-visibility: auto` + `contain-intrinsic-size` | heavy below-the-fold regions (archive tails, long FAQ, footer) | set intrinsic size or content jumps on scroll-into-view |
| Scroll-driven Animations (`animation-timeline: view()` / `scroll()`) | native reveal-on-scroll without a scroll library | wrap in `@supports`; `IntersectionObserver` fallback (§7); respect `prefers-reduced-motion` |
| `forced-colors` | cheap Windows High Contrast win | small block for buttons/borders |

### 5.3 Skip by default

- **`light-dark()` / full dark-mode toggle** — only on explicit request (§3.4 still declares a single `color-scheme`).
- **Anchor Positioning API** — support uneven; not needed for standard framework chrome.

---

## 6) Motion + `prefers-reduced-motion`

Spend boldness in **one** place per page (hero reveal, primary CTA), not on every card.

- **Scroll reveal:** prefer native CSS `animation-timeline` (§5.2) wrapped in `@supports`. Where unsupported, fall back to `IntersectionObserver` toggling `.is-visible` — same visual result, no CDN.
- Hover/focus feedback (underline, border, hard-shadow nudge, optional button lift from §3.5) is expected.
- Blanket fade-and-slide on every section is the generic-AI default — skip it.
- Wrap non-essential motion:

```css
@media (prefers-reduced-motion: no-preference) {
  .reveal { opacity: 0; transform: translateY(16px); transition: opacity .6s var(--aa-ease-out), transform .6s var(--aa-ease-out); }
  .reveal.is-visible { opacity: 1; transform: none; }
}
```

Without the wrapper, content must render in its final visible state. No mouse-parallax by default.

---

## 7) JS: prefer zero, stay idempotent

- Framework **may** implement mobile off-canvas with a checkbox/`:checked` pattern and **no JS**. Prefer that when it meets a11y needs.
- Any script added to the design must be **idempotent** (tolerate double load / double `init` via a `data-*` guard or module flag).
- **Do not** attach a second click/submit listener on theme hooks the platform or framework already owns (search toggle, menu, auth).
- **Never** intercept `$MFORM_N$` submission with custom `fetch` / `FormData` — the platform handles mail forms and shows the `_uWnd` success modal. Style that modal in `3/3` instead of reimplementing submit.
- `MutationObserver` is situational for restyling overlays: watch for `_uWnd` injection after form success (or a header search panel class toggle) to add a class / manage focus — not a general-purpose DOM watcher.
- Defensive `?.` / `??` around every `querySelector`. Throttle scroll/resize/mousemove with `requestAnimationFrame`. Use `IntersectionObserver` as the scroll-reveal fallback where native `animation-timeline` (§5.2) is unsupported.
- No ES modules, bundlers, or a second competing script file for the same chrome.

---

## 8) Imagery

### 8.1 Stock anti-cliché + abstract defaults

Do not fill gaps with handshake close-ups, diverse-team fist-bumps, or stock-smile portraits — the fastest "AI template" tell. Niche-specific photography (a real workshop, classroom, product) is fine when it clearly belongs to the brief.

When no fitting photo exists, default to one of:

- Abstract token-led compositions (`mask`/`clip-path`, grid/dot, layered shapes from `--aa-color-*`).
- Oversized semantic SVG motifs (decorative, low opacity) rather than photo-shaped empty boxes.
- For Technical observatory: data/UI panels or monospace-adjacent motifs instead of person photos.

### 8.2 Content image consistency

- One consistent `aspect-ratio` across card covers / thumbs in a given region (e.g. all `16:9` or all `4:5`) — mixed ratios read as a scraped collage.
- **One consistent treatment**, not per-image improvisation: either all photos run natural/untouched, or all share the same duotone / `mix-blend-mode` / filter family derived from the token accent. Never mix a treated hero with untouched full-color thumbs further down the page.
- `object-fit: cover` (or `contain` for logos) applied uniformly; gate content images on platform existence variables so empty slots do not reserve broken layouts.
- Reserve space with `aspect-ratio` + `loading="lazy"` / `decoding="async"` (§5.1). Empty avatars: initials monograms in the same language as §4.1 — never generic silhouette placeholders.

---

## 9) Social proof

For home / custom pages that need trust signals — pick a treatment; do not invent a fifth card style:

- **Stat/counter row:** large numeral (between H1 and H2 scale) + small `--aa-color-ink-muted` label. Animated count-up is optional polish — if used, trigger once via `IntersectionObserver` and skip under `prefers-reduced-motion` (show the final number immediately).
- **Logo strip (partners/press):** single row, `object-fit: contain`, uniform height, desaturated (`filter: grayscale(1) opacity(.7)`) with full color on `:hover`.
- **Testimonials:** quote + attribution; real photo under §8.2 rules, or initials monogram (§4.1) — never a stock silhouette. Rating stars, if needed, use the icon/sprite language from §4 / `RATING.md` — never emoji stars.

---

## 10) Copy & voice

Form alone is not enough — generic headlines make a token-perfect shell read as an AI template.

- Headlines state a specific outcome or claim, not a category label ("Weekly studio notes on clay and glaze" beats "Welcome to Our Blog").
- Buttons/CTAs name the next step ("Browse the archive", "Add to cart") — avoid "Submit" / "Click here" when a concrete verb fits.
- Reject the same AI-tell habits as §2.3 in body copy and CTAs (tracked-out eyebrows, `→` on every link, decorative `A · B · C` strings).
- Prefer active voice and concrete specifics over vague superlatives.

### 10.1 Footer copyright line

Treat copyright as a deliberate footer piece, not unstyled browser-default text:

```html
<p class="footer-copyright">© $YEAR$ $SITE_NAME$. All rights reserved.</p>
```

- Always `$YEAR$` — never a hardcoded year, never a client-side `Date()` substitute.
- Style at the small end of the type scale (~13–14px), color `--aa-color-ink-muted` on the footer surface (§1.6).
- Separate it from `$POWERED_BY$` with a hairline `border-top` (or equivalent) and `--aa-space-4` / `--aa-space-5` gap — muted copyright above or beside powered-by, never visually competing with primary footer nav.

---

## 11) Accessibility checklist

Fold into post-publish QA / shell audit:

- [ ] `@supports` gates situational CSS; essentials do not depend on one path.
- [ ] `:focus-visible` on every link, button, and field; no bare `outline: none`.
- [ ] Tap targets ≥ 44×44px with ≥ 8px gaps.
- [ ] Contrast ≥ 4.5:1 body / ≥ 3:1 large text and UI borders.
- [ ] `prefers-reduced-motion` — content usable with motion reduced.
- [ ] `color-scheme` declared in `:root`.
- [ ] One `forced-colors` fallback block if borders/buttons rely on background alone.
- [ ] Nesting ≤ 3; no system overrides trapped in `@layer`.
- [ ] Hero/drawer heights use `dvh`/`svh` — no bare `100vh`.
- [ ] Container width (§1.4) picked once from 1080–1280 and set in `:root`.
- [ ] Regions use deliberate recipes from §1.5 — not default centered hero + 3 equal cards everywhere.
- [ ] Page rhythm (§1.6): 2–3 deliberate surface shifts, not one flat scroll.
- [ ] One solid primary CTA per viewport; CTAs use `a.btn` / higher-specificity selectors — no bare `a { color: inherit }` beating `.btn`.
- [ ] Metadata icons from `ui-icons.svg` semantics; chrome icons from sprite or Heroicons/Lucide/Tabler — no emoji/webfonts/hand-drawn paths.
- [ ] No `backdrop-filter`/`filter`/`transform` on ancestors of `position: fixed` overlays.
- [ ] One token prefix; states from §3.5 reused across modules (§3.6); global chrome only in `3/3`.
- [ ] Non-negotiables from §1.3 hold on every full-page shell, not only home.
- [ ] No generic stock-photo clichés (§8.1); content images share aspect-ratio / object-fit (§8.2).
- [ ] Footer copyright uses `$YEAR$`, muted, hairline-separated from `$POWERED_BY$` (§10.1).
- [ ] If no brand logo was provided, monogram fallback generated (§4.1).

---

## 12) Visual do-not summary

- Do not default to Inter, glass, pills, or dark-teal gradients.
- Do not start CSS right after naming a direction — write non-negotiables + must-not-look-like (§1.3) first.
- Do not hardcode one container max on every build — pick from §1.4 once per direction density.
- Do not default every home to centered hero + three equal cards — pick recipes from §1.5.
- Do not ship one flat background across all major regions — use §1.6 rhythm (2–3 shifts).
- Do not introduce a second token prefix beside the project registry.
- Do not put CSS in framework HTML; shells must use `/_st/my.css?v=...`, never orphan `/my.css`.
- Do not nest CSS more than 3 levels.
- Do not duplicate global chrome outside `3/3`, or invent module-local accent hexes that ignore site tokens (§3.6).
- Do not use bare `a { color: inherit }` (or equally broad link resets) at a scope that beats `.btn` — require `a.btn` / higher specificity for CTAs.
- Do not show two solid primary CTAs in one viewport.
- Do not hand-draw chrome icons or use emoji/icon fonts.
- Do not leave the header with plain text and no mark when no logo exists — generate the §4.1 monogram.
- Do not use bare `100vh` for heroes/drawers — use `dvh`/`svh`.
- Do not add a second listener on existing theme hooks.
- Do not ship motion without a `prefers-reduced-motion` path.
- Do not fill imagery gaps with generic stock clichés; keep card image ratios consistent (§8).
- Do not leave footer copyright as unstyled default text, or hardcode the year — use `$YEAR$` and separate it from `$POWERED_BY$` (§10.1).
- Do not skip the full-page shell chrome audit after framework publish (`SKILL.md`).
