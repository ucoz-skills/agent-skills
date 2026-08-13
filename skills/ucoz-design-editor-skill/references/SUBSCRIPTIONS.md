# Subscriptions & Plans Module (`module_id=25`)

Guide for safely redesigning the Subscriptions module. The module is responsible for plans, prices, payment periods, access control, and checkout. The template is responsible only for presentation.

**Core rule: never replace the system plan output with static cards, a fake "Subscribe" button, or a hand-written payment form.**

---

## What is known before reading templates

| Area | Value |
|---|---|
| Module | `module_id=25` |
| Module CSS template | `module_id=25, template_id=8` |
| Plan data | Configured in the uCoz Control Panel, not in HTML templates |
| Template details / variables | Determine only after `list_modules` + `read_template` on the specific site |

Never transfer IDs, variable names, URLs, or DOM structure from another site — they may differ between theme versions.

---

## Mandatory pre-redesign checks

1. `modules_tool(action="module_status", module_code="subscriptions")` or `modules_tool(action="modules_list")` — confirm the module is installed and active.
2. `templates_tool(action="list_modules")` — find all `module_id=25` templates; do not assume purpose by number.
3. `read_template` with `include_variables=true` on every template to be changed.
4. Read `25/8` (module CSS) and `3/3` (site-wide CSS).
5. Confirm every variable, system form, link, handler, and class from the live template. If a variable is unconfirmed, do not use it.
6. Before a large redesign: `list_backups` — check backup headroom.

If `module_status` does not support the code `subscriptions`, do not guess: use the result of `modules_list` and record the real module name for the current site.

---

## Template map

| ID | Purpose | Must preserve |
|---|---|---|
| `25/1` | Plans page (main) | `$COUNT$`, all `$ITEM1_VIEW$`…`$ITEM10_VIEW$`, guest login templates, `subscriptions.js` |
| `25/2` | Plan card | `$ID$`, prices, `$TYPE$`, `$ADVANTAGE_ITEMS_LIST$`, guest conditions, `/subscriptions/checkout/$ID$` link |
| `25/3` | Checkout form | `handleSubscriptionCheckout(this)`, `$PAY_METHODS$`, `$FIELDS$`, `$DONATE_FORM$`, `#subs_submit_checkout` |
| `25/4` | Checkout page | `$TITLE$`, `$BODY$`, `$AJAX_JS$` and all nested elements from `25/3` |
| `25/5` | Success page | `$TITLE$`, `$BODY$`, `$AJAX_JS$` |
| `25/6` | User subscriptions | `$COUNT$`, `$BODY$`, `$USER_SUBSCRIPTIONS_URL$` |
| `25/7` | Payment history | `$TITLE$`, `$DESCRIPTION$`, `$COUNT$`, `$BODY$` |
| `25/8` | Module CSS (legacy) | Do not add new theme here — external CSS is the source of truth |

`25/2`, `25/3`, and `25/8` are nested components — do NOT add sidebar to them.

---

## What must never be changed

Never cut, replace, or manually imitate:

- System plan output with current prices
- Links and buttons that trigger purchase, renewal, dashboard redirect, or payment
- Hidden fields, tokens, JS handlers, and system classes inside native forms
- Conditions that determine the current user's plan, plan availability, and billing period
- Error messages, payment statuses, and post-payment redirect flow

Wrapping confirmed system output in additional container elements is allowed if it does not change the nesting the module's scripts rely on. When in doubt, change only CSS.

---

## Redesign strategy

1. Find tokens first in `3/3`: colors, fonts, spacing, radius, shadows, transitions.
2. In `25/8` create styles only for subscriptions, scoped to a class that is actually present on the module's page. Verify which element carries the module class (`body`, `#layout`, or another container) before writing selectors.
3. Preserve all confirmed system element classes and IDs. Add your own classes with a neutral prefix (e.g. `.plans-*`).
4. Use `patch_template` for targeted changes. `code_search` must be copied byte-for-byte from a fresh `read_template` with no reformatting.
5. Full rewrite via `update_template` only after user confirmation. Re-`read_template` before saving; run `validate_template`.
6. After saving: `read_template` again; check the public page — prices, all CTAs, period toggle, authenticated and unauthenticated flows must work.

---

## Plans page layout (`25/1`) + plan card (`25/2`)

- For authenticated users, CTAs must link to `/subscriptions/checkout/$ID$`.
- For guests, preserve `showSubsLoginModal(…)` and templates `#subs_login_alert_tpl` / `#subs_login_alert_tpl3`.
- Highlight the recommended plan only via the module's confirmed `$IS_ADVANTAGE$` flag. If the module does not expose this flag on the specific site, a temporary name-based rule is allowed only with an explicit comment in the template and the site owner's agreement.
- Do NOT add a decorative period toggle, comparison table, or trial if they do not actually change real subscription parameters in uCoz.
- If a yearly billing period does not exist in module settings, ask the user to create it in the Control Panel first. Then re-read the template and variables.

---

## Period toggle

Only add a period toggle if the module already outputs two billing periods and provides working controls or confirmed data for them. Style the **existing control** — do not add a decorative toggle that changes only text or CSS but not the actual period being purchased.

---

## Comparison table and FAQ

May be placed after the system plan list in the same full template, or extracted to a global block if also needed on the homepage. The feature list in the table must match the real access granted by the configured plans — do not promise features a plan does not provide.

For FAQ, use semantic `details`/`summary` or the existing confirmed accordion. Every interactive element must have `:hover` and `:focus-visible`. On mobile, cards stack to one column; the comparison table scrolls inside its own container without widening the page.

---

## Homepage preview of plans

The homepage preview must link to the real subscriptions page. If dynamic prices and states are needed, find the confirmed module output that can be safely embedded. If no such output exists, make the preview without prices and with a CTA to the plans page — or agree with the site owner on a static marketing preview that will need manual maintenance.

---

## Dashboard shell (subscription pages with sidebar)

Pages `25/4`–`25/7` use the same dashboard shell as the profile page (`4/5`):

```html
<div id="layout" class="aa-account-layout">
$GLOBAL_CLEFTER$
<main class="aa-account-main">
  <header class="aa-account-topbar">...</header>
  <div class="aa-account-content aa-subs-dashboard-content">...</div>
</main>
</div>
```

**Do not** output `$GLOBAL_AHEADER$` or `$GLOBAL_BFOOTER$` in these shell templates. Preserve `$POWERED_BY$` and `/.s/t/2301/main.js`. Link external CSS from `/css/style.css` with a fresh `?v=`.

**Do not** add sidebar to `25/2`, `25/3`, or `25/8` — they are nested components.

**Plans page `25/1`** keeps natural page height; sidebar is `position:sticky; height:100vh`. Pages `25/4`–`25/7` use a fixed viewport height on desktop (`100vh` flex chain): `#layout`, sidebar, and `.aa-account-main` are fixed-height; only `.aa-account-content` scrolls.

For `25/6` and `25/7`, system styles may set a narrow `max-width` on `.aa-subs-dashboard-content`. Override: `.aa-subs-dashboard-content{width:100%;max-width:none;margin-inline:0}` scoped to `body.module-subscriptions.aa-subscriptions-dashboard`.

Checkout width (`25/4`): `#subs_checkout_form`, `.aa-subs-checkout-form`, and their direct cards must have `width:100%; max-width:none` — do not vary by `$TYPE_RAW$` (all plan types use the same dashboard width).

System legal text (`$RECURRENT_CHARGE_INFO$`, `.aa-subs-checkout-note`) must not be deleted or rewritten. Style the wrapper, paragraphs, and links for readability only. The `$USER_SUBSCRIPTIONS_URL$` or `/subscriptions/my` link must remain functional.

---

## CSS strategy

For a full subscription landing redesign: use one canonical external FTP file (e.g. `/css/style.css`) linked with `?v=` in every rewritten shell template. Do NOT maintain a competing theme in `25/8` — it overrides external design and restores system blue buttons.

Never add `<style>` blocks to HTML templates. Add JavaScript only when necessary and only after confirming it does not duplicate module logic.

---

## Compatibility and accessibility

- Every full-page shell: exactly one `$GLOBAL_AHEADER$` and one `$GLOBAL_BFOOTER$`. No duplicated header, footer, or `$POWERED_BY$`.
- All buttons and links: visible `:hover` and `:focus-visible` states.
- At `max-width:768px`: plan cards stack to one column; comparison table scrolls inside its own container.

---

## Pre-delivery checklist

- [ ] Module is active; plans and periods exist in the Control Panel.
- [ ] All variables and system elements confirmed on the current site.
- [ ] Prices, periods, availability, and CTAs come from the module, not static HTML.
- [ ] The recommended plan is highlighted visually but uses the native checkout button.
- [ ] The period toggle actually changes data and the plan being purchased.
- [ ] The comparison table matches the configured plan settings.
- [ ] No duplicated markup in header, footer, or homepage.
- [ ] CSS works on mobile, with keyboard, and with dark theme enabled.
- [ ] After saving: template re-read; public page verified as guest and as authenticated user.
