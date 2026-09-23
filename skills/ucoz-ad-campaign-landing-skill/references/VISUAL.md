# uCoz Landing Visual System

Version: **1.1** (generation rules from Landing Skill v1.4, embedded in Ad Campaign Landing Skill **v2**)

This file is the **visual contract** for segment landing pages built via `LANDING_PLAYBOOK.md`. `LANDING_PLAYBOOK.md` owns the MCP mechanics (`page_add`, FTP, menus, forms, SEO). This file owns what the HTML/CSS actually looks like: style selection, typography and spacing, color tokens, icons, the modern CSS/JS toolbox, motion, and accessibility.

**Read this file before Step 4 of `LANDING_PLAYBOOK.md`** (the first line of CSS). Everything here is mandatory unless explicitly marked "situational" or "only on request."

## 0) Paid-series mode (overrides §1–§12)

One site and one campaign share a single `style.css` and `script.js`. The rules below apply to the series as a whole, not again per URL.

If a point in this section conflicts with §1–§12, this section wins:

1. **One visual direction per series.** §1.1 forbids reusing one look across unrelated niches and briefs. Inside one experiment every segment looks like the same brand. Do not pick a new style per slug.
2. **H1 and message match beat a "better" headline.** `COPY.md` §Message Match (overlap ≥ 0.60, H1 carries the meaning of `headline_1`) beats §10 when a rewritten outcome headline drops the overlap. Apply §10 to subheads, the lead, and the CTA without breaking the H1. If cells differ only by the destination URL, do not rewrite the H1 (`EXPERIMENT_AND_LANDING_QA.md` §2).
3. **The hero is not a service catalog.** §1.5 recipes for benefits and cards sit below the first screen. The first screen is the ad offer, not an "our services" grid.
4. **Product screenshots.** UI shots follow `EXPERIMENT_AND_LANDING_QA.md` §6 and `LANDING_PLAYBOOK.md` §2.1: `object-fit: contain`, unequal card heights are allowed. §8.2 (one crop and treatment) applies to photography, not to interface screenshots.
5. **Logo beats monogram.** If an official mark exists, use it (playbook §2.1). §4.1 (monogram) applies only when there is no brand asset.
6. **A quiet premium hero does not hide the offer.** Even in Editorial / Luxury, the click meaning and the primary CTA are visible on the first screen without scrolling at 320 px.

---


## 1) Style intake & routing

### 1.1 Never default to one universal palette

Do not reuse the same look (e.g. "dark navy + teal") for every brief regardless of niche. A clinic, a construction company, and an online school should not visually resemble each other. If the user gives brand colors/fonts in Step 0 of `LANDING_PLAYBOOK.md`, use them and skip routing. If the user says "make it nice" with no brand input, **you** pick one named direction below based on the niche and state it in one sentence before writing CSS:

> "I'll build this in a Professional / Trust direction: deep navy `#0F2A43`, warm ivory `#F7F4EE`, one amber accent `#E0A93B`, Fraunces for headings + Inter for body, generous whitespace, no default card-grid."

### 1.2 Style catalog (12 directions)

Each entry is a starting contract, not a locked recipe — adjust hex values to the brand while keeping the same *character* (mode, type pairing, density, signature move).

| # | Style | Mode / Type | Core palette (4–6) | Signature move | Best for | Avoid it for |
|---|---|---|---|---|---|---|
| 1 | **Professional / Trust** | Light, sans | navy `#0F2A43`, slate `#5B6B7A`, ivory `#F7F4EE`, amber `#E0A93B` | confident single-accent CTA, no gradients | clinics, legal, finance, B2B services | youth/consumer brands |
| 2 | **Editorial / Monochrome** | Light, serif display + sans body | ink `#111111`, paper `#FAFAF7`, one muted accent | oversized serif headline, generous line-height | premium services, personal brand, education | anything needing loud CTAs |
| 3 | **Swiss Minimalist** | Light, grotesque sans | black, white, one primary hue | strict grid, hairline rules, no radius | agencies, consulting, portfolios | playful/consumer products |
| 4 | **Luxury / Quiet Premium** | Light or dark, serif display | deep charcoal/burgundy, warm metallic accent | slow reveal, ample negative space | beauty, real estate, premium retail | budget/mass-market offers |
| 5 | **Industrial / Utility** | Light, condensed sans | concrete grey `#3A3F44`, safety orange `#E8590C`, off-white | diagonal dividers, bold numerals for stats | construction, logistics, repair services | wellness, kids |
| 6 | **Botanical / Organic** | Light, humanist serif/sans | cream `#F6F1E7`, sage `#7C8B6F`, terracotta `#C1682D` | rounded shapes, soft photography crops | wellness, eco, food, family services | fintech, industrial |
| 7 | **SaaS / Tech Dark** | Dark, geometric sans | near-black `#0B0E14`, one vivid accent (cyan/violet) | glow behind CTA, thin borders | IT courses, software, startups | traditional/local services |
| 8 | **Academia / Warm Editorial** | Light, serif | cream `#F8F3E9`, burgundy `#7A2E2E` or forest `#2F4D3A` | pull-quotes as visual anchors | courses, coaching, publishing | fast-consumer retail |
| 9 | **Playful Geometric** | Light, rounded sans | 2 bright accents max on white/cream base | soft blob shapes, rounded corners everywhere | kids, events, casual consumer | legal, medical, finance |
| 10 | **Warm Craft / Handmade** | Light, humanist serif/sans | warm neutrals, clay `#B5622F` accent | textured backgrounds, hand-drawn dividers used sparingly | artisans, local/small business | tech, enterprise |
| 11 | **Enterprise Clean** | Light, sans | cool grey `#EDEFF2`, deep blue `#1D4ED8` | data-forward stat blocks, restrained motion | corporate services, B2B SaaS, consulting | consumer/lifestyle brands |
| 12 | **Neo-Brutalism** *(situational)* | Light, mono/sans display | 2–3 raw high-contrast colors, hard black borders | thick borders, offset hard shadows (no blur) | youth brands, creative agencies | anything needing to look "trustworthy/calm" |

### 1.3 Routing table (niche → default styles)

| Niche | Default candidates (pick one, offer alt) | Only on explicit request |
|---|---|---|
| Clinic / medical / legal / finance | Professional, Enterprise Clean, Swiss Minimalist | — |
| Education / courses / coaching | Academia, Editorial, SaaS Dark (tech courses) | Playful Geometric (kids-specific) |
| Construction / repair / logistics | Industrial, Professional | — |
| Beauty / real estate / premium retail | Luxury, Editorial, Botanical | — |
| Wellness / eco / food / family | Botanical, Warm Craft | — |
| IT / SaaS / startups | SaaS Dark, Enterprise Clean, Swiss Minimalist | Cyberpunk/Web3-type neon looks |
| Events / kids / casual consumer | Playful Geometric, Warm Craft | Neo-Brutalism |
| Creative agency / portfolio | Swiss Minimalist, Editorial | Neo-Brutalism, Kinetic (heavy motion) |

**Exotic styles are opt-in only** (ask or wait for explicit request): Neo-Brutalism, Cyberpunk/Vaporwave-type neon-on-dark, Claymorphism, Neumorphism, heavy Kinetic/GSAP-style motion. These clash more often with a system mail-form modal restyle (Step 4.1) and with legibility requirements below — they cost more QA time than they're worth as a silent default.

### 1.4 Layout & grid — fluid, not a fixed number

Do not hardcode the same container width (e.g. always `1200px`) on every landing regardless of content. Derive it instead:

```css
:root {
  --container-max: 1200px; /* pick per landing: 1080–1280px depending on §1.2 density and copy length */
}
.container {
  width: min(92%, var(--container-max));
  margin-inline: auto;
}
```

- **The number floats per brief**, chosen once from a range, not a rule to reuse verbatim: dense/data-forward styles (Enterprise Clean, Industrial, SaaS Dark with stat blocks) sit toward `1280px`; editorial/quiet-premium styles with long line lengths and generous whitespace (Editorial, Luxury, Academia) sit toward `1080–1140px` so the measure (§2.1) doesn't get too wide. Restate the chosen value once in `:root`, not per section.
- Gutter is the fluid side padding already defined in §2.2 (`clamp(20px, 5vw, 64px)`) — don't add a second, different gutter system on top of it.
- **Alignment default:** left-align body copy and multi-line lists; center alignment is reserved for the hero statement and standalone CTA blocks, never for a paragraph of body text. Never use `text-align: justify` — it produces uneven word-spacing rivers in narrow columns.
- **Full-bleed exception:** a section that intentionally breaks out of the container (image band, stat strip, testimonial carousel background) may go edge-to-edge; wrap the page in `overflow-x: hidden` on `body` or the outer wrapper so a miscalculated full-bleed element can't introduce horizontal scroll (ties into the Step 9 QA check).

### 1.5 Section layout recipes (pick, don't default)

The fastest way a landing reads as generic is every section using the same "centered heading + 3-card grid" recipe. For each section below, pick one variant that fits the chosen style (§1.2) and the actual content — don't apply the first one from habit.

| Section | Variant A | Variant B | Variant C |
|---|---|---|---|
| Hero | **Split** — copy + CTA left/right, visual or shape on the other side | **Centered** — stacked headline/subhead/CTA, no side visual, works for Editorial/Luxury/Swiss | **Editorial offset** — asymmetric text block overlapping a background image/color panel, headline breaks the grid slightly |
| Benefits / features | 3–4 col card grid (the safe default — fine when content is genuinely parallel) | **Alternating rows** — icon/image left, text right, flips per row (good for 3–5 items with real detail per item) | **Icon + text list**, no cards, no borders — reads lighter for Editorial/Academia/Luxury |
| Social proof | Logo strip (grayscale, single row) | Stat/counter row (§9) | Testimonial grid/carousel (§9) |
| Pricing / offer | Single centered card (one clear offer) | Side-by-side comparison cards (2–3 tiers) | Simple list rows with a single CTA at the bottom |
| FAQ | Native `<details>`/`<summary>` accordion, single column | Two-column list (no accordion) for ≤6 short Q&As | Popover-API disclosure (§5.2) if the style calls for a lighter interaction |
| CTA (closing) | Full-bleed color/image band | Inline strip inside the container | Repeat the hero's exact composition in miniature, for symmetry |
| Footer | Multi-column link map (services / pages / contact) | Simple centered minimal footer (small sites, single-page landings) | — |

Mixing variants across sections is expected and good — a Hero-Split + Alternating-rows + Logo-strip + Accordion-FAQ + Inline-CTA page reads as designed; five sections all in the "centered block" shape reads as a template.

### 1.6 Non-negotiables & anti-references — before writing any code

A named direction from §1.2 still leaves plenty of room to drift into a generic look — "Editorial / Monochrome" or "SaaS / Tech Dark" is a character, not a fully pinned-down spec. Close that gap the same way a well-written design brief would: **immediately after picking the direction, and before writing a single line of CSS**, write down two short lists (a few words per line, not full sentences):

- **Non-negotiables** — 3–5 concrete, checkable rules for *this* landing. Concrete means a reviewer could fail the build against the list without guessing ("zero border-radius everywhere" is checkable; "feels sophisticated" is not).
- **Must not look like** — 2–3 named adjacent looks this specific direction could accidentally collapse into if the non-negotiables are skipped.

This list does not need to go in the user-facing report — it is a working artifact that keeps the build honest against the chosen character across dozens of small decisions (does this card get a shadow? does this button get rounded corners? how fast does this hover transition run?).

**Worked example — Editorial / Monochrome:**

- Non-negotiables: zero border-radius on every element (buttons, cards, images — sharp corners only); no drop shadows anywhere, depth comes from color inversion and border weight instead; one serif display face for headlines, oversized (`clamp(2rem, 5vw, 3.25rem)`+) on desktop; hover/focus transitions ≤150ms, no eased slide-ins; at least one deliberately inverted section (black bg, white text) used for emphasis.
- Must not look like: a modern SaaS template with rounded cards and a gradient accent; generic "clean corporate" with soft shadows and 8px radii — the moment shadows or rounded corners appear, it has drifted out of this direction.

**Worked example — SaaS / Tech Dark:**

- Non-negotiables: near-black background (`#0B0E14`-range, never pure `#000`); exactly one vivid accent hue, used sparingly (CTA, active states, glow) — never two competing accents; thin 1px hairline borders (`rgba(255,255,255,.08)`-range) delineate cards instead of shadows; a subtle glow/blur behind the primary CTA or hero visual — this is the signature move, skipping it makes the page indistinguishable from "generic dark mode"; a monospace or geometric-sans treatment for numerals/stats/code-like UI accents even if body text is humanist sans.
- Must not look like: a light SaaS template with inverted colors and no other layout change (Tech Dark needs its own glow/border decisions, not just a palette flip); Cyberpunk/Vaporwave (multiple clashing neon hues, scanline textures) — that is a separate, opt-in-only exotic direction (§1.3).

**For any of the other 10 directions**, produce the same two lists yourself at this same level of specificity before coding — numbers, hex ranges, and named alternative looks, not adjectives.

---

## 2) Typography & spacing floor (mandatory in every style)

This floor applies **regardless of which style from §1.2 is chosen**. A style can change the typeface pairing and color; it cannot break these numbers.

### 2.1 Type scale

| Role | Size | Notes |
|---|---|---|
| H1 | `clamp(1.75rem, 4vw, 2.75rem)` | 1–2 lines. Editorial/Luxury may go up to `clamp(2rem, 5vw, 3.25rem)`, never further. |
| H2 | `clamp(1.35rem, 2.6vw, 1.75rem)` | Must not visually compete with H1. |
| Body | `16–18px`, line-height `1.5–1.65` | Measure (line length) `45–70ch`. |
| Buttons / labels | `14–16px` | Sentence case by default — see anti-patterns. |
| Eyebrow (if content truly needs one) | `13px`, modest letter-spacing | See §2.3 before using at all. |

Two font roles max: one display/heading family, one body family (or a single family across both, with weight doing the work). Pick the pairing deliberately per the chosen style in §1.2 — do not default to "Inter everywhere" for every brief.

### 2.2 Spacing scale

Use one scale everywhere: `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 96` (px). Section padding via `clamp()`, e.g. `padding: clamp(48px, 8vw, 96px) clamp(20px, 5vw, 64px);` — not a fixed `120px 24px` on every breakpoint.

**Hierarchy rule:** the gap *between* sections must read larger than the gap *inside* a section, which must read larger than the gap between related elements (label → value, icon → title). If all three look the same, the page has no rhythm.

### 2.3 Typography anti-patterns (reject by default)

- `min-height: 100vh` (or fixed `100vh`) on the hero — mobile browsers' address bar causes jump/jank. Use `100dvh` (§5.1).
- Giant hero H1 (68–82px) as the unexamined default — see §2.1 sizes.
- Tracked-out ALL-CAPS eyebrow label above every heading.
- `→` appended to every button/link label.
- Meta strings joined with middle dots (`A · B · C`).
- Accenting a single word in the headline via bold/italic/color as decoration rather than meaning.
- Numbered `01 / 02 / 03` markers unless the content is genuinely a sequence (steps, timeline).
- One word per line in the hero "for drama" — only acceptable in Editorial/Luxury, and only with a stated reason.

### 2.4 Free wins

- `text-wrap: balance` on H1/H2 — prevents ugly orphan lines. No fallback needed; unsupported browsers just ignore the line.
- `text-wrap: pretty` on body paragraphs and long CTAs.

---

## 3) Color & tokens

### 3.1 Required `:root` contract

Every `style.css` must open with a token block. Do not scatter hex values through component rules.

```css
:root {
  /* surfaces */
  --bg:          #F7F4EE;
  --surface:     #FFFFFF;
  --surface-2:   #F0ECE3;

  /* text */
  --text:        #14181C;
  --text-muted:  rgba(20,24,28,0.64);
  --on-accent:   #FFFFFF;

  /* brand */
  --accent:      #E0A93B;
  --accent-hover:#C9922A; /* see 3.3 for derivation instead of hand-picking */

  /* structure */
  --border:      rgba(20,24,28,0.12);
  --radius-sm:   8px;
  --radius-md:   14px;
  --radius-lg:   24px;

  /* spacing (mirrors §2.2) */
  --space-1: 4px;  --space-2: 8px;  --space-3: 12px; --space-4: 16px;
  --space-5: 24px; --space-6: 32px; --space-7: 48px; --space-8: 64px; --space-9: 96px;

  /* elevation */
  --shadow-sm: 0 1px 4px rgba(20,24,28,0.06), 0 4px 16px rgba(20,24,28,0.04);
  --shadow-md: 0 2px 8px rgba(20,24,28,0.06), 0 12px 40px rgba(20,24,28,0.08);

  /* type */
  --font-display: "Fraunces", serif;
  --font-body:    "Inter", system-ui, sans-serif;

  /* motion easing (see §6) */
  --ease-out: cubic-bezier(0.16,1,0.3,1);

  color-scheme: light; /* or dark — see 3.4 */
}
```

Component CSS reads these variables; it does not invent new hex values inline.

### 3.2 Contrast minimums (WCAG AA)

- Body text on background: **≥ 4.5:1**.
- Large text (24px+/bold 19px+) and UI component borders: **≥ 3:1**.
- Practical defaults that always pass without computing anything: near-black text (`#14181C`-ish) on near-white/cream backgrounds, near-white text on navy/charcoal backgrounds. If a custom brand color is used as a text color on a light surface, sanity-check it's clearly darker than mid-grey — when in doubt, darken it rather than trust the raw brand hex for body copy.

### 3.3 Deriving hover/tint variants: OKLCH + `color-mix()`

Instead of hand-picking a second hex for hover/darken states (or reaching for an external palette script), derive it from the token in CSS:

```css
:root {
  --accent: #E0A93B; /* HEX fallback first — always declared */
}
@supports (color: color-mix(in oklch, red, blue)) {
  :root {
    --accent-hover: color-mix(in oklch, var(--accent) 82%, black);
    --accent-tint:  color-mix(in oklch, var(--accent) 12%, white);
  }
}
```

The HEX line always ships first so non-supporting browsers keep a valid, tested color; the `@supports` block only *upgrades* it. This replaces "algorithmic lighten/darken" tooling with plain CSS.

### 3.4 `color-scheme`

Declare `color-scheme: light;` (or `dark;`) explicitly in `:root` even if you're not building a light/dark toggle. Without it, form fields and scrollbars can silently invert under the visitor's OS dark mode and clash with your hand-picked colors.

Full dual-theme support via `light-dark()` is **situational** — only build it if the brand explicitly asks for a dark-mode toggle. It doubles the QA surface for a page whose only job is to convert a visit into a lead.

### 3.5 Component states

Define these once per component, in the token vocabulary from §3.1 — not invented ad hoc per landing.

| Component | Default | Hover | `:focus-visible` | Active | Disabled |
|---|---|---|---|---|---|
| Primary button | `--accent` bg, `--on-accent` text | `--accent-hover` bg (§3.3), slight lift (`transform: translateY(-1px)`) | 2px outline in `--accent`, offset 2px | `translateY(0)`, no lift | `--surface-2` bg, `--text-muted` text, `cursor: not-allowed` |
| Secondary/ghost button | transparent bg, `--border` outline, `--text` label | `--surface-2` bg fill | same outline rule as primary | border darkens slightly | `--border` at 50% opacity, no hover fill |
| Text input / textarea | `--surface` bg, `--border` outline | border shifts to `--text-muted` | border becomes `--accent`, ring via `box-shadow` (not just color, for `forced-colors`) | — | `--surface-2` bg, `--text-muted` placeholder |
| Card (benefit/pricing/testimonial) | `--surface` bg, `--shadow-sm` | `--shadow-md` (§3.1) — shadow grows, nothing else moves unless the whole card is a link | outline on the card itself if the whole card is a clickable link | — | — |

One primary CTA visible per viewport at a time — if a section has a primary and a secondary action, the secondary is always the ghost/outline variant, never a second solid-filled button competing for attention.

### 3.6 Page-level rhythm

Beyond the spacing hierarchy inside a section (§2.2), alternate `--bg` and `--surface` (or `--surface-2`) between consecutive sections down the page. A landing where every section shares the exact same background reads as one long undifferentiated scroll; strict light/dark banding on every other section reads as a spreadsheet. Alternate deliberately — most pages read best with 2–3 background shifts total (e.g. hero on `--bg`, benefits on `--surface`, proof on `--bg`, CTA on a bold `--accent`-tinted band, footer on `--surface-2`), not a shift on literally every section boundary.

---

## 4) Icons

**Default source: [Heroicons](https://heroicons.com/) Outline, 24×24.** Fallback sources if a needed glyph is missing, in order: [Lucide](https://lucide.dev/), then [Tabler Icons](https://tabler.io/icons/) (6000+ icons, same outline-on-a-24px-grid character, MIT license) for niche/specific glyphs the first two don't cover. Do not hand-draw new SVG path data for UI chrome — inconsistent stroke weight and optical alignment is one of the fastest ways a landing page reads as amateur. Whichever source a glyph comes from, keep `stroke-width` consistent across every icon used on the same page — mixing a 1.5px-stroke Heroicon next to a 2px-stroke Tabler icon reads as inconsistent even though both are "real" icon libraries.

Inline SVG pattern (copy the glyph, keep this shell):

```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <!-- path(s) from Heroicons/Lucide/Tabler -->
</svg>
```

`stroke="currentColor"` means the icon inherits `color` from its CSS context — it follows the token system automatically, no separate icon-color variable needed.

**Exception:** the logo and the favicon remain custom brand assets (already covered by `LANDING_PLAYBOOK.md` Step 4.2) — they are brand identity, not UI chrome.

### 4.1 Logo fallback — no brand asset provided

If the client has no existing logo file (common — most leads booking a landing page have never had one designed), do not leave the header with plain site-name text and no graphic anchor, and do not skip straight to favicon generation in `LANDING_PLAYBOOK.md` Step 4.2 assuming a logo already exists. Generate a minimal one first:

- **Monogram mark:** a rounded-square or circular tile (reuse `--radius-md`/`--radius-lg`) in `--accent`, containing the site name's first 1–2 letters set in `--font-display`, `--on-accent` color. Pair it inline next to the site name in `--font-display`/`--font-body`.
- Keep it simple enough to survive the favicon downscale to 32×32 (§4.2 of the playbook reuses this exact shape/color) — a single letterform or two, one accent color, no gradient detail finer than what still reads at favicon size.
- This is a placeholder identity, not a full brand exercise — state to the user in the report (Step 7 item 14) that a simple monogram was generated in absence of a provided logo, so they know to swap it if they commission real branding later.

**Forbidden:**
- emoji as UI icons (existing rule, restated here for completeness);
- icon webfonts (Font Awesome CDN, etc.) — extra request, FOUC risk, harder to theme;
- icon-only buttons/links without an `aria-label` or visually-hidden text.

---

## 5) Modern CSS toolbox

Everything below is real CSS you can ship today. It's split into three tiers so the skill doesn't turn into a checklist applied blindly. **Two hard platform rules govern all of it:**

- **Nesting depth ≤ 3 levels**, everywhere, in every rule. Deeper nesting is harder to scan and harder to hand-edit later.
- uCoz's own system CSS (`.xw-*` success-modal selectors, `$NMENU_N$` menu output, the compiled `2301.css` bundle) is **not** wrapped in a cascade layer. If you put your own override rules inside `@layer`, an unlayered system rule of equal specificity will silently win over it. Keep any selector that overrides system markup (`.xw-*`, generated menu classes, `.goods-list`, etc.) **outside `@layer`**, or in the highest-priority layer, and verify it actually applies after publishing.

### 5.1 Mandatory (use in every landing, any style)

| Feature | Use it for |
|---|---|
| Custom Properties | the `:root` token contract in §3.1 |
| `clamp()` / `min()` / `max()` / `calc()` | fluid type (§2.1) and section padding (§2.2) instead of breakpoint jumps |
| `dvh` / `svh` / `lvh` instead of `vh` | hero height — fixes the mobile-Safari address-bar jump |
| `aspect-ratio` + responsive images (`srcset`/`sizes`, `loading="lazy"`, `decoding="async"`) | hero/media blocks — reserves space, avoids CLS |
| `:focus-visible` | every interactive element — never remove the default focus ring without replacing it |
| `prefers-reduced-motion` | wraps every non-essential animation (§6) |
| `text-wrap: balance` / `pretty` | §2.4 |
| `:where()` / `:is()` | grouping selectors without raising specificity — the direct fix for `.section` vs `.cta` rules silently cancelling each other |
| `@supports` | the umbrella rule for every "situational" feature below — always progressive enhancement, never the only code path for something essential |

### 5.2 Situational (use when there's a concrete reason)

| Feature | When | Caveat |
|---|---|---|
| CSS Nesting | always fine to use | max 3 levels (hard rule above) |
| `@layer` | organizing tokens/reset/components/utilities inside your own `style.css` | never for rules overriding uCoz system markup — see platform rule above |
| Container Queries | benefit cards / pricing cards that may sit in sections of different widths | pairs with, doesn't replace, the mandatory 320/375/768/1024 viewport checks in `LANDING_PLAYBOOK.md` Step 9 |
| Logical Properties (`margin-inline`, `padding-block`, …) | genuinely useful here: uCoz sites get machine-translated into RTL languages via the site-translator skill; logical properties make the layout flip for free | — |
| OKLCH + `color-mix()` | deriving hover/tint from `--accent` (§3.3) | HEX declared first, always |
| `mask` / `clip-path` | one deliberate shape treatment (hero image mask, angled divider) | spend it in exactly one place — see restraint principle in §6 |
| `backdrop-filter` | sticky header pill, open mobile-menu overlay | one or two spots max — can be janky on low-end Android |
| `:has()` | form field states (`.field:has(input:invalid)`), styling a parent when mobile menu is open | keep the selector shallow (nesting rule) |
| Popover API | simple disclosure widgets — FAQ accordion, cookie/consent banner | not for the primary nav — that needs manual close-on-anchor-click logic already required in `script.js` |
| `content-visibility: auto` + `contain-intrinsic-size` | heavy below-the-fold sections (FAQ, testimonials, footer) | set an intrinsic size or content jumps on scroll-into-view |
| `forced-colors` media query | one small block so buttons/borders stay visible under Windows High Contrast Mode | cheap, easy accessibility win |
| Scroll-driven Animations (`animation-timeline: view()` / `scroll()`) | native replacement for GSAP ScrollTrigger reveal-on-scroll | must be wrapped in `@supports`, must have an `IntersectionObserver` fallback (§7), must respect `prefers-reduced-motion` |

### 5.3 Skip by default

- **`light-dark()` / full dark-mode toggle** — only if the brand explicitly asks (§3.4).
- **Anchor Positioning API** — support is still uneven in 2026 and a lead-gen landing has no complex tooltip/positioning need that justifies the risk.
- **`:nth-child(... of ...)`** — real but niche; keep it as a toolbox idea, not a rule to apply somewhere just because it exists.

---

## 6) Motion

**Spend boldness in one place.** One orchestrated entrance moment on first load (hero only) reads as intentional; fade-and-slide-up on every single section reads as the generic AI-landing default. Pick one, not both.

- **Scroll reveal:** prefer native CSS `animation-timeline` (§5.2) wrapped in `@supports`. Where it isn't supported, fall back to `IntersectionObserver` in `script.js` toggling a `.is-visible` class — same visual result, no CDN dependency.
- **Hover/focus motion** (button lift, card border brighten, icon nudge) is fine and encouraged everywhere — it responds to a person's action, which is a different category from ambient scroll motion.
- **`prefers-reduced-motion` is mandatory**, not optional, for every non-essential animation:

```css
@media (prefers-reduced-motion: no-preference) {
  .reveal { opacity: 0; transform: translateY(16px); transition: opacity .6s var(--ease-out), transform .6s var(--ease-out); }
  .reveal.is-visible { opacity: 1; transform: none; }
}
```

Without the wrapper, `.reveal` should simply render in its final state — never permanently hidden for a user who has motion reduced.

- **No mouse-parallax by default.** If a brand explicitly wants an immersive hero, implement it with a handful of CSS custom properties updated from a throttled (`requestAnimationFrame`) `mousemove` listener in `script.js` — not a GSAP CDN dependency (see `LANDING_PLAYBOOK.md` Step 4/4.1: CSS and JS stay in the two owned files, no external animation framework).

---

## 7) JS baseline for `script.js`

`script.js` stays a single file, plain script (no `<script type="module">`, no bundler) — it's uploaded whole via FTP, not diffed like the HTML template, so file size isn't the constraint; keeping it a single global-scope-safe IIFE is what matters for predictable behavior alongside uCoz's own inline scripts.

### 7.1 Mandatory baseline style

Write every new script in this idiom, not as a blanket "use modern JS" aspiration:

- Optional chaining `?.`, nullish coalescing `??`, logical assignment (`&&=`, `||=`, `??=`) around every `querySelector` call, since a block may legitimately not exist on a given landing:
  ```js
  document.querySelector('.burger')?.addEventListener('click', toggleMenu);
  ```
- Arrow functions, template literals, destructuring, spread/rest, default parameters, `for...of`, optional catch binding (`catch {}`) — the default way to write every function, not a special case.
- `URLSearchParams` for reading/forwarding query params (already the pattern used for UTM-preserve logic in the companion ad-campaign skill) — use it, don't hand-parse `location.search`.
- `IntersectionObserver` as the fallback path for scroll reveals where native `animation-timeline` (§5.2) isn't supported.
- `requestAnimationFrame` to throttle any `scroll`/`resize`/`mousemove` handler — never run layout-affecting code on every raw event tick.

### 7.2 Situational — use with a concrete reason

| Tool | When | Platform caveat |
|---|---|---|
| `async/await`, `fetch`, `AbortController` | a genuinely custom, non-system widget: on-page calculator, extra content fetch, on-site search | **never** to intercept `$MFORM_N$` submission — the mail form is already handled natively by the platform, which shows the `_uWnd` success modal covered in `LANDING_PLAYBOOK.md` Step 4.1. Reimplementing that with `fetch`/`FormData` fights the platform instead of styling it. |
| `Intl.NumberFormat`, `Intl.PluralRules` | genuinely useful for the RU market: formatting `25 000 ₽`, correct plural agreement ("1 курс / 2 курса / 5 курсов") instead of hardcoded strings | — |
| `MutationObserver` | detect the moment `_uWnd` gets injected into the DOM after a successful form submit, to add a class / manage focus for accessibility | targeted use serving the existing modal-restyle requirement — not a general-purpose DOM watcher |
| `ResizeObserver` | fine-tune the burger breakpoint against the header's real rendered width (variable logo length) | optional polish, not required |
| Classes / private fields (`#`) / getters-setters | only for a genuinely stateful widget (a multi-slide case-study carousel, a multi-step form) | for a menu toggle or reveal class, plain functions are simpler and easier to audit — don't wrap two lines of state in a class for its own sake |
| `Map`/`Set`, `Array.from`, `Object.groupBy` | if the page does real client-side data shaping (e.g. filtering a pricing table) | not needed for static marketing content |
| `requestIdleCallback` | deferring a genuinely non-critical script (e.g. a late-loaded analytics snippet) | needs a `setTimeout` fallback — Safari doesn't implement it |

### 7.3 Out of scope — do not reach for these on a landing page

ES Modules (`import`/`export`) — `script.js` stays one file, no build step; modules add ceremony without benefit here. `Promise.all`/`allSettled`/`any`/`race` — no parallel async workloads exist on a typical static landing. Iterators/Generators, `Symbol`, `BigInt`, `structuredClone()` — genuine language features with no landing-page use case; including them would be checkbox completeness, not engineering judgment. `EventTarget`/`CustomEvent` — useful in larger app architectures, overkill for a ~150-line script. `FormData` — same platform caveat as `fetch` above; only relevant for a genuinely custom, non-system form.

---

## 8) Imagery & photography direction

### 8.1 If no real photos exist

Most leads booking a landing have no photoshoot to draw on. **The rule below targets a specific genre of generic stock photo, not stock photography as a source.** A handshake close-up, a diverse group fist-bumping in an open-plan office, a woman laughing alone at a salad — these are the single fastest "this is an AI-generated landing" tell, faster than any CSS mistake, *because they're generic and interchangeable across any brief*. A specific, well-chosen stock photo pulled from a direct link (Unsplash, Pexels, or similar) that actually looks like it belongs to this niche — a real dental chair, an actual construction site, a genuine classroom — is fine, and often reads better than an all-abstract page. The test is specificity, not source: if you'd struggle to tell which industry a photo is for, it's a cliché and gets replaced by one of the defaults below; if it clearly, concretely fits the brief, a stock photo is a legitimate choice.

When no fitting photo is available at all, default instead to one of:

- An abstract composition built from the token system: layered gradient blobs, a large geometric shape (§5.2 `mask`/`clip-path`), or a subtle grid/dot pattern behind the hero copy.
- A large-scale icon or illustrative motif (an oversized Heroicons/Lucide/Tabler glyph used as decoration, not UI, at low opacity or in `--surface-2`) rather than a photo-shaped placeholder.
- For Industrial/Enterprise Clean/SaaS Dark styles: a stat block or product-UI mockup (screenshot-style panel) instead of a person photo — it fits those styles' character better anyway.

### 8.2 If real photos exist

- **One consistent crop ratio** across every photo on the page (e.g. all `4:5` for team/portrait shots, all `16:9` for facility/process shots) — mixed aspect ratios read as a scraped stock-photo collage.
- **One consistent treatment**, not per-image improvisation: either all photos run natural/untouched, or all run through the same `color-mix()`/`mix-blend-mode` duotone tint derived from `--accent` (ties the imagery back into the token system in §3.1). Never mix a duotone hero image with untouched, full-color photos further down the page.
- Reserve space with `aspect-ratio` + explicit `width`/`height` attributes and `loading="lazy"` / `decoding="async"` (already mandatory, §5.1) — this is both a CLS fix and, incidentally, what keeps inconsistent crop ratios visible early enough to fix them.

## 9) Social proof patterns

Common section, no default treatment defined elsewhere in this file — pick per the recipe chosen in §1.5.

- **Stat/counter row:** large number (type scale between H1 and H2, §2.1) + small `--text-muted` label underneath. An animated count-up is situational polish, not a requirement — if used, trigger it via `IntersectionObserver` (§7.1) on scroll-into-view once, and skip the animation entirely under `prefers-reduced-motion` (show the final number immediately).
- **Logo strip (partners/press):** single row, `object-fit: contain`, uniform height, desaturated (`filter: grayscale(1) opacity(.7)`) with full color on `:hover` — never stretched or unevenly sized logos.
- **Testimonials:** quote text, then attribution (name, role/company) with either a real photo (§8.2 crop/treatment rules apply) or, absent one, an initials monogram in the same style as the logo fallback (§4.1) — never a generic placeholder silhouette avatar. Ratings, if used, are a row of icon stars (§4, `stroke`/`fill="currentColor"`) — never emoji stars.

## 10) Copy & voice

This file is otherwise entirely about form (CSS/HTML/JS). It matters just as much that the words filling that form aren't generic — a pixel-perfect token system still reads as an AI landing if every heading is "Comprehensive Solutions for Your Business Needs."

- Headlines state a specific outcome or claim, not a category label ("Book a dentist appointment in 60 seconds" beats "Quality Dental Care").
- Buttons/CTAs name the actual next step and, where natural, the result ("Get my free estimate" beats "Submit" or "Click here").
- Avoid the same AI-tell patterns called out for headlines in §2.3 (tracked-out eyebrows, `→` appended to links, `A · B · C` meta strings) in body copy and CTAs too — they're copy habits, not just typography settings.
- Prefer active voice and concrete numbers/specifics over vague superlatives ("14-day turnaround" beats "fast and reliable").

### 10.1 Footer copyright line

The footer's copyright line is easy to leave as an unstyled browser-default afterthought — don't. Treat it as a small, deliberate piece of the footer, not dead weight:

```html
<p class="footer-copyright">© $YEAR$ $SITE_NAME$. All rights reserved.</p>
```

- Always `$YEAR$`, per `LANDING_PLAYBOOK.md` principle 5 — never a hardcoded number, never a client-side `Date()` substitute for it.
- Style it at the small end of the type scale (§2.1, ~13–14px), color `--text-muted` on whichever surface token the footer uses (§3.6) — quiet, not competing visually with the footer's link columns.
- Place it visually apart from (usually below) the main footer link columns — a hairline `border-top` with `--space-4`/`--space-5` gap above it (§2.2) is enough separation; it doesn't need its own full section.

---

## 11) Accessibility & progressive enhancement checklist

Fold this into `LANDING_PLAYBOOK.md` Step 9 (responsiveness check) as the visual half of pre-delivery QA:

- [ ] `@supports` used for every situational CSS feature in §5.2 — nothing essential depends on a single code path.
- [ ] `:focus-visible` visible on every link, button, and form field; no `outline: none` without a replacement.
- [ ] Tap targets ≥ 44×44px with ≥ 8px spacing between adjacent targets (nav items, form controls, footer links).
- [ ] Body text vs. background ≥ 4.5:1; large text/UI borders ≥ 3:1 (§3.2).
- [ ] `prefers-reduced-motion` respected — content is fully visible/usable with motion reduced, not stuck hidden.
- [ ] `color-scheme` declared in `:root`.
- [ ] One `forced-colors` fallback block present if borders/buttons rely on background color alone to read as clickable.
- [ ] CSS nesting nowhere exceeds 3 levels.
- [ ] No selector overriding `.xw-*` / menu / module system markup lives inside `@layer`.
- [ ] Icons are Heroicons/Lucide/Tabler inline SVG, `stroke="currentColor"` — no hand-drawn paths, no emoji, no icon webfont.
- [ ] Headline follows the type scale in §2.1 — not the 68–82px unexamined default.
- [ ] Exactly one orchestrated motion moment (hero) plus hover/focus feedback — not fade-up-everywhere.
- [ ] `$MFORM_N$` submission is untouched by custom `fetch`/`FormData` code.
- [ ] Container width (§1.4) picked once from the 1080–1280px range and set in `:root`, not a different number improvised per section.
- [ ] Each section uses a deliberate layout recipe from §1.5 — not the same "centered + 3-card grid" shape repeated on every section.
- [ ] Button/input/card states (§3.5) cover hover, `:focus-visible`, active, and disabled — not just a default and a hover.
- [ ] Background alternates deliberately between sections (§3.6) — page doesn't read as one flat, undifferentiated scroll.
- [ ] No generic stock-photo clichés (handshake, fist-bump team, stock-smile) in imagery (§8.1); if real photos are used, crop ratio and treatment are consistent across all of them (§8.2).
- [ ] Headlines/CTAs state a specific outcome, not a generic category label (§10).
- [ ] Footer copyright line uses `$YEAR$`, styled small/muted, visually separated from the link columns (§10.1).
- [ ] If no brand logo was provided, a simple monogram fallback was generated (§4.1) — header isn't left with plain text and no graphic anchor.

---

## 12) Visual "do not" summary

- Do not reuse one universal palette for every brief regardless of niche — pick a named direction from §1.2 and state it.
- Do not default to "Inter everywhere" without deciding it fits the chosen style's character.
- Do not hand-draw new icon path data — use Heroicons/Lucide/Tabler.
- Do not wrap `.xw-*` / `$NMENU_N$` / module-generated selectors inside `@layer`.
- Do not nest CSS more than 3 levels deep.
- Do not intercept `$MFORM_N$` submission with `fetch`/`FormData` — style the native `_uWnd` modal instead (`LANDING_PLAYBOOK.md` Step 4.1).
- Do not add GSAP, Tailwind, or any external UI-framework CDN — CSS/JS stay inline in `style.css`/`script.js` per `LANDING_PLAYBOOK.md` Step 4/4.1; use the native CSS/JS toolbox in §5–§7 instead.
- Do not ship any animation without a `prefers-reduced-motion` fallback.
- Do not use `100vh` for the hero — use `100dvh`.
- Do not fade-and-slide-up every section — spend the one orchestrated motion moment on the hero only.
- Do not hardcode the same container max-width on every landing — pick from the range in §1.4 per brief.
- Do not apply the same "centered heading + 3-card grid" recipe to every section — pick per §1.5.
- Do not use generic stock-photo clichés (handshake, diverse-team fist-bump, stock-smile) as hero/section imagery — use §8.1's abstract/icon-led defaults when no real photos exist.
- Do not leave the footer copyright line as unstyled browser-default text, and do not hardcode the year — use `$YEAR$`, styled per §10.1.
- Do not leave a header with plain site-name text and no graphic anchor when no logo was provided — generate the monogram fallback in §4.1.
- Do not start coding right after naming a style — write the non-negotiables + must-not-look-like lists from §1.6 first, or the direction will drift toward generic mid-build.
