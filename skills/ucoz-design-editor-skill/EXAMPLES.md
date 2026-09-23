# Implementation patterns

Ready-to-adapt UI/JS/CSS patterns from real uCoz redesign work.
Platform rules live in `references/`; use this file when implementing optional UI features
(auth modal, card grids, shop tokens, dashboard shells) so you do not repeat known pitfalls.

**Reading the CSS below:** literal hex values here (`#191925`, `#2a2a38`, `#e8eaed`, `#9ca3af`, …)
are illustrative defaults for a specific example project, kept consistent across sections on
purpose (ink / ink-hover / border / muted). Before pasting a pattern into a live project, resolve
these through that project's own token contract — declare them once in the canonical `:root`
(`ARCHITECTURE.md`'s token workflow, values and naming per `VISUAL.md` §3.1) and reference them
via `var(--aa-color-…)`, rather than copying the literal hex into a second, undeclared spot.

---

## Table of contents

- [Auth modal](#auth-modal)
- [Account dashboard shell](#account-dashboard-shell)
- [Content cards and allEntries CSS](#content-cards-and-allentries-css)
- [Shop patterns](#shop-patterns)
- [Search overlay](#search-overlay)
- [Menu CSS pitfalls](#menu-css-pitfalls)
- [Subscriptions and articles UI](#subscriptions-and-articles-ui)
- [Forum glass Apple](#forum-glass-apple)
- [uWnd system popups glass](#uwnd-glass)

---

## Auth modal

Instead of relying on uCoz's native `_uWnd` login popup (which is hard to style and sandboxed),
implement a fully custom modal directly in `AHEADER`.

### Trigger links

Replace every `href="$LOGIN_LINK$"` — both in the desktop user-popover and in the mobile drawer —
with:

```html
href="#" data-auth-open="login"
```

A single delegated listener catches all `[data-auth-open]` elements. Also override
`window.loginPopupForm` so any uCoz-rendered link with `onclick="loginPopupForm()"` opens the
same modal:

```javascript
window.loginPopupForm = function () { openModal('login'); };
```

### Modal HTML

Add at the very end of `AHEADER`, after all existing `</script>` tags:

```html
<div id="nt-auth-modal" class="nt-modal-overlay"
     aria-hidden="true" role="dialog" aria-modal="true"
     aria-labelledby="nt-modal-title">
  <div class="nt-modal">
    <div class="nt-modal-head">
      <span class="nt-modal-title" id="nt-modal-title">Sign in</span>
      <button class="nt-modal-close" id="nt-modal-close" type="button" aria-label="Close">
        <!-- Heroicons "x-mark" outline, 24x24 — see VISUAL.md §4 (no hand-drawn icon paths) -->
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="nt-modal-body" id="nt-modal-body"></div>
  </div>
</div>
```

The `#nt-modal-body` is empty — forms are injected dynamically via `innerHTML` in JS.

### POST transport (`/index/sub/`)

This is uCoz's native session endpoint. Use `credentials: 'same-origin'`.

**Login** (`a=2`):

```
POST /index/sub/
a=2&ajax=1&rnd=515&user=<login>&password=<pass>&rem=0
```

`rem=1` means "remember me". Detect wrong credentials by checking whether the response body
contains the word for "password" (`пароль`) — uCoz's legacy error-heuristic.  
After a successful login, redirect to `/index/8` (the personal cabinet); do **not** reload the
current page.

**Password restore** (`a=6`):

```
POST /index/sub/
a=6&ajax=1&t=1&s=<login-or-email>
```

**Registration** — link to `/index/3` (uCoz system page) rather than a custom form, unless uAPI
registration is explicitly needed.

### JS outline (delegated listener + modal logic)

```javascript
(function () {
  // cached refs
  var overlay = document.getElementById('nt-auth-modal');
  var body    = document.getElementById('nt-modal-body');
  var closeBtn= document.getElementById('nt-modal-close');

  // Override platform hook
  window.loginPopupForm = function () { openModal('login'); };

  function openModal(mode) {
    body.innerHTML = mode === 'restore' ? buildRestoreForm() : buildLoginForm();
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('freeze');
    body.querySelector('input[name=user], input[name=s]').focus();
  }

  function closeModal() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('freeze');
  }

  // Delegated trigger
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-auth-open]');
    if (t) { e.preventDefault(); openModal(t.dataset.authOpen); }
  });

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  function buildLoginForm() {
    return '<form class="nt-auth-form" id="nt-login-form">'
      + '<input type="text"     name="user"     placeholder="Login" autocomplete="username">'
      + '<input type="password" name="password" placeholder="Password" autocomplete="current-password">'
      + '<label><input type="checkbox" name="rem" value="1"> Remember me</label>'
      + '<button type="submit">Sign in</button>'
      + '<a href="#" data-auth-open="restore" class="nt-auth-link">Forgot password?</a>'
      + '<a href="/index/3" class="nt-auth-link">Create account</a>'
      + '</form>';
  }

  function buildRestoreForm() {
    return '<form class="nt-auth-form" id="nt-restore-form">'
      + '<input type="text" name="s" placeholder="Login or email" autocomplete="username">'
      + '<button type="submit">Send reset link</button>'
      + '<a href="#" data-auth-open="login" class="nt-auth-link">Back to sign in</a>'
      + '</form>';
  }

  // Form submit handler
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.id !== 'nt-login-form' && form.id !== 'nt-restore-form') return;
    e.preventDefault();
    var data = new URLSearchParams(new FormData(form));
    if (form.id === 'nt-login-form') {
      data.set('a', '2'); data.set('ajax', '1'); data.set('rnd', '515');
    } else {
      data.set('a', '6'); data.set('ajax', '1'); data.set('t', '1');
    }
    fetch('/index/sub/', { method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: data.toString()
    }).then(function (r) { return r.text(); }).then(function (txt) {
      if (form.id === 'nt-login-form') {
        if (txt.indexOf('\u043f\u0430\u0440\u043e\u043b\u044c') !== -1) {
          // "пароль" in response → wrong credentials
          body.querySelector('.nt-auth-error') || body.insertAdjacentHTML(
            'afterbegin', '<p class="nt-auth-error">Incorrect login or password.</p>');
        } else {
          window.location.href = '/index/8';
        }
      } else {
        body.innerHTML = '<p class="nt-auth-success">Check your email for a reset link.</p>';
      }
    });
  });
}());
```

### Shells without AHEADER must copy the modal

Full-page shells that do **not** include `$GLOBAL_AHEADER$` must embed the same `#nt-auth-modal`
markup and load `/js/script.js` before their closing `</body>` tag. Affected templates include:

- `4/2` (registration page)
- `4/3` (access denied)
- `25/4`, `25/5` (subscription checkout / success)
- `10/1`–`10/6` (articles catalog shells)

### CSS (in `module 3 / template 3`)

```css
/* ===== NT AUTH MODAL ===== */
.nt-modal-overlay{
  align-items:center;background:rgba(9,9,16,.6);display:flex;inset:0;
  justify-content:center;opacity:0;pointer-events:none;
  position:fixed;transition:opacity .22s;z-index:99999;
}
.nt-modal-overlay.is-open{opacity:1;pointer-events:auto}

/* ⚠️ border and outline MUST be !important — global reset can inject them */
.nt-modal{
  background:#fff;border:none!important;border-radius:20px;
  max-width:420px;outline:none!important;overflow:hidden;
  transform:translateY(12px);transition:transform .22s;width:calc(100% - 32px);
}
.nt-modal-overlay.is-open .nt-modal{transform:translateY(0)}

.nt-modal-head{
  align-items:center;background:#191925;display:flex;
  justify-content:space-between;padding:16px 20px;
}
.nt-modal-title{color:#fff;font:600 16px/1 inherit}
.nt-modal-close{
  appearance:none;background:transparent;border:0;color:#fff;
  cursor:pointer;line-height:1;opacity:.7;padding:4px;
}
.nt-modal-close svg{display:block;height:20px;width:20px} /* explicit size — SVG has no width/height attrs */
.nt-modal-close:hover{opacity:1}
.nt-modal-body{padding:24px 24px 28px}

.nt-auth-form{display:flex;flex-direction:column;gap:12px}
.nt-auth-form input[type=text],
.nt-auth-form input[type=password]{
  border:1px solid #d1d5db;border-radius:8px;font:inherit;
  outline:none;padding:10px 14px;width:100%;
}
.nt-auth-form input:focus{border-color:#191925}
.nt-auth-form button[type=submit]{
  background:#191925;border:0;border-radius:8px;color:#fff;
  cursor:pointer;font:600 15px/1 inherit;padding:12px;
}
.nt-auth-form button:hover{background:#2a2a38}
.nt-auth-link{color:#6b7280;font-size:13px;text-align:center;text-decoration:none}
.nt-auth-link:hover{color:#191925}
.nt-auth-error{color:#c0392b;font-size:13px;margin:0}
.nt-auth-success{color:#16a34a;font-size:13px;margin:0}

body.freeze{overflow:hidden}
```

---

## Account dashboard shell

Used for the user profile page (`4/5`), all other user cabinet pages (`4/6`, `4/7`, `4/8`,
`4/11`), and subscription dashboard shells (`25/4`–`25/7`).

### Required shell structure

```html
<div id="layout" class="aa-account-layout">
  $GLOBAL_CLEFTER$
  <main class="aa-account-main">
    <header class="aa-account-topbar">
      <!-- topbar content, $LOGOUT_LINK$ on the right -->
    </header>
    <div class="aa-account-content">
      $BODY$
    </div>
  </main>
</div>
```

**Do not** add `$GLOBAL_AHEADER$` or `$GLOBAL_BFOOTER$` inside dashboard shells. Keep
`$AJAX_JS$`, `/.s/t/2301/main.js`, and `$POWERED_BY$` in their expected positions.

### No AHEADER on cabinet pages

`AHEADER` breaks the dashboard grid and duplicates navigation. On cabinet pages the sidebar
(`CLEFTER`) owns navigation. The footer is added **after** `#layout`:

```html
</div><!-- /#layout -->
$GLOBAL_BFOOTER$
```

### CSS isolation — do not apply the grid to plain `#layout`

```css
/* Sidebar hidden everywhere by default */
.aa-account-sidebar { display: none !important; }

/* Grid activates only when the account-layout modifier is present */
#layout.aa-account-layout {
  display: grid !important;
  grid-template-columns: 276px minmax(0, 1fr) !important;
}

/* Show sidebar only inside the account layout */
#layout.aa-account-layout > .aa-account-sidebar {
  display: flex !important;
}
```

Setting the grid on plain `#layout` breaks system pages where uCoz also inserts `$GLOBAL_CLEFTER$`.
After any CSS change upload the file via FTP and bump the `?v=` query string in both `1/AHEADER`
and the direct CSS link in `4/5`.

### CLEFTER — sidebar navigation rules

`1/CLEFTER` outputs `.aa-account-sidebar` with the cabinet navigation list.

- Do **not** wrap it in `<?if($_IS_OWN_PROFILE$)?>` — that variable is unavailable in global block
  context, so the sidebar disappears entirely.
- Active link is assigned by `/js/script.js` from `window.location.pathname`. Use exact matches
  for `/profile`, `/subscriptions/my`, `/subscriptions`, `/publ`. Do **not** hard-code
  `.is-active` in the block markup.
- Do **not** use a CSS selector like `[href="/subscriptions/my"]` to force an active state inside
  subscription shells — it conflicts with URL-based state on `/subscriptions` and produces two
  white items. Target `.is-active` only.

### Mobile sidebar (off-canvas drawer)

At `max-width:900px` the sidebar becomes an off-canvas drawer with a scrim. It closes on link
click, scrim click, `Escape`, and viewport expansion. The sidebar initializer in `/js/script.js`
must be idempotent because some legacy shells also load `script.js`.

Required elements in `CLEFTER`:

```html
<aside id="aa-account-sidebar" class="aa-account-sidebar">
  <!-- nav items -->
</aside>
<button data-aa-sidebar-toggle aria-label="Menu"></button>
<div data-aa-sidebar-scrim></div>
```

### Scroll rules summary

On desktop (`min-width: 901px`), `#layout`, sidebar, and `.aa-account-main` form a fixed
`100vh` stack. Only `.aa-account-content` scrolls. On mobile, remove all fixed heights.

For the subscriptions pricing page (`25/1`) specifically, the sidebar uses `position: sticky`
with `height: 100vh` so the help/FAQ nav item stays anchored at the bottom while the pricing
cards scroll past. Templates `25/4`–`25/7` use the fixed-height scrolling `.aa-account-content`
pattern, not the pricing variant.

---

## Content cards and allEntries CSS

### Card HTML (`7/4` — article card view fragment)

```html
<article class="blog-card">
  <?if($COVER_IMAGE$)?>
  <a class="blog-card-img-wrap" href="$ENTRY_URL$">
    <img class="blog-card-img" src="$COVER_SMALL_URL$" alt="$TITLE$"
         loading="lazy" width="400" height="225">
  </a>
  <?endif?>
  <div class="blog-card-body">
    <div class="blog-card-meta">
      <?if($CATEGORY_NAME$)?>
      <a class="blog-card-cat" href="$CATEGORY_URL$">$CATEGORY_NAME$</a>
      <span class="blog-card-sep" aria-hidden="true">•</span>
      <?endif?>
      <span class="blog-card-date">$DATE$</span>
    </div>
    <h3 class="blog-card-title"><a href="$ENTRY_URL$">$TITLE$</a></h3>
    <?if($MODER_PANEL$)?><div class="blog-card-moder">$MODER_PANEL$</div><?endif?>
  </div>
</article>
```

Gate on `$COVER_IMAGE$`, not `$COVER_URL$` — the URL string may be truthy even when no image
exists. Use `$COVER_SMALL_URL$` for card thumbnails; use `$COVER_URL$` only for full-width heroes.

### Shell wrapper (`7/1`)

```html
<div class="blog-grid entries-grid">$BODY$</div>
```

The `entries-grid` class suppresses the theme's `margin-block` rule that adds huge spacing between
adjacent `[id^=entryID]` siblings when no `.entries-grid` descendant is present:

```css
/* theme rule we are suppressing by adding entries-grid */
.layout:where(:not(:has(.entries-grid,.ss-entries-grid))) [id^=entryID]+[id^=entryID] {
  margin-block: var(--tpl-spacing-3xl);
}
```

### The full `#allEntries` / `td.archiveEntries` CSS split

This is the most critical pattern in this section. **Do not skip it.**

On the main blog page (`7/1`), uCoz renders:

```html
<div id="allEntries">
  <div id="entryID2"><article class="blog-card">…</article></div>
  <div id="entryID1"><article class="blog-card">…</article></div>
  <div class="catPages1">…</div>
</div>
```

On category / archive pages (`7/2`), uCoz wraps everything in a `<table>`:

```html
<div id="allEntries">
  <table class="catAllEntries">
    <tr>
      <td class="archiveEntries">
        <div id="entryID12">…</div>
        <div id="entryID11">…</div>
        <div class="catPages1">…</div>
      </td>
    </tr>
  </table>
</div>
```

If you apply `display:grid` directly on `#allEntries` site-wide, on category pages `#allEntries`
has exactly **one child** (`<table>`), so the grid squeezes that one child into 1 of 3 columns —
cards render at roughly 1/3 viewport width.

**The fix — split the rule with `:has()`:**

```css
/* ===== BLOG GRID ===== */

/* Container reset — grid lives on #allEntries, not .blog-grid */
.blog-grid { display: block; }

/* Main page: #allEntries has no <table>, so grid it directly */
#allEntries:not(:has(table)) {
  display: grid !important;
  gap: 28px !important;
  grid-template-columns: repeat(3, 1fr) !important;
  margin-top: 0 !important;
}

/* Category / archive pages: disable grid on #allEntries; let td handle it */
#allEntries:has(table) { display: block !important; }

/* Reset table ancestors so their layout algorithm doesn't override td width */
table.catAllEntries,
table.catAllEntries > tbody,
table.catAllEntries > tbody > tr {
  display: block !important;
  width: 100% !important;
}

/* The actual grid parent on category/archive pages */
td.archiveEntries {
  display: grid !important;
  gap: 28px !important;
  grid-template-columns: repeat(3, 1fr) !important;
  width: 100% !important;
  box-sizing: border-box !important;
}

td.archiveEntries [id^='entryID'] { min-width: 0; }
td.archiveEntries .catPages1 { grid-column: 1 / -1; text-align: center; }

/* Prevent grid blowout on main page too */
#allEntries:not(:has(table)) [id^="entryID"] { min-width: 0; }
#allEntries:not(:has(table)) .catPages1 { grid-column: 1 / -1; text-align: center; }
```

**Repeat both sides of the split at every responsive breakpoint.** A partial fix (e.g. only
updating `#allEntries` at `max-width:640px` while forgetting `td.archiveEntries`) re-introduces
the narrow-cards bug at that breakpoint.

```css
@media (max-width: 1024px) {
  #allEntries:not(:has(table)) { grid-template-columns: 1fr 1fr !important; }
  td.archiveEntries           { grid-template-columns: 1fr 1fr !important; }
}
@media (max-width: 640px) {
  #allEntries:not(:has(table)) { grid-template-columns: 1fr !important; }
  td.archiveEntries           { grid-template-columns: 1fr !important; }
}
```

### Card CSS

```css
.blog-card {
  background: #fff;
  border: 1px solid #e8eaed;
  border-radius: 16px;
  overflow: hidden;
  transition: box-shadow .25s;
}
.blog-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.09); }

.blog-card-img-wrap { aspect-ratio: 16/9; display: block; overflow: hidden; }
.blog-card-img { height: 100%; object-fit: cover; transition: transform .35s ease; width: 100%; }
.blog-card:hover .blog-card-img { transform: scale(1.04); }

.blog-card-body { padding: 20px 24px 28px; }
.blog-card-meta { align-items: center; display: flex; flex-wrap: wrap; gap: 6px 8px; margin-bottom: 12px; }
.blog-card-cat  { color: #191925; font: 700 11px/1 inherit; letter-spacing: .1em;
                  text-decoration: none; text-transform: uppercase; }
.blog-card-cat:hover { opacity: .7; }
.blog-card-sep  { color: #c9cdd3; font-size: 11px; }
.blog-card-date { color: #9ca3af; font-size: 13px; }
.blog-card-title { font: 700 19px/1.35 inherit; margin: 0; }
.blog-card-title a { color: #191925; text-decoration: none; }
.blog-card-title a:hover { opacity: .75; }
.blog-card-moder { margin-top: 10px; }
```

### Editor "add entry" link — remove bracket wrapper

The stock uCoz string wraps the add link in literal `[ ]` characters. Strip them and style the
`<a>` as a button:

```html
<?if($ADD_ENTRY_LINK$)?>
<a class="blog-add-link" href="$ADD_ENTRY_LINK$">Add article</a>
<?endif?>
```

```css
.blog-add-link {
  background: #191925; border-radius: 8px; color: #fff;
  padding: 10px 20px; text-decoration: none;
}
```

---

## Shop patterns

### Theme tokens (`20/9`)

Prefer CSS variables over per-button overrides when the theme already uses `var(--tpl-accent-blue)`:

```css
/*--- Shop theme tokens (module 20 pages) ---*/
body.module-shop {
  --tpl-accent-blue: #191925;
  --tpl-accent-blue-dark: #2a2a38;
}
```

Scope to `body.module-shop` only — `/_st/shop.css` loads exclusively on module-20 pages; do not
recolor other modules from here.

### Breadcrumbs — unified `nt-breadcrumbs` on all shop templates

Every redesigned shop template (`20/1`, `20/2`, `20/4`, `20/5`, `20/10`, `20/11`) must use the
same class and pattern. Never mix patterns across pages, never use raw `$SHOP_PATH$`, and never
create page-specific breadcrumb classes.

```html
<nav class="breadcrumbs nt-breadcrumbs" aria-label="Breadcrumb">
  <a href="$HOME_PAGE_LINK$">Home</a><span class="breadcrumb-sep">/</span>
  <a href="/shop">Catalog</a><span class="breadcrumb-sep">/</span>
  <!-- optional category/parent links -->
  <span class="breadcrumb-curr" aria-current="page">CURRENT PAGE TITLE</span>
</nav>
```

CSS — single block in `20/9` only, `!important` required to beat theme `2301.css` rules that
shrink link size and dim the current-page segment:

```css
/*--- Shop breadcrumbs (ALL templates 20/*) ---*/
body.module-shop .nt-breadcrumbs {
  align-items: center; color: #9a9da7; display: flex; flex-wrap: wrap;
  font-size: 16px !important; gap: 0; line-height: 1.5; margin: 0 0 20px;
}
body.module-shop .layout .nt-breadcrumbs a,
body.module-shop .nt-breadcrumbs a {
  color: #9a9da7 !important; font-size: 16px !important; font-weight: 400 !important;
  line-height: 1.5 !important; opacity: 1 !important; text-decoration: none;
}
body.module-shop .nt-breadcrumbs a:hover { color: #191925 !important; }
body.module-shop .nt-breadcrumbs .breadcrumb-sep {
  color: #c4c8d0 !important; font-size: 16px !important;
  line-height: 1.5 !important; margin: 0 10px; opacity: 1 !important;
}
body.module-shop .nt-breadcrumbs .breadcrumb-curr {
  color: #6b7280 !important; font-size: 16px !important;
  font-weight: 400 !important; line-height: 1.5 !important; opacity: 1 !important;
}
```

### Mobile `.goods-list` override

uCoz injects inline styles on informer wrappers, e.g.
`style="display:grid;gap:20px;grid-template-columns:repeat(5,232px)"`. On mobile this forces five
fixed 232px columns and overflows the viewport. Theme CSS loses to the inline rule.

Fix in `20/9` — switch to flex+wrap on mobile only:

```css
@media screen and (max-width: 767px) {
  body.module-shop :is(.goods-box, .products-box) .goods-list {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 12px !important;
    overflow: visible !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  body.module-shop :is(.goods-box, .products-box) .goods-list > :is(.list-item, .product-card) {
    flex: 1 1 calc(50% - 6px) !important;
    max-width: calc(50% - 6px) !important;
    min-width: 0 !important;
  }
}
@media screen and (max-width: 420px) {
  body.module-shop :is(.goods-box, .products-box) .goods-list > :is(.list-item, .product-card) {
    flex: 1 1 100% !important;
    max-width: 100% !important;
  }
}
```

Do **not** wrap `$TOP_SOLD$` / `$TOP_VIEW$` / `$LAST_ADD$` in your own `<div class="goods-list">`.
These informers already output their own `.goods-list` wrapper with inline grid styles. Adding
another `.goods-list` nests two grids; the outer `auto-fill` creates several ~236px tracks but has
only **one** grid item (the inner wrapper), collapsing the real product grid to a single narrow
column.

Correct usage:

```html
<!-- Correct: outer wrapper is NOT .goods-list -->
<div class="info-box goods-box">
  $TOP_VIEW$
</div>

<!-- Wrong: double-nests .goods-list -->
<div class="info-box goods-box">
  <div class="goods-list">$TOP_VIEW$</div>
</div>
```

### `toggle-btn` selector note (invoices page)

On the invoices page (`$PAGE_ID$='invoices'`), theme `2301.css` provides `.toggle-btn` for a
mobile filter toggle whose label text comes from
`.toggle-btn-text:before { content: attr(data-open-text) }`.

When writing black-button rules, **never** use a bare `button` selector:

```css
/* Wrong — paints .toggle-btn black and erases its label */
button { background: #191925; color: #fff; }

/* Correct — target only primary action buttons */
button:not(.toggle-btn) { background: #191925; color: #fff; }
```

Also do not force `display: inline-flex !important` on `.toggle-btn` — that overrides theme
`display: none` on desktop. Control `.toggle-btn` visibility only inside the container query:
`@container invoices-filters (width < 480px)`.

### Checkout grid — brief concept

Layout for `20/5` (`$PAGE_ID$='checkout'`):

1. Cart table stays in `$BODY$` above the grid, wrapped in `.nt-checkout-cart`.
2. Below it: two-column `.nt-checkout-grid` — left column: `$PAYMENT_LIST$` + `$DELIVERY_LIST$`;
   right column: `$ORDER_FIELDS$`, order summary, `$ORDER_BUTTON$`.
3. Keep `class="layout checkout-layout"` on `#layout` — theme `2301.css` has scoped
   `.checkout-layout` rules for `#order-table` and `.methods-list`.
4. Use `body.module-shop .checkout-layout …` selectors, never `body.module-shop.checkout-layout`
   (the layout class is on `#layout`, not `<body>`).

---

## Search overlay

### AHEADER markup contract

Theme `main.js` depends on these IDs and classes — do not rename them:

| Element | Role |
|---|---|
| `#header_mb_search.header-search` | Root wrapper |
| `.js-mb-search-btn` | Toggle button — `main.js` toggles `.open` on the panel |
| `#header_mb_search_panel.header-search-panel` | Full-screen overlay root |
| `.js-header-search-overlay` | Dimmed backdrop — click closes the panel |
| `.header-search-drawer` | Dark bar that slides down from the top |
| `.js-mb-search-input` | Query field — auto-focused by `main.js` on open |
| `.js-header-search-close` | Close button (handled by custom script in `AHEADER`) |

Form: `method="get"`, `action="/search/"`, hidden `name="t" value="0"`, field `name="q"`.

Do **not** add a second click handler on `.js-mb-search-btn` — `main.js` already toggles `.open`.
A duplicate listener would double-toggle and immediately close the drawer.

### Shell pattern (`19/1`)

```html
<html ...>
<head>
  <link rel="stylesheet" href="/_st/my.css?v=1" />
  <!-- Manrope font, /_st/shop.css (manual link — module 19 does not auto-load it), $AJAX_JS$ -->
</head>
<body class="module-search search-page">
<div id="layout" class="layout search-layout">
  $GLOBAL_AHEADER$
  <div class="main">
    <div class="container">
      <h1>Site search</h1>
      <div class="search-page-form">$SEARCHFORM$</div>
      <?if($SEARCH_QUERY$)?>
        <p class="search-summary">
          Showing $NUM_SHOWN$ of $NUM_ENTRIES$ results for «$SEARCH_QUERY$»
        </p>
        $BODY$
        $PAGE_SELECTOR$
      <?else?>
        <p>Enter a query above.</p>
      <?endif?>
    </div>
  </div>
  $GLOBAL_BFOOTER$
</div>
<?if(0)?>$POWERED_BY$<?endif?>
</body>
</html>
```

No sidebar — remove `CLEFTER` / `#sidebar` entirely (not just `display:none`).

### Result card (`19/2`) — not `eBlock`

```html
<div class="search-result-card">
  <?if($THUMB$)?>
  <a class="search-result-thumb" href="$ENTRY_URL$">
    <img src="$SMALL$" alt="$TITLE$" loading="lazy">
  </a>
  <?endif?>
  <div class="search-result-body">
    <a class="search-result-title" href="$ENTRY_URL$">$TITLE$</a>
    <p class="search-result-snippet">$MESSAGE$</p>
    <span class="search-result-meta">$ENTRY_DEPTH$ · $SAVING_DATE$</span>
  </div>
</div>
```

Use `.search-result-card`, not the legacy `<table class="eBlock">` pattern.

### Desktop visibility

Theme default hides `.header-search` on desktop. Enable in `20/9`:

```css
@media screen and (min-width: 1025px) {
  .header-search { display: block; position: relative; }
  .header-search .header-mobile-button { display: flex; }
}
```

Do **not** re-add the old absolute 280px dropdown — the full-screen overlay CSS replaces it.

### CSS injection order trap

uCoz injects `base.min.css` / `layer7.min.css` **after** `/_st/shop.css` in `<head>`, so theme
`[type=submit] { background: var(--tpl-accent-blue) }` can beat shop.css rules without
`!important`. Fix: place an inline `<style>` at the **end of the `19/1` `<body>`** (after platform
CSS in document order) with `!important` on field/button overrides.

---

## Menu CSS pitfalls

### 1. Empty `<ul></ul>` on every menu item

The platform adds `uWithSubmenu` class **and** an empty child `<ul></ul>` to every top-level
item, even when there are no real children.

```css
/* Hide dropdown arrow when no real children exist */
.main-menu .uMenuRoot > li:not(:has(> ul > li)) > .menu-submenu-btn {
  display: none !important;
}

/* Hide empty dropdown panel */
ul:not(:has(> li)) {
  display: none !important;
}

/* Style dropdown panel only when it has real items */
@media screen and (min-width: 1025px) {
  .main-menu .uMenuRoot > li > ul:has(> li) {
    /* dropdown styles here */
    background: #191925;
    border-radius: 8px;
    min-width: 220px;
    padding: 8px 0;
  }
}
```

### 2. Desktop dropdown CSS — scope to `min-width: 1025px` only

Dropdown positioning rules must live **only** inside:

```css
@media screen and (min-width: 1025px) { … }
```

Unscoped or `max-width: 1024px` dropdown rules break the mobile slide-navigation (submenus
slide in from the right via `position: absolute; left: 100%` on `li > ul`).

Never do this:

```css
/* WRONG — breaks mobile slide-nav */
.main-menu-nav li ul { display: block; position: static; }
```

### 3. Mobile drawer spacing

Theme 2301 sets `.menubar` (inside `max-width: 1024px`) to
`justify-content: space-between; height: 100%` and adds `margin-bottom: auto` on `.menubar-block`
with `.user-box`. This creates huge empty gaps between nav / auth / social sections.

Fix in `AHEADER` inline `<style>`, inside `@media (max-width: 1024px)`:

```css
.menubar { justify-content: flex-start !important; overflow-y: auto !important; }
:where(.menubar-block:has(> .user-box)) { margin-bottom: 0 !important; margin-top: 0 !important; }
.main-menu-nav { flex: 0 0 auto !important; }
```

Blocks then stack compactly from the top; long menus scroll inside `.menubar`.

### 4. Do not move `.main-menu` in the HTML

Mobile menu JS depends on exact nesting:

```
.header-left > .main-menu.menu-evolving > .menubar > .main-menu-nav > …
```

To center the nav on desktop without touching the DOM:

```css
@media screen and (min-width: 1025px) {
  .header-container { position: relative; }
  .header-left .main-menu {
    left: 50%; position: absolute; top: 50%;
    transform: translate(-50%, -50%);
  }
}
```

### 5. Where to put menu CSS

| Scope | Where |
|---|---|
| Desktop dropdown + mobile drawer overrides | `AHEADER` inline `<style>` — inside the correct `@media`, must not leak |
| Site-wide header chrome (top bar, social icons) | `module 3 / template 3` (`3/3`) |

---

## Subscriptions and articles UI

These are optional UI patterns — platform constraints for the subscriptions module live in
[references/SUBSCRIPTIONS.md](references/SUBSCRIPTIONS.md).

### Pricing / dashboard layout notes

The subscriptions module (`module_id=25`) uses the same `aa-account-layout` dashboard shell as
the user profile. Key layout differences:

- **`25/1` (pricing page):** natural page height, no fixed-height scroll container. The sidebar
  uses `position: sticky; height: 100vh` so the bottom nav/FAQ item is always visible.
- **`25/4`–`25/7` (checkout, my subscriptions, payment history):** fixed `100dvh` stack;
  only `.aa-account-content` scrolls.

Scrollbar inside `.aa-account-content` should be thin and light:

```css
.aa-account-content::-webkit-scrollbar { width: 6px; }
.aa-account-content::-webkit-scrollbar-track { background: transparent; }
.aa-account-content::-webkit-scrollbar-thumb { background: #d7d7dc; border-radius: 3px; }
```

Do not use a dark thumb — keep it consistent with the articles catalog.

### Full-width override for `25/6` and `25/7`

System styles may set a narrow `max-width` on `.aa-subs-dashboard-content`, producing an awkward
thin column inside the dashboard. Override scoped to the dashboard body class:

```css
body.module-subscriptions.aa-subscriptions-dashboard .aa-subs-dashboard-content {
  width: 100%;
  max-width: none;
  margin-inline: 0;
}
```

### Access badge classes (`10/7`, `10/4`)

Cards and article pages render `.aa-access-badge` to indicate which subscription tier can access
the content. The badge describes the **category**, not the visitor's current plan.

```css
.aa-access-badge {
  border-radius: 4px; font: 700 10px/1 inherit;
  letter-spacing: .08em; padding: 3px 7px; text-transform: uppercase;
}
.aa-access-badge--free { background: #191925; color: #fff; }
.aa-access-badge--plus { background: #3b6cf4; color: #fff; }
.aa-access-badge--pro  { background: #84cc16; color: #191925; }
```

Use nested `<?if?>` to assign the badge — uCoz does not support `<?elseif?>`, and a fallback
`<?else?>` would incorrectly badge any future unknown category:

```html
<?if($CID$=1 or $CID$=2)?>
  <span class="aa-access-badge aa-access-badge--free">Free</span>
<?endif?>
<?if($CID$=4)?>
  <span class="aa-access-badge aa-access-badge--plus">Plus</span>
  <?if($CID$=3)?>
    <span class="aa-access-badge aa-access-badge--pro">Pro</span>
  <?endif?>
<?endif?>
```

### Articles catalog scroll panel quirks

On desktop (`min-width: 801px`), the list pages (`10/1`, `10/3`) and the article page (`10/4`)
use a fixed-height viewport stack. Only `.aa-articles-scroll` (list pages) or
`.aa-article-entry` (article page) should scroll.

- Do **not** assign `overflow-y: auto` only to `.aa-articles-list` — the scroll area ends before
  the sidebar's bottom "Help & FAQ" block becomes visible.
- On the article page, do **not** scroll only `.aa-article-entry-body` — scroll the entire
  `.aa-article-entry` (cover + title + body + footer together).
- On mobile, do not fix heights — the document scrolls normally.

Article page panel CSS (one white card, rounded top corners only):

```css
.aa-article-panel {
  background: #fff;
  border: 1px solid #e8eaed;
  border-radius: 22px 22px 0 0;
  overflow: hidden;
}

/* Desktop: article entry fills available height */
@media (min-width: 801px) {
  body.aa-articles-entry-page .aa-account-main,
  body.aa-articles-entry-page .aa-account-content {
    overflow: hidden;
  }
  body.aa-articles-entry-page .aa-article-entry {
    overflow-y: auto;
    height: 100%;
    padding-bottom: 48px;
  }
}
```

### Subscription `$RECURRENT_CHARGE_INFO$` and legal notes

Do **not** remove or rewrite `.aa-subs-checkout-note` or `$RECURRENT_CHARGE_INFO$` — these are
platform-required legal text. You may only style the wrapper, paragraphs, and links for
readability. Ensure the `/subscriptions/my` link (or `$USER_SUBSCRIPTIONS_URL$`) stays clickable
inside the note.

---

## Forum glass Apple

Platform rules: [references/FORUM.md](references/FORUM.md).

Glass redesign for Forum (`module_id=8`) is **CSS-only**: keep `8/2`–`8/5` markup and especially
`8/3` `#frM53` IDs intact. Prefer `body.module-forum` rules in `3/3` or an FTP asset; a scoped
`<style id="forum-glass-apple">` in shell `8/1` is an acceptable recovery path when `patch_template`
on `3/3` silently fails to persist.

### Design tokens

```css
body.module-forum {
  --forum-glass-bg: rgba(255, 255, 255, .58);
  --forum-glass-border: rgba(255, 255, 255, .55);
  --forum-accent: #007aff;
  --forum-ink: #1d1d1f;
  --forum-muted: #6e6e73;
  --forum-radius: 18px;
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Inter, Helvetica, Arial, sans-serif;
  background:
    radial-gradient(1200px 600px at 10% -10%, rgba(120, 190, 255, .35), transparent 60%),
    radial-gradient(900px 500px at 90% 0%, rgba(190, 160, 255, .28), transparent 55%),
    linear-gradient(180deg, #eef2f7 0%, #e8ecf3 45%, #f4f6fa 100%) !important;
}
```

### Top tools nav: pill buttons, not `[ links ]`

Replace the legacy table menu with:

```html
<nav class="forum-menu forum-menu-pills" aria-label="Forum tools">
  <a class="fNavLink" href="$RECENT_THREADS_LINK$" rel="nofollow">…</a>
  <!-- more .fNavLink pills; keep language markers -->
</nav>
```

Style `.fNavLink` as pill chips (`border-radius:980px`). Hide `.forum-bracket-symbol` / `.forum-middot-symbol`.

### Thread page must restyle engine chrome

Override `.ThrTopButtonsTbl` / `.frmBtns`, `.breadcrumbs` / `.forumNamesBar`, `.threadpage-posts-table .forum-title`, `.ThrBotTbl`, `#thread_search_form`, `.forumModerFuncsTbl`, and `.post-buttons` — not only `.post-card`. Otherwise `/forum/3-1-1` still looks stock.

Use flex on `.ThrForumBarRow1` (breadcrumb + search). `.ThrForumBarCl12` often contains search, not subscribe — do not treat it as a single pill link. Theme stacks this row as a **column** — force `flex-direction:row !important`.

`#subscribe` is in `.gTableTop` (often floated). Without `float:none` + flex title row it sits beside the post and kills full width. Use `width:100%` (not `auto`) on the posts table/card. Hide duplicate `.ThrBotRow1` under posts; keep `$ENTRY_MANAGE_ICON_BAR$` only in the post footer.

Stock Reply/New topic/etc. are **SVG images** (`/.s/img/fr/bt/.../*.svg`). Hide the `img` and set Russian labels via `a:has(img[src*="t_reply"])::after`.

### What to frost

| Target | Treatment |
|---|---|
| `.forum-box` / `.forumContent` | Strong glass panel + blur + radius + soft shadow (more transparent than solid white) |
| `.forum-menu .fNavLink` | Pill buttons (no brackets / middots) |
| `.frmBtns a` / SVG actions | Text pills via `::after` (primary Reply, secondary New topic / poll) |
| Breadcrumbs + `#thread_search_form` | Soft chip / glass control groups with real gaps |
| `.ThrBotRow2` / `.ThrBotRow3` | Separate glass toolbars |
| `.post-card` | Two-column glass card; stack ≤900px |
| `.post-buttons a` | Small pill actions |
| `#frM53.gTable` | Glass form; keep `frM*` / `frF*` IDs |

Do **not** put `backdrop-filter` on the site header that contains the mobile menu.

Verify both `/forum` and a live topic URL after each change.

---

## uWnd glass

CSS sample for platform toasts/modals. **Canonical rules** (9-slice, mail success, CSS placement, checklist) → [references/UWND.md](references/UWND.md).

```css
div[id^="_uwndTop"]:not(.cp-modal):not(.cp-alert) .xw-plain {
  border-radius: 18px !important;
  box-shadow: 0 18px 50px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.16) !important;
}
div[id^="_uwndTop"]:not(.cp-modal):not(.cp-alert) .xw-plain :is(.xw-tl,.xw-ml,.xw-bl,.xw-blank) {
  background-color: rgba(44,46,50,.78) !important;
  backdrop-filter: saturate(180%) blur(28px) !important;
  -webkit-backdrop-filter: saturate(180%) blur(28px) !important;
}
div[id^="_uwndTop"]:not(.cp-modal):not(.cp-alert) :is(.xw-hdr-text,.myWinCont) {
  color: #f5f5f7 !important;
  font-size: 14px !important;
  font-weight: 500 !important;
}
body:not(.bw-theme) .uwnd-toast {
  --uwnd-toast-radius: 18px !important;
  --uwnd-toast-padding: 16px 18px !important;
}
```

For **full** mail-form success modals, also zero 9-slice `background-image` on `.xw-tl`…`.xw-bc` and restyle `.xt-close` / `.xt-close2` — see [UWND.md](references/UWND.md).
