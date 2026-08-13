# uCoz system windows (`_uWnd` / `xw-*`)

Canonical rules for styling **platform popups and modals** injected by uCoz JS — not module HTML, not forum-specific.

Covers:

- Short toasts (`_uWnd.alert`, forum subscribe, “need login”, …)
- Full modal windows (mail-form **success** message, confirmations, …)
- Shared 9-slice chrome (`.xw-*`)

UI CSS samples → [../EXAMPLES.md](../EXAMPLES.md#uwnd-glass). Custom **login** UX (replace `_uWnd` login) → [../EXAMPLES.md](../EXAMPLES.md#auth-modal).

---

## Mental model

These windows are **not** in templates. `_uWnd` / related helpers append markup to `<body>` (outside `#layout`):

```html
<div id="_uwndTopN" style="position:fixed; z-index:…; left:…; top:…">
  <div id="_uwndWndN" class="xw-plain xw-active …">
    <!-- 9-slice frame + header / body / close -->
  </div>
</div>
```

Toast-style alerts may also get class `.uwnd-toast` on the top wrapper (layer7 tokens `--uwnd-toast-*`).

Do **not** restyle Control Panel windows: `.cp-modal`, `.cp-alert`.

---

## DOM map

| Piece | Role |
|---|---|
| `div[id^="_uwndTop"]` | Fixed wrapper; **inline** `left` / `top` / `z-index` / `opacity` — do not fight positioning |
| `div[id^="_uwndWnd"]` / `.xw-plain` | Window root |
| `.xw-tl` `.xw-tr` `.xw-tc` `.xw-ml` `.xw-mr` `.xw-mc` `.xw-bl` `.xw-br` `.xw-bc` | **9-slice** frame (legacy `background-image` borders) |
| `.xw-shadow` | Outer shadow/container (older skins) |
| `.xw-hdr` / `.xw-hdr-text` / `.xw-draggable` | Title bar |
| `.xw-body` / `.myWinCont` / `.myWinCont b` | Body text; `<b>` often holds the title line inside success text |
| `.myWinFooter` | Footer row inside the window |
| `.xt-close` / `.xt-close2` | Close control (default icon = `background-image`) |
| `.xw-sps` / `.xw-blank` / `.xw-disabled` | Spacer / placeholder / blocked overlay |
| `.uwnd-toast` | Compact toast skin (forum notices, short alerts) |

Modern toast layouts may set many inner parts to `display:contents` and paint only `.xw-tl` / `.xw-ml` / `.xw-bl` — always verify live DOM before assuming which nodes hold the fill.

---

## Where to put CSS

Follow [ARCHITECTURE.md](ARCHITECTURE.md). These popups appear on **any** page (forum, mail forms, shop, …), so styles must be **site-wide**.

| Priority | Place | When |
|---|---|---|
| 1 | One **FTP** stylesheet (e.g. `/css/uwnd.css`) linked from `1/AHEADER` or every full-page shell `<head>` | Preferred for redesigns; survives when `3/3` patches fail to persist |
| 2 | Site CSS `module 3 / template 3` | Default when `3/3` saves reliably — always re-`read_template` after save |
| 3 | Temporary `<style id="uwnd-…">` in `1/AHEADER` | **Recovery only** |

Forum shell `8/1` inline CSS is the wrong long-term home for `_uWnd` rules (forum-only pages would miss mail-form success modals, etc.).

Bump `?v=` on the FTP `<link>` after every upload.

---

## Full modal override (mail form success, etc.)

After adding / redesigning a page with `$MFORM_<ID>$`, **always** restyle the default success window so it matches the site design. The classic skin uses a **9-slice** `background-image` frame — if you only change `.xw-plain` fill, the old frame remains.

### Required zeroing

On all nine frame parts (`.xw-tl` … `.xw-bc`):

```css
background: none !important;
background-image: none !important;
border: none !important;
```

Also zero `background-image` on `.xw-hdr`, `.xt-close`, `.xt-close2`.

Hide spacers that only exist for the old frame: `.xw-sps { display: none !important; }`.

### Rebuild the chrome

1. Put border + radius + shadow on `.xw-shadow` and/or `.xw-plain` (depending on live markup).
2. Style `.xw-hdr` / `.xw-hdr-text`, `.xw-body`, `.myWinCont`, `.myWinCont b`, `.myWinFooter`.
3. Restyle close: `.xt-close` / `.xt-close2` — no sprite; e.g. `::before { content: "×"; }` or an inline SVG background you control.
4. Style `.xw-blank` / `.xw-disabled` if they show in your flows.
5. Match site tokens (color, type, radius, spacing). Do not rely on system inline styles.

### Scope

Prefer:

```css
div[id^="_uwndTop"]:not(.cp-modal):not(.cp-alert) { … }
```

so Control Panel chrome stays untouched.

---

## Short toasts (`.uwnd-toast` / `_uWnd.alert`)

Examples: forum “subscribed / unsubscribed”, “authorization required”, validation tips.

1. Paint glass / fill on the strips that actually show background (often `.xw-tl`, `.xw-ml`, `.xw-bl`) — not only `.xw-plain`.
2. You may tune layer7 tokens: `--uwnd-toast-radius`, `--uwnd-toast-padding`.
3. Typography: `.xw-hdr-text`, `.myWinCont`.
4. Still do not override inline `left` / `top` on `_uwndTop*`.

---

## Hard rules

1. **Site-wide CSS path** — FTP or `3/3`, not forum-only shells, for anything that also affects mail / shop / index.
2. **Verify persistence** — after `3/3` `patch_template`, re-`read_template`; if empty, fall back to FTP.
3. **9-slice** — for full modals, zero `background-image` on the nine frame parts or the stock frame stays.
4. **Close buttons** — both `.xt-close` and `.xt-close2`; default art is CSS background.
5. **No CP restyle** — skip `.cp-modal` / `.cp-alert`.
6. **`backdrop-filter`** — windows sit on `<body>`; do not nest `position:fixed` UI inside an element that already has `backdrop-filter` ([ARCHITECTURE.md](ARCHITECTURE.md)).
7. **Login** — restyling the native login `_uWnd` is fragile; prefer a custom auth modal ([../EXAMPLES.md](../EXAMPLES.md#auth-modal)).
8. After mail-form work: success modal override is **mandatory** in the redesign checklist (same expectation as the landing playbook).

---

## Checklist (any redesign that shows `_uWnd`)

- [ ] CSS lives in FTP shared file or verified `3/3` (not only a module shell)
- [ ] Full modal: 9-slice images zeroed (`.xw-tl` … `.xw-bc`)
- [ ] `.xw-sps` hidden; `.xw-hdr` / body / footer / `.myWinCont` / `.myWinCont b` styled
- [ ] `.xt-close` + `.xt-close2` restyled without stock sprites
- [ ] Toasts (`.uwnd-toast`) checked on a live action (subscribe, alert, …)
- [ ] Mail form submit shows a branded success window
- [ ] `.cp-modal` / `.cp-alert` unchanged
- [ ] FTP `?v=` bumped after upload

---

## Related

- [ARCHITECTURE.md](ARCHITECTURE.md) — CSS placement / verification
- [TOOLS.md](TOOLS.md) — mail forms / `$MFORM_*$`
- [FORUM.md](FORUM.md) — forum chrome (not the home for `_uWnd` CSS)
- [../EXAMPLES.md](../EXAMPLES.md#uwnd-glass) — glass CSS sample
- [../EXAMPLES.md](../EXAMPLES.md#auth-modal) — replace login `_uWnd`
