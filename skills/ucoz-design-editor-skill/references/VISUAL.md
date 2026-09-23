# uCoz Design Editor Visual System

Version: **1.0** (ships with Design Editor Skill **v1.1**)

This file is the **visual contract** for redesign work done via `ucoz-mcp`. The other reference files own platform mechanics — `ARCHITECTURE.md` says *where* CSS/tokens live and how to verify they went live, `SYSTEM-MARKUP.md` says what the DOM actually looks like per module, `EDITING.md` says how to patch safely. This file owns what the redesign actually *looks like*: identity extraction, typography and spacing, color tokens, icons, the modern CSS/JS toolbox, motion, imagery, and accessibility.

**Read this file before Step 4 of the core workflow** (`Edit` — before writing any CSS/HTML rule). Everything here is mandatory unless explicitly marked "situational" or "only on request." Where this file and a module reference (`SHOP.md`, `FORUM.md`, …) both apply, the module reference wins on platform mechanics (DOM structure, system classes, JS hooks) and this file wins on visual decisions (colors, type, spacing, motion).

---

## 1) Identity: extract first, invent only when needed

A redesign task is not a blank page — the site already has a logo, and usually already has *some* color and type choices baked into `3/3`, even if inconsistent. Treat that as the starting material, not noise to override by default.

### 1.1 Extraction checklist (do this before choosing anything new)

1. Read the current `module 3/3` stylesheet and note any existing `:root` custom properties (`ARCHITECTURE.md` §CSS design tokens) — if a token registry already exists, extend it; do not start a second one.
2. Read the header global block (`1/AHEADER`) for the logo — its dominant colors and shape are the strongest identity signal on the site, stronger than any existing CSS token, because it is usually the one asset nobody touches carelessly.
3. Note the site's niche/module mix (shop vs. forum vs. blog-only vs. subscriptions) — it constrains which style directions in §1.3 make sense.
4. Ask the user directly only when the brief is ambiguous about *how much* to change: "full visual overhaul" vs. "tidy up and modernize within the current palette." Do not assume "redesign" always means "replace the palette."

### 1.2 When to keep vs. replace the existing identity

- **Keep and systematize** (default when the site has a coherent-ish palette already): promote the existing dominant colors into the named token contract in §3.1, fill gaps (missing hover/muted/border tokens) by deriving them per §3.3, and apply the typography floor in §2 on top. This is almost always the right call for an established site with real traffic and existing user familiarity.
- **Replace** (only when the user explicitly asks for a new look, or the existing site has no coherent palette to extract — literally different hex values on every page): pick one named direction from §1.3, same routing logic as a greenfield brief.

### 1.3 Style catalog (12 directions) and routing

Same catalog and routing table used for greenfield landing pages — reuse it here for the "replace" path in §1.2, or as a naming vocabulary even for the "keep" path ("this site is already basically Professional/Trust, we're just formalizing it").

| # | Style | Mode / Type | Core palette (4–6) | Signature move | Best for | Avoid it for |
|---|---|---|---|---|---|---|
| 1 | **Professional / Trust** | Light, sans | navy `#0F2A43`, slate `#5B6B7A`, ivory `#F7F4EE`, amber `#E0A93B` | confident single-accent CTA, no gradients | clinics, legal, finance, B2B services | youth/consumer brands |
| 2 | **Editorial / Monochrome** | Light, serif display + sans body | ink `#111111`, paper `#FAFAF7`, one muted accent | oversized serif headline, generous line-height | premium services, personal brand, education | anything needing loud CTAs |
| 3 | **Swiss Minimalist** | Light, grotesque sans | black, white, one primary hue | strict grid, hairline rules, no radius | agencies, consulting, portfolios | playful/consumer products |
| 4 | **Luxury / Quiet Premium** | Light or dark, serif display | deep charcoal/burgundy, warm metallic accent | slow reveal, ample negative space | beauty, real estate, premium retail | budget/mass-market offers |
| 5 | **Industrial / Utility** | Light, condensed sans | concrete grey `#3A3F44`, safety orange `#E8590C`, off-white | diagonal dividers, bold numerals for stats | construction, logistics, repair services | wellness, kids |
| 6 | **Botanical / Organic** | Light, humanist serif/sans | cream `#F6F1E7`, sage `#7C8B6F`, terracotta `#C1682D` | rounded shapes, soft photography crops | wellness, eco, food, family services | fintech, industrial |
| 7 | **SaaS / Tech Dark** | Dark, geometric sans | near-black `#0B0E14`, one vivid accent (cyan/violet) | glow behind CTA, thin borders | IT courses, software, startups, forums/communities | traditional/local services |
| 8 | **Academia / Warm Editorial** | Light, serif | cream `#F8F3E9`, burgundy `#7A2E2E` or forest `#2F4D3A` | pull-quotes as visual anchors | blogs, courses, coaching, publishing | fast-consumer retail |
| 9 | **Playful Geometric** | Light, rounded sans | 2 bright accents max on white/cream base | soft blob shapes, rounded corners everywhere | kids, events, casual consumer | legal, medical, finance |
| 10 | **Warm Craft / Handmade** | Light, humanist serif/sans | warm neutrals, clay `#B5622F` accent | textured backgrounds, hand-drawn dividers used sparingly | artisans, local/small business | tech, enterprise |
| 11 | **Enterprise Clean** | Light, sans | cool grey `#EDEFF2`, deep blue `#1D4ED8` | data-forward stat blocks, restrained motion | corporate services, B2B SaaS, consulting, shops | consumer/lifestyle brands |
| 12 | **Neo-Brutalism** *(situational)* | Light, mono/sans display | 2–3 raw high-contrast colors, hard black borders | thick borders, offset hard shadows (no blur) | youth brands, creative agencies, forums | anything needing to look "trustworthy/calm" |

**Exotic styles are opt-in only:** Neo-Brutalism, Cyberpunk/Vaporwave-type neon-on-dark, Claymorphism, Neumorphism, heavy Kinetic motion. On a multi-module site (shop + forum + subscriptions all sharing one `3/3`) these are far more expensive to keep consistent across every module's system markup than on a single landing page — ask before defaulting to one.

**No universal default.** Do not reach for the same "dark navy + teal" or "glass everywhere" look on every redesign job regardless of the site's actual niche and existing identity — that is exactly the generic-AI-makeover failure mode this file exists to prevent.

### 1.4 Non-negotiables & anti-references — before writing any code

A named direction from §1.3 (or a "keep and systematize" call from §1.2) still leaves plenty of room to drift into a generic look. Close that gap the same way a well-written design brief would: **immediately after deciding the direction, and before writing a single CSS rule**, write down two short lists (a few words per line, not full sentences):

- **Non-negotiables** — 3–5 concrete, checkable rules for *this* redesign. Concrete means a reviewer could check the live site against the list without guessing ("zero border-radius everywhere" is checkable; "feels sophisticated" is not).
- **Must not look like** — 2–3 named adjacent looks this specific direction could accidentally collapse into if the non-negotiables are skipped.

This does not need to go in the user-facing report — it is a working artifact that keeps every module (blog, shop, forum, subscriptions) honest against the same character, instead of each module page quietly drifting toward whatever looks "reasonable" in isolation.

**Worked example — Editorial / Monochrome:**

- Non-negotiables: zero border-radius on every element across every module (cards, buttons, product images); no drop shadows anywhere — depth comes from color inversion and border weight; one serif display face for headings, oversized on desktop; hover/focus transitions ≤150ms; at least one deliberately inverted block (black bg, white text) used for emphasis on the homepage/hero.
- Must not look like: a modern SaaS template with rounded cards and a gradient accent; generic "clean corporate" with soft shadows and 8px radii — the moment shadows or rounded corners appear on a shop card or forum panel, it has drifted out of this direction.

**Worked example — SaaS / Tech Dark:**

- Non-negotiables: near-black background (`#0B0E14`-range, never pure `#000`) applied consistently across blog, shop, and forum shells; exactly one vivid accent hue, used sparingly — never two competing accents across different modules; thin 1px hairline borders instead of shadows on every card type (blog card, product card, post card); a subtle glow/blur behind the primary CTA — the signature move, applied site-wide, not only on the homepage.
- Must not look like: a light template with inverted colors and no other change per module; Cyberpunk/Vaporwave (multiple clashing neon hues) — a separate, opt-in-only exotic direction.

**For any other direction**, produce the same two lists yourself at this same level of specificity — and, since this skill spans multiple modules on one site, explicitly check each list against §3.6 (cross-module consistency): a non-negotiable that only gets applied to the homepage and quietly skipped on the shop/forum pages is not actually non-negotiable.

---

## 2) Typography & spacing floor (mandatory in every style)

This floor applies **regardless of which style is kept or chosen in §1**, and regardless of whether the task is a full overhaul or a targeted module restyle. It governs new/restyled *chrome and UI* (headers, cards, buttons, nav) — it does not mean rewriting the site owner's actual entry/article body copy typography wholesale; scope changes to what the task actually touches.

### 2.1 Type scale

| Role | Size | Notes |
|---|---|---|
| H1 (page/hero-level) | `clamp(1.75rem, 4vw, 2.75rem)` | 1–2 lines. Editorial/Luxury may go up to `clamp(2rem, 5vw, 3.25rem)`, never further. |
| H2 (section-level) | `clamp(1.35rem, 2.6vw, 1.75rem)` | Must not visually compete with H1. |
| Body | `16–18px`, line-height `1.5–1.65` | Measure (line length) `45–70ch` for prose (blog/article bodies, FAQ text). |
| Buttons / labels / nav items | `14–16px` | Sentence case by default — see anti-patterns. |
| Eyebrow (if content truly needs one) | `13px`, modest letter-spacing | See §2.3 before using at all. |

Two font roles max: one display/heading family, one body family (or a single family across both, with weight doing the work). Pick the pairing deliberately per §1.3 — do not default to "Inter everywhere" regardless of the site's existing character.

### 2.2 Spacing scale

Use one scale everywhere: `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 96` (px). Section/card padding via `clamp()`, e.g. `padding: clamp(24px, 4vw, 48px);` for cards, `padding: clamp(48px, 8vw, 96px) clamp(20px, 5vw, 64px);` for full sections — not a fixed value baked into every breakpoint separately.

**Hierarchy rule:** the gap *between* sections/blocks must read larger than the gap *inside* one, which must read larger than the gap between related elements (label → value, icon → title). If a redesigned module page and the site's other pages disagree on this rhythm, the redesign reads as bolted-on rather than integrated.

### 2.3 Typography anti-patterns (reject by default)

- Giant hero H1 (68–82px) as the unexamined default when adding/restyling a hero-style header — see §2.1 sizes.
- Tracked-out ALL-CAPS eyebrow label above every heading.
- `→` appended to every button/link label.
- Meta strings joined with middle dots (`A · B · C`) as a new decorative habit — note `EXAMPLES.md`'s search-result meta (`$ENTRY_DEPTH$ · $SAVING_DATE$`) already uses a middle dot; that one is fine as an existing, single, functional separator — the anti-pattern is *adding new* decorative middle-dot strings elsewhere, not removing the one that's already there.
- Accenting a single word in a headline via bold/italic/color as decoration rather than meaning.
- One word per line in a hero "for drama" — only acceptable in Editorial/Luxury, and only with a stated reason.

### 2.4 Free wins

- `text-wrap: balance` on new H1/H2 headings — prevents ugly orphan lines. No fallback needed.
- `text-wrap: pretty` on body paragraphs and long CTAs/descriptions.

---

## 3) Color & tokens

### 3.1 This file defines the contract; `ARCHITECTURE.md` defines the workflow

`ARCHITECTURE.md`'s "CSS design tokens" section is the *process* rule: one canonical `:root`, no second registry, reference via `var()`. That section never says *what* to name things or *what values* to require — this section fills that gap.

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
  --aa-color-accent-hover:#2a2a38; /* derive per §3.3 instead of hand-picking */

  /* structure */
  --aa-color-border:      rgba(20,24,28,0.12);
  --aa-radius-sm:         8px;
  --aa-radius-md:         14px;
  --aa-radius-lg:         24px;

  /* spacing (mirrors §2.2) */
  --aa-space-1: 4px;  --aa-space-2: 8px;  --aa-space-3: 12px; --aa-space-4: 16px;
  --aa-space-5: 24px; --aa-space-6: 32px; --aa-space-7: 48px; --aa-space-8: 64px; --aa-space-9: 96px;

  /* elevation */
  --aa-shadow-sm: 0 1px 4px rgba(20,24,28,0.06), 0 4px 16px rgba(20,24,28,0.04);
  --aa-shadow-md: 0 2px 8px rgba(20,24,28,0.06), 0 12px 40px rgba(20,24,28,0.08);

  /* type */
  --aa-font-display: "Fraunces", serif;
  --aa-font-body:     "Inter", system-ui, sans-serif;

  /* container width for any new custom section/global block */
  --aa-container-max: 1200px; /* 1080–1280px depending on §1.3 style density */

  color-scheme: light; /* or dark — see §3.4 */
}
```

Naming convention is `--aa-<category>-<role>`, matching the `--aa-color-ink` / `--aa-space-24` examples already used in `ARCHITECTURE.md`. **Do not introduce a second prefix** (e.g. `--dx-*`, `--ui-*`) alongside `--aa-*` in the same project — that recreates the "second local token registry" `ARCHITECTURE.md` explicitly forbids.

**If the site already has real tokens under a different prefix:** extend that prefix, don't rename it to `--aa-*` and don't run two prefixes in parallel. The prefix above is illustrative, not mandatory.

**Container width:** a fluid container (`width: min(92%, var(--aa-container-max))`) applies only when you are building a genuinely new custom section (a new global block, a new page-editor page) — most module pages already have their layout fixed by the theme/system markup (see `SYSTEM-MARKUP.md`), and this token does not override that.

### 3.2 Contrast minimums (WCAG AA)

- Body text on background: **≥ 4.5:1**.
- Large text (24px+/bold 19px+) and UI component borders: **≥ 3:1**.
- Practical defaults that always pass without computing anything: near-black text on near-white/cream backgrounds, near-white text on navy/charcoal backgrounds. If an extracted brand color (§1.1) is used as body text on a light surface, sanity-check it's clearly darker than mid-grey — darken it rather than trust the raw extracted hex.

### 3.3 Deriving hover/tint variants: OKLCH + `color-mix()`

```css
:root {
  --aa-color-accent: #191925; /* HEX fallback first — always declared */
}
@supports (color: color-mix(in oklch, red, blue)) {
  :root {
    --aa-color-accent-hover: color-mix(in oklch, var(--aa-color-accent) 82%, black);
    --aa-color-accent-tint:  color-mix(in oklch, var(--aa-color-accent) 12%, white);
  }
}
```

The HEX line always ships first so non-supporting browsers keep a valid, tested color; `@supports` only *upgrades* it. This is the concrete mechanism behind the pattern several module references already gesture at (e.g. `USERS.md`'s `--tpl-accent-blue` shortcut, `SHOP.md`'s hover-color table) — use it instead of hand-picking a second hex per component.

### 3.4 `color-scheme`

Declare `color-scheme: light;` (or `dark;`) explicitly in the project `:root`, even without a light/dark toggle — otherwise form fields and scrollbars can silently invert under the visitor's OS dark mode and clash with the redesign's colors.

### 3.5 Component states

Define these once, in the token vocabulary from §3.1 — not invented ad hoc per module page.

| Component | Default | Hover | `:focus-visible` | Active | Disabled |
|---|---|---|---|---|---|
| Primary button | `--aa-color-accent` bg, `--aa-color-on-accent` text | `--aa-color-accent-hover` bg (§3.3) | 2px outline in `--aa-color-accent`, offset 2px | `translateY(0)`, no lift | `--aa-color-surface-2` bg, `--aa-color-ink-muted` text |
| Secondary/ghost button | transparent bg, `--aa-color-border` outline | `--aa-color-surface-2` bg fill | same outline rule as primary | border darkens slightly | `--aa-color-border` at 50% opacity |
| Text input / textarea | `--aa-color-surface` bg, `--aa-color-border` outline | border shifts to `--aa-color-ink-muted` | border → `--aa-color-accent`, ring via `box-shadow` (not color alone — matters for `forced-colors`) | — | `--aa-color-surface-2` bg |
| Card (blog/shop/forum) | `--aa-color-surface` bg, `--aa-shadow-sm` | `--aa-shadow-md` — shadow grows; add `:hover` per `SHOP.md`'s explicit requirement | outline on the card if the whole card is a link | — | — |

`SHOP.md` already mandates `:hover`/`:focus-visible` on every shop control — this table is the site-wide version of that same requirement, extended to every module, not just shop.

### 3.6 Cross-module consistency

The same button, card, and badge should look the same whether they appear on the blog, the shop, or the forum — unless a module has a deliberate, stated reason to diverge (e.g. Forum Glass in `EXAMPLES.md` is an intentional, scoped exception limited to `body.module-forum`). When a module CSS file (`20/9`, `9/10`, `25/8`, …) sets its own accent shortcut (`--tpl-accent-blue`, per `USERS.md`/`SHOP.md`), derive it from the same `--aa-color-accent`, don't invent an unrelated color per module.

---

## 4) Icons

**Default source: [Heroicons](https://heroicons.com/) Outline, 24×24.** Fallback sources if a needed glyph is missing, in order: [Lucide](https://lucide.dev/), then [Tabler Icons](https://tabler.io/icons/) (6000+ icons, same outline-on-a-24px-grid character, MIT license). Do not hand-draw new SVG path data for UI chrome — inconsistent stroke weight and optical alignment is one of the fastest ways a "redesign" still reads as amateur. Keep `stroke-width` consistent across every icon used on the same page, whichever library a given glyph came from.

**This includes existing patterns in `EXAMPLES.md`:** the auth-modal close icon and any other inline `<svg><path>` written by hand should be swapped for the equivalent Heroicons/Lucide/Tabler glyph, not treated as a special case because it's "just an X."

Inline SVG pattern (copy the glyph, keep this shell):

```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <!-- path(s) from Heroicons/Lucide/Tabler -->
</svg>
```

`stroke="currentColor"` means the icon inherits `color` from its CSS context — it follows the token system automatically.

**Exception:** the site's actual logo and favicon are brand identity, not UI chrome — extract and preserve them per §1.1, do not run them through an icon library.

**Forbidden:**
- emoji as UI icons;
- icon webfonts (Font Awesome CDN, etc.) — extra request, FOUC risk, harder to theme;
- icon-only buttons/links without an `aria-label` or visually-hidden text — several module references (`FORUM.md`'s hidden Reply/New-topic SVG images, `EXAMPLES.md`'s forum pill buttons) already replace stock icon images with text; keep that accessible-by-default pattern rather than reverting to icon-only.

---

## 5) Modern CSS toolbox

Several of these already appear ad hoc across the module references (`:has()` in the menu and forum patterns, `aspect-ratio` on blog card thumbnails, `@container` on the shop invoices filter). This section formalizes when to reach for each, so usage is consistent rather than one-off. **Two hard platform rules govern all of it:**

- **Nesting depth ≤ 3 levels**, everywhere. Deeper nesting is harder to scan and harder to hand-patch later via `patch_template`'s byte-exact matching.
- uCoz's own system CSS (compiled theme bundle, `.xw-*` modal selectors, `$NMENU_N$`/module-generated classes) is **not** wrapped in a cascade layer. Any selector overriding system markup must stay **outside `@layer`**, or in the highest-priority layer, and must be re-verified live after publishing (`ARCHITECTURE.md`'s CSS verification procedure) — not assumed from the source alone.

### 5.1 Mandatory

| Feature | Use it for |
|---|---|
| Custom Properties | the `:root` token contract in §3.1 |
| `clamp()` / `min()` / `max()` / `calc()` | fluid type (§2.1) and spacing (§2.2) instead of breakpoint jumps |
| `aspect-ratio` + responsive images (`loading="lazy"`, `decoding="async"`) | card thumbnails, product images, avatars — already used for blog cards in `EXAMPLES.md`; apply the same to shop and forum media |
| `:focus-visible` | every interactive element touched by the redesign — never remove the default focus ring without a replacement |
| `prefers-reduced-motion` | wraps every non-essential new animation (§6) |
| `text-wrap: balance` / `pretty` | §2.4 |
| `:where()` / `:is()` | grouping selectors without raising specificity — useful when two module-scoped rules (e.g. `body.module-shop` vs `body.module-forum`) would otherwise need duplicated selectors |
| `@supports` | the umbrella rule for every situational feature below |

### 5.2 Situational

| Feature | When | Caveat |
|---|---|---|
| CSS Nesting | always fine | max 3 levels (hard rule above) |
| `@layer` | organizing your own tokens/reset/components inside `3/3` | never for rules overriding system markup — see platform rule above |
| Container Queries | cards that render at different widths depending on sidebar/grid context — `SHOP.md`'s `@container invoices-filters` is the existing example | pairs with, doesn't replace, fixed-breakpoint checks at 320/375/768/1024 |
| `:has()` | already load-bearing in this skill's own patterns — the `#allEntries:has(table)` split, the empty-`<ul>` menu fix, form field states | keep the selector shallow (nesting rule); re-verify on a live page after publishing, since `:has()` mistakes fail silently rather than throwing |
| OKLCH + `color-mix()` | deriving hover/tint from `--aa-color-accent` (§3.3) | HEX declared first, always |
| `mask` / `clip-path` | one deliberate shape treatment (avatar mask, angled section divider) | spend it in exactly one place per redesign |
| `backdrop-filter` | glass treatments like Forum Glass Apple — **never** on an element that contains a `position: fixed` descendant (mobile menu, modal) — see `ARCHITECTURE.md`'s fixed-positioning containing-block trap |
| Popover API | simple disclosure widgets (FAQ accordion, cookie banner) — not the primary nav, which needs the existing menu JS hooks intact |
| `content-visibility: auto` + `contain-intrinsic-size` | heavy below-the-fold sections on long pages (forum thread archives, long FAQ) | set an intrinsic size or content jumps on scroll-into-view |
| `forced-colors` media query | one small block so buttons/borders stay visible under Windows High Contrast Mode | cheap accessibility win |
| Scroll-driven Animations (`animation-timeline`) | native reveal-on-scroll for a new hero-style page/global block | must be wrapped in `@supports`, must have a JS fallback, must respect `prefers-reduced-motion` |

### 5.3 Skip by default

- **`light-dark()` / full dark-mode toggle** — only if explicitly requested (§3.4).
- **Anchor Positioning API** — support still uneven; no redesign task here has a positioning need that justifies the risk.

---

## 6) Motion

**Spend boldness in one place per page, not on every card/section.** A blog card growing its shadow on `:hover` (already in `EXAMPLES.md`) is fine and expected — that is feedback on a person's action. A brand-new fade-and-slide-up entrance on every section of every module page is the generic-AI-redesign default; don't add it as a blanket pattern across a multi-module site redesign.

- **Scroll reveal**, if requested for a specific new page/hero: prefer native `animation-timeline` (§5.2) wrapped in `@supports`, with a plain "no fallback" state (content visible, no animation) rather than a hidden-by-default state for non-supporting browsers.
- **Hover/focus motion** (button lift, card shadow growth, icon nudge) is fine and expected everywhere already covered by existing patterns.
- **`prefers-reduced-motion` is mandatory** for any new non-essential animation:

```css
@media (prefers-reduced-motion: no-preference) {
  .reveal { opacity: 0; transform: translateY(16px); transition: opacity .6s, transform .6s; }
  .reveal.is-visible { opacity: 1; transform: none; }
}
```

Without the wrapper, `.reveal` should render in its final state by default — never permanently hidden for a user with reduced motion.

- **No mouse-parallax by default.** If explicitly requested, implement via a throttled (`requestAnimationFrame`) listener in the site's own script file — not a new external animation framework/CDN.

---

## 7) JS: extend the existing script, don't fork it

Every module reference that touches JS already assumes a single existing `main.js`/`script.js` with hooks the theme depends on (search overlay's delegated `.js-mb-search-btn` listener, the account-sidebar's idempotent initializer, the menu's exact DOM-nesting dependency). This section is about *how* to add new behavior into that reality, not a green-field JS baseline.

### 7.1 Rules specific to editing an existing site's script

- **Never add a second click/submit listener for something the theme already handles** — `SEARCH.md`'s note about not double-binding `.js-mb-search-btn` is the general case: read the existing script first, confirm what's already wired, and extend rather than duplicate. A duplicate listener causes double-toggling, double-submits, or silently cancels the platform's own behavior.
- **New initializers must be idempotent** — the account-sidebar pattern (`EXAMPLES.md`) exists because some legacy shells load the script twice; any new `init()` you add must tolerate being called more than once (guard with a `data-*` flag or a module-level boolean) rather than assuming single execution.
- **Defensive syntax around every new `querySelector`** (`?.`, `??`) — a block may legitimately not exist on every page/shell the script runs on:
  ```js
  document.querySelector('.nt-new-widget')?.addEventListener('click', handler);
  ```
- **`IntersectionObserver`** for any new scroll-triggered reveal, as the fallback path when `animation-timeline` isn't supported (§5.2/§6).
- **`requestAnimationFrame`** to throttle any new `scroll`/`resize`/`mousemove` handler — never run layout-affecting code on every raw event tick, especially on pages that already have the theme's own scroll listeners (search overlay, sticky headers, menu breakpoint logic).
- **`URLSearchParams`** for reading/building query strings (search, filters) instead of hand-parsing `location.search`.

### 7.2 Never touch

- Do not rewrite or duplicate the platform's native POST transport (`/index/sub/` for auth, `mail_*`-backed forms) with your own `fetch`/`FormData` unless the task is explicitly building a fully custom flow like the Auth Modal in `EXAMPLES.md` (which deliberately reimplements login/restore against the documented native endpoint) — for anything using `$MFORM_N$` mail forms, the platform's own submission handling stays untouched, same rule as in `ucoz-landing-skill`.
- Do not introduce ES Modules, a bundler, or a second script file loaded on the same pages as the existing one — one script, plain, global scope, same as it already is.

---

## 8) Imagery & photography direction

### 8.1 If new imagery is needed and no real photos exist

Most redesign briefs don't come with a fresh photoshoot. **Do not fill placeholders with generic stock clichés** — handshake close-ups, diverse-team fist-bumps, stock-smile portraits. These are the fastest "this is an AI-generated redesign" tell, faster than any CSS mistake, *because they're generic and interchangeable across any site*. A specific, well-chosen photo from a direct link (Unsplash, Pexels) that concretely fits the niche is a legitimate choice; the test is specificity, not source.

When no fitting photo exists at all, default instead to:

- An abstract composition from the token system (gradient blobs, geometric shapes via `mask`/`clip-path`, subtle grid/dot patterns).
- A large-scale icon/illustrative motif (oversized Heroicons/Lucide/Tabler glyph as decoration) rather than a photo-shaped placeholder.
- For Industrial/Enterprise Clean/SaaS Dark styles: a stat block or UI-mockup panel instead of a person photo.

### 8.2 Existing content images (blog covers, product photos, avatars)

- Do not invent new crop ratios or treatments for existing content images — `EXAMPLES.md`'s blog card pattern already fixes `aspect-ratio: 16/9` with `object-fit: cover` for covers; keep that consistent across every card-based module (shop products, forum thread previews) rather than introducing a different ratio per module.
- Gate on the existence variable (`$COVER_IMAGE$`, not `$COVER_URL$`, per `EXAMPLES.md`'s existing note) before rendering an `<img>` — missing-image placeholders are a common redesign regression when this gate is skipped.
- User avatars with no uploaded photo: an initials monogram (same visual treatment as the logo-fallback idea in landing work — a tinted tile with 1–2 letters) reads better than a generic silhouette placeholder.

---

## 9) Social proof & trust patterns

Relevant mainly for shop (reviews, ratings) and subscriptions (plan comparisons already covered in `SUBSCRIPTIONS.md`) — apply the same visual discipline used elsewhere in this file rather than ad hoc styling per request.

- **Ratings/reviews:** a row of icon stars (§4, `stroke`/`fill="currentColor"`) — never emoji stars.
- **Stat/counter blocks** (e.g. "1200+ orders fulfilled" on a shop homepage): large number in a type size between H1 and H2 (§2.1) + small `--aa-color-ink-muted` label. Animate only via `IntersectionObserver` on first scroll-into-view, and skip the animation entirely under `prefers-reduced-motion`.
- **Testimonial cards:** quote + attribution, real photo (§8.2 rules apply) or an initials monogram — never a generic silhouette avatar.

---

## 10) Accessibility & progressive enhancement checklist

Fold this into the core workflow's **Validate** step (Step 5) for any non-trivial visual change:

- [ ] `@supports` used for every situational CSS feature in §5.2 — nothing essential depends on a single code path.
- [ ] `:focus-visible` visible on every link, button, and form field touched by the redesign; no `outline: none` without a replacement.
- [ ] Tap targets ≥ 44×44px with ≥ 8px spacing between adjacent targets.
- [ ] Body text vs. background ≥ 4.5:1; large text/UI borders ≥ 3:1 (§3.2).
- [ ] `prefers-reduced-motion` respected — content is fully visible/usable with motion reduced, not stuck hidden.
- [ ] `color-scheme` declared in the project `:root`.
- [ ] CSS nesting nowhere exceeds 3 levels; no selector overriding system markup lives inside `@layer` (re-verified on the live page, per `ARCHITECTURE.md`).
- [ ] Icons are Heroicons/Lucide/Tabler inline SVG, `stroke="currentColor"` — no hand-drawn paths, no emoji, no icon webfont.
- [ ] No new `backdrop-filter`/`filter`/`transform` added to an ancestor of a `position: fixed` element (`ARCHITECTURE.md`'s containing-block trap).
- [ ] Any new hover/shadow/motion pattern reuses the token vocabulary in §3.1 rather than introducing fresh literal values.
- [ ] Cross-module consistency (§3.6) checked: the same component looks the same on every module unless a documented, scoped exception applies.

---

## 11) Visual "do not" summary

- Do not reach for the same default look (e.g. "dark navy + teal" or "glass everywhere") on every redesign regardless of the site's actual niche and existing identity — extract first (§1.1), then keep-and-systematize or replace deliberately (§1.2).
- Do not introduce a second CSS-variable prefix alongside an existing `--aa-*` (or equivalent) registry — extend the one that exists.
- Do not hand-draw new icon path data, including "it's just an X" cases — use Heroicons/Lucide/Tabler.
- Do not wrap system-markup-overriding selectors inside `@layer`.
- Do not nest CSS more than 3 levels deep.
- Do not add a second click/submit listener for behavior the theme's existing script already handles.
- Do not add `backdrop-filter`/`filter`/`transform` to any ancestor of a `position: fixed` element.
- Do not use generic stock-photo clichés when filling an imagery gap — use the abstract/icon-led defaults in §8.1.
- Do not ship a new animation without a `prefers-reduced-motion` fallback.
- Do not apply the same literal hex value in five different pattern sections without naming it as a token first — that recreates the exact inconsistency this file exists to close.
- Do not start editing CSS right after naming a direction — write the non-negotiables + must-not-look-like lists from §1.4 first, and check them against every module the redesign touches, not just the homepage.
